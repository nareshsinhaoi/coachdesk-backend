<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold text-slate-800">Teachers</h1>
  <a href="<?= site_url('admin/teachers/create') ?>" class="btn-primary text-sm"><i class="fas fa-plus mr-1"></i> Add Teacher</a>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Name</th><th>Qualification</th><th>Phone</th><th>Joined</th><th>Status</th></tr></thead>
      <tbody>
        <?php if (empty($teachers)): ?>
        <tr><td colspan="5" class="text-center text-slate-400 py-8">No teachers yet.</td></tr>
        <?php else: foreach ($teachers as $t): ?>
        <tr>
          <td class="font-medium"><?= esc($t['name']) ?></td>
          <td><?= esc($t['qualification'] ?? '—') ?></td>
          <td><?= esc($t['phone'] ?? '—') ?></td>
          <td class="text-xs text-slate-500"><?= esc($t['joining_date'] ?? '—') ?></td>
          <td><span class="badge badge-green"><?= esc($t['status']) ?></span></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>