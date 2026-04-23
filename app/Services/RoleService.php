<?php

namespace App\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleService
{
    /**
     * List roles with pagination and filters.
     */
    public function list($request)
    {
        $query = Role::query()->withCount(['permissions']);

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        if (! in_array($sortBy, ['name', 'created_at'], true)) {
            $sortBy = 'created_at';
        }
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $paginator = $query->paginate($request->get('per_page', 15));

        $paginator->getCollection()->transform(function (Role $role) {
            $role->setAttribute('users_count', $this->getUsersQuery($role->id)->count());

            return $role;
        });

        return $paginator;
    }

    /**
     * Get full role details with permissions and users.
     */
    public function detail($roleId): Role
    {
        $role = Role::with(['permissions'])->findOrFail($roleId);
        $users = $this->getUsersQuery($role->id)->get();

        $role->setRelation('users', $users);
        $role->setAttribute('users_count', $users->count());

        return $role;
    }

    /**
     * Create a new role.
     */
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'web',
            ]);

            if (! empty($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            return $this->detail($role->id);
        });
    }

    /**
     * Update role details.
     */
    public function update($roleId, array $data): Role
    {
        return DB::transaction(function () use ($roleId, $data) {
            $role = Role::findOrFail($roleId);

            if ($this->isSystemRole($role->name) && isset($data['name']) && $data['name'] !== $role->name) {
                abort(422, 'Cannot rename a protected system role.');
            }

            $role->update([
                'name' => $data['name'] ?? $role->name,
            ]);

            return $this->detail($role->id);
        });
    }

    /**
     * Sync permissions for a role.
     */
    public function syncPermissions($roleId, array $permissionIds): Role
    {
        return DB::transaction(function () use ($roleId, $permissionIds) {
            $role = Role::findOrFail($roleId);

            if ($role->name === 'superadmin' && empty($permissionIds)) {
                abort(422, 'Cannot remove all permissions from superadmin role.');
            }

            $role->syncPermissions($permissionIds);

            return $this->detail($role->id);
        });
    }

    /**
     * Delete a role.
     */
    public function delete($roleId): void
    {
        $role = Role::findOrFail($roleId);

        // Prevent deleting system roles
        if ($this->isSystemRole($role->name)) {
            abort(422, 'Cannot delete protected system roles.');
        }

        // Prevent deleting roles with users
        if ($this->getUsersQuery($role->id)->exists()) {
            abort(422, 'Cannot delete roles assigned to users.');
        }

        $role->delete();
    }

    /**
     * Get role summary counts.
     */
    public function getSummary(): array
    {
        $systemRoles = ['superadmin', 'admin', 'member'];

        return [
            'total'       => Role::count(),
            'system'      => Role::whereIn('name', $systemRoles)->count(),
            'custom'      => Role::whereNotIn('name', $systemRoles)->count(),
            'in_use'      => DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->distinct('role_id')
                ->count('role_id'),
        ];
    }

    private function getUsersQuery(int|string $roleId)
    {
        return User::query()->whereIn('id', function ($query) use ($roleId) {
            $query->select('model_id')
                ->from('model_has_roles')
                ->where('role_id', $roleId)
                ->where('model_type', User::class);
        });
    }

    /**
     * Check if a role is a system role.
     */
    private function isSystemRole(string $roleName): bool
    {
        return in_array($roleName, ['superadmin', 'admin', 'member']);
    }
}
