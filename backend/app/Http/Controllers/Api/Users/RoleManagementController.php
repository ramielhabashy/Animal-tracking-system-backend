<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleManagementController extends Controller
{
    protected $systemRoles = ['Admin', 'Owner', 'Manager', 'Shepherd', 'Doctor'];

    public function index(Request $request): JsonResponse
    {
        $query = Role::with('permissions');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $roles = $query->get()->map(function ($role) {
            $userCount = User::role($role->name)->count();
            return [
                'id' => $role->id,
                'name' => $role->name,
                'type' => $role->type ?? 'user',
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name'),
                'user_count' => $userCount,
                'is_system' => in_array($role->name, $this->systemRoles),
                'created_at' => $role->created_at,
                'updated_at' => $role->updated_at,
            ];
        });

        $permissions = Permission::all()->pluck('name');
        $permissionsByCategory = $this->getPermissionsByCategory($permissions);

        return response()->json([
            'roles' => $roles,
            'permissions' => $permissions,
            'permissionsByCategory' => $permissionsByCategory,
        ]);
    }

    protected function getPermissionsByCategory($permissions)
    {
        $categories = [
            'users' => ['user_view', 'user_create', 'user_edit', 'user_delete', 'user_assign_role'],
            'animals' => ['animal_view', 'animal_create', 'animal_edit', 'animal_delete', 'animal_view_health'],
            'devices' => ['device_view', 'device_create', 'device_edit', 'device_delete'],
            'geofences' => ['geofence_view', 'geofence_create', 'geofence_edit', 'geofence_delete'],
            'geofence_alerts' => ['geofence_alert_view', 'geofence_alert_configure'],
            'tasks' => ['task_view', 'task_create', 'task_complete', 'task_delete'],
            'reports' => ['report_view', 'report_export'],
            'settings' => ['settings_view', 'settings_edit'],
            'medical' => ['medical_record_view', 'medical_record_create', 'medical_record_edit'],
            'vaccinations' => ['vaccination_view', 'vaccination_create', 'vaccination_edit'],
            'auctions' => ['auction_view', 'auction_create', 'auction_edit', 'auction_bid'],
            'support' => ['support_ticket_view', 'support_ticket_respond', 'support_ticket_resolve'],
            'billing' => ['billing_invoice_view', 'billing_payment_view', 'billing_refund_process'],
            'customer_service' => ['cs_user_view', 'cs_user_message', 'cs_subscription_modify'],
            'platform' => ['platform_report_view', 'platform_audit_view', 'platform_announcement'],
        ];

        $result = [];
        foreach ($categories as $category => $perms) {
            $categoryPerms = $permissions->filter(fn($p) => in_array($p, $perms));
            if ($categoryPerms->isNotEmpty()) {
                $result[$category] = [
                    'label' => $this->getCategoryLabel($category),
                    'permissions' => $categoryPerms->values(),
                ];
            }
        }

        $otherPerms = $permissions->filter(fn($p) => !in_array($p, array_merge(...array_values($categories))));
        if ($otherPerms->isNotEmpty()) {
            $result['other'] = [
                'label' => 'Other',
                'permissions' => $otherPerms->values(),
            ];
        }

        return $result;
    }

    private function getCategoryLabel(string $category): string
    {
        return match ($category) {
            'users' => 'Users',
            'animals' => 'Animals',
            'devices' => 'Devices',
            'geofences' => 'Geofences',
            'geofence_alerts' => 'Geofence Alerts',
            'tasks' => 'Tasks',
            'reports' => 'Reports',
            'settings' => 'Settings',
            'medical' => 'Medical Records',
            'vaccinations' => 'Vaccinations',
            'auctions' => 'Auctions',
            'support' => 'Support Tickets',
            'billing' => 'Billing & Payments',
            'customer_service' => 'Customer Service',
            'platform' => 'Platform Admin',
            default => ucfirst($category),
        };
    }

    public function storeRole(Request $request): JsonResponse
    {
        $authUser = $request->user();

        if (!$authUser || !$authUser->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized. Admin role required.', 'error' => 'unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:roles,name',
            'type' => 'sometimes|string|in:admin,user',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'type' => $validated['type'] ?? 'user',
        ]);

        if (!empty($validated['permissions'])) {
            $permissions = Permission::whereIn('name', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        return response()->json([
            'message' => 'Role created successfully',
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'type' => $role->type,
                'permissions' => $role->permissions->pluck('name'),
                'is_system' => false,
            ],
        ], 201);
    }

    public function updateRole(Request $request, string $role): JsonResponse
    {
        $authUser = $request->user();

        if (!$authUser || !$authUser->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized. Admin role required.', 'error' => 'unauthorized'], 403);
        }

        $roleModel = Role::where('name', $role)->first();

        if (!$roleModel) {
            return response()->json(['message' => 'Role not found', 'error' => 'not_found'], 404);
        }

        if (in_array($role, $this->systemRoles)) {
            return response()->json(['message' => 'Cannot modify system roles', 'error' => 'system_role'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:50|unique:roles,name',
            'type' => 'sometimes|string|in:admin,user',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $updates = [];
        if (isset($validated['name']) && $validated['name'] !== $role) {
            if (Role::where('name', $validated['name'])->exists()) {
                return response()->json(['message' => 'Role name already exists', 'error' => 'duplicate'], 400);
            }
            $updates['name'] = $validated['name'];
        }
        if (isset($validated['type'])) {
            $updates['type'] = $validated['type'];
        }
        if (!empty($updates)) {
            $roleModel->update($updates);
        }

        if (isset($validated['permissions'])) {
            $permissions = Permission::whereIn('name', $validated['permissions'])->get();
            $roleModel->syncPermissions($permissions);
        }

        return response()->json([
            'message' => 'Role updated successfully',
            'role' => [
                'id' => $roleModel->id,
                'name' => $roleModel->fresh()->name,
                'type' => $roleModel->type,
                'permissions' => $roleModel->fresh()->permissions->pluck('name'),
            ],
        ]);
    }

    public function deleteRole(Request $request, string $role): JsonResponse
    {
        $authUser = $request->user();

        if (!$authUser || !$authUser->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized. Admin role required.', 'error' => 'unauthorized'], 403);
        }

        $roleModel = Role::where('name', $role)->first();

        if (!$roleModel) {
            return response()->json(['message' => 'Role not found', 'error' => 'not_found'], 404);
        }

        if (in_array($role, $this->systemRoles)) {
            return response()->json(['message' => 'Cannot delete system roles', 'error' => 'system_role'], 403);
        }

        $userCount = User::role($role)->count();
        if ($userCount > 0) {
            return response()->json(['message' => 'Cannot delete role with users. Reassign users first.', 'error' => 'has_users'], 400);
        }

        $roleModel->delete();

        return response()->json(['message' => 'Role deleted successfully']);
    }

    public function getUserRoles(User $user): JsonResponse
    {
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function updateUserRoles(Request $request, User $user): JsonResponse
    {
        $authUser = $request->user();

        if (!$authUser || !$authUser->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized. Admin role required.', 'error' => 'unauthorized'], 403);
        }

        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user->syncRoles($validated['roles']);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'roles' => $user->fresh()->getRoleNames(),
            'message' => 'User roles updated successfully',
        ]);
    }
}
