<?php

namespace App\Modules\Admin\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'public_id', 'tenant_id', 'name', 'email', 'phone',
        'password_hash', 'avatar_path', 'status',
        'email_verified_at', 'phone_verified_at',
        'last_login_at', 'last_login_ip', 'failed_logins', 'locked_until',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'name'     => 'required|min_length[2]|max_length[150]',
        'email'    => 'required|valid_email|max_length[190]',
        'password' => 'permit_empty|min_length[8]',
        'status'   => 'in_list[active,inactive,locked]',
    ];

    protected $allowCallbacks = true;
    protected $beforeInsert   = ['generatePublicId', 'hashPassword'];
    protected $beforeUpdate   = ['hashPassword'];

    protected function generatePublicId(array $data): array
    {
        if (empty($data['data']['public_id'])) {
            $data['data']['public_id'] = $this->uuid4();
        }
        return $data;
    }

    protected function hashPassword(array $data): array
    {
        if (! empty($data['data']['password'])) {
            $data['data']['password_hash'] = password_hash($data['data']['password'], PASSWORD_BCRYPT);
            unset($data['data']['password']);
        }
        return $data;
    }

    /* -------- Finders -------- */

    public function findForLogin(string $email): ?array
    {
        return $this->where('email', $email)
                    ->where('status', 'active')
                    ->where('deleted_at IS NULL')
                    ->first();
    }

    public function findByPublicId(string $publicId, ?int $tenantId = null): ?array
    {
        $b = $this->where('public_id', $publicId);
        if ($tenantId !== null) $b->where('tenant_id', $tenantId);
        return $b->first();
    }

    public function listByTenant(int $tenantId, int $perPage = 20)
    {
        return $this->where('tenant_id', $tenantId)
                    ->where('deleted_at IS NULL')
                    ->orderBy('id', 'desc')
                    ->paginate($perPage);
    }

    /* -------- Login tracking -------- */

    public function recordLogin(int $userId, string $ip): void
    {
        $this->builder()
            ->where('id', $userId)
            ->update([
                'last_login_at' => date('Y-m-d H:i:s'),
                'last_login_ip' => $ip,
                'failed_logins' => 0,
                'locked_until'  => null,
            ]);
    }

    public function recordFailedLogin(int $userId, int $maxAttempts = 5, int $lockMinutes = 15): void
    {
        $user = $this->find($userId);
        if (! $user) return;
        $attempts = (int) $user['failed_logins'] + 1;
        $update = ['failed_logins' => $attempts];
        if ($attempts >= $maxAttempts) {
            $update['locked_until'] = date('Y-m-d H:i:s', strtotime("+{$lockMinutes} minutes"));
        }
        $this->builder()->where('id', $userId)->update($update);
    }

    public function isLocked(array $user): bool
    {
        return ! empty($user['locked_until']) && strtotime($user['locked_until']) > time();
    }

    /* -------- Password -------- */

    public function verifyPassword(array $user, string $plain): bool
    {
        return password_verify($plain, $user['password_hash']);
    }

    public function setPassword(int $userId, string $newPlain): bool
    {
        return (bool) $this->builder()
            ->where('id', $userId)
            ->update(['password_hash' => password_hash($newPlain, PASSWORD_BCRYPT)]);
    }

    /* -------- Roles & permissions -------- */

    public function getRoles(int $userId): array
    {
        return $this->db->table('user_roles ur')
            ->select('r.id, r.name, r.slug, ur.branch_id')
            ->join('roles r', 'r.id = ur.role_id')
            ->where('ur.user_id', $userId)
            ->get()->getResultArray();
    }

    public function getPermissions(int $userId): array
    {
        // Union of role permissions + user overrides (allow wins, deny overrides)
        $rolePerms = $this->db->table('user_roles ur')
            ->select('p.permission_key')
            ->join('role_permissions rp', 'rp.role_id = ur.role_id')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('ur.user_id', $userId)
            ->get()->getResultArray();

        $keys = array_column($rolePerms, 'permission_key');

        $overrides = $this->db->table('user_permissions up')
            ->select('p.permission_key, up.effect')
            ->join('permissions p', 'p.id = up.permission_id')
            ->where('up.user_id', $userId)
            ->get()->getResultArray();

        foreach ($overrides as $o) {
            if ($o['effect'] === 'allow') {
                $keys[] = $o['permission_key'];
            } else {
                $keys = array_diff($keys, [$o['permission_key']]);
            }
        }
        return array_values(array_unique($keys));
    }

    protected function uuid4(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }
}