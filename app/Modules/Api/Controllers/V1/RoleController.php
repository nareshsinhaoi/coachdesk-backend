<?php

namespace App\Modules\Api\Controllers\V1;

use App\Controllers\BaseController;
use App\Modules\Api\Libraries\TenantContext;

class RoleController extends BaseController
{
    public function index()
    {
        $tenantId = TenantContext::getTenantId();

        // System roles (tenant_id IS NULL) + tenant-specific roles
        $roles = db_connect()->table('cd_roles')
            ->groupStart()
                ->where('tenant_id', $tenantId)
                ->orWhere('tenant_id IS NULL')
            ->groupEnd()
            ->get()->getResult();

        return $this->response->setJSON(['status' => true, 'data' => $roles]);
    }

    public function permissions($roleId)
    {
        $perms = db_connect()->table('cd_role_permissions rp')
            ->join('cd_permissions p', 'p.id = rp.permission_id')
            ->where('rp.role_id', $roleId)
            ->get()->getResult();

        return $this->response->setJSON(['status' => true, 'data' => $perms]);
    }
}