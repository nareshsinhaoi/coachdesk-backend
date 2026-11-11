<?php

namespace App\Modules\Admin\Models;

use CodeIgniter\Model;

/**
 * Handles both attendance_sessions and attendance_records.
 * Not a standard Model for a single table — provides methods for both.
 */
class AttendanceModel extends Model
{
    protected $table         = 'attendance_sessions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'public_id', 'tenant_id', 'branch_id', 'batch_id',
        'attendance_date', 'status', 'submitted_by', 'submitted_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';    // table has no updated_at
    protected $deletedField  = '';

    protected $allowCallbacks = true;
    protected $beforeInsert   = ['generatePublicId'];
    protected function generatePublicId(array $data): array
    {
        if (empty($data['data']['public_id'])) {
            $data['data']['public_id'] = $this->uuid4();
        }
        return $data;
    }

    /* =========================================================
     * SESSIONS
     * ========================================================= */

    public function findSessionByBatchDate(int $tenantId, int $batchId, string $date): ?array
    {
        return $this->where('tenant_id', $tenantId)
                    ->where('batch_id', $batchId)
                    ->where('attendance_date', $date)
                    ->first();
    }

    public function listSessionsByDate(int $tenantId, string $date, ?int $branchId = null): array
    {
        $b = $this->db->table('attendance_sessions s')
            ->select('s.*, b.batch_name, b.batch_code,
                      (SELECT COUNT(*) FROM attendance_records WHERE session_id = s.id AND attendance_status = "present") AS present_count,
                      (SELECT COUNT(*) FROM attendance_records WHERE session_id = s.id AND attendance_status = "absent")  AS absent_count,
                      (SELECT COUNT(*) FROM attendance_records WHERE session_id = s.id AND attendance_status = "late")    AS late_count')
            ->join('batches b', 'b.id = s.batch_id')
            ->where('s.tenant_id', $tenantId)
            ->where('s.attendance_date', $date);

        if ($branchId) $b->where('s.branch_id', $branchId);
        return $b->get()->getResultArray();
    }

    /**
     * Create or return existing session for batch+date.
     */
    public function ensureSession(int $tenantId, int $branchId, int $batchId, string $date, int $userId): int
    {
        $existing = $this->findSessionByBatchDate($tenantId, $batchId, $date);
        if ($existing) return (int) $existing['id'];

        $this->insert([
            'tenant_id'       => $tenantId,
            'branch_id'       => $branchId,
            'batch_id'        => $batchId,
            'attendance_date' => $date,
            'status'          => 'draft',
            'submitted_by'    => $userId,
        ]);
        return (int) $this->getInsertID();
    }

    /* =========================================================
     * RECORDS
     * ========================================================= */

    public function getRecords(int $sessionId): array
    {
        return $this->db->table('attendance_records ar')
            ->select('ar.*, s.student_code, s.name')
            ->join('students s', 's.id = ar.student_id')
            ->where('ar.session_id', $sessionId)
            ->get()->getResultArray();
    }

    /**
     * Bulk save records for a session.
     * $data = [ ['student_id' => 1, 'attendance_status' => 'present', 'remarks' => ''], ... ]
     * Atomic transaction — all or nothing.
     */
    public function saveRecords(int $tenantId, int $sessionId, array $records): bool
    {
        $allowed = ['present', 'absent', 'late', 'half_day', 'on_leave'];

        $this->db->transStart();

        foreach ($records as $r) {
            if (! isset($r['student_id'], $r['attendance_status'])) continue;
            if (! in_array($r['attendance_status'], $allowed, true)) continue;

            $existing = $this->db->table('attendance_records')
                ->where(['session_id' => $sessionId, 'student_id' => (int) $r['student_id']])
                ->get()->getRow();

            if ($existing) {
                $this->db->table('attendance_records')
                    ->where('id', $existing->id)
                    ->update([
                        'attendance_status' => $r['attendance_status'],
                        'remarks'           => $r['remarks'] ?? null,
                    ]);
            } else {
                $this->db->table('attendance_records')->insert([
                    'tenant_id'         => $tenantId,
                    'session_id'        => $sessionId,
                    'student_id'        => (int) $r['student_id'],
                    'attendance_status' => $r['attendance_status'],
                    'remarks'           => $r['remarks'] ?? null,
                ]);
            }
        }

        $this->db->table('attendance_sessions')
            ->where('id', $sessionId)
            ->update(['status' => 'submitted', 'submitted_at' => date('Y-m-d H:i:s')]);

        $this->db->transComplete();
        return $this->db->transStatus();
    }

    /* =========================================================
     * STUDENT-LEVEL REPORTS
     * ========================================================= */

    /**
     * Percentage for a student over a date range (optionally per batch).
     */
    public function studentPercentage(int $tenantId, int $studentId, string $from, string $to, ?int $batchId = null): array
    {
        $b = $this->db->table('attendance_records ar')
            ->join('attendance_sessions s', 's.id = ar.session_id')
            ->where('ar.tenant_id', $tenantId)
            ->where('ar.student_id', $studentId)
            ->where('s.attendance_date >=', $from)
            ->where('s.attendance_date <=', $to);
        if ($batchId) $b->where('s.batch_id', $batchId);

        $rows = $b->select('ar.attendance_status, COUNT(*) AS c')
                  ->groupBy('ar.attendance_status')
                  ->get()->getResultArray();

        $counts = ['present' => 0, 'absent' => 0, 'late' => 0, 'half_day' => 0, 'on_leave' => 0];
        foreach ($rows as $r) $counts[$r['attendance_status']] = (int) $r['c'];
        $total = array_sum($counts);
        $presentWeighted = $counts['present'] + ($counts['late'] * 0.5) + ($counts['half_day'] * 0.5);

        return [
            'counts'    => $counts,
            'total'     => $total,
            'percentage'=> $total > 0 ? round(($presentWeighted / $total) * 100, 2) : 0,
        ];
    }

    public function batchPercentage(int $tenantId, int $batchId, string $from, string $to): array
    {
        $row = $this->db->table('attendance_records ar')
            ->select('COUNT(*) AS total,
                      SUM(CASE WHEN ar.attendance_status = "present" THEN 1 ELSE 0 END) AS present,
                      SUM(CASE WHEN ar.attendance_status = "absent"  THEN 1 ELSE 0 END) AS absent,
                      SUM(CASE WHEN ar.attendance_status = "late"    THEN 1 ELSE 0 END) AS late')
            ->join('attendance_sessions s', 's.id = ar.session_id')
            ->where('ar.tenant_id', $tenantId)
            ->where('s.batch_id', $batchId)
            ->where('s.attendance_date >=', $from)
            ->where('s.attendance_date <=', $to)
            ->get()->getRow();

        $total = (int) ($row->total ?? 0);
        return [
            'total'      => $total,
            'present'    => (int) ($row->present ?? 0),
            'absent'     => (int) ($row->absent ?? 0),
            'late'       => (int) ($row->late ?? 0),
            'percentage' => $total > 0 ? round((($row->present ?? 0) / $total) * 100, 2) : 0,
        ];
    }

    protected function uuid4(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }
}