<?php
namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
require_once __DIR__ . '/../Helpers/admin_helper.php';

class FeeController extends BaseController
{
    public function index()
    {
        $db = db_connect();
        //$this->db = \Config\Database::connect();
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

        if ($search) $q->like('cd_students.name', $search);

        $students = $q->get()->getResultArray();

        $payments = $db->table('cd_payments p')
            ->select('p.*, s.name AS student_name')
            ->join('cd_students s', 's.id = p.student_id')
            ->where('p.tenant_id', tenant_id())
            ->orderBy('p.id', 'desc')
            ->limit(20)->get()->getResultArray();

        return view('App\Modules\Admin\Views\fees\index', [
            'title' => 'Fees & Payments', 'students' => $students, 'payments' => $payments,
        ]);
    }

    public function collect()
    {
        $db = db_connect();
        $students = $db->table('cd_students')
            ->where('tenant_id', tenant_id())->where('status', 'active')
            ->orderBy('name')->get()->getResultArray();

        return view('App\Modules\Admin\Views\fees\collect', [
            'title' => 'Collect Payment', 'students' => $students,
        ]);
    }

    public function store()
    {
        $db = db_connect();
        $studentId = (int) $this->request->getPost('student_id');
        $amount    = (float) $this->request->getPost('amount');
        $method    = $this->request->getPost('method');
        $date      = $this->request->getPost('payment_date') ?? date('Y-m-d');
        $notes     = $this->request->getPost('notes');

        if (! $studentId || $amount <= 0) {
            return redirect()->back()->withInput()->with('error', 'Student and amount are required.');
        }

        $student = $db->table('cd_students')
            ->where(['tenant_id' => tenant_id(), 'id' => $studentId])->get()->getRow();
        if (! $student) return redirect()->back()->with('error', 'Invalid student.');

        // Generate receipt number
        $prefix = 'RCPT-' . date('Y') . '-';
        $last = $db->table('cd_payments')
            ->like('receipt_number', $prefix, 'after')
            ->where('tenant_id', tenant_id())
            ->orderBy('id', 'desc')->get()->getRow();
        $seq = $last ? ((int) substr($last->receipt_number, -4)) + 1 : 1;
        $receiptNo = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

        $db->transStart();
        $db->table('cd_payments')->insert([
            'public_id'     => uuid4(),
            'tenant_id'     => tenant_id(),
            'student_id'    => $studentId,
            'receipt_number'=> $receiptNo,
            'payment_date'  => $date,
            'amount'        => $amount,
            'method'        => $method,
            'status'        => 'success',
            'notes'         => $notes,
            'received_by'   => session()->get('user_id'),
        ]);
        $paymentId = $db->insertID();

        // Allocate to earliest pending installments
        $remaining = $amount;
        $pending = $db->table('cd_fee_installments fi')
            ->join('cd_student_fee_accounts fa', 'fa.id = fi.fee_account_id')
            ->where('fa.student_id', $studentId)
            ->where('fi.status !=', 'paid')
            ->orderBy('fi.due_date', 'asc')
            ->get()->getResultArray();

        foreach ($pending as $inst) {
            if ($remaining <= 0) break;
            $alloc = min($remaining, (float) $inst['amount']);
            $db->table('cd_payment_allocations')->insert([
                'tenant_id'        => tenant_id(),
                'payment_id'       => $paymentId,
                'installment_id'   => $inst['id'],
                'allocated_amount' => $alloc,
            ]);
            $db->table('cd_fee_installments')->where('id', $inst['id'])
                ->update(['status' => 'paid']);
            $remaining -= $alloc;
        }

        $db->transComplete();
        return redirect()->to('/admin/fees')->with('success', 'Payment recorded: ' . $receiptNo);
    }


