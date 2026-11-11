<?php
namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
require_once __DIR__ . '/../Helpers/admin_helper.php';

class AuditLogController extends BaseController
{
    public function index()
    {
        if (! is_super_admin()) return redirect()->to('/admin/dashboard');

        $db = db_connect();
        $tenantFilter = $this->request->getGet('tenant_id');
        $actionFilter = $this->request->getGet('action');

        $q = $db->table('cd_activity_logs al')
            ->select('al.*, t.name AS tenant_name, u.name AS user_name, u.email AS user_email')
            ->join('cd_tenants t', 't.id = al.tenant_id', 'left')
            ->join('cd_users u', 'u.id = al.user_id', 'left')
            ->orderBy('al.id', 'desc');

        if ($tenantFilter) $q->where('al.tenant_id', (int) $tenantFilter);
        if ($actionFilter) $q->like('al.action', $actionFilter);

        $logs = $q->limit(500)->get()->getResultArray();

        $tenants = $db->table('cd_tenants')->where('deleted_at IS NULL')->get()->getResultArray();

        return view('App\Modules\Admin\Views\audit_logs\index', [
            'title' => 'Audit Logs', 'logs' => $logs, 'tenants' => $tenants,
        ]);
    }
}