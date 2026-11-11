<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<div class="mb-4">
  <a href="<?= site_url('admin/attendance') ?>" class="text-sm text-indigo-600">← Back</a>
  <h1 class="text-2xl font-bold text-slate-800 mt-2">Mark Attendance — <?= esc($batch['batch_name']) ?></h1>
  <p class="text-sm text-slate-500">Date: <?= esc($date) ?> · <?= count($students) ?> students</p>
</div>

<form method="post" action="<?= site_url('admin/attendance/save') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="batch_id" value="<?= $batch['id'] ?>">
  <input type="hidden" name="attendance_date" value="<?= esc($date) ?>">

  <div class="card mb-4">
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Student</th><th>Mark</th></tr></thead>
        <tbody>
          <?php foreach ($students as $s): $cur = $existing[$s['id']] ?? 'present'; ?>
          <tr>
            <td>
              <div class="font-medium"><?= esc($s['name']) ?></div>
              <div class="text-xs text-slate-500 font-mono"><?= esc($s['student_code']) ?></div>
            </td>
            <td>
              <div class="flex gap-1 flex-wrap">
                <?php foreach (['present'=>'green','late'=>'amber','half_day'=>'amber','absent'=>'red','on_leave'=>'gray'] as $st => $color): ?>
                <label class="cursor-pointer">
                  <input type="radio" name="status[<?= $s['id'] ?>]" value="<?= $st ?>" <?= $cur === $st ? 'checked' : '' ?> class="peer sr-only">
                  <span class="px-3 py-1 rounded-lg text-xs font-medium bg-slate-100 peer-checked:bg-<?= $color ?>-500 peer-checked:text-white"><?= $st ?></span>
                </label>
                <?php endforeach; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <button class="btn-primary"><i class="fas fa-check mr-1"></i> Save Attendance</button>
</form>

<?= $this->endSection() ?>