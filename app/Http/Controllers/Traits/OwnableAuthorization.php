<?php

namespace App\Http\Controllers\Traits;

use App\Models\User;
use App\Models\AnimalGroup;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

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

        if ($user->hasRole('Manager')) {
            if ($user->managed_by) {
                return $ownerId == $user->managed_by;
            }
            return true;
        }

        if ($user->hasRole('Shepherd')) {
            if ($user->managed_by && $ownerId == $user->managed_by) {
                return true;
            }
            $assignedOwnerIds = $user->assignedGroups()
                ->select('owner_id')
                ->distinct()
                ->pluck('owner_id')
                ->toArray();
            if (in_array($ownerId, $assignedOwnerIds)) {
                return true;
            }
            return false;
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

        if ($user->hasRole('Manager')) {
            if ($user->managed_by) {
                return $query->where('owner_id', $user->managed_by);
            }
            return $query;
        }

        if ($user->hasRole('Shepherd')) {
            return $this->applyShepherdFilter($request, $query);
        }

        return $query;
    }

    protected function applyShepherdFilter(Request $request, Builder $query): Builder
    {
        $user = $this->getUser($request);
        $firstTable = $query->getQuery()->from;

        if ($firstTable === 'animal_groups') {
            $assignedIds = $user->assignedGroups()->pluck('animal_groups.id');
            if ($assignedIds->isNotEmpty()) {
                return $query->whereIn('id', $assignedIds);
            }
            if ($user->managed_by) {
                return $query->where('owner_id', $user->managed_by);
            }
            return $query;
        }

        if ($firstTable === 'animals') {
            $assignedGroupIds = $user->assignedGroups()->pluck('animal_groups.id');
            if ($assignedGroupIds->isNotEmpty()) {
                return $query->whereIn('id', function ($q) use ($assignedGroupIds) {
                    $q->select('animal_id')
                        ->from('animal_group_member')
                        ->whereIn('animal_group_id', $assignedGroupIds);
                });
            }
            if ($user->managed_by) {
                return $query->where('owner_id', $user->managed_by);
            }
            return $query;
        }

        if ($user->managed_by) {
            return $query->where('owner_id', $user->managed_by);
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
