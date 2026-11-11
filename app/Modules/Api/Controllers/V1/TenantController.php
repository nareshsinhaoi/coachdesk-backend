<?php

namespace App\Modules\Api\Controllers\V1;

use App\Controllers\BaseController;
use App\Modules\Api\Libraries\TenantContext;

class TenantController extends BaseController
{
    public function index()
    {
        $db = db_connect();

        // Super admin sees all; tenant user sees only own
        if (TenantContext::isSuperAdmin()) {
            $tenants = $db->table('cd_tenants')->where('deleted_at IS NULL')->get()->getResult();
        } else {
            $tenants = $db->table('cd_tenants')
                ->where('id', TenantContext::getTenantId())
                ->where('deleted_at IS NULL')
                ->get()->getResult();
        }

        return $this->response->setJSON([
            'status' => true,
            'data' => $tenants,
        ]);
    }

    public function show($id)
    {
        $tenant = db_connect()->table('cd_tenants')
            ->where('public_id', $id)
            ->where('deleted_at IS NULL')
            ->get()->getRow();

        if ($tenant === null) {
            return $this->response->setStatusCode(404)
                ->setJSON(['status' => false, 'message' => 'Tenant not found']);
        }

        // Tenant user can only view own tenant
        if (! TenantContext::isSuperAdmin() && (int) $tenant->id !== TenantContext::getTenantId()) {
            return $this->response->setStatusCode(403)
                ->setJSON(['status' => false, 'message' => 'Access denied']);
        }

        return $this->response->setJSON(['status' => true, 'data' => $tenant]);
    }
}