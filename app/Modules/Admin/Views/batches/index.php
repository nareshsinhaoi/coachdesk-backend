<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold text-slate-800">Batches</h1>
  <?php if (can('batches.manage')): ?>
  <a href="<?= site_url('admin/batches/create') ?>" class="btn-primary text-sm"><i class="fas fa-plus mr-1"></i> New Batch</a>
  <?php endif; ?>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Code</th><th>Batch</th><th>Course</th><th>Capacity</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($batches as $b): ?>
        <tr>
          <td class="font-mono text-xs"><?= esc($b['batch_code']) ?></td>
          <td class="font-medium"><?= esc($b['batch_name']) ?></td>
          <td><?= esc($b['course_name'] ?? '—') ?></td>
          <td><?= esc($b['capacity']) ?></td>
          <td>
            <span class="badge <?= $b['status'] === 'running' ? 'badge-green' : ($b['status'] === 'upcoming' ? 'badge-blue' : 'badge-gray') ?>">
              <?= esc($b['status']) ?>
            </span>
          </td>
          <td class="text-right">
            <a href="<?= site_url('admin/batches/'.$b['public_id'].'/students') ?>" class="text-indigo-600 text-xs font-medium">Students</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>