<?php

namespace App\Modules\Admin\Models;

use CodeIgniter\Model;

/**
 * Handles fee_accounts, installments, payments, allocations, refunds.
 */
class FeeModel extends Model
{
    protected $table         = 'payments';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'public_id', 'tenant_id', 'student_id', 'receipt_number',
        'payment_date', 'amount', 'method', 'transaction_ref',
        'status', 'notes', 'received_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowCallbacks = true;
    protected $beforeInsert   = ['generatePublicId', 'generateReceipt'];
    protected function generatePublicId(array $data): array
    {
        if (empty($data['data']['public_id'])) {
            $data['data']['public_id'] = $this->uuid4();
        }
        return $data;
    }
    protected function generateReceipt(array $data): array
    {
        if (! empty($data['data']['receipt_number'])) return $data;
        $tenantId = (int) ($data['data']['tenant_id'] ?? 0);
        $year = date('Y');
        $prefix = "RCPT-{$year}-";
        $last = $this->db->table('payments')
            ->like('receipt_number', $prefix, 'after')
            ->where('tenant_id', $tenantId)
            ->orderBy('id', 'desc')->get()->getRow();
        $seq = $last ? ((int) substr($last->receipt_number, -4)) + 1 : 1;
        $data['data']['receipt_number'] = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
        return $data;
    }

    /* =========================================================
     * FEE ACCOUNTS
     * ========================================================= */

    public function getAccountForStudent(int $tenantId, int $studentId): ?array
    {
        return $this->db->table('student_fee_accounts')
            ->where(['tenant_id' => $tenantId, 'student_id' => $studentId, 'status' => 'active'])
            ->get()->getRowArray();
    }

    public function createFeeAccount(int $tenantId, int $studentId, ?int $feeStructureId, float $total, float $discount = 0): int
    {
        $this->db->table('student_fee_accounts')->insert([
            'public_id'        => $this->uuid4(),
            'tenant_id'        => $tenantId,
            'student_id'       => $studentId,
            'fee_structure_id' => $feeStructureId,
            'total_amount'     => $total,
            'discount_amount'  => $discount,
            'status'           => 'active',
        ]);
        return (int) $this->db->insertID();
    }

    /* =========================================================
     * INSTALLMENTS
     * ========================================================= */

    public function getInstallments(int $accountId): array
    {
        return $this->db->table('fee_installments')
            ->where('fee_account_id', $accountId)
            ->orderBy('due_date', 'asc')
            ->get()->getResultArray();
    }

    public function getPendingInstallments(int $accountId): array
    {
        return $this->db->table('fee_installments')
            ->where('fee_account_id', $accountId)
            ->where('status !=', 'paid')
            ->orderBy('due_date', 'asc')
            ->get()->getResultArray();
    }

    public function accountSummary(int $accountId): array
    {
        $acc = $this->db->table('student_fee_accounts')->where('id', $accountId)->get()->getRow();
        if (! $acc) return [];

        $total = (float) $acc->total_amount - (float) $acc->discount_amount;

        $collected = (float) ($this->db->table('payment_allocations pa')
            ->selectSum('pa.allocated_amount', 's')
            ->join('fee_installments fi', 'fi.id = pa.installment_id')
            ->where('fi.fee_account_id', $accountId)
            ->get()->getRow()->s ?? 0);

        $pending = $this->db->table('fee_installments')
            ->where('fee_account_id', $accountId)
            ->where('status !=', 'paid')
            ->countAllResults();

        $overdue = $this->db->table('fee_installments')
            ->where('fee_account_id', $accountId)
            ->where('status !=', 'paid')
            ->where('due_date <', date('Y-m-d'))
            ->countAllResults();

        return [
            'total'     => $total,
            'collected' => $collected,
            'balance'   => round($total - $collected, 2),
            'pending_installments' => $pending,
            'overdue_installments' => $overdue,
        ];
    }

    /* =========================================================
     * PAYMENTS
     * ========================================================= */

