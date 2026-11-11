<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;

require_once __DIR__ . '/../Helpers/admin_helper.php';

class FeeController extends BaseController
{
    /* ============================================================
     * INDEX — Fee summary + recent payments
     * ============================================================ */
    public function index()
    {
        $db     = db_connect();
        $search = $this->request->getGet('search');

        $q = $db->table('cd_students')
            ->select('cd_students.id, cd_students.public_id, cd_students.student_code, cd_students.name,
                      COALESCE(SUM(cd_fee_installments.amount), 0) AS total_fees,
                      COALESCE((
                          SELECT SUM(cd_payment_allocations.allocated_amount)
                          FROM cd_payment_allocations
                          JOIN cd_fee_installments
                            ON cd_fee_installments.id = cd_payment_allocations.installment_id
                          WHERE cd_fee_installments.fee_account_id = cd_student_fee_accounts.id
                      ), 0) AS paid_amount')
            ->join('cd_student_fee_accounts', 'cd_student_fee_accounts.student_id = cd_students.id', 'left')
            ->join('cd_fee_installments', 'cd_fee_installments.fee_account_id = cd_student_fee_accounts.id', 'left')
            ->where('cd_students.tenant_id', tenant_id())
            ->where('cd_students.deleted_at IS NULL')
            ->groupBy('cd_students.id');

        if ($search) {
            $q->like('cd_students.name', $search);
        }

        $students = $q->get()->getResultArray();

        $payments = $db->table('cd_payments p')
            ->select('p.*, s.name AS student_name, s.student_code')
            ->join('cd_students s', 's.id = p.student_id')
            ->where('p.tenant_id', tenant_id())
            ->orderBy('p.id', 'desc')
            ->limit(20)->get()->getResultArray();

        return view('App\Modules\Admin\Views\fees\index', [
            'title'    => 'Fees & Payments',
            'students' => $students,
            'payments' => $payments,
        ]);
    }

    /* ============================================================
     * COLLECT — Show form
     * ============================================================ */
    public function collect()
    {
        $db       = db_connect();
        $students = $db->table('cd_students')
            ->where('tenant_id', tenant_id())
            ->where('status', 'active')
            ->where('deleted_at IS NULL')
            ->orderBy('name')
            ->get()->getResultArray();

        return view('App\Modules\Admin\Views\fees\collect', [
            'title'    => 'Collect Payment',
            'students' => $students,
        ]);
    }

    /* ============================================================
     * STORE — Record a payment + allocate to installments
     * Redirects to /admin/fees/{publicId}/success
     * ============================================================ */
    public function store()
    {
        $db        = db_connect();
        $studentId = (int) $this->request->getPost('student_id');
        $amount    = (float) $this->request->getPost('amount');
        $method    = $this->request->getPost('method');
        $date      = $this->request->getPost('payment_date');
        $date      = $date ? $date : date('Y-m-d');
        $notes     = $this->request->getPost('notes');

        // ---- Validation ----
        if (! $studentId || $amount <= 0) {
            return redirect()->back()->withInput()->with('error', 'Student and amount are required.');
        }

        $allowedMethods = ['cash', 'upi', 'card', 'bank_transfer', 'cheque', 'online'];
        if (! in_array($method, $allowedMethods, true)) {
            return redirect()->back()->withInput()->with('error', 'Invalid payment method.');
        }

        $student = $db->table('cd_students')
            ->where(['tenant_id' => tenant_id(), 'id' => $studentId])
            ->where('deleted_at IS NULL')
            ->get()->getRow();

        if (! $student) {
            return redirect()->back()->withInput()->with('error', 'Invalid student.');
        }

        // ---- Generate receipt number (tenant-scoped) ----
        $prefix = 'RCPT-' . date('Y') . '-';
        $last   = $db->table('cd_payments')
            ->like('receipt_number', $prefix, 'after')
            ->where('tenant_id', tenant_id())
            ->orderBy('id', 'desc')
            ->get()->getRow();

        $seq       = $last ? ((int) substr($last->receipt_number, -4)) + 1 : 1;
        $receiptNo = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

        // ---- Transaction: insert payment + allocate ----
        $db->transBegin();

        try {
            $db->table('cd_payments')->insert([
                'public_id'      => uuid4(),
                'tenant_id'      => tenant_id(),
                'student_id'     => $studentId,
                'receipt_number' => $receiptNo,
                'payment_date'   => $date,
                'amount'         => $amount,
                'method'         => $method,
                'status'         => 'success',
                'notes'          => $notes,
                'received_by'    => session()->get('user_id'),
            ]);
            $paymentId = $db->insertID();

            // Allocate to earliest pending installments
            $remaining = $amount;
            $pending   = $db->table('cd_fee_installments fi')
                ->join('cd_student_fee_accounts fa', 'fa.id = fi.fee_account_id')
                ->where('fa.student_id', $studentId)
                ->where('fa.tenant_id', tenant_id())
                ->where('fi.status !=', 'paid')
                ->orderBy('fi.due_date', 'asc')
                ->get()->getResultArray();

            foreach ($pending as $inst) {
                if ($remaining <= 0) {
                    break;
                }

                // How much of this installment was already allocated previously
                $alreadyRow = $db->table('cd_payment_allocations')
                    ->selectSum('allocated_amount', 'total')
                    ->where('installment_id', (int) $inst['id'])
                    ->get()->getRow();
                $alreadyAllocated = $alreadyRow && $alreadyRow->total !== null
                    ? (float) $alreadyRow->total
                    : 0.0;

                $installmentRemaining = (float) $inst['amount'] - $alreadyAllocated;
                if ($installmentRemaining <= 0) {
                    continue;
                }

                $alloc = min($remaining, $installmentRemaining);

                $db->table('cd_payment_allocations')->insert([
                    'tenant_id'        => tenant_id(),
                    'payment_id'       => $paymentId,
                    'installment_id'   => (int) $inst['id'],
                    'allocated_amount' => $alloc,
                ]);

                // Mark as paid only when fully covered; otherwise partial
                $newAllocated = $alreadyAllocated + $alloc;
                if ($newAllocated >= (float) $inst['amount']) {
                    $db->table('cd_fee_installments')
                        ->where('id', (int) $inst['id'])
                        ->update(['status' => 'paid']);
                } else {
                    $db->table('cd_fee_installments')
                        ->where('id', (int) $inst['id'])
                        ->update(['status' => 'partial']);
                }

                $remaining -= $alloc;
            }

            // Audit log
            $db->table('cd_activity_logs')->insert([
                'tenant_id'   => tenant_id(),
                'user_id'     => session()->get('user_id'),
                'action'      => 'payment.collected',
                'module'      => 'fees',
                'record_type' => 'payment',
                'record_id'   => $paymentId,
                'metadata'    => json_encode([
                    'receipt' => $receiptNo,
                    'amount'  => $amount,
                    'method'  => $method,
                ]),
                'ip_address'  => $this->request->getIPAddress(),
            ]);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed');
            }
            $db->transCommit();

            // Redirect to success page with receipt actions
            $payment = $db->table('cd_payments')->where('id', $paymentId)->get()->getRow();

            return redirect()->to('/admin/fees/' . $payment->public_id . '/success');

        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Fee collection failed: ' . $e->getMessage());

            return redirect()->back()->withInput()
                ->with('error', 'Unable to record payment. Please try again.');
        }
    }

