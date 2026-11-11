<?php
namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Api\Services\DashboardService;
use App\Modules\Api\Libraries\TenantContext;
require_once __DIR__ . '/../Helpers/admin_helper.php';


class DashboardController extends BaseController
{
    public function index()
    {
        // die('123');
        $service = new DashboardService();

        if (is_super_admin()) {
            return view('\App\Modules\Admin\Views\dashboard\super', [
                'title' => 'Super Admin Dashboard',
                'stats' => $service->superAdminDashboard(),
            ]);
        }

        $user = current_user();
        if ($user === null) {
            return redirect()->to('/admin/login')->with('error', 'Please log in.');
        }

        $branchId = session()->get('branch_id');
        TenantContext::set(
            (int) $user->tenant_id,
            $branchId !== null ? (int) $branchId : null,
            (int) $user->id
        );

        try {
            $stats = $service->stats();
        } finally {
            TenantContext::reset();
        }

        return view('\App\Modules\Admin\Views\dashboard\index', [
            'title' => 'Dashboard',
            'stats' => $stats,
        ]);
    }
}