    /**
     * Record a payment + allocate to earliest pending installments.
     * Wrapped in a transaction for consistency.
     */
    public function recordPayment(int $tenantId, int $studentId, float $amount, string $method, string $date, int $receivedBy, ?string $notes = null): array
    {
        $this->db->transStart();

        $this->insert([
            'tenant_id'    => $tenantId,
            'student_id'   => $studentId,
            'payment_date' => $date,
            'amount'       => $amount,
            'method'       => $method,
            'status'       => 'success',
            'notes'        => $notes,
            'received_by'  => $receivedBy,
        ]);
        $paymentId = (int) $this->getInsertID();

        // Allocate
        $account = $this->getAccountForStudent($tenantId, $studentId);
        $remaining = $amount;
        if ($account) {
            $pending = $this->getPendingInstallments((int) $account['id']);
            foreach ($pending as $inst) {
                if ($remaining <= 0) break;
                $alloc = min($remaining, (float) $inst['amount']);
                $this->db->table('payment_allocations')->insert([
                    'tenant_id'        => $tenantId,
                    'payment_id'       => $paymentId,
                    'installment_id'   => (int) $inst['id'],
                    'allocated_amount' => $alloc,
                ]);
                $this->db->table('fee_installments')
                    ->where('id', (int) $inst['id'])
                    ->update(['status' => 'paid']);
                $remaining -= $alloc;
            }
        }

        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            return ['ok' => false, 'reason' => 'Payment failed'];
        }

        return [
            'ok'       => true,
            'payment'  => $this->find($paymentId),
            'unallocated' => max(0, $remaining),
        ];
    }

    public function studentPayments(int $tenantId, int $studentId, int $limit = 50): array
    {
        return $this->where('tenant_id', $tenantId)
                    ->where('student_id', $studentId)
                    ->orderBy('id', 'desc')
                    ->limit($limit)
                    ->findAll();
    }

    public function recentPayments(int $tenantId, int $limit = 20): array
    {
        return $this->db->table('payments p')
            ->select('p.*, s.name AS student_name, s.student_code')
            ->join('students s', 's.id = p.student_id')
            ->where('p.tenant_id', $tenantId)
            ->orderBy('p.id', 'desc')
            ->limit($limit)
            ->get()->getResultArray();
    }

    /* =========================================================
     * STATS / REPORTS
     * ========================================================= */

    public function collectedToday(int $tenantId): float
    {
        return (float) ($this->where('tenant_id', $tenantId)
                            ->where('payment_date', date('Y-m-d'))
                            ->where('status', 'success')
                            ->selectSum('amount', 's')
                            ->first()['s'] ?? 0);
    }

    public function collectedBetween(int $tenantId, string $from, string $to): float
    {
        return (float) ($this->where('tenant_id', $tenantId)
                            ->where('payment_date >=', $from)
                            ->where('payment_date <=', $to)
                            ->where('status', 'success')
                            ->selectSum('amount', 's')
                            ->first()['s'] ?? 0);
    }

    public function defaulters(int $tenantId, int $limit = 50): array
    {
        return $this->db->table('students s')
            ->select('s.id, s.public_id, s.student_code, s.name, s.phone,
                      COUNT(fi.id) AS overdue_count,
                      SUM(fi.amount) AS overdue_amount')
            ->join('student_fee_accounts fa', 'fa.student_id = s.id')
            ->join('fee_installments fi', 'fi.fee_account_id = fa.id')
            ->where('s.tenant_id', $tenantId)
            ->where('s.deleted_at IS NULL')
            ->where('fi.status !=', 'paid')
            ->where('fi.due_date <', date('Y-m-d'))
            ->groupBy('s.id')
            ->orderBy('overdue_amount', 'desc')
            ->limit($limit)
            ->get()->getResultArray();
    }

    /* =========================================================
     * REFUNDS
     * ========================================================= */

    public function recordRefund(int $tenantId, int $paymentId, float $amount, string $reason, ?int $processedBy = null): array
    {
        $payment = $this->where(['tenant_id' => $tenantId, 'id' => $paymentId])->first();
        if (! $payment) return ['ok' => false, 'reason' => 'Payment not found'];
        if ($amount > (float) $payment['amount']) return ['ok' => false, 'reason' => 'Refund exceeds payment'];

        $this->db->table('payment_refunds')->insert([
            'public_id'    => $this->uuid4(),
            'tenant_id'    => $tenantId,
            'payment_id'   => $paymentId,
            'amount'       => $amount,
            'reason'       => $reason,
            'status'       => 'pending',
            'processed_by' => $processedBy,
        ]);
        return ['ok' => true, 'refund_id' => $this->db->insertID()];
    }

    protected function uuid4(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }
}