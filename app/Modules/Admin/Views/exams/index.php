<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<h1 class="text-2xl font-bold text-slate-800 mb-4">Exams</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 card">
    <div class="p-4 border-b border-slate-200 font-semibold text-slate-700">Scheduled Exams</div>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Exam</th><th>Batch</th><th>Date</th><th>Results</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php if (empty($exams)): ?>
          <tr><td colspan="6" class="text-center text-slate-400 py-8">No exams yet.</td></tr>
          <?php else: foreach ($exams as $e): ?>
          <tr>
            <td class="font-medium"><?= esc($e['exam_name']) ?></td>
            <td><?= esc($e['batch_name']) ?></td>
            <td class="text-xs text-slate-500"><?= esc($e['exam_date']) ?></td>
            <td><?= $e['result_count'] ?></td>
            <td><span class="badge <?= $e['status'] === 'published' ? 'badge-green' : 'badge-blue' ?>"><?= esc($e['status']) ?></span></td>
            <td class="text-right">
              <a href="<?= site_url('admin/exams/'.$e['public_id'].'/marks') ?>" class="text-indigo-600 text-xs font-medium">Enter Marks</a>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card p-4">
    <div class="font-semibold text-slate-700 mb-3">Schedule New Exam</div>
    <form method="post" action="<?= site_url('admin/exams') ?>" class="space-y-3">
      <?= csrf_field() ?>
      <div>
        <label class="text-sm font-medium text-slate-600">Batch</label>
        <select name="batch_id" required class="input mt-1">
          <option value="">— Select —</option>
          <?php foreach ($batches as $b): ?>
            <option value="<?= $b['id'] ?>"><?= esc($b['batch_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium text-slate-600">Exam Name</label>
        <input name="exam_name" required class="input mt-1">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-600">Exam Date</label>
        <input type="date" name="exam_date" value="<?= date('Y-m-d') ?>" class="input mt-1">
      </div>
      <button class="btn-primary w-full text-sm">Schedule</button>
    </form>
  </div>
</div>

<?= $this->endSection() ?>