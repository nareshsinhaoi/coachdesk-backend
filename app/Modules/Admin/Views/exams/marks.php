<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<div class="mb-4">
  <a href="<?= site_url('admin/exams') ?>" class="text-sm text-indigo-600">← Back</a>
  <h1 class="text-2xl font-bold text-slate-800 mt-2"><?= esc($exam['exam_name']) ?> — Marks Entry</h1>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Student</th><th>Marks (out of 100)</th></tr></thead>
      <tbody>
        <?php foreach ($students as $s): ?>
        <tr>
          <td>
            <div class="font-medium"><?= esc($s['name']) ?></div>
            <div class="text-xs text-slate-500 font-mono"><?= esc($s['student_code']) ?></div>
          </td>
          <td><input type="number" min="0" max="100" class="input" style="max-width:120px;" placeholder="—"></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<p class="text-xs text-slate-500 mt-4">Full marks-entry save flow available in the next iteration (requires exam_subjects configuration).</p>

<?= $this->endSection() ?>