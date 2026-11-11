<?php

namespace App\Modules\Api\Controllers\V1;

use App\Controllers\BaseController;
use App\Modules\Api\Libraries\TenantContext;

/**
 * Staff user management for the Admin frontend (Users page).
 */
class UserApiController extends BaseController
{
    public function index()
    {
        $tenantId = TenantContext::getTenantId();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $page  = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit = min(100, max(1, (int) ($this->request->getGet('perPage') ?? $this->request->getGet('limit') ?? 20)));
        $search = trim((string) $this->request->getGet('search'));
        $status = $this->request->getGet('status');

        $b = db_connect()->table('cd_users')
            ->select("cd_users.id, cd_users.public_id, cd_users.name, cd_users.email, cd_users.phone,
                      cd_users.status, cd_users.last_login_at, cd_users.branch_id, cd_users.created_at,
                      GROUP_CONCAT(r.name SEPARATOR ', ') AS roles,
                      cd_branches.name AS branch_name")
            ->join('cd_user_roles ur', 'ur.user_id = cd_users.id', 'left')
            ->join('cd_roles r', 'r.id = ur.role_id', 'left')
            ->join('cd_branches', 'cd_branches.id = cd_users.branch_id', 'left')
            ->where('cd_users.tenant_id', $tenantId)
            ->where('cd_users.deleted_at IS NULL')
            ->groupBy('cd_users.id');

        if ($search !== '') {
            $b->groupStart()
                ->like('cd_users.name', $search)
                ->orLike('cd_users.email', $search)
                ->orLike('cd_users.phone', $search)
                ->groupEnd();
        }
        if ($status && in_array($status, ['active', 'inactive', 'locked'], true)) {
            $b->where('cd_users.status', $status);
        }

        $countBuilder = clone $b;
        $total = (int) $countBuilder->countAllResults(false);
        $users = $b->orderBy('cd_users.id', 'DESC')
            ->limit($limit, ($page - 1) * $limit)
            ->get()->getResultArray();

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Users retrieved',
            'data'    => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int) ceil($total / max(1, $limit)),
            ],
        ]);
    }

    public function formOptions()
    {
        $tenantId = TenantContext::getTenantId();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $db = db_connect();

        $roles = $db->table('cd_roles')
            ->select('id, name, slug')
            ->where('tenant_id', $tenantId)
            ->orWhere('tenant_id IS NULL')
            ->where('slug !=', 'super_admin')
            ->orderBy('is_system', 'desc')
            ->orderBy('name', 'asc')
            ->get()->getResultArray();

        $branches = $db->table('cd_branches')
            ->select('id, public_id, name')
            ->where(['tenant_id' => $tenantId, 'deleted_at' => null])
            ->where('status', 'active')
            ->orderBy('name', 'asc')
            ->get()->getResultArray();

        return $this->response->setJSON([
            'status' => true,
            'data'   => ['roles' => $roles, 'branches' => $branches],
        ]);
    }

    public function create()
    {
        $tenantId = TenantContext::getTenantId();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $body = $this->request->getJSON(true) ?? [];

        $name  = trim($body['name'] ?? '');
        $email = trim($body['email'] ?? '');
        $roleId = (int) ($body['role_id'] ?? 0);

        if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $roleId) {
            return $this->response->setStatusCode(422)
                ->setJSON(['status' => false, 'message' => 'name, valid email and role_id are required']);
        }

        $db = db_connect();

        if ($db->table('cd_users')->where(['tenant_id' => $tenantId, 'email' => $email])->where('deleted_at IS NULL')->countAllResults()) {
            return $this->response->setStatusCode(409)
                ->setJSON(['status' => false, 'message' => 'A user with this email already exists']);
        }

        $role = $db->table('cd_roles')->where('id', $roleId)->get()->getRow();
        if (! $role) {
            return $this->response->setStatusCode(404)
                ->setJSON(['status' => false, 'message' => 'Role not found']);
        }

        // Generate a temporary password
        $tempPass = 'Temp@' . substr(md5(uniqid('', true)), 0, 8);

        $db->transBegin();
        try {
            $db->table('cd_users')->insert([
                'public_id'         => uuid4(),
                'tenant_id'         => $tenantId,
                'name'              => $name,
                'email'             => $email,
                'phone'             => $body['phone'] ?? null,
                'password_hash'     => password_hash($tempPass, PASSWORD_BCRYPT),
                'email_verified_at' => date('Y-m-d H:i:s'),
                'status'            => 'active',
            ]);
            $newUserId = $db->insertID();

            $db->table('cd_user_roles')->insert([
                'user_id'   => $newUserId,
                'role_id'   => $roleId,
                'branch_id' => ! empty($body['branch_id']) ? (int) $body['branch_id'] : null,
            ]);

            if ($db->transStatus() === false) throw new \RuntimeException('Transaction failed');
            $db->transCommit();

            return $this->response->setStatusCode(201)->setJSON([
                'status'  => true,
                'message' => 'User created',
                'data'    => [
                    'public_id'      => $db->table('cd_users')->select('public_id')->where('id', $newUserId)->get()->getRow()->public_id,
                    'temp_password'  => $tempPass,
                ],
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'API user create failed: {m}', ['m' => $e->getMessage()]);
            return $this->response->setStatusCode(500)
                ->setJSON(['status' => false, 'message' => 'Unable to create user']);
        }
    }

    public function update($publicId)
    {
        $tenantId = TenantContext::getTenantId();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $db  = db_connect();
        $row = $db->table('cd_users')
            ->where(['tenant_id' => $tenantId, 'public_id' => $publicId, 'deleted_at' => null])
            ->get()->getRowArray();
        if (! $row) {
            return $this->response->setStatusCode(404)
                ->setJSON(['status' => false, 'message' => 'User not found']);
        }

        $body   = $this->request->getJSON(true) ?? [];
        $update = [];

        if (isset($body['name']) && trim($body['name']) !== '') {
            $update['name'] = trim($body['name']);
        }
        if (isset($body['phone'])) {
            $update['phone'] = $body['phone'] ?: null;
        }
        if (isset($body['status']) && in_array($body['status'], ['active', 'inactive', 'locked'], true)) {
            $update['status'] = $body['status'];
        }
        if (isset($body['email'])) {
            $email = trim($body['email']);
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->response->setStatusCode(422)
                    ->setJSON(['status' => false, 'message' => 'Invalid email']);
            }
            $dup = $db->table('cd_users')
                ->where(['tenant_id' => $tenantId, 'email' => $email])
                ->where('deleted_at IS NULL')->where('id !=', $row['id'])
                ->countAllResults();
            if ($dup) {
                return $this->response->setStatusCode(409)
                    ->setJSON(['status' => false, 'message' => 'Email already in use']);
            }
            $update['email'] = $email;
        }

        if ($update) {
            $db->table('cd_users')->where('id', $row['id'])->update($update);
        }

        // Role / branch reassignment
        if (! empty($body['role_id']) || array_key_exists('branch_id', $body)) {
            $ur = $db->table('cd_user_roles')->where('user_id', $row['id'])->get()->getRow();
            if ($ur) {
                $urUpdate = [];
                if (! empty($body['role_id']))  $urUpdate['role_id']  = (int) $body['role_id'];
                if (array_key_exists('branch_id', $body)) {
                    $urUpdate['branch_id'] = ! empty($body['branch_id']) ? (int) $body['branch_id'] : null;
                }
                if ($urUpdate) {
                    $db->table('cd_user_roles')->where('id', $ur->id)->update($urUpdate);
                }
            } elseif (! empty($body['role_id'])) {
                $db->table('cd_user_roles')->insert([
                    'user_id'   => $row['id'],
                    'role_id'   => (int) $body['role_id'],
                    'branch_id' => ! empty($body['branch_id']) ? (int) $body['branch_id'] : null,
                ]);
            }
        }

        $fresh = $db->table('cd_users')->where('id', $row['id'])->get()->getRowArray();

        return $this->response->setJSON([
            'status' => true,
            'message' => 'User updated',
            'data'   => ['id' => $fresh['id'], 'name' => $fresh['name'], 'email' => $fresh['email'], 'status' => $fresh['status']],
        ]);
    }

    public function resetPassword($publicId)
    {
        $tenantId = TenantContext::getTenantId();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $db  = db_connect();
        $row = $db->table('cd_users')
            ->where(['tenant_id' => $tenantId, 'public_id' => $publicId, 'deleted_at' => null])
            ->get()->getRowArray();
        if (! $row) {
            return $this->response->setStatusCode(404)
                ->setJSON(['status' => false, 'message' => 'User not found']);
        }

        $tempPass = 'Temp@' . substr(md5(uniqid('', true)), 0, 8);

        $db->table('cd_users')->where('id', $row['id'])
            ->update(['password_hash' => password_hash($tempPass, PASSWORD_BCRYPT)]);

        // Force re-login everywhere
        $db->table('cd_refresh_tokens')
            ->where('user_id', $row['id'])->where('revoked_at IS NULL')
            ->update(['revoked_at' => date('Y-m-d H:i:s')]);

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Password reset',
            'data'   => ['temp_password' => $tempPass],
        ]);
    }
}
