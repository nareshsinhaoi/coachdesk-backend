<?php

namespace App\Modules\Api\Controllers\V1;

use App\Controllers\BaseController;
use App\Modules\Api\Libraries\TenantContext;

/**
 * REST endpoints for Attendance, Fees, Exams and Reports.
 * Mirrors the business logic already used by the Admin module.
 */
class OperationsController extends BaseController
{
    protected function pag(int $page, int $limit, int $total): array
    {
        return [
            'page'       => $page,
            'limit'      => $limit,
            'total'      => $total,
            'totalPages' => (int) ceil($total / max(1, $limit)),
        ];
    }

    private function tenant(): ?int
    {
        return TenantContext::getTenantId();
    }

    private function branch(): ?int
    {
        return TenantContext::getBranchId();
    }

    /* ================= ATTENDANCE ================= */

    /** GET /api/v1/attendance?date=YYYY-MM-DD — sessions for a day */
    public function attendanceIndex()
    {
        $tenantId = $this->tenant();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $date = $this->request->getGet('date') ?: date('Y-m-d');

        $b = db_connect()->table('cd_attendance_sessions')
            ->select("cd_attendance_sessions.id, cd_attendance_sessions.public_id, cd_attendance_sessions.attendance_date,
                      cd_attendance_sessions.status, cd_batches.batch_name, cd_batches.batch_code,
                      (SELECT COUNT(*) FROM cd_attendance_records WHERE session_id = cd_attendance_sessions.id AND attendance_status = 'present') AS present_count,
                      (SELECT COUNT(*) FROM cd_attendance_records WHERE session_id = cd_attendance_sessions.id AND attendance_status = 'absent') AS absent_count,
                      (SELECT COUNT(*) FROM cd_attendance_records WHERE session_id = cd_attendance_sessions.id AND attendance_status = 'late') AS late_count,
                      (SELECT COUNT(*) FROM cd_attendance_records WHERE session_id = cd_attendance_sessions.id) AS total_marked")
            ->join('cd_batches', 'cd_batches.id = cd_attendance_sessions.batch_id')
            ->where('cd_attendance_sessions.tenant_id', $tenantId)
            ->where('cd_attendance_sessions.attendance_date', $date);

        if ($this->branch()) {
            $b->where('cd_attendance_sessions.branch_id', $this->branch());
        }

        $sessions = $b->orderBy('cd_batches.batch_name', 'ASC')->get()->getResultArray();

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Attendance sessions retrieved',
            'data'    => ['date' => $date, 'sessions' => $sessions],
        ]);
    }

    /** GET /api/v1/attendance/mark?batch_id=1&date=YYYY-MM-DD — roster for marking */
    public function attendanceMark()
    {
        $tenantId = $this->tenant();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $batchId = (int) ($this->request->getGet('batch_id') ?? 0);
        $date    = $this->request->getGet('date') ?: date('Y-m-d');

        $batch = db_connect()->table('cd_batches')
            ->where(['id' => $batchId, 'tenant_id' => $tenantId])
            ->get()->getRowArray();
        if (! $batch) {
            return $this->response->setStatusCode(404)
                ->setJSON(['status' => false, 'message' => 'Batch not found']);
        }

        $db       = db_connect();
        $session  = $db->table('cd_attendance_sessions')
            ->where(['batch_id' => $batchId, 'attendance_date' => $date])
            ->get()->getRowArray();

        $students = $db->table('cd_batch_students bs')
            ->select('s.id, s.public_id, s.student_code, s.name')
            ->join('cd_students s', 's.id = bs.student_id')
            ->where(['bs.batch_id' => $batchId, 'bs.status' => 'active'])
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        $existing = [];
        if ($session) {
            $rows = $db->table('cd_attendance_records')
                ->where('session_id', $session['id'])->get()->getResultArray();
            foreach ($rows as $r) {
                $existing[$r['student_id']] = $r['attendance_status'];
            }
        }

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Attendance roster retrieved',
            'data'    => [
                'date'     => $date,
                'session'  => $session ? ['public_id' => $session['public_id'], 'status' => $session['status']] : null,
                'batch'    => ['public_id' => $batch['public_id'], 'batch_name' => $batch['batch_name']],
                'students' => $students,
                'marked'   => $existing,
            ],
        ]);
    }

    /** POST /api/v1/attendance/save — { batch_id, attendance_date, status: {student_id: status} } */
    public function attendanceSave()
    {
        $tenantId = $this->tenant();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $body      = $this->request->getJSON(true) ?? [];
        $batchId   = (int) ($body['batch_id'] ?? 0);
        $date      = $body['attendance_date'] ?? date('Y-m-d');
        $statuses  = $body['status'] ?? [];

        if (! $batchId || ! is_array($statuses) || ! $statuses) {
            return $this->response->setStatusCode(422)
                ->setJSON(['status' => false, 'message' => 'batch_id and status map required']);
        }

        $db = db_connect();
        $batch = $db->table('cd_batches')
            ->where(['id' => $batchId, 'tenant_id' => $tenantId])->get()->getRow();
        if (! $batch) {
            return $this->response->setStatusCode(404)
                ->setJSON(['status' => false, 'message' => 'Batch not found']);
        }

        $allowed = ['present', 'absent', 'late', 'half_day', 'on_leave'];

        $db->transStart();

        $session = $db->table('cd_attendance_sessions')
            ->where(['batch_id' => $batchId, 'attendance_date' => $date])
            ->get()->getRowArray();

        if (! $session) {
            $db->table('cd_attendance_sessions')->insert([
                'public_id'       => uuid4(),
                'tenant_id'       => $tenantId,
                'branch_id'       => $this->branch() ?? 0,
                'batch_id'        => $batchId,
                'attendance_date' => $date,
                'status'          => 'submitted',
                'submitted_at'    => date('Y-m-d H:i:s'),
            ]);
            $sessionId = $db->insertID();
        } else {
            $sessionId = $session['id'];
            $db->table('cd_attendance_sessions')
                ->where('id', $sessionId)
                ->update(['submitted_at' => date('Y-m-d H:i:s')]);
        }

        $saved = 0;
        foreach ($statuses as $studentId => $status) {
            if (! in_array($status, $allowed, true)) {
                continue;
            }
            $studentId = (int) $studentId;
            $exists = $db->table('cd_attendance_records')
                ->where(['session_id' => $sessionId, 'student_id' => $studentId])->get()->getRow();

            if ($exists) {
                $db->table('cd_attendance_records')->where('id', $exists->id)
                    ->update(['attendance_status' => $status]);
            } else {
                $db->table('cd_attendance_records')->insert([
                    'tenant_id'         => $tenantId,
                    'session_id'        => $sessionId,
                    'student_id'        => $studentId,
                    'attendance_status' => $status,
                ]);
            }
            $saved++;
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)
                ->setJSON(['status' => false, 'message' => 'Failed to save attendance']);
        }

        return $this->response->setJSON([
            'status' => true, 'message' => 'Attendance saved', 'data' => ['saved' => $saved],
        ]);
    }

    /* ================= FEES ================= */

    /** GET /api/v1/fees — student-wise fee summary + recent payments */
    public function feesIndex()
    {
        $tenantId = $this->tenant();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $db   = db_connect();
        $page  = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit = min(100, max(1, (int) ($this->request->getGet('perPage') ?? $this->request->getGet('limit') ?? 50)));
        $search = trim((string) $this->request->getGet('search'));

        $b = $db->table('cd_students')
            ->select('cd_students.id, cd_students.public_id, cd_students.student_code, cd_students.name,
                      COALESCE(SUM(cd_fee_installments.amount), 0) AS total_fees,
                      COALESCE(SUM(CASE WHEN cd_fee_installments.status IN ("paid","partial") THEN cd_fee_installments.amount ELSE 0 END), 0) AS paid_estimate')
            ->join('cd_student_fee_accounts', 'cd_student_fee_accounts.student_id = cd_students.id', 'left')
            ->join('cd_fee_installments', 'cd_fee_installments.fee_account_id = cd_student_fee_accounts.id', 'left')
            ->where('cd_students.tenant_id', $tenantId)
            ->where('cd_students.deleted_at IS NULL')
            ->groupBy('cd_students.id');

        if ($search !== '') {
            $b->groupStart()
                ->like('cd_students.name', $search)
                ->orLike('cd_students.student_code', $search)
                ->groupEnd();
        }

        $countBuilder = clone $b;
        $total = (int) $countBuilder->countAllResults(false);
        $students = $b->orderBy('cd_students.name', 'ASC')
            ->limit($limit, ($page - 1) * $limit)->get()->getResultArray();

        // Exact paid amounts come from allocations (authoritative)
        foreach ($students as &$s) {
            $paidRow = $db->table('cd_payment_allocations pa')
                ->selectSum('pa.allocated_amount', 'paid')
                ->join('cd_fee_installments fi', 'fi.id = pa.installment_id')
                ->join('cd_student_fee_accounts fa', 'fa.id = fi.fee_account_id')
                ->where('fa.student_id', $s['id'])
                ->get()->getRow();
            $paid = $paidRow && $paidRow->paid !== null ? (float) $paidRow->paid : 0.0;
            $s['paid_amount']   = $paid;
            $s['total_fees']    = (float) $s['total_fees'];
            $s['due_amount']    = max(0.0, (float) $s['total_fees'] - $paid);
        }
        unset($s);

        $payments = $db->table('cd_payments p')
            ->select('p.public_id, p.receipt_number, p.payment_date, p.amount, p.method, p.status, p.notes,
                      s.name AS student_name, s.student_code')
            ->join('cd_students s', 's.id = p.student_id')
            ->where('p.tenant_id', $tenantId)
            ->orderBy('p.id', 'desc')
            ->limit(20)->get()->getResultArray();

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Fees retrieved',
            'data'    => ['students' => $students, 'payments' => $payments],
            'pagination' => $this->pag($page, $limit, $total),
        ]);
    }

    /** POST /api/v1/fees/collect — { student_id, amount, method, payment_date?, notes? } */
    public function feesCollect()
    {
        $tenantId = $this->tenant();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $body   = $this->request->getJSON(true) ?? [];
        $userId = $this->request->user->id ?? null;

        $studentId = (int) ($body['student_id'] ?? 0);
        $amount    = (float) ($body['amount'] ?? 0);
        $method    = $body['method'] ?? '';
        $date      = $body['payment_date'] ?: date('Y-m-d');
        $date      = $date ?: date('Y-m-d');
        $notes     = $body['notes'] ?? null;

        $allowedMethods = ['cash', 'upi', 'card', 'bank_transfer', 'cheque', 'online'];
        if (! $studentId || $amount <= 0 || ! in_array($method, $allowedMethods, true)) {
            return $this->response->setStatusCode(422)
                ->setJSON(['status' => false, 'message' => 'student_id, positive amount and valid method required']);
        }

        $db = db_connect();
        $student = $db->table('cd_students')
            ->where(['tenant_id' => $tenantId, 'id' => $studentId])
            ->where('deleted_at IS NULL')->get()->getRow();
        if (! $student) {
            return $this->response->setStatusCode(404)
                ->setJSON(['status' => false, 'message' => 'Student not found']);
        }

        // Tenant-scoped sequential receipt number
        $prefix = 'RCPT-' . date('Y') . '-';
        $last   = $db->table('cd_payments')
            ->like('receipt_number', $prefix, 'after')
            ->where('tenant_id', $tenantId)
            ->orderBy('id', 'desc')->get()->getRow();
        $seq       = $last ? ((int) substr($last->receipt_number, -4)) + 1 : 1;
        $receiptNo = $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

        $db->transBegin();
        try {
            $db->table('cd_payments')->insert([
                'public_id'      => uuid4(),
                'tenant_id'      => $tenantId,
                'student_id'     => $studentId,
                'receipt_number' => $receiptNo,
                'payment_date'   => $date,
                'amount'         => $amount,
                'method'         => $method,
                'status'         => 'success',
                'notes'          => $notes,
                'received_by'    => $userId,
            ]);
            $paymentId = $db->insertID();

            // Allocate to earliest pending installments
            $remaining = $amount;
            $pending = $db->table('cd_fee_installments fi')
                ->join('cd_student_fee_accounts fa', 'fa.id = fi.fee_account_id')
                ->where('fa.student_id', $studentId)
                ->where('fa.tenant_id', $tenantId)
                ->where('fi.status !=', 'paid')
                ->orderBy('fi.due_date', 'asc')
                ->get()->getResultArray();

            foreach ($pending as $inst) {
                if ($remaining <= 0) break;

                $alreadyRow = $db->table('cd_payment_allocations')
                    ->selectSum('allocated_amount', 'total')
                    ->where('installment_id', (int) $inst['id'])->get()->getRow();
                $already = $alreadyRow && $alreadyRow->total !== null ? (float) $alreadyRow->total : 0.0;

                $instRemaining = (float) $inst['amount'] - $already;
                if ($instRemaining <= 0) continue;

                $alloc = min($remaining, $instRemaining);

                $db->table('cd_payment_allocations')->insert([
                    'tenant_id'        => $tenantId,
                    'payment_id'       => $paymentId,
                    'installment_id'   => (int) $inst['id'],
                    'allocated_amount' => $alloc,
                ]);

                $newAllocated = $already + $alloc;
                $db->table('cd_fee_installments')->where('id', (int) $inst['id'])
                    ->update(['status' => $newAllocated >= (float) $inst['amount'] ? 'paid' : 'partial']);

                $remaining -= $alloc;
            }

            $db->table('cd_activity_logs')->insert([
                'tenant_id'   => $tenantId,
                'user_id'     => $userId,
                'action'      => 'payment.collected',
                'module'      => 'fees',
                'record_type' => 'payment',
                'record_id'   => $paymentId,
                'metadata'    => json_encode(['receipt' => $receiptNo, 'amount' => $amount, 'method' => $method]),
            ]);

            if ($db->transStatus() === false) throw new \RuntimeException('Transaction failed');
            $db->transCommit();

            $payment = $db->table('cd_payments')->where('id', $paymentId)->get()->getRowArray();

            return $this->response->setStatusCode(201)->setJSON([
                'status'  => true,
                'message' => 'Payment recorded',
                'data'    => [
                    'public_id'      => $payment['public_id'],
                    'receipt_number' => $payment['receipt_number'],
                    'amount'         => (float) $payment['amount'],
                    'method'         => $payment['method'],
                    'payment_date'   => $payment['payment_date'],
                ],
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'API fee collection failed: {m}', ['m' => $e->getMessage()]);
            return $this->response->setStatusCode(500)
                ->setJSON(['status' => false, 'message' => 'Unable to record payment']);
        }
    }

    /* ================= EXAMS ================= */

    /** GET /api/v1/exams */
    public function examsIndex()
    {
        $tenantId = $this->tenant();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $b = db_connect()->table('cd_exams')
            ->select('cd_exams.id, cd_exams.public_id, cd_exams.exam_name, cd_exams.exam_date, cd_exams.status,
                      cd_exams.batch_id, cd_batches.batch_name,
                      (SELECT COUNT(*) FROM cd_exam_results WHERE cd_exam_results.exam_id = cd_exams.id) AS result_count')
            ->join('cd_batches', 'cd_batches.id = cd_exams.batch_id')
            ->where('cd_exams.tenant_id', $tenantId)
            ->where('cd_exams.deleted_at IS NULL');

        if ($this->branch()) {
            $b->where('cd_exams.branch_id', $this->branch());
        }

        $exams = $b->orderBy('cd_exams.exam_date', 'DESC')->get()->getResultArray();

        $batches = db_connect()->table('cd_batches')
            ->select('id, public_id, batch_name')
            ->where('tenant_id', $tenantId)->where('status', 'running')
            ->orderBy('batch_name', 'ASC')->get()->getResultArray();

        return $this->response->setJSON([
            'status' => true, 'message' => 'Exams retrieved',
            'data'   => ['exams' => $exams, 'batches' => $batches],
        ]);
    }

    /** POST /api/v1/exams — { batch_id, exam_name, exam_date? } */
    public function examsCreate()
    {
        $tenantId = $this->tenant();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $body = $this->request->getJSON(true) ?? [];
        $batchId  = (int) ($body['batch_id'] ?? 0);
        $examName = trim($body['exam_name'] ?? '');

        if (! $batchId || $examName === '') {
            return $this->response->setStatusCode(422)
                ->setJSON(['status' => false, 'message' => 'batch_id and exam_name required']);
        }

        $db = db_connect();
        $batch = $db->table('cd_batches')
            ->where(['id' => $batchId, 'tenant_id' => $tenantId])->get()->getRow();
        if (! $batch) {
            return $this->response->setStatusCode(404)
                ->setJSON(['status' => false, 'message' => 'Batch not found']);
        }

        $db->table('cd_exams')->insert([
            'public_id'  => uuid4(),
            'tenant_id'  => $tenantId,
            'branch_id'  => $this->branch() ?? 0,
            'batch_id'   => $batchId,
            'exam_name'  => $examName,
            'exam_date'  => $body['exam_date'] ?? date('Y-m-d'),
            'status'     => 'scheduled',
        ]);
        $id = $db->insertID();
        $exam = $db->table('cd_exams')->where('id', $id)->get()->getRowArray();

        return $this->response->setStatusCode(201)
            ->setJSON(['status' => true, 'message' => 'Exam scheduled', 'data' => $exam]);
    }

    /** GET /api/v1/exams/(:segment)/marks — roster + existing marks */
    public function examMarks($examPublicId)
    {
        $tenantId = $this->tenant();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $db   = db_connect();
        $exam = $db->table('cd_exams')
            ->select('cd_exams.*, cd_batches.batch_name')
            ->join('cd_batches', 'cd_batches.id = cd_exams.batch_id')
            ->where(['cd_exams.public_id' => $examPublicId, 'cd_exams.tenant_id' => $tenantId])
            ->get()->getRowArray();
        if (! $exam) {
            return $this->response->setStatusCode(404)
                ->setJSON(['status' => false, 'message' => 'Exam not found']);
        }

        $students = $db->table('cd_batch_students bs')
            ->select('s.id, s.public_id, s.student_code, s.name')
            ->join('cd_students s', 's.id = bs.student_id')
            ->where(['bs.batch_id' => $exam['batch_id'], 'bs.status' => 'active'])
            ->orderBy('s.name', 'ASC')->get()->getResultArray();

        $rows = $db->table('cd_exam_results')
            ->where('exam_id', $exam['id'])->get()->getResultArray();
        $results = [];
        foreach ($rows as $r) {
            $results[$r['student_id']] = [
                'marks_obtained' => $r['marks_obtained'],
                'total_marks'    => $r['total_marks'],
                'grade'          => $r['grade'] ?? null,
                'remarks'        => $r['remarks'] ?? null,
            ];
        }

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Exam marks retrieved',
            'data'    => [
                'exam' => [
                    'public_id' => $exam['public_id'], 'exam_name' => $exam['exam_name'],
                    'exam_date' => $exam['exam_date'], 'batch_name' => $exam['batch_name'],
                ],
                'students' => $students,
                'results'  => $results,
            ],
        ]);
    }

    /** POST /api/v1/exams/(:segment)/marks — { marks: { student_id: {marks_obtained, total_marks, grade?} } } */
    public function examMarksSave($examPublicId)
    {
        $tenantId = $this->tenant();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $body = $this->request->getJSON(true) ?? [];
        $marks = $body['marks'] ?? [];
        if (! is_array($marks) || ! $marks) {
            return $this->response->setStatusCode(422)
                ->setJSON(['status' => false, 'message' => 'marks map required']);
        }

        $db   = db_connect();
        $exam = $db->table('cd_exams')
            ->where(['public_id' => $examPublicId, 'tenant_id' => $tenantId])->get()->getRow();
        if (! $exam) {
            return $this->response->setStatusCode(404)
                ->setJSON(['status' => false, 'message' => 'Exam not found']);
        }

        $db->transStart();
        $saved = 0;
        foreach ($marks as $studentId => $m) {
            if (! isset($m['marks_obtained'])) continue;
            $studentId = (int) $studentId;

            $existing = $db->table('cd_exam_results')
                ->where(['exam_id' => $exam->id, 'student_id' => $studentId])->get()->getRow();

            $payload = [
                'marks_obtained' => (float) $m['marks_obtained'],
                'total_marks'    => isset($m['total_marks']) ? (float) $m['total_marks'] : ($existing->total_marks ?? 100),
            ];
            if (isset($m['grade']))    $payload['grade'] = $m['grade'];
            if (isset($m['remarks']))  $payload['remarks'] = $m['remarks'];

            if ($existing) {
                $db->table('cd_exam_results')->where('id', $existing->id)->update($payload);
            } else {
                $db->table('cd_exam_results')->insert(array_merge($payload, [
                    'tenant_id'  => $tenantId,
                    'exam_id'    => $exam->id,
                    'student_id' => $studentId,
                ]));
            }
            $saved++;
        }
        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)
                ->setJSON(['status' => false, 'message' => 'Failed to save marks']);
        }

        return $this->response->setJSON([
            'status' => true, 'message' => 'Marks saved', 'data' => ['saved' => $saved],
        ]);
    }

    /* ================= REPORTS ================= */

    /** GET /api/v1/reports — fee trend + attendance by batch + exam stats */
    public function reportsIndex()
    {
        $tenantId = $this->tenant();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $db = db_connect();

        $feeMonthly = $db->query(
            "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS ym, SUM(amount) AS total
             FROM cd_payments
             WHERE tenant_id = ? AND status = 'success'
             GROUP BY ym ORDER BY ym DESC LIMIT 12", [$tenantId]
        )->getResultArray();

        $attendanceByBatch = $db->query(
            "SELECT b.batch_name,
                    SUM(CASE WHEN ar.attendance_status = 'present' THEN 1 ELSE 0 END) AS present,
                    SUM(CASE WHEN ar.attendance_status = 'absent'  THEN 1 ELSE 0 END) AS absent,
                    SUM(CASE WHEN ar.attendance_status = 'late'    THEN 1 ELSE 0 END) AS late,
                    COUNT(*) AS total
             FROM cd_attendance_records ar
             JOIN cd_attendance_sessions s ON s.id = ar.session_id
             JOIN cd_batches b ON b.id = s.batch_id
             WHERE ar.tenant_id = ?
             GROUP BY b.id", [$tenantId]
        )->getResultArray();

        $examStats = $db->query(
            "SELECT b.batch_name, COUNT(DISTINCT er.student_id) AS students_graded,
                    ROUND(AVG(er.marks_obtained / NULLIF(er.total_marks, 0)) * 100, 1) AS avg_percentage
             FROM cd_exam_results er
             JOIN cd_exams e ON e.id = er.exam_id
             JOIN cd_batches b ON b.id = e.batch_id
             WHERE er.tenant_id = ?
             GROUP BY b.id", [$tenantId]
        )->getResultArray();

        return $this->response->setJSON([
            'status' => true, 'message' => 'Reports retrieved',
            'data'   => [
                'fee_monthly'        => $feeMonthly,
                'attendance_by_batch'=> $attendanceByBatch,
                'exam_stats'         => $examStats,
            ],
        ]);
    }
}
