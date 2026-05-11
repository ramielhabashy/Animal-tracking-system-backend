<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponse;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * User Management Controller
 * 
 * Handles CRUD operations for users with role-based access control.
 * 
 * Role Hierarchy:
 * - Admin: Full access to all users
 * - Owner: Can manage their team (users with managed_by = owner_id)
 * - Veterinarian/Shepherd: Can only view/edit their own profile
 */
class UserController extends Controller
{
    use ApiResponse;

    /**
     * Get authenticated user
     */
    private function getAuthUser(Request $request): ?User
    {
        return $request->user();
    }

    /**
     * Get authenticated user's role
     */
    private function getAuthRole(Request $request): string
    {
        $user = $request->user();
        return $user ? $user->getPrimaryRoleName() : 'Owner';
    }

    /**
     * Check if authenticated user can access a target user
     */
    private function canAccessUser(Request $request, User $targetUser): bool
    {
        $authUser = $this->getAuthUser($request);
        if (!$authUser) {
            return false;
        }

        $role = $this->getAuthRole($request);
        $targetRole = $targetUser->getPrimaryRoleName();

        // Admin can access anyone
        if ($role === 'Admin') {
            return true;
        }

        // Non-admins cannot access Admin users
        if ($targetRole === 'Admin') {
            return false;
        }

        // Owner can access their managed users
        if ($role === 'Owner' && $targetUser->managed_by == $authUser->id) {
            return true;
        }

        // Doctor can access their Owner and team members
        if ($role === 'Doctor' && $authUser->managed_by) {
            if ($targetUser->id == $authUser->managed_by ||
                $targetUser->managed_by == $authUser->managed_by ||
                $targetUser->id == $authUser->id) {
                return true;
            }
        }

        // Users can access their own profile
        if ($authUser->id == $targetUser->id) {
            return true;
        }

        return false;
    }

    /**
     * Filter query by role-based permissions
     */
    private function filterByRole(Request $request, $query)
    {
        $authUser = $this->getAuthUser($request);

        if (!$authUser) {
            return $query->where('id', 0);
        }

        $role = $authUser->getPrimaryRoleName();

        // Admin sees everything
        if ($role === 'Admin') {
            return $query;
        }

        // Owner sees their managed users
        if ($role === 'Owner') {
            return $query->where('managed_by', $authUser->id);
        }

        // Doctor sees themselves, the Owner they work for, and other team members
        if ($role === 'Doctor') {
            if ($authUser->managed_by) {
                return $query->where(function ($q) use ($authUser) {
                    $q->where('id', $authUser->id)
                      ->orWhere('managed_by', $authUser->managed_by)
                      ->orWhere('id', $authUser->managed_by);
                });
            }
            return $query->where('id', $authUser->id);
        }

        // Others see only themselves
        return $query->where('id', $authUser->id);
    }

    /**
     * List all users (filtered by role)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['debug' => 'no user', 'error' => 'auth_failed'], 401);
        }

        $query = User::query()->with('subscriptionTier');

        // Apply role-based filtering
        $query = $this->filterByRole($request, $query);

        // Paginate and format response
        $perPage = $request->input('per_page', 15);
        $users = $query->paginate($perPage);

        return UserResource::collection($users)->response();
    }

    /**
     * Show single user details
     */
    public function show(Request $request, User $user): JsonResponse
    {
        if (!$this->canAccessUser($request, $user)) {
            return $this->forbidden('Unauthorized to view this user');
        }

        $user->load('subscriptionTier');

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'location' => $user->location,
            'role' => $user->getPrimaryRoleName(),
            'roles' => $user->getRoleNames()->toArray(),
            'is_active' => $user->is_active,
            'avatar_url' => $user->avatar_url,
            'subscription_tier_id' => $user->subscription_tier_id,
            'subscription_tier' => $user->subscriptionTier,
            'managed_by' => $user->managed_by,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
    }

    /**
     * Create new user (Admin or Owner only)
     */
    public function store(Request $request): JsonResponse
    {
        $authUser = $this->getAuthUser($request);
        $authRole = $this->getAuthRole($request);

        if (!$authUser) {
            return $this->unauthorized();
        }

        // Only Admin and Owner can create users
        if (!in_array($authRole, ['Admin', 'Owner'])) {
            return $this->forbidden('Only Admin and Owner can add team members');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string',
            'location' => 'nullable|string',
            'role' => 'sometimes|string|exists:roles,name',
            'is_active' => 'nullable|boolean',
            'password' => 'nullable|string|min:8',
            'subscription_tier_id' => 'nullable|exists:subscription_tiers,id',
        ]);

