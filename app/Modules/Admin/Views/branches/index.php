<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>
<h1 class="text-2xl font-bold text-slate-800 mb-4">Branches</h1>
<div class="card"><div class="table-wrap"><table class="data">
  <thead><tr><th>Code</th><th>Name</th><th>City</th><th>Phone</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($branches as $b): ?>
    <tr>
      <td class="font-mono text-xs"><?= esc($b['branch_code']) ?></td>
      <td class="font-medium"><?= esc($b['name']) ?></td>
      <td><?= esc($b['city'] ?? '—') ?></td>
      <td><?= esc($b['phone'] ?? '—') ?></td>
      <td><span class="badge <?= $b['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>"><?= esc($b['status']) ?></span></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
<?= $this->endSection() ?>