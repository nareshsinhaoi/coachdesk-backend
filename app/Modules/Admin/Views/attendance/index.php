<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<h1 class="text-2xl font-bold text-slate-800 mb-4">Attendance</h1>

<form method="get" class="card p-3 mb-4 flex gap-2 items-center">
  <label class="text-sm font-medium text-slate-600">Date</label>
  <input type="date" name="date" value="<?= esc($date) ?>" class="input w-auto">
  <button class="btn-primary text-sm">View</button>
</form>

<div class="card p-4 mb-4">
  <div class="font-semibold text-slate-700 mb-3">Mark Attendance</div>
  <div class="flex flex-wrap gap-2">
    <?php foreach ($batches as $b): ?>
      <a href="<?= site_url('admin/attendance/mark?batch_id='.$b['id'].'&date='.$date) ?>"
         class="px-3 py-2 bg-slate-100 hover:bg-indigo-100 rounded-lg text-sm">
        <?= esc($b['batch_name']) ?>
      </a>
    <?php endforeach; ?>
    <?php if (empty($batches)): ?><p class="text-sm text-slate-500">No running batches.</p><?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="p-4 border-b border-slate-200 font-semibold text-slate-700">Sessions on <?= esc($date) ?></div>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Batch</th><th>Present</th><th>Absent</th><th>Status</th></tr></thead>
      <tbody>
        <?php if (empty($sessions)): ?>
        <tr><td colspan="4" class="text-center text-slate-400 py-8">No sessions yet for this date.</td></tr>
        <?php else: foreach ($sessions as $s): ?>
        <tr>
          <td class="font-medium"><?= esc($s['batch_name']) ?> <span class="text-xs text-slate-500">(<?= esc($s['batch_code']) ?>)</span></td>
          <td class="text-emerald-600 font-semibold"><?= $s['present_count'] ?></td>
          <td class="text-rose-600 font-semibold"><?= $s['absent_count'] ?></td>
          <td><span class="badge badge-green"><?= esc($s['status']) ?></span></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>