        $requestedRole = $validated['role'] ?? 'Shepherd';

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password'] ?? 'Welcome123'),
            'phone' => $validated['phone'] ?? null,
            'location' => $validated['location'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];

        // Owner restrictions: can only create team roles (not Admin or Owner)
        if ($authRole === 'Owner') {
            if (in_array($requestedRole, ['Admin', 'Owner'])) {
                return $this->forbidden('Owner can only add team roles, not Admin or Owner');
            }
            $userData['managed_by'] = $authUser->id;
        } elseif ($authRole === 'Admin') {
            if ($request->has('managed_by') && $request->managed_by) {
                $userData['managed_by'] = $request->managed_by;
            }
            if ($request->has('subscription_tier_id') && $request->subscription_tier_id) {
                $userData['subscription_tier_id'] = $request->subscription_tier_id;
            }
        }

        $user = User::create($userData);
        $user->assignRole($requestedRole);

        // Handle avatar upload
        if ($request->hasFile('avatar_url')) {
            $file = $request->file('avatar_url');
            $filename = 'avatar_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('public/avatars', $filename, 'local');
            $user->avatar_url = '/storage/' . str_replace('public/', '', $path);
            $user->save();
        }

        return $this->created($user->fresh(), 'User created successfully');
    }

    /**
     * Update existing user
     */
    public function update(Request $request, User $user): JsonResponse
    {
        if (!$this->canAccessUser($request, $user)) {
            return $this->forbidden('Unauthorized to update this user');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string',
            'location' => 'nullable|string',
            'role' => 'sometimes|exists:roles,name',
            'is_active' => 'nullable|boolean',
            'password' => 'nullable|string|min:8',
            'managed_by' => 'nullable|exists:users,id',
            'subscription_tier_id' => 'nullable|exists:subscription_tiers,id',
            'notify_user' => 'nullable|boolean',
        ]);

        $role = $this->getAuthRole($request);

        if ($role !== 'Admin') {
            // Owner can change role for managed users, but not to Admin or Owner
            if (isset($validated['role']) && in_array($validated['role'], ['Admin', 'Owner'])) {
                return $this->forbidden('Owner can only assign team roles, not Admin or Owner');
            }
            unset($validated['subscription_tier_id'], $validated['managed_by']);
        }

        // Update role if provided
        if (isset($validated['role']) && $validated['role']) {
            $user->syncRoles([$validated['role']]);
            unset($validated['role']);
        }

        // Handle managed_by = null
        if (array_key_exists('managed_by', $validated) && $validated['managed_by'] === null) {
            $user->update(['managed_by' => null]);
            unset($validated['managed_by']);
        }

        // Hash password if provided
        $passwordChanged = false;
        if (isset($validated['password']) && $validated['password']) {
            $validated['password'] = Hash::make($validated['password']);
            $passwordChanged = true;
        } else {
            unset($validated['password']);
        }

        $notifyUser = !empty($validated['notify_user']);
        unset($validated['notify_user']);

        if (!empty($validated)) {
            $user->update($validated);
        }

        if ($passwordChanged && $notifyUser) {
            // TODO: Implement password change notification
            // e.g., Mail::to($user->email)->send(new PasswordChangedMail($user));
        }

        return $this->updated($user->fresh(), 'User updated successfully');
    }

    /**
     * Delete user
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $authUser = $this->getAuthUser($request);

        if (!$this->canAccessUser($request, $user)) {
            return $this->forbidden('Unauthorized to delete this user');
        }

        // Prevent self-deletion
        if ($authUser && $authUser->id === $user->id) {
            return $this->forbidden('You cannot delete your own account');
        }

        // Delete avatar file from storage
        if ($user->avatar_url && Storage::disk('local')->exists(str_replace('/storage/', '', $user->avatar_url))) {
            Storage::disk('local')->delete(str_replace('/storage/', '', $user->avatar_url));
        }

        $user->delete();

        return $this->deleted('User deleted successfully');
    }

    /**
     * List doctors (users with Doctor role) for veterinarian dropdown
     */
    public function doctors(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $doctors = User::whereHas('roles', function ($q) {
            $q->where('name', 'Doctor');
        })
        ->where('is_active', true)
        ->select(['id', 'name'])
        ->orderBy('name')
        ->get();

        return response()->json(['data' => $doctors]);
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        if (!$this->canAccessUser($request, $user)) {
            return $this->forbidden('Unauthorized');
        }

        $user->update(['is_active' => !$user->is_active]);

        return $this->success([
            'user' => $user->fresh(),
            'message' => $user->is_active ? 'User activated' : 'User deactivated'
        ]);
    }
}
