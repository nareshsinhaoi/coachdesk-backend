<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold text-slate-800">Courses</h1>
  <?php if (can('courses.manage')): ?>
  <a href="<?= site_url('admin/courses/create') ?>" class="btn-primary text-sm"><i class="fas fa-plus mr-1"></i> New Course</a>
  <?php endif; ?>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Code</th><th>Name</th><th>Duration</th><th>Fees</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($courses as $c): ?>
        <tr>
          <td class="font-mono text-xs"><?= esc($c['course_code']) ?></td>
          <td class="font-medium"><?= esc($c['course_name']) ?></td>
          <td><?= esc($c['duration'] ?? '—') ?></td>
          <td><?= money($c['fees']) ?></td>
          <td><span class="badge <?= $c['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>"><?= esc($c['status']) ?></span></td>
          <td class="text-right">
            <?php if (can('courses.manage')): ?>
            <a href="<?= site_url('admin/courses/'.$c['public_id'].'/edit') ?>" class="text-indigo-600 text-xs font-medium">Edit</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>