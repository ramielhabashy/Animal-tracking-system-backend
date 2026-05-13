<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponse;
use App\Http\Controllers\Traits\SendsEmailNotifications;
use App\Mail\UserInvitationMail;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserInvitation;
use App\Models\SubscriptionTier;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class InvitationController extends Controller
{
    use ApiResponse, SendsEmailNotifications;

    private function notifyInvitationAccepted(UserInvitation $invitation, User $newUser): void
    {
        $recipients = [];

        if ($invitation->created_by) {
            $recipients[$invitation->created_by] = true;
        }

        if ($invitation->managed_by && $invitation->managed_by !== $invitation->created_by) {
            $recipients[$invitation->managed_by] = true;
        }

        $roleLabel = $invitation->role;

        foreach (array_keys($recipients) as $userId) {
            Notification::create([
                'user_id' => $userId,
                'type' => 'invitation_accepted',
                'title' => 'Invitation Accepted',
                'body' => "{$newUser->name} ({$newUser->email}) has accepted their invitation and joined as {$roleLabel}.",
                'data' => [
                    'user_id' => $newUser->id,
                    'user_name' => $newUser->name,
                    'user_email' => $newUser->email,
                    'role' => $roleLabel,
                    'link' => '/users',
                ],
            ]);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $authUser = $request->user();
        $authRole = $authUser->getPrimaryRoleName();

        if (!in_array($authRole, ['Admin', 'Owner'])) {
            return $this->forbidden('Only Admin and Owner can invite users');
        }

        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'role' => 'required|string|exists:roles,name',
            'managed_by' => 'nullable|exists:users,id',
        ]);

        $requestedRole = $validated['role'];

        if ($authRole === 'Owner' && in_array($requestedRole, ['Admin', 'Owner'])) {
            return $this->forbidden('Owner can only invite team roles, not Admin or Owner');
        }

        $token = Str::random(64);
        $expiresAt = now()->addDays(7);

        $invitation = UserInvitation::create([
            'email' => $validated['email'],
            'token' => $token,
            'role' => $requestedRole,
            'managed_by' => $validated['managed_by'] ?? ($authRole === 'Owner' ? $authUser->id : null),
            'created_by' => $authUser->id,
            'expires_at' => $expiresAt,
        ]);

        $frontendBase = env('FRONTEND_URL', config('app.url'));
        $acceptUrl = rtrim($frontendBase, '/') . '/invitations/' . $token;

        try {
            Mail::to($invitation->email)->send(new UserInvitationMail($invitation, $acceptUrl));
        } catch (\Throwable $e) {
            \Log::error('Failed to send invitation email: ' . $e->getMessage());
        }

        return $this->created([
            'invitation' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'expires_at' => $invitation->expires_at,
            ],
            'invitation_link' => $acceptUrl,
        ], 'Invitation sent successfully');
    }

    public function show(string $token): JsonResponse
    {
        $invitation = UserInvitation::where('token', $token)->first();

        if (!$invitation) {
            return $this->notFound('Invalid invitation link');
        }

        if (!$invitation->isValid()) {
            if ($invitation->used_at) {
                return $this->error('This invitation has already been used', 410, null, 'invitation_used');
            }
            return $this->error('This invitation has expired', 410, null, 'invitation_expired');
        }

        return $this->success([
            'email' => $invitation->email,
            'role' => $invitation->role,
            'token' => $invitation->token,
        ]);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = UserInvitation::where('token', $token)->first();

        if (!$invitation) {
            return $this->notFound('Invalid invitation link');
        }

        if (!$invitation->isValid()) {
            if ($invitation->used_at) {
                return $this->error('This invitation has already been used', 410, null, 'invitation_used');
            }
            return $this->error('This invitation has expired', 410, null, 'invitation_expired');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string',
            'language' => 'nullable|string',
        ]);

        $freeTier = SubscriptionTier::where('slug', 'free')->first();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $invitation->email,
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'is_active' => true,
            'language' => $validated['language'] ?? 'en',
            'subscription_tier_id' => $freeTier?->id,
            'managed_by' => $invitation->managed_by,
        ]);

        $user->assignRole($invitation->role);

        if ($invitation->role === 'Owner' && $user->subscription_tier_id) {
            UserSubscription::create([
                'user_id' => $user->id,
                'tier_id' => $user->subscription_tier_id,
                'status' => 'active',
                'started_at' => now(),
                'billing_cycle' => 'monthly',
            ]);
        }

        $invitation->update(['used_at' => now()]);

        $this->notifyInvitationAccepted($invitation, $user);

        $token = $user->createToken('auth-token')->plainTextToken;

        $this->sendNotificationMail(
            $user,
            'welcome',
            'Welcome to ' . config('app.name', 'Oasis Trace'),
            [
                'Your account has been created successfully.',
                "You have been invited as a {$invitation->role}.",
                'You can now log in and start using the platform.',
            ],
            rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/login',
            'Go to Dashboard',
        );

        return $this->created([
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
        ], 'Account created successfully');
    }

    private function filterByRole($query)
    {
        $user = request()->user();
        $role = $user->getPrimaryRoleName();

        if ($role === 'Admin' || $user->isStaff()) {
            return $query;
        }

        if ($role === 'Owner') {
            return $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('managed_by', $user->id);
            });
        }

        return $query->where('created_by', $user->id);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = UserInvitation::with(['creator:id,name,email', 'manager:id,name,email']);

        $query = $this->filterByRole($query);

        if ($search = $request->input('search')) {
            $search = '%' . $search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', $search)
                  ->orWhere('role', 'like', $search);
            });
        }

        if ($status = $request->input('status')) {
            $now = now();
            if ($status === 'pending') {
                $query->whereNull('used_at')->where('expires_at', '>', $now);
            } elseif ($status === 'used') {
                $query->whereNotNull('used_at');
            } elseif ($status === 'expired') {
                $query->whereNull('used_at')->where('expires_at', '<=', $now);
            }
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        $perPage = $request->input('per_page', 15);
        $invitations = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $invitations->getCollection()->transform(function ($inv) {
            $now = now();
            if ($inv->used_at) {
                $inv->status = 'used';
            } elseif ($inv->expires_at <= $now) {
                $inv->status = 'expired';
            } else {
                $inv->status = 'pending';
            }
            $inv->invitation_link = rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/invitations/' . $inv->token;
            return $inv;
        });

        $usedRoles = UserInvitation::select('role')->distinct()->pluck('role');

        return $this->success([
            'invitations' => $invitations->items(),
            'meta' => [
                'current_page' => $invitations->currentPage(),
                'last_page' => $invitations->lastPage(),
                'per_page' => $invitations->perPage(),
                'total' => $invitations->total(),
            ],
            'filter_roles' => $usedRoles,
        ]);
    }

    public function resend(string $id): JsonResponse
    {
        $user = request()->user();
        $invitation = UserInvitation::findOrFail($id);

        if ($invitation->used_at) {
            return $this->error('Cannot resend — invitation already used', 400, null, 'invitation_used');
        }

        if ($invitation->expires_at <= now()) {
            $invitation->update(['expires_at' => now()->addDays(7)]);
        }

        $frontendBase = env('FRONTEND_URL', config('app.url'));
        $acceptUrl = rtrim($frontendBase, '/') . '/invitations/' . $invitation->token;

        try {
            Mail::to($invitation->email)->send(new UserInvitationMail($invitation, $acceptUrl));
        } catch (\Throwable $e) {
            \Log::error('Failed to resend invitation email: ' . $e->getMessage());
            return $this->error('Failed to send email', 500);
        }

        return $this->success(null, 'Invitation resent successfully');
    }

    public function cancel(string $id): JsonResponse
    {
        $invitation = UserInvitation::findOrFail($id);

        if ($invitation->used_at) {
            return $this->error('Cannot cancel — invitation already used', 400, null, 'invitation_used');
        }

        $invitation->delete();

        return $this->deleted('Invitation cancelled successfully');
    }
}