    /* ============================================================
     * SUCCESS — Post-collection page with PDF download + next steps
     * ============================================================ */
    public function success($paymentPublicId)
    {
        $db = db_connect();

        $payment = $db->table('cd_payments p')
            ->select('p.*, s.name AS student_name, s.student_code, s.phone AS student_phone, s.email AS student_email')
            ->join('cd_students s', 's.id = p.student_id')
            ->where('p.public_id', $paymentPublicId)
            ->where('p.tenant_id', tenant_id())
            ->get()->getRowArray();

        if (! $payment) {
            return redirect()->to('/admin/fees')->with('error', 'Payment not found.');
        }

        return view('App\Modules\Admin\Views\fees\success', [
            'title'   => 'Payment Recorded',
            'payment' => $payment,
        ]);
    }

    /* ============================================================
     * RECEIPT — Download branded PDF
     * ============================================================ */
    public function receipt($paymentPublicId)
    {
        $data = $this->buildReceiptData($paymentPublicId);

        if (is_string($data)) {
            return redirect()->to('/admin/fees')->with('error', $data);
        }

        $html     = view('App\Modules\Admin\Views\fees\receipt_pdf', $data);
        $filename = 'Receipt-' . $data['payment']['receipt_number'];

        (new \App\Modules\Admin\Libraries\PdfService())->render($html, $filename, 'download');
    }

