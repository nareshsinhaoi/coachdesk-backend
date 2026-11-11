<?php

namespace App\Modules\Api\Controllers\V1;

use App\Controllers\BaseController;
use App\Modules\Api\Libraries\TenantContext;

class BranchController extends BaseController
{
    public function index()
    {
        $tenantId = TenantContext::getTenantId();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $branches = db_connect()->table('cd_branches')
            ->where('tenant_id', $tenantId)
            ->where('deleted_at IS NULL')
            ->get()->getResult();

        return $this->response->setJSON(['status' => true, 'data' => $branches]);
    }

    public function create()
    {
        $tenantId = TenantContext::getTenantId();
        $data = [
            'public_id' => bin2hex(random_bytes(16)),
            'tenant_id' => $tenantId,
            'name' => $this->request->getJsonVar('name'),
            'branch_code' => $this->request->getJsonVar('branch_code'),
            'address_line1' => $this->request->getJsonVar('address_line1'),
            'city' => $this->request->getJsonVar('city'),
            'state' => $this->request->getJsonVar('state'),
            'status' => 'active',
        ];

        // Validate required fields
        if (empty($data['name']) || empty($data['branch_code'])) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => false,
                'message' => 'name and branch_code are required',
            ]);
        }

        // Check duplicate within tenant
        $exists = db_connect()->table('cd_branches')
            ->where(['tenant_id' => $tenantId, 'branch_code' => $data['branch_code']])
            ->countAllResults();

        if ($exists > 0) {
            return $this->response->setStatusCode(409)->setJSON([
                'status' => false,
                'message' => 'Branch code already exists for this tenant',
            ]);
        }

        db_connect()->table('cd_branches')->insert($data);

        return $this->response->setStatusCode(201)->setJSON([
            'status' => true,
            'message' => 'Branch created',
            'data' => $data,
        ]);
    }
}