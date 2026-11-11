<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<div class="mb-4">
  <a href="<?= site_url('admin/batches') ?>" class="text-sm text-indigo-600">← Back</a>
  <h1 class="text-2xl font-bold text-slate-800 mt-2"><?= esc($title) ?></h1>
</div>

<form method="post" action="<?= site_url('admin/batches') ?>" class="card p-6 space-y-4 max-w-2xl">
  <?= csrf_field() ?>
  <div class="grid grid-cols-2 gap-4">
    <div class="col-span-2">
      <label class="text-sm font-medium text-slate-600">Course *</label>
      <select name="course_id" required class="input mt-1">
        <option value="">— Select —</option>
        <?php foreach ($courses as $c): ?>
          <option value="<?= $c['id'] ?>"><?= esc($c['course_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-sm font-medium text-slate-600">Batch Name *</label>
      <input name="batch_name" required class="input mt-1" placeholder="Maths Morning">
    </div>
    <div>
      <label class="text-sm font-medium text-slate-600">Batch Code *</label>
      <input name="batch_code" required class="input mt-1 font-mono" placeholder="MATH-A">
    </div>
    <div>
      <label class="text-sm font-medium text-slate-600">Capacity</label>
      <input type="number" name="capacity" value="30" class="input mt-1">
    </div>
    <div>
      <label class="text-sm font-medium text-slate-600">Start Date</label>
      <input type="date" name="start_date" value="<?= date('Y-m-d') ?>" class="input mt-1">
    </div>
    <div>
      <label class="text-sm font-medium text-slate-600">Timing Start</label>
      <input type="time" name="timing_start" class="input mt-1">
    </div>
    <div>
      <label class="text-sm font-medium text-slate-600">Timing End</label>
      <input type="time" name="timing_end" class="input mt-1">
    </div>
    <div>
      <label class="text-sm font-medium text-slate-600">Status</label>
      <select name="status" class="input mt-1">
        <option value="upcoming">Upcoming</option>
        <option value="running">Running</option>
        <option value="completed">Completed</option>
      </select>
    </div>
  </div>
  <button class="btn-primary">Create Batch</button>
</form>

<?= $this->endSection() ?>