    /* ============================================================
     * RECEIPT INLINE — Preview in browser (print-friendly)
     * ============================================================ */
    public function receiptInline($paymentPublicId)
    {
        $data = $this->buildReceiptData($paymentPublicId);
        if (is_string($data)) {
            return redirect()->to('/admin/fees')->with('error', $data);
        }

        $html     = view('App\Modules\Admin\Views\fees\receipt_pdf', $data);
        $filename = 'Receipt-' . $data['payment']['receipt_number'];

        (new \App\Modules\Admin\Libraries\PdfService())->render($html, $filename, 'inline');
    }

    /* ============================================================
     * PRIVATE — Shared data builder for receipt views
     * ============================================================ */
    /**
     * @param  string       $paymentPublicId
     * @return array|string  Array of view data on success, string error message on failure.
     */
    protected function buildReceiptData($paymentPublicId)
    { 
        $db = db_connect();

        $payment = $db->table('cd_payments')
            ->select('cd_payments.*,
                      cd_students.name AS student_name,
                      cd_students.student_code,
                      cd_students.phone AS student_phone,
                      cd_students.email AS student_email,
                      cd_students.address_line1,
                      cd_students.city,
                      cd_students.state,
                      cd_students.pincode')
            ->join('cd_students', 'cd_students.id = cd_payments.student_id')
            ->where('cd_payments.public_id', $paymentPublicId)
            ->where('cd_payments.tenant_id', tenant_id())
            ->get()->getRowArray();// echo $db->getLastQuery(); die('@@@');

        if (! $payment) {
            return 'Payment not found.';
        }

        // Tenant info
        $tenant = $db->table('cd_tenants')->where('id', tenant_id())->get()->getRowArray();

        // Tenant branding
        $brandingRow = $db->table('cd_settings')
            ->where(['tenant_id' => tenant_id(), 'setting_key' => 'branding'])
            ->get()->getRow();

        $branding = [];
        if ($brandingRow) {
            $decoded = json_decode($brandingRow->setting_value, true);
            if (is_array($decoded)) {
                $branding = $decoded;
            }
        }

        // Allocation breakdown (what this payment covered)
        $allocations = $db->table('cd_payment_allocations pa')
            ->select('pa.allocated_amount, fi.installment_name, fi.due_date')
            ->join('cd_fee_installments fi', 'fi.id = pa.installment_id')
            ->where('pa.payment_id', $payment['id'])
            ->orderBy('fi.due_date', 'asc')
            ->get()->getResultArray();

        // Receiver name
        $receivedBy = null;
        if (! empty($payment['received_by'])) {
            $receivedBy = $db->table('cd_users')
                ->where('id', $payment['received_by'])
                ->get()->getRow();
        }

        // Total paid to date (for footer summary)
        $totalRow = $db->table('cd_payments')
            ->selectSum('amount', 'total')
            ->where(['tenant_id' => tenant_id(), 'student_id' => $payment['student_id']])
            ->where('status', 'success')
            ->get()->getRow();
        $totalPaid = $totalRow && $totalRow->total !== null ? (float) $totalRow->total : 0.0;

        return [
            'payment'     => $payment,
            'tenant'      => $tenant,
            'branding'    => $branding,
            'allocations' => $allocations,
            'receivedBy'  => $receivedBy,
            'totalPaid'   => $totalPaid,
        ];
    }
}