<?php

namespace App\Http\Controllers\Traits;

use App\Models\User;
use Illuminate\Http\Request;

trait OwnableAuthorization
{
    private function getUserId(Request $request): ?string
    {
        // If authenticated via token, use that user
        if ($request->user()) {
            return (string) $request->user()->id;
        }
        return $request->header('X-User-Id');
    }

    private function getUserRole(Request $request): ?string
    {
        // If authenticated via token, use that user's role
        if ($request->user()) {
            return $request->user()->role;
        }
        return $request->header('X-User-Role');
    }

    private function getUser(Request $request): ?User
    {
        $userId = $this->getUserId($request);
        return $userId ? User::find($userId) : null;
    }

    protected function canAccessOwner(Request $request, ?int $ownerId): bool
    {
        $userId = $this->getUserId($request);
        $userRole = $this->getUserRole($request);

        if ($userRole === 'Admin') {
            return true;
        }

        if ($ownerId === null) {
            return true;
        }

        if ($userRole === 'Owner' && $ownerId == $userId) {
            return true;
        }

        if (in_array($userRole, ['Manager', 'Shepherd'])) {
            $user = $this->getUser($request);
            if ($user && $user->managed_by) {
                return $ownerId == $user->managed_by;
            }
        }

        return false;
    }

    protected function canModifyOwner(Request $request, ?int $ownerId): bool
    {
        $userId = $this->getUserId($request);
        $userRole = $this->getUserRole($request);

        if ($userRole === 'Admin') {
            return true;
        }

        if ($ownerId === null) {
            return true;
        }

        if ($userRole === 'Owner' && $ownerId == $userId) {
            return true;
        }

        if ($userRole === 'Manager') {
            $user = $this->getUser($request);
            if ($user && $user->managed_by) {
                return $ownerId == $user->managed_by;
            }
        }

        return false;
    }

    protected function filterByOwner(Request $request, $query)
    {
        $userId = $this->getUserId($request);
        $userRole = $this->getUserRole($request);

        if ($userRole === 'Admin') {
            return $query;
        }

        if ($userRole === 'Owner') {
            return $query->where('owner_id', $userId);
        }

        if (in_array($userRole, ['Manager', 'Shepherd'])) {
            $user = $this->getUser($request);
            if ($user && $user->managed_by) {
                return $query->where('owner_id', $user->managed_by);
            }
            return $query->where('id', 0);
        }

        return $query->where('id', 0);
    }

    protected function canAccessAsOwner(Request $request): bool
    {
        $userRole = $this->getUserRole($request);
        return in_array($userRole, ['Admin', 'Owner', 'Manager']);
    }

    protected function canCreateAsOwner(Request $request): bool
    {
        $userRole = $this->getUserRole($request);
        return in_array($userRole, ['Admin', 'Owner', 'Manager']);
    }
}