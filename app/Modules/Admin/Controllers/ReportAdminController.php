<?php
namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
require_once __DIR__ . '/../Helpers/admin_helper.php';

class ReportAdminController extends BaseController
{
    public function revenue()
    {
        if (! is_super_admin()) return redirect()->to('/admin/dashboard');

        $db = db_connect();
        $monthly = $db->query("
            SELECT DATE_FORMAT(paid_at, '%Y-%m') AS ym, SUM(amount) AS total, COUNT(*) AS cnt
            FROM cd_subscription_payments
            WHERE status = 'success'
            GROUP BY ym ORDER BY ym DESC LIMIT 12
        ")->getResultArray();

        $byPlan = $db->query("
            SELECT p.name, COUNT(ts.id) AS subs, SUM(p.price) AS mrr
            FROM cd_subscription_plans p
            LEFT JOIN cd_tenant_subscriptions ts ON ts.plan_id = p.id AND ts.status = 'active'
            GROUP BY p.id
            ORDER BY mrr DESC
        ")->getResultArray();

        return view('App\Modules\Admin\Views\reports\revenue', [
            'title' => 'Revenue Report', 'monthly' => $monthly, 'byPlan' => $byPlan,
        ]);
    }
}