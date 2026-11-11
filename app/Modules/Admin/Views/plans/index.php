<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold text-slate-800">Subscription Plans</h1>
  <a href="<?= site_url('admin/plans/create') ?>" class="btn-primary text-sm">
    <i class="fas fa-plus mr-1"></i> New Plan
  </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
  <?php foreach ($plans as $p): ?>
  <div class="card p-5">
    <div class="flex items-start justify-between mb-2">
      <div class="text-lg font-bold text-slate-800"><?= esc($p['name']) ?></div>
      <span class="badge <?= $p['is_active'] ? 'badge-green' : 'badge-gray' ?>">
        <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
      </span>
    </div>
    <div class="text-2xl font-bold text-indigo-600 mb-1">
      <?= money($p['price']) ?>
      <span class="text-xs text-slate-500">/<?= esc($p['billing_cycle']) ?></span>
    </div>
    <ul class="text-sm text-slate-600 space-y-1 mt-3 mb-4">
      <li>👥 <?= $p['max_students'] ?> students</li>
      <li>🧑‍🏫 <?= $p['max_staff'] ?> staff</li>
      <li>🏢 <?= $p['max_branches'] ?> branches</li>
    </ul>
    <div class="text-xs text-slate-500 mb-3"><?= $p['active_count'] ?> active subscriptions</div>
    <a href="<?= site_url('admin/plans/'.$p['id'].'/edit') ?>"
       class="block text-center text-sm text-indigo-600 py-2 border border-indigo-200 rounded-lg hover:bg-indigo-50">
      Edit Plan
    </a>
  </div>
  <?php endforeach; ?>
</div>

<?= $this->endSection() ?>