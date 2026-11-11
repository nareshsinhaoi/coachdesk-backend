<?php echo $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?php echo $this->section('content') ?>

<div class="flex items-center justify-between mb-4">
  <div>
    <h1 class="text-2xl font-bold text-slate-800">Fees & Payments</h1>
    <p class="text-sm text-slate-500"><?php echo count($students) ?> students · <?php echo count($payments) ?> recent payments</p>
  </div>
  <a href="<?php echo site_url('admin/fees/collect') ?>" class="btn-primary text-sm">
    <i class="fas fa-plus mr-1"></i> Collect Payment
  </a>
</div>

<!-- Filter -->
<form method="get" class="card p-3 mb-4 flex gap-2 flex-wrap items-center">
  <input type="search" name="search" value="<?php echo esc($_GET['search'] ?? '') ?>"
         placeholder="Search student name…" class="input flex-1 min-w-[240px]">
  <button class="btn-primary text-sm">Search</button>
  <?php if (! empty($_GET['search'])): ?>
    <a href="<?php echo site_url('admin/fees') ?>" class="text-sm text-slate-600 self-center">Clear</a>
  <?php endif; ?>
</form>

<!-- Student Fee Summary -->
<div class="card mb-4">
  <div class="p-4 border-b border-slate-200 font-semibold text-slate-700">Student Fee Summary</div>
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th>Code</th>
          <th>Student</th>
          <th>Total</th>
          <th>Paid</th>
          <th>Balance</th>
          <th>Status</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($students)): ?>
          <tr><td colspan="7" class="text-center text-slate-400 py-8">No students found.</td></tr>
        <?php else: foreach ($students as $s):
          $balance = (float) $s['total_fees'] - (float) $s['paid_amount'];
        ?>
          <tr>
            <td class="font-mono text-xs"><?php echo esc($s['student_code']) ?></td>
            <td class="font-medium"><?php echo esc($s['name']) ?></td>
            <td><?php echo money($s['total_fees']) ?></td>
            <td class="text-emerald-600 font-semibold"><?php echo money($s['paid_amount']) ?></td>
            <td class="<?php echo $balance > 0 ? 'text-rose-600' : 'text-slate-500' ?> font-semibold"><?php echo money($balance) ?></td>
            <td>
              <?php if ($balance <= 0 && $s['total_fees'] > 0): ?>
                <span class="badge badge-green text-xs">Paid</span>
              <?php elseif ($s['paid_amount'] > 0): ?>
                <span class="badge badge-amber text-xs">Partial</span>
              <?php else: ?>
                <span class="badge badge-red text-xs">Unpaid</span>
              <?php endif; ?>
            </td>
            <td class="text-right">
              <a href="<?php echo site_url('admin/fees/collect?student_id='.$s['id']) ?>"
                 class="text-indigo-600 text-xs font-medium">
                <i class="fas fa-rupee-sign mr-1"></i> Collect
              </a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Recent Payments -->
<div class="card">
  <div class="p-4 border-b border-slate-200 font-semibold text-slate-700">Recent Payments</div>
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th>Receipt</th>
          <th>Student</th>
          <th>Amount</th>
          <th>Method</th>
          <th>Date</th>
          <th class="text-right">Receipt PDF</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($payments)): ?>
          <tr><td colspan="6" class="text-center text-slate-400 py-8">No payments recorded yet.</td></tr>
        <?php else: foreach ($payments as $p): ?>
          <tr>
            <td class="font-mono text-xs"><?php echo esc($p['receipt_number']) ?></td>
            <td><?php echo esc($p['student_name']) ?></td>
            <td class="text-emerald-600 font-semibold"><?php echo money($p['amount']) ?></td>
            <td><span class="badge badge-blue"><?php echo esc($p['method']) ?></span></td>
            <td class="text-xs text-slate-500"><?php echo date('d M Y', strtotime($p['payment_date'])) ?></td>
            <td class="text-right whitespace-nowrap">
              <a href="<?php echo site_url('admin/fees/'.$p['public_id'].'/receipt') ?>"
                 class="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-medium rounded-md"
                 title="Download PDF receipt">
                <i class="fas fa-file-pdf"></i> Download
              </a>
              <a href="<?php echo site_url('admin/fees/'.$p['public_id'].'/receipt/inline') ?>"
                 target="_blank"
                 class="inline-flex items-center gap-1 ml-1 px-2.5 py-1 bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-medium rounded-md"
                 title="Preview in browser">
                <i class="fas fa-eye"></i> Preview
              </a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php echo $this->endSection() ?>