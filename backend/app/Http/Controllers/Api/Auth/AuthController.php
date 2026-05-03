<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid credentials', 'error' => 'invalid_credentials'], 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials', 'error' => 'invalid_credentials'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Account is inactive', 'error' => 'account_inactive'], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;
        $user->load('subscriptionTier');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getPrimaryRoleName(),
                'roles' => $user->getRoleNames()->toArray(),
                'phone' => $user->phone,
                'language' => $user->language,
                'subscription_tier_id' => $user->subscription_tier_id,
                'subscription_tier' => $user->subscriptionTier,
            ],
            'token' => $token,
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'phone' => 'nullable|string',
            'language' => 'nullable|string|in:en,ar',
        ]);

        $freeTier = \App\Models\SubscriptionTier::where('slug', 'free')->first();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone' => $request->phone,
            'is_active' => true,
            'language' => $request->language ?? 'en',
            'subscription_tier_id' => $freeTier?->id,
        ]);

        $user->assignRole('Owner');

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getPrimaryRoleName(),
                'roles' => $user->getRoleNames()->toArray(),
                'phone' => $user->phone,
                'language' => $user->language,
                'subscription_tier_id' => $user->subscription_tier_id,
            ],
            'token' => $token,
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if ($user) {
                $user->currentAccessToken()->delete();
            }
        } catch (\Exception $e) {
            // Ignore token deletion errors
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('subscriptionTier');
        
        $roleNames = $user->getRoleNames()->toArray();
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $roleNames[0] ?? 'Owner',
            'roles' => $roleNames,
            'phone' => $user->phone,
            'location' => $user->location,
            'language' => $user->language,
            'avatar_url' => $user->avatar_url,
            'is_active' => $user->is_active,
            'subscription_tier_id' => $user->subscription_tier_id,
            'subscription_tier' => $user->subscriptionTier,
            'managed_by' => $user->managed_by,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string',
            'location' => 'nullable|string',
            'language' => 'sometimes|in:en,ar',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->fresh(),
        ]);
    }

    public function features(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'has_geofencing' => $user->subscriptionTier?->has_geofencing ?? false,
            'has_auctions' => $user->subscriptionTier?->has_auctions ?? false,
            'has_medical_records' => $user->subscriptionTier?->has_medical_records ?? false,
            'has_tasks' => $user->subscriptionTier?->has_tasks ?? false,
            'has_advanced_reports' => $user->subscriptionTier?->has_advanced_reports ?? false,
            'has_api_access' => $user->subscriptionTier?->has_api_access ?? false,
            'has_ai_assistant' => $user->subscriptionTier?->has_ai_assistant ?? false,
            'tier_name' => $user->subscriptionTier?->name ?? 'Free',
            'tier_slug' => $user->subscriptionTier?->slug ?? 'free',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Password reset link sent to your email.']);
        }

        return response()->json(['message' => 'Unable to send reset link.'], 400);
    }
}
