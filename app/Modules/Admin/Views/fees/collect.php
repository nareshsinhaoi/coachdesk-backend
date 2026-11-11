<?php echo $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?php echo $this->section('content') ?>

<?php
// Pre-selected student (from ?student_id= in URL)
$selectedId = (int) ($_GET['student_id'] ?? old('student_id') ?? 0);
?>

<div class="mb-4">
  <a href="<?php echo site_url('admin/fees') ?>" class="text-sm text-indigo-600">← Back to Fees</a>
  <h1 class="text-2xl font-bold text-slate-800 mt-2">Collect Payment</h1>
</div>

<form method="post" action="<?php echo site_url('admin/fees') ?>" class="card p-6 space-y-4 max-w-2xl" id="collectForm">
  <?php echo csrf_field() ?>

  <div>
    <label class="text-sm font-medium text-slate-600">Student *</label>
    <select name="student_id" required class="input mt-1" id="studentSelect">
      <option value="">— Select a student —</option>
      <?php foreach ($students as $s): ?>
        <option value="<?php echo $s['id'] ?>" data-code="<?php echo esc($s['student_code']) ?>"
                <?php echo $selectedId === (int) $s['id'] ? 'selected' : '' ?>>
          <?php echo esc($s['student_code']) ?> — <?php echo esc($s['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="text-sm font-medium text-slate-600">Amount (₹) *</label>
      <input type="number" step="0.01" name="amount" required min="0.01"
             value="<?php echo old('amount') ?>" class="input mt-1" placeholder="0.00">
    </div>
    <div>
      <label class="text-sm font-medium text-slate-600">Payment Date *</label>
      <input type="date" name="payment_date" required
             value="<?php echo old('payment_date', date('Y-m-d')) ?>" class="input mt-1">
    </div>
  </div>

  <div>
    <label class="text-sm font-medium text-slate-600">Payment Method *</label>
    <select name="method" required class="input mt-1">
      <?php foreach (['cash'=>'Cash','upi'=>'UPI','card'=>'Card','bank_transfer'=>'Bank Transfer','cheque'=>'Cheque','online'=>'Online'] as $k=>$v): ?>
        <option value="<?php echo $k ?>" <?php echo old('method') === $k ? 'selected' : '' ?>><?php echo $v ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div>
    <label class="text-sm font-medium text-slate-600">Notes (optional)</label>
    <textarea name="notes" rows="2" class="input mt-1" placeholder="Reference number, remarks…"><?php echo old('notes') ?></textarea>
  </div>

  <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-800">
    <i class="fas fa-info-circle mr-1"></i>
    After saving, a <strong>PDF receipt</strong> will be generated automatically. You can download or print it directly.
  </div>

  <div class="flex gap-2 pt-2 border-t border-slate-200">
    <button class="btn-primary"><i class="fas fa-rupee-sign mr-1"></i> Save &amp; Generate Receipt</button>
    <a href="<?php echo site_url('admin/fees') ?>" class="px-4 py-2 text-sm text-slate-600">Cancel</a>
  </div>
</form>

<?php echo $this->endSection() ?>