<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<h1 class="text-2xl font-bold text-slate-800 mb-4">Audit Logs</h1>

<form method="get" class="card p-3 mb-4 flex gap-2 flex-wrap">
  <select name="tenant_id" class="input w-auto">
    <option value="">All Institutes</option>
    <?php foreach ($tenants as $t): ?>
      <option value="<?= $t['id'] ?>" <?= ($_GET['tenant_id'] ?? '') == $t['id'] ? 'selected' : '' ?>>
        <?= esc($t['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <input type="text" name="action" placeholder="Filter action (e.g. auth.login)" value="<?= esc($_GET['action'] ?? '') ?>" class="input flex-1 min-w-[240px]">
  <button class="btn-primary text-sm">Filter</button>
</form>

<div class="card">
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Time</th><th>Institute</th><th>User</th><th>Action</th><th>Module</th><th>IP</th></tr></thead>
      <tbody>
        <?php foreach ($logs as $l): ?>
          <tr>
            <td class="text-xs"><?= esc($l['created_at']) ?></td>
            <td class="text-xs"><?= esc($l['tenant_name'] ?? '—') ?></td>
            <td class="text-xs">
              <?= esc($l['user_name'] ?? '—') ?>
              <div class="text-slate-400"><?= esc($l['user_email'] ?? '') ?></div>
            </td>
            <td><span class="badge badge-blue font-mono text-xs"><?= esc($l['action']) ?></span></td>
            <td class="text-xs"><?= esc($l['module']) ?></td>
            <td class="text-xs text-slate-500"><?= esc($l['ip_address'] ?? '—') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>