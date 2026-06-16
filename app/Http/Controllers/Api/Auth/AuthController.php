<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\SendsEmailNotifications;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Cache;

use Laravel\Sanctum\PersonalAccessToken;

/**
 * Authentication Controller
 * Handles user login, registration, logout, and profile management
 * Uses Sanctum tokens for API authentication
 */
class AuthController extends Controller
{
    use SendsEmailNotifications;
    /**
     * User Login
     * Validates credentials, checks account status, and returns auth token
     * 
     * @param Request $request Contains email and password
     * @return JsonResponse User data with API token, or error message
     */
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

    /**
     * User Registration
     * Creates new user with 'Owner' role and Free subscription tier
     * 
     * @param Request $request Registration data (name, email, password, etc.)
     * @return JsonResponse Created user data with token, or validation errors
     */
    public function register(Request $request): JsonResponse
    {
        // Validate registration input with confirmation for password
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',  // Ensure unique email
            'password' => 'required|min:8|confirmed',         // Require password confirmation
            'phone' => 'nullable|string',
            'language' => 'nullable|string',
        ]);

        // Get Free tier for new users (default subscription)
        $freeTier = \App\Models\SubscriptionTier::where('slug', 'free')->first();

        // Create new user with default values
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),  // Always bcrypt passwords
            'phone' => $request->phone,
            'is_active' => true,                       // New users are active by default
            'language' => $request->language ?? 'en',  // Default to English
            'subscription_tier_id' => $freeTier?->id,  // Assign Free tier
        ]);

        // Assign 'Owner' role - owners can manage their own animals and team
        $user->assignRole('Owner');

        // Generate auth token for immediate login after registration
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->sendNotificationMail(
            $user,
            'welcome',
            'Welcome to ' . config('app.name', 'Oasis Trace'),
            [
                'Your account has been created successfully.',
                'You can now log in and start managing your livestock with all the features available to you.',
            ],
            rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/login',
            'Go to Dashboard',
        );

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
        ], 201);  // 201 Created status
    }

    /**
     * Logout User
     * Revokes the current API token
     * Frontend should also clear localStorage/sessionStorage
     * 
     * @param Request $request
     * @return JsonResponse Success message
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if ($user && $user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }
        } catch (\Throwable $e) {
            // Ignore errors (token might already be invalid)
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Get Current Authenticated User
     * Returns full user profile with roles and subscription info
     * Used by frontend/mobile to restore user session
     * 
     * @param Request $request
     * @return JsonResponse User profile data
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('subscriptionTier');  // Eager load subscription data
        
        $roleNames = $user->getRoleNames()->toArray();
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $roleNames[0] ?? 'Owner',      // Primary role for backward compatibility
            'roles' => $roleNames,                     // All assigned roles
            'phone' => $user->phone,
            'location' => $user->location,             // User's location/farm address
            'language' => $user->language,             // For i18n
            'avatar_url' => $user->avatar_url,         // Profile picture URL
            'is_active' => $user->is_active,           // Account status
            'subscription_tier_id' => $user->subscription_tier_id,
            'subscription_tier' => $user->subscriptionTier,
            'managed_by' => $user->managed_by,         // If managed by another user (Owner/Admin)
        ]);
    }

    /**
     * Update User Profile
     * Allows users to update their own profile information
     * 
     * @param Request $request Profile fields to update
     * @return JsonResponse Updated user data
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        // Validate only provided fields (sometimes = optional but validated if present)
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string',
            'location' => 'nullable|string',
            'language' => 'sometimes|string',
        ]);

        // Update only validated fields
        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->fresh(),  // Return fresh data from DB
        ]);
    }

    /**
     * Change User Password
     * Allows authenticated users to change their own password
     * Requires current password verification for security
     * 
     * @param Request $request Contains current_password, password, password_confirmation
     * @return JsonResponse Success/error message
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect',
                'error' => 'invalid_password',
            ], 400);
        }

        if ($validated['current_password'] === $validated['password']) {
            return response()->json([
                'message' => 'New password must be different from current password',
                'error' => 'same_password',
            ], 400);
        }

        $user->update([
            'password' => bcrypt($validated['password']),
        ]);

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Get User's Subscription Features
     * Returns feature flags based on user's subscription tier
     * Frontend uses this to show/hide features
     * 
     * @param Request $request
     * @return JsonResponse Feature flags
     */
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

    /**
     * Forgot Password
     * Sends password reset link to user's email
     * Uses Laravel's built-in Password facade
     *
     * @param Request $request Contains user's email
     * @return JsonResponse Success/error message
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json(['message' => 'If this email exists, an OTP has been sent.'], 200);
            }

            // Generate OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Store in cache with key reset_otp_{email}
            Cache::put('reset_otp_' . $request->email, [
                'otp' => $otp,
                'email' => $request->email,
            ], now()->addMinutes(10));

            // Send OTP email
            Mail::to($user->email)->queue(
                new \App\Mail\NotificationMail(
                    subject: 'Password Reset OTP - ' . config('app.name'),
                    greeting: 'Hello ' . $user->name . ',',
                    lines: [
                        'Your password reset OTP code is: ' . $otp,
                        'This code expires in 10 minutes.',
                        'If you did not request this, please ignore this email.',
                    ],
                    actionUrl: null,
                    actionText: null,
                    footerText: 'Oasis Trace - Livestock Tracking Platform'
                )
            );

            return response()->json(['message' => 'OTP sent to your email.']);
        } catch (\Exception $e) {
            \Log::error('Forgot password error: ' . $e->getMessage());
            return response()->json(['message' => 'Server error. Please try again.'], 500);
        }
    }

    /**
     * Reset Password
     * Completes the password reset process using token from email
     *
     * @param Request $request Contains token, email, and new password
     * @return JsonResponse Success/error message
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'password' => 'required|min:8|confirmed',
        ]);

        $cached = Cache::get('reset_otp_' . $request->email);

        if (!$cached || $cached['otp'] !== $request->otp) {
            return response()->json([
                'message' => 'Invalid or expired OTP.',
                'error' => 'invalid_otp',
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $user->forceFill([
            'password' => bcrypt($request->password),
        ])->save();

        // Clean up OTP from cache
        Cache::forget('reset_otp_' . $request->email);

        return response()->json(['message' => 'Password has been reset successfully.']);
    }
}
