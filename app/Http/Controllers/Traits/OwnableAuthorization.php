<?php

namespace App\Http\Controllers\Traits;

use App\Models\User;
use Illuminate\Http\Request;

trait OwnableAuthorization
{
    private function getUserId(Request $request): ?string
    {
        return $request->user()?->id ? (string) $request->user()->id : null;
    }

    private function getUserRole(Request $request): ?string
    {
        return $request->user()?->getPrimaryRoleName();
    }

    private function getUser(Request $request): ?User
    {
        return $request->user();
    }

    private function isAdminOrStaff(Request $request): bool
    {
        $user = $this->getUser($request);
        return $user && ($user->hasRole('Admin') || $user->isStaff());
    }

    protected function canAccessOwner(Request $request, ?int $ownerId): bool
    {
        $user = $this->getUser($request);
        
        if (!$user) {
            return $ownerId === null;
        }

        if ($this->isAdminOrStaff($request)) {
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

    protected function canModifyOwner(Request $request, ?int $ownerId): bool
    {
        $user = $this->getUser($request);
        
        if (!$user) {
            return $ownerId === null;
        }

        if ($this->isAdminOrStaff($request)) {
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

    protected function filterByOwner(Request $request, $query)
    {
        $user = $this->getUser($request);

        if (!$user) {
            return $query;
        }

        if ($this->isAdminOrStaff($request)) {
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

    protected function canAccessAsOwner(Request $request): bool
    {
        $user = $this->getUser($request);
        return $user && ($user->hasAnyRole(['Admin', 'Owner', 'Manager']) || $user->isStaff());
    }

    protected function canCreateAsOwner(Request $request): bool
    {
        $user = $this->getUser($request);
        return $user && ($user->hasAnyRole(['Admin', 'Owner', 'Manager']) || $user->isStaff());
    }
}
