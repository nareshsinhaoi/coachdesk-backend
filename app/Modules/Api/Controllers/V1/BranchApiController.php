<?php

namespace App\Modules\Api\Controllers\V1;

use App\Controllers\BaseController;
use App\Modules\Api\Libraries\TenantContext;

class BranchApiController extends BaseController
{
    public function index()
    {
        $tenantId = TenantContext::getTenantId();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $db = db_connect();

        $branches = $db->table('cd_branches')
            ->select('cd_branches.*,
                      (SELECT COUNT(*) FROM cd_users WHERE cd_users.branch_id = cd_branches.id AND cd_users.deleted_at IS NULL) AS staff_count,
                      (SELECT COUNT(*) FROM cd_students WHERE cd_students.branch_id = cd_branches.id AND cd_students.deleted_at IS NULL) AS student_count')
            ->where('cd_branches.tenant_id', $tenantId)
            ->where('cd_branches.deleted_at IS NULL')
            ->orderBy('cd_branches.name', 'ASC')
            ->get()->getResultArray();

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Branches retrieved',
            'data' => $branches,
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
        $code  = trim($body['branch_code'] ?? '');

        if ($name === '' || $code === '') {
            return $this->response->setStatusCode(422)
                ->setJSON(['status' => false, 'message' => 'name and branch_code are required']);
        }

        $db = db_connect();

        $exists = $db->table('cd_branches')
            ->where(['tenant_id' => $tenantId, 'branch_code' => $code, 'deleted_at' => null])
            ->countAllResults();
        if ($exists) {
            return $this->response->setStatusCode(409)
                ->setJSON(['status' => false, 'message' => 'Branch code already exists for this institute']);
        }

        $row = [
            'public_id'     => uuid4(),
            'tenant_id'     => $tenantId,
            'name'          => $name,
            'branch_code'   => $code,
            'address_line1' => $body['address_line1'] ?? null,
            'address_line2' => $body['address_line2'] ?? null,
            'city'          => $body['city'] ?? null,
            'state'         => $body['state'] ?? null,
            'pincode'       => $body['pincode'] ?? null,
            'phone'         => $body['phone'] ?? null,
            'email'         => $body['email'] ?? null,
            'status'        => ($body['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
        ];

        $db->table('cd_branches')->insert($row);
        $row['id'] = $db->insertID();

        return $this->response->setStatusCode(201)
            ->setJSON(['status' => true, 'message' => 'Branch created', 'data' => $row]);
    }

    public function update($publicId)
    {
        $tenantId = TenantContext::getTenantId();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $db   = db_connect();
        $row  = $db->table('cd_branches')
            ->where(['tenant_id' => $tenantId, 'public_id' => $publicId, 'deleted_at' => null])
            ->get()->getRowArray();
        if (! $row) {
            return $this->response->setStatusCode(404)
                ->setJSON(['status' => false, 'message' => 'Branch not found']);
        }

        $body    = $this->request->getJSON(true) ?? [];
        $allowed = ['name', 'address_line1', 'address_line2', 'city', 'state', 'pincode', 'phone', 'email'];
        $update  = array_intersect_key($body, array_flip($allowed));
        foreach ($update as $k => $v) {
            $update[$k] = ($v === '' ? null : $v);
        }
        if (isset($body['status']) && in_array($body['status'], ['active', 'inactive'], true)) {
            $update['status'] = $body['status'];
        }

        // branch_code uniqueness if changed
        if (! empty($body['branch_code']) && $body['branch_code'] !== $row['branch_code']) {
            $dup = $db->table('cd_branches')
                ->where(['tenant_id' => $tenantId, 'branch_code' => $body['branch_code'], 'deleted_at' => null])
                ->where('id !=', $row['id'])
                ->countAllResults();
            if ($dup) {
                return $this->response->setStatusCode(409)
                    ->setJSON(['status' => false, 'message' => 'Branch code already exists for this institute']);
            }
            $update['branch_code'] = $body['branch_code'];
        }

        if ($update) {
            $db->table('cd_branches')->where('id', $row['id'])->update($update);
        }

        $fresh = $db->table('cd_branches')->where('id', $row['id'])->get()->getRowArray();
        return $this->response->setJSON(['status' => true, 'message' => 'Branch updated', 'data' => $fresh]);
    }

    public function delete($publicId)
    {
        $tenantId = TenantContext::getTenantId();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $db  = db_connect();
        $row = $db->table('cd_branches')
            ->where(['tenant_id' => $tenantId, 'public_id' => $publicId, 'deleted_at' => null])
            ->get()->getRowArray();
        if (! $row) {
            return $this->response->setStatusCode(404)
                ->setJSON(['status' => false, 'message' => 'Branch not found']);
        }

        // Guard: block delete when branch still has staff or students
        $staff   = (int) $db->table('cd_users')->where(['branch_id' => $row['id'], 'deleted_at' => null])->countAllResults();
        $students = (int) $db->table('cd_students')->where(['branch_id' => $row['id'], 'deleted_at' => null])->countAllResults();

        if ($staff > 0 || $students > 0) {
            return $this->response->setStatusCode(409)->setJSON([
                'status'  => false,
                'message' => "Cannot delete: branch still has {$staff} staff and {$students} students assigned.",
            ]);
        }

        $db->table('cd_branches')->where('id', $row['id'])
            ->update(['deleted_at' => date('Y-m-d H:i:s')]);

        return $this->response->setJSON(['status' => true, 'message' => 'Branch removed']);
    }
}
