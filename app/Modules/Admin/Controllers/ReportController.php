<?php
namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
require_once __DIR__ . '/../Helpers/admin_helper.php';

class ReportController extends BaseController
{
    public function index()
    {
        $db = db_connect();
        $tenantId = tenant_id();

        $feeMonthly = $db->query(
            "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS ym, SUM(amount) AS total
             FROM cd_payments WHERE tenant_id = ? AND status = 'success'
             GROUP BY ym ORDER BY ym DESC LIMIT 12", [$tenantId]
        )->getResultArray();

        $attendanceByBatch = $db->query(
            "SELECT b.batch_name,
                    SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) AS present,
                    SUM(CASE WHEN ar.attendance_status = 'absent' THEN 1 ELSE 0 END) AS absent,
                    COUNT(*) AS total
             FROM cd_attendance_records ar
             JOIN cd_attendance_sessions s ON s.id = ar.session_id
             JOIN cd_batches b ON b.id = s.batch_id
             WHERE ar.tenant_id = ?
             GROUP BY b.id", [$tenantId]
        )->getResultArray();

        return view('App\Modules\Admin\Views\reports\index', [
            'title' => 'Reports',
            'feeMonthly' => $feeMonthly,
            'attendanceByBatch' => $attendanceByBatch,
        ]);
    }
}