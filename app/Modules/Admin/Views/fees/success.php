<?php echo $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?php echo $this->section('content') ?>

<div class="max-w-2xl mx-auto">

  <!-- Success icon -->
  <div class="text-center mb-6">
    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-emerald-100 mb-4">
      <i class="fas fa-check text-4xl text-emerald-600"></i>
    </div>
    <h1 class="text-2xl font-bold text-slate-800">Payment Recorded Successfully</h1>
    <p class="text-sm text-slate-500 mt-1">Receipt <span class="font-mono font-semibold"><?php echo esc($payment['receipt_number']) ?></span> has been issued.</p>
  </div>

  <!-- Payment summary card -->
  <div class="card p-6 mb-4">
    <div class="grid grid-cols-2 gap-4 text-sm">
      <div>
        <div class="text-xs uppercase text-slate-500 mb-1">Student</div>
        <div class="font-semibold text-slate-800"><?php echo esc($payment['student_name']) ?></div>
        <div class="text-xs text-slate-500 font-mono"><?php echo esc($payment['student_code']) ?></div>
      </div>
      <div class="text-right">
        <div class="text-xs uppercase text-slate-500 mb-1">Amount Paid</div>
        <div class="text-2xl font-bold text-emerald-600"><?php echo money($payment['amount']) ?></div>
      </div>
      <div>
        <div class="text-xs uppercase text-slate-500 mb-1">Method</div>
        <div class="font-medium capitalize"><?php echo esc(str_replace('_', ' ', $payment['method'])) ?></div>
      </div>
      <div class="text-right">
        <div class="text-xs uppercase text-slate-500 mb-1">Date</div>
        <div class="font-medium"><?php echo date('d M Y', strtotime($payment['payment_date'])) ?></div>
      </div>
    </div>
  </div>

  <!-- Action buttons -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
    <a href="<?php echo site_url('admin/fees/'.$payment['public_id'].'/receipt') ?>"
       class="flex items-center justify-center gap-2 bg-rose-600 hover:bg-rose-700 text-white py-3 rounded-lg font-medium transition">
      <i class="fas fa-file-pdf"></i> Download PDF Receipt
    </a>
    <a href="<?php echo site_url('admin/fees/'.$payment['public_id'].'/receipt/inline') ?>"
       target="_blank"
       class="flex items-center justify-center gap-2 bg-slate-700 hover:bg-slate-800 text-white py-3 rounded-lg font-medium transition">
      <i class="fas fa-print"></i> Preview / Print
    </a>
  </div>

  <!-- Next steps -->
  <div class="card p-4 mb-4">
    <div class="text-xs uppercase tracking-wider text-slate-500 mb-2">What's Next</div>
    <div class="space-y-2">
      <a href="<?php echo site_url('admin/fees/collect?student_id='.$payment['student_id']) ?>"
         class="flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-700">
        <i class="fas fa-plus-circle"></i> Collect another payment for <?php echo esc($payment['student_name']) ?>
      </a>
      <a href="<?php echo site_url('admin/fees') ?>"
         class="flex items-center gap-2 text-sm text-slate-700 hover:text-slate-900">
        <i class="fas fa-list"></i> Back to Fees & Payments
      </a>
      <a href="<?php echo site_url('admin/dashboard') ?>"
         class="flex items-center gap-2 text-sm text-slate-700 hover:text-slate-900">
        <i class="fas fa-gauge"></i> Go to Dashboard
      </a>
    </div>
  </div>

</div>

<?php echo $this->endSection() ?>