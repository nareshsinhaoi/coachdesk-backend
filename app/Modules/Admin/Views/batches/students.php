<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<div class="mb-4">
  <a href="<?= site_url('admin/batches') ?>" class="text-sm text-indigo-600">← Back</a>
  <h1 class="text-2xl font-bold text-slate-800 mt-2"><?= esc($batch['batch_name'] ?? 'Batch') ?> — Students</h1>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Code</th><th>Name</th><th>Phone</th><th>Enrolled</th></tr></thead>
      <tbody>
        <?php if (empty($students)): ?>
        <tr><td colspan="4" class="text-center text-slate-400 py-8">No students enrolled yet.</td></tr>
        <?php else: foreach ($students as $s): ?>
        <tr>
          <td class="font-mono text-xs"><?= esc($s['student_code']) ?></td>
          <td><?= esc($s['name']) ?></td>
          <td><?= esc($s['phone'] ?? '—') ?></td>
          <td class="text-xs text-slate-500"><?= esc($s['start_date']) ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>