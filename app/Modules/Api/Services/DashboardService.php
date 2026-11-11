<?php

namespace App\Modules\Api\Services;

use App\Modules\Api\Libraries\TenantContext;

class DashboardService
{
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    /**
     * Get dashboard stats for the current tenant/branch or super admin.
     */
    public function superAdminDashboard(): array
    {
        $db = $this->db;

        // Tenants
        $tenants = [
            'total'     => (int) $db->table('cd_tenants')->where('deleted_at IS NULL')->countAllResults(),
            'active'    => (int) $db->table('cd_tenants')->where(['status' => 'active', 'deleted_at' => null])->countAllResults(),
            'trial'     => (int) $db->table('cd_tenants')->where(['status' => 'trial', 'deleted_at' => null])->countAllResults(),
            'suspended' => (int) $db->table('cd_tenants')->where(['status' => 'suspended', 'deleted_at' => null])->countAllResults(),
            'expired'   => (int) $db->table('cd_tenants')->where(['status' => 'expired', 'deleted_at' => null])->countAllResults(),
        ];

        // Global students / users across all tenants
        $students_total = (int) $db->table('cd_students')->where('deleted_at IS NULL')->countAllResults();
        $users_total    = (int) $db->table('cd_users')->where('deleted_at IS NULL')->where('tenant_id IS NOT NULL')->countAllResults();

        // MRR (this month)
        $mrr = (float) ($db->table('cd_subscription_payments')
            ->selectSum('amount', 'total')
            ->where('status', 'success')
            ->where('paid_at >=', date('Y-m-01 00:00:00'))
            ->get()->getRow()->total ?? 0);

        // Total collected all-time
        $revenue_total = (float) ($db->table('cd_subscription_payments')
            ->selectSum('amount', 'total')
            ->where('status', 'success')
            ->get()->getRow()->total ?? 0);

        // Recent tenants (last 10)
        $recent_tenants = $db->table('cd_tenants')
            ->where('deleted_at IS NULL')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get()->getResultArray();

        // Tenants expiring soon (7 days)
        $expiring = $db->table('cd_tenant_subscriptions ts')
            ->select('t.name, t.code, ts.end_date, ts.status')
            ->join('cd_tenants t', 't.id = ts.tenant_id')
            ->where('ts.end_date <=', date('Y-m-d', strtotime('+7 days')))
            ->where('ts.end_date >=', date('Y-m-d'))
            ->where('ts.status', 'active')
            ->orderBy('ts.end_date', 'asc')
            ->get()->getResultArray();

        // Per-tenant student counts
        $perTenant = $db->query("
        SELECT t.id, t.name, t.code, t.status,
               (SELECT COUNT(*) FROM cd_students WHERE tenant_id = t.id AND deleted_at IS NULL) AS student_count,
               (SELECT COUNT(*) FROM cd_branches WHERE tenant_id = t.id AND deleted_at IS NULL) AS branch_count
        FROM cd_tenants t
        WHERE t.deleted_at IS NULL
        ORDER BY t.id DESC
    ")->getResultArray();

        // Signups per month (last 6)
        $signups = $db->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS c
        FROM cd_tenants
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY ym ORDER BY ym ASC
    ")->getResultArray();

        return [
            'tenants'         => $tenants,
            'students_total'  => $students_total,
            'users_total'     => $users_total,
            'mrr'             => $mrr,
            'revenue_total'   => $revenue_total,
            'recent_tenants'  => $recent_tenants,
            'expiring'        => $expiring,
            'per_tenant'      => $perTenant,
            'signups_chart'   => $signups,
        ];
    }
    /**
     * endpoint: GET /api/dashboard/stats
     */

    public function stats(): array
    {
        $tenantId = TenantContext::getTenantId();
        $branchId = TenantContext::getBranchId();

        if ($tenantId === null) {
            return $this->superAdminStats();
        }

        return [
            'students'           => $this->studentStats($tenantId, $branchId),
            'teachers'           => $this->teacherStats($tenantId, $branchId),
            'batches'            => $this->batchStats($tenantId, $branchId),
            'attendance_today'   => $this->attendanceToday($tenantId, $branchId),
            'fees'               => $this->feeStats($tenantId, $branchId),
            'recent_payments'    => $this->recentPayments($tenantId, $branchId),
            'upcoming_exams'     => $this->upcomingExams($tenantId, $branchId),
            'monthly_collection' => $this->monthlyCollection($tenantId, $branchId),
            'admissions_chart'   => $this->admissionsChart($tenantId, $branchId),
        ];
    }

    /* ---------------- SUPER ADMIN ---------------- */
    protected function superAdminStats(): array
    {
        $mrrRow = $this->db->table('cd_subscription_payments')
            ->selectSum('amount', 'total')
            ->where('status', 'success')
            ->where('paid_at >=', date('Y-m-01 00:00:00'))
            ->get()->getRow();
        $mrr = $mrrRow && $mrrRow->total !== null ? (float) $mrrRow->total : 0.0;

        return [
            'tenants' => [
                'total'   => (int) $this->db->table('cd_tenants')->where('deleted_at IS NULL')->countAllResults(),
                'active'  => (int) $this->db->table('cd_tenants')->where(['status' => 'active',  'deleted_at' => null])->countAllResults(),
                'trial'   => (int) $this->db->table('cd_tenants')->where(['status' => 'trial',   'deleted_at' => null])->countAllResults(),
                'expired' => (int) $this->db->table('cd_tenants')->where(['status' => 'expired', 'deleted_at' => null])->countAllResults(),
            ],
            'students_total' => (int) $this->db->table('cd_students')->where('deleted_at IS NULL')->countAllResults(),
            'mrr'            => $mrr,
        ];
    }

    /* ---------------- STUDENTS ---------------- */
    protected function studentStats(int $tenantId, ?int $branchId): array
    {
        $base = function () use ($tenantId, $branchId) {
            $b = $this->db->table('cd_students')
                ->where('cd_students.tenant_id', $tenantId)
                ->where('cd_students.deleted_at IS NULL');
            if ($branchId) {
                $b->where('cd_students.branch_id', $branchId);
            }
            return $b;
        };

        $total     = (int) $base()->countAllResults();
        $active    = (int) $base()->where('cd_students.status', 'active')->countAllResults();
        $inactive  = (int) $base()->where('cd_students.status', 'inactive')->countAllResults();
        $completed = (int) $base()->where('cd_students.status', 'completed')->countAllResults();
        $newMonth  = (int) $base()->where('cd_students.admission_date >=', date('Y-m-01'))->countAllResults();

        return [
            'total'          => $total,
            'active'         => $active,
            'inactive'       => $inactive,
            'completed'      => $completed,
            'new_this_month' => $newMonth,
        ];
    }

    /* ---------------- TEACHERS ---------------- */
    protected function teacherStats(int $tenantId, ?int $branchId): array
    {
        $b = $this->db->table('cd_teachers')
            ->where('cd_teachers.tenant_id', $tenantId)
            ->where('cd_teachers.deleted_at IS NULL');
        if ($branchId) {
            $b->where('cd_teachers.branch_id', $branchId);
        }

        return [
            'total' => (int) $b->countAllResults(),
        ];
    }

    /* ---------------- BATCHES ---------------- */
    protected function batchStats(int $tenantId, ?int $branchId): array
    {
        $base = function () use ($tenantId, $branchId) {
            $b = $this->db->table('cd_batches')
                ->where('cd_batches.tenant_id', $tenantId)
                ->where('cd_batches.deleted_at IS NULL');
            if ($branchId) {
                $b->where('cd_batches.branch_id', $branchId);
            }
            return $b;
        };

        $total     = (int) $base()->countAllResults();
        $running   = (int) $base()->where('cd_batches.status', 'running')->countAllResults();
        $upcoming  = (int) $base()->where('cd_batches.status', 'upcoming')->countAllResults();
        $completed = (int) $base()->where('cd_batches.status', 'completed')->countAllResults();

        return [
            'total'     => $total,
            'running'   => $running,
            'upcoming'  => $upcoming,
            'completed' => $completed,
        ];
    }

    /* ---------------- ATTENDANCE TODAY ---------------- */
    protected function attendanceToday(int $tenantId, ?int $branchId): array
    {
        $sql = "SELECT cd_attendance_records.attendance_status, COUNT(*) AS cnt
                FROM cd_attendance_records
                JOIN cd_attendance_sessions
                  ON cd_attendance_sessions.id = cd_attendance_records.session_id
                WHERE cd_attendance_sessions.attendance_date = CURDATE()
                  AND cd_attendance_records.tenant_id = ?
                  " . ($branchId ? "AND cd_attendance_sessions.branch_id = ?" : "") . "
                GROUP BY cd_attendance_records.attendance_status";

        $params = $branchId ? [$tenantId, $branchId] : [$tenantId];
        $rows   = $this->db->query($sql, $params)->getResultArray();

        $out = ['present' => 0, 'absent' => 0, 'late' => 0, 'half_day' => 0, 'on_leave' => 0];
        foreach ($rows as $r) {
            if (isset($out[$r['attendance_status']])) {
                $out[$r['attendance_status']] = (int) $r['cnt'];
            }
        }
        $out['total_marked'] = array_sum($out);

        return $out;
    }

    /* ---------------- FEES ---------------- */
    protected function feeStatsOld(int $tenantId, ?int $branchId): array
    {
        // Collected today
        $todayRow = $this->db->table('cd_payments')
            ->selectSum('amount', 'total')
            ->where('cd_payments.tenant_id', $tenantId)
            ->where('cd_payments.payment_date', date('Y-m-d'))
            ->where('cd_payments.status', 'success')
            ->get()->getRow();
        $collectedToday = $todayRow && $todayRow->total !== null ? (float) $todayRow->total : 0.0;

        // Collected this month
        $monthRow = $this->db->table('cd_payments')
            ->selectSum('amount', 'total')
            ->where('cd_payments.tenant_id', $tenantId)
            ->where('cd_payments.payment_date >=', date('Y-m-01'))
            ->where('cd_payments.status', 'success')
            ->get()->getRow();
        $collectedMonth = $monthRow && $monthRow->total !== null ? (float) $monthRow->total : 0.0;

        // Outstanding = total billed on pending installments - amount already allocated
        $sql = "SELECT
                  COALESCE((
                      SELECT SUM(cd_fee_installments.amount)
                      FROM cd_fee_installments
                      WHERE cd_fee_installments.tenant_id = ?
                        AND cd_fee_installments.status <> 'paid'
                  ), 0) AS billed,
                  COALESCE((
                      SELECT SUM(cd_payment_allocations.allocated_amount)
                      FROM cd_payment_allocations
                      JOIN cd_fee_installments
                        ON cd_fee_installments.id = cd_payment_allocations.installment_id
                      WHERE cd_fee_installments.tenant_id = ?
                  ), 0) AS collected";
        $row       = $this->db->query($sql, [$tenantId, $tenantId])->getRow();
        $billed    = $row && $row->billed !== null ? (float) $row->billed : 0.0;
        $collected = $row && $row->collected !== null ? (float) $row->collected : 0.0;

        // Defaulters = students with at least one overdue pending installment
        $defaulters = (int) $this->db->table('cd_fee_installments')
            ->join('cd_student_fee_accounts', 'cd_student_fee_accounts.id = cd_fee_installments.fee_account_id')
            ->where('cd_fee_installments.tenant_id', $tenantId)
            ->where('cd_fee_installments.status', 'pending')
            ->where('cd_fee_installments.due_date <', date('Y-m-d'))
            ->groupBy('cd_student_fee_accounts.student_id')
            ->countAllResults();

        return [
            'collected_today'  => $collectedToday,
            'collected_month'  => $collectedMonth,
            'outstanding'      => max(0.0, $billed - $collected),
            'defaulters_count' => $defaulters,
        ];
    }

    protected function feeStats(int $tenantId, ?int $branchId): array
    {
        // Collected today
        $todayRow = $this->db->table('cd_payments')
            ->selectSum('amount', 'total')
            ->where('cd_payments.tenant_id', $tenantId)
            ->where('cd_payments.payment_date', date('Y-m-d'))
            ->where('cd_payments.status', 'success')
            ->get()->getRow();
        $collectedToday = $todayRow && $todayRow->total !== null ? (float) $todayRow->total : 0.0;

        // Collected this month
        $monthRow = $this->db->table('cd_payments')
            ->selectSum('amount', 'total')
            ->where('cd_payments.tenant_id', $tenantId)
            ->where('cd_payments.payment_date >=', date('Y-m-01'))
            ->where('cd_payments.status', 'success')
            ->get()->getRow();
        $collectedMonth = $monthRow && $monthRow->total !== null ? (float) $monthRow->total : 0.0;

        // Outstanding = total billed on pending installments - amount already allocated
        $sql = "SELECT
                  COALESCE((
                      SELECT SUM(cd_fee_installments.amount)
                      FROM cd_fee_installments
                      WHERE cd_fee_installments.tenant_id = ?
                        AND cd_fee_installments.status <> 'paid'
                  ), 0) AS billed,
                  COALESCE((
                      SELECT SUM(cd_payment_allocations.allocated_amount)
                      FROM cd_payment_allocations
                      JOIN cd_fee_installments
                        ON cd_fee_installments.id = cd_payment_allocations.installment_id
                      WHERE cd_fee_installments.tenant_id = ?
                  ), 0) AS collected";
        $row       = $this->db->query($sql, [$tenantId, $tenantId])->getRow();
        $billed    = $row && $row->billed !== null ? (float) $row->billed : 0.0;
        $collected = $row && $row->collected !== null ? (float) $row->collected : 0.0;

        // ─── Defaulters count — FIXED: use native SQL, no countAllResults() wrapper ───
        $defSql = "SELECT COUNT(DISTINCT cd_student_fee_accounts.student_id) AS cnt
                   FROM cd_fee_installments
                   JOIN cd_student_fee_accounts
                     ON cd_student_fee_accounts.id = cd_fee_installments.fee_account_id
                   WHERE cd_fee_installments.tenant_id = ?
                     AND cd_fee_installments.status = 'pending'
                     AND cd_fee_installments.due_date < CURDATE()";
        $defRow = $this->db->query($defSql, [$tenantId])->getRow();
        $defaulters = $defRow && $defRow->cnt !== null ? (int) $defRow->cnt : 0;

        return [
            'collected_today'  => $collectedToday,
            'collected_month'  => $collectedMonth,
            'outstanding'      => max(0.0, $billed - $collected),
            'defaulters_count' => $defaulters,
        ];
    }

    protected function recentPayments(int $tenantId, ?int $branchId): array
    {
        return $this->db->table('cd_payments')
            ->select('cd_payments.public_id,
                      cd_payments.receipt_number,
                      cd_payments.amount,
                      cd_payments.method,
                      cd_payments.payment_date,
                      cd_students.name AS student_name')
            ->join('cd_students', 'cd_students.id = cd_payments.student_id')
            ->where('cd_payments.tenant_id', $tenantId)
            ->where('cd_payments.status', 'success')
            ->orderBy('cd_payments.payment_date', 'DESC')
            ->orderBy('cd_payments.id', 'DESC')
            ->limit(5)
            ->get()->getResultArray();
    }

    protected function upcomingExams(int $tenantId, ?int $branchId): array
    {
        return $this->db->table('cd_exams')
            ->select('cd_exams.public_id,
                      cd_exams.exam_name,
                      cd_exams.exam_date,
                      cd_batches.batch_name')
            ->join('cd_batches', 'cd_batches.id = cd_exams.batch_id')
            ->where('cd_exams.tenant_id', $tenantId)
            ->where('cd_exams.exam_date >=', date('Y-m-d'))
            ->where('cd_exams.deleted_at IS NULL')
            ->orderBy('cd_exams.exam_date', 'ASC')
            ->limit(5)
            ->get()->getResultArray();
    }

    protected function monthlyCollection(int $tenantId, ?int $branchId): array
    {
        $sql = "SELECT DATE_FORMAT(cd_payments.payment_date, '%Y-%m') AS ym,
                       SUM(cd_payments.amount) AS total
                FROM cd_payments
                WHERE cd_payments.tenant_id = ?
                  AND cd_payments.status = 'success'
                  AND cd_payments.payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY ym
                ORDER BY ym ASC";
        $rows = $this->db->query($sql, [$tenantId])->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'month' => $r['ym'],
                'total' => (float) $r['total'],
            ];
        }
        return $out;
    }

    // protected function admissionsChart(int $tenantId, ?int $branchId): array
    // {
    //     $sql = "SELECT DATE_FORMAT(admission_date, '%Y-%m') AS ym, COUNT(*) AS cnt
    //             FROM cd_students
    //             WHERE tenant_id = ? AND deleted_at IS NULL
    //               AND admission_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    //             GROUP BY ym ORDER BY ym ASC";
    //     $rows = $this->db->query($sql, [$tenantId])->getResultArray();
    //     return array_map(fn($r) => ['month' => $r['ym'], 'count' => (int) $r['cnt']], $rows);
    // }
    protected function admissionsChart(int $tenantId, ?int $branchId): array
    {
        $sql = "SELECT DATE_FORMAT(cd_students.admission_date, '%Y-%m') AS ym,
                       COUNT(*) AS cnt
                FROM cd_students
                WHERE cd_students.tenant_id = ?
                  AND cd_students.deleted_at IS NULL
                  AND cd_students.admission_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY ym
                ORDER BY ym ASC";
        $rows = $this->db->query($sql, [$tenantId])->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'month' => $r['ym'],
                'count' => (int) $r['cnt'],
            ];
        }
        return $out;
    }
}
