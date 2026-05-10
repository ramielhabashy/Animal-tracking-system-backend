<?php

namespace App\Http\Controllers\Traits;

use App\Models\User;
use Illuminate\Http\Request;

trait OwnableAuthorization
{
    /**
     * Get authenticated user ID from request
     * Tries: Sanctum user, then X-User-Id header
     */
    private function getUserId(Request $request): ?string
    {
        if ($request->user()) {
            return (string) $request->user()->id;
        }
        return $request->header('X-User-Id');
    }

    /**
     * Get authenticated user's primary role from request
     * Tries: Sanctum user, then X-User-Role header
     */
    private function getUserRole(Request $request): ?string
    {
        if ($request->user()) {
            return $request->user()->getPrimaryRoleName();
        }
        return $request->header('X-User-Role');
    }

    /**
     * Get full User model from request
     * Tries: Sanctum user, then lookup by X-User-Id header
     */
    private function getUser(Request $request): ?User
    {
        if ($request->user()) {
            return $request->user();
        }
        $userId = $request->header('X-User-Id');
        return $userId ? User::find($userId) : null;
    }

    /**
     * Check if user can ACCESS (view) resources belonging to a specific owner
     */
    protected function canAccessOwner(Request $request, ?int $ownerId): bool
    {
        $user = $this->getUser($request);
        
        if (!$user) {
            return $ownerId === null;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        if ($ownerId === null) {
            return true;
        }

        if ($user->hasRole('Owner') && $ownerId == $user->id) {
            return true;
        }
        if ($user->hasRole('Doctor')) {
            if ($user->managed_by) {
                return $ownerId == $user->managed_by;
            }
            return true;
        }

        if ($user->hasAnyRole(['Manager', 'Shepherd'])) {
            if ($user->managed_by) {
                return $ownerId == $user->managed_by;
            }
            return true;
        }

        return false;
    }

    /**
     * Check if user can MODIFY (edit/delete) resources belonging to a specific owner
     */
    protected function canModifyOwner(Request $request, ?int $ownerId): bool
    {
        $user = $this->getUser($request);
        
        if (!$user) {
            return $ownerId === null;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        if ($ownerId === null) {
            return true;
        }

        if ($user->hasRole('Owner') && $ownerId == $user->id) {
            return true;
        }
        if ($user->hasRole('Doctor')) {
            if ($user->managed_by) {
                return $ownerId == $user->managed_by;
            }
            return true;
        }

        if ($user->hasRole('Manager')) {
            if ($user->managed_by) {
                return $ownerId == $user->managed_by;
            }
            return true;
        }

        return false;
    }

    /**
     * Filter an Eloquent query by user's accessible resources
     */
    protected function filterByOwner(Request $request, $query)
    {
        $user = $this->getUser($request);

        if (!$user) {
            return $query;
        }

        if ($user->hasRole('Admin')) {
            return $query;
        }

        $userId = $user->id;
        if (!$userId) {
            return $query->where('id', 0);
        }

        if ($user->hasRole('Owner')) {
            return $query->where('owner_id', $userId);
        }
        if ($user->hasRole('Doctor')) {
            if ($user->managed_by) {
                return $query->where('owner_id', $user->managed_by);
            }
            return $query;
        }

        if ($user->hasAnyRole(['Manager', 'Shepherd'])) {
            if ($user->managed_by) {
                return $query->where('owner_id', $user->managed_by);
            }
            return $query;
        }

        return $query;
    }

    /**
     * Check if user has owner-level access
     */
    protected function canAccessAsOwner(Request $request): bool
    {
        $user = $this->getUser($request);
        return $user && $user->hasAnyRole(['Admin', 'Owner', 'Manager']);
    }

    /**
     * Check if user can create resources as an owner
     */
    protected function canCreateAsOwner(Request $request): bool
    {
        $user = $this->getUser($request);
        return $user && $user->hasAnyRole(['Admin', 'Owner', 'Manager']);
    }
}
