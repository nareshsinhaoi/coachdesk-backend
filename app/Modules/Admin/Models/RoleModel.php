<?php

namespace App\Modules\Admin\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table         = 'roles';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['public_id', 'tenant_id', 'name', 'slug', 'is_system'];

    public function forTenant(int $tenantId): array
    {
        return $this->where('tenant_id', $tenantId)
                    ->orWhere('tenant_id', null)
                    ->orderBy('is_system', 'desc')
                    ->orderBy('name', 'asc')
                    ->findAll();
    }

    public function getPermissions(int $roleId): array
    {
        return $this->db->table('role_permissions rp')
            ->select('p.id, p.permission_key, p.module')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('rp.role_id', $roleId)
            ->get()->getResultArray();
    }

    public function setPermissions(int $roleId, array $permissionIds): void
    {
        $this->db->table('role_permissions')->where('role_id', $roleId)->delete();
        foreach ($permissionIds as $pid) {
            $this->db->table('role_permissions')->insert([
                'role_id'       => $roleId,
                'permission_id' => (int) $pid,
            ]);
        }
    }

    public function assignToUser(int $userId, int $roleId, ?int $branchId = null): void
    {
        $exists = $this->db->table('user_roles')
            ->where(['user_id' => $userId, 'role_id' => $roleId, 'branch_id' => $branchId])
            ->countAllResults();
        if (! $exists) {
            $this->db->table('user_roles')->insert([
                'user_id'   => $userId,
                'role_id'   => $roleId,
                'branch_id' => $branchId,
            ]);
        }
    }

    public function removeFromUser(int $userId, int $roleId, ?int $branchId = null): void
    {
        $b = $this->db->table('user_roles')
            ->where(['user_id' => $userId, 'role_id' => $roleId]);
        if ($branchId === null) {
            $b->where('branch_id', null);
        } else {
            $b->where('branch_id', $branchId);
        }
        $b->delete();
    }
}