    /* ============================================================
     * RECEIPT PDF
     * ============================================================ */
    /**
     * GET /admin/fees/{paymentPublicId}/receipt
     * Generates a branded PDF receipt for a payment.
     */
    public function receipt($paymentPublicId)
    {
        $db = db_connect();

        // Load payment (tenant-scoped)
        $payment = $db->table('cd_payments p')
            ->select('p.*, s.name AS student_name, s.student_code, s.phone AS student_phone, s.email AS student_email, s.address_line1, s.city, s.state, s.pincode')
            ->join('cd_students s', 's.id = p.student_id')
            ->where('p.public_id', $paymentPublicId)
            ->where('p.tenant_id', tenant_id())
            ->get()->getRowArray();

        if (! $payment) {
            return redirect()->to('/admin/fees')->with('error', 'Payment not found.');
        }

        // Load tenant info for header
        $tenant = $db->table('cd_tenants')->where('id', tenant_id())->get()->getRowArray();

        // Load tenant branding settings
        $branding = $db->table('cd_settings')
            ->where(['tenant_id' => tenant_id(), 'setting_key' => 'branding'])
            ->get()->getRow();
        $brandingData = $branding ? json_decode($branding->setting_value, true) : [];

        // Load payment allocations to show what this payment covered
        $allocations = $db->table('cd_payment_allocations pa')
            ->select('pa.allocated_amount, fi.installment_name, fi.due_date')
            ->join('cd_fee_installments fi', 'fi.id = pa.installment_id')
            ->where('pa.payment_id', $payment['id'])
            ->get()->getResultArray();

        // Who received it
        $receivedBy = null;
        if (! empty($payment['received_by'])) {
            $receivedBy = $db->table('cd_users')->where('id', $payment['received_by'])->get()->getRow();
        }

        // Total paid to date for this student (for the footer)
        $totalPaid = (float) ($db->table('cd_payments')
            ->selectSum('amount', 'total')
            ->where(['tenant_id' => tenant_id(), 'student_id' => $payment['student_id']])
            ->where('status', 'success')
            ->get()->getRow()->total ?? 0);

        // Build view data
        $data = [
            'payment'     => $payment,
            'tenant'      => $tenant,
            'branding'    => $brandingData,
            'allocations' => $allocations,
            'receivedBy'  => $receivedBy,
            'totalPaid'   => $totalPaid,
        ];

        // Render HTML
        $html = view('App\Modules\Admin\Views\fees\receipt_pdf', $data);

        // Stream as PDF
        $filename = 'Receipt-' . $payment['receipt_number'];
        (new \App\Modules\Admin\Libraries\PdfService())->render($html, $filename, 'download');
    }

    /**
     * GET /admin/fees/{paymentPublicId}/receipt/inline
     * Opens the PDF in the browser (for preview / printing without download).
     */
    public function receiptInline($paymentPublicId)
    {
        $db = db_connect();

        $payment = $db->table('cd_payments p')
            ->select('p.*, s.name AS student_name, s.student_code, s.phone AS student_phone, s.email AS student_email, s.address_line1, s.city, s.state, s.pincode')
            ->join('cd_students s', 's.id = p.student_id')
            ->where('p.public_id', $paymentPublicId)
            ->where('p.tenant_id', tenant_id())
            ->get()->getRowArray();

        if (! $payment) {
            return redirect()->to('/admin/fees')->with('error', 'Payment not found.');
        }

        $tenant   = $db->table('cd_tenants')->where('id', tenant_id())->get()->getRowArray();
        $branding = $db->table('cd_settings')
            ->where(['tenant_id' => tenant_id(), 'setting_key' => 'branding'])
            ->get()->getRow();
        $brandingData = $branding ? json_decode($branding->setting_value, true) : [];

        $allocations = $db->table('cd_payment_allocations pa')
            ->select('pa.allocated_amount, fi.installment_name, fi.due_date')
            ->join('cd_fee_installments fi', 'fi.id = pa.installment_id')
            ->where('pa.payment_id', $payment['id'])
            ->get()->getResultArray();

        $receivedBy = null;
        if (! empty($payment['received_by'])) {
            $receivedBy = $db->table('cd_users')->where('id', $payment['received_by'])->get()->getRow();
        }

        $totalPaid = (float) ($db->table('cd_payments')
            ->selectSum('amount', 'total')
            ->where(['tenant_id' => tenant_id(), 'student_id' => $payment['student_id']])
            ->where('status', 'success')
            ->get()->getRow()->total ?? 0);

        $html = view('App\Modules\Admin\Views\fees\receipt_pdf', [
            'payment'     => $payment,
            'tenant'      => $tenant,
            'branding'    => $brandingData,
            'allocations' => $allocations,
            'receivedBy'  => $receivedBy,
            'totalPaid'   => $totalPaid,
        ]);

        $filename = 'Receipt-' . $payment['receipt_number'];
        (new \App\Modules\Admin\Libraries\PdfService())->render($html, $filename, 'inline');
    }
    
}