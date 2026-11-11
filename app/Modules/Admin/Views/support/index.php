<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<h1 class="text-2xl font-bold text-slate-800 mb-4">Support Tickets</h1>

<div class="card">
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr><th>Institute</th><th>User</th><th>Subject</th><th>Priority</th><th>Status</th><th>Created</th><th class="text-right">Actions</th></tr>
      </thead>
      <tbody>
        <?php if (empty($tickets)): ?>
          <tr><td colspan="7" class="text-center text-slate-400 py-6">No tickets yet.</td></tr>
        <?php else: foreach ($tickets as $t): ?>
          <tr>
            <td class="text-xs"><?= esc($t['tenant_name'] ?? '—') ?></td>
            <td>
              <div class="font-medium text-sm"><?= esc($t['user_name'] ?? '—') ?></div>
              <div class="text-xs text-slate-500"><?= esc($t['user_email'] ?? '') ?></div>
            </td>
            <td>
              <div class="font-medium"><?= esc($t['subject']) ?></div>
              <div class="text-xs text-slate-500 truncate max-w-xs"><?= esc(substr($t['description'] ?? '', 0, 60)) ?></div>
            </td>
            <td>
              <span class="badge <?= $t['priority']==='urgent' ? 'badge-red' :
                                  ($t['priority']==='high'   ? 'badge-amber' :
                                  ($t['priority']==='medium' ? 'badge-blue' : 'badge-gray')) ?>">
                <?= esc($t['priority']) ?>
              </span>
            </td>
            <td>
              <span class="badge <?= $t['status']==='open'        ? 'badge-red' :
                                  ($t['status']==='in_progress'? 'badge-amber' :
                                  ($t['status']==='resolved'   ? 'badge-green' : 'badge-gray')) ?>">
                <?= esc($t['status']) ?>
              </span>
            </td>
            <td class="text-xs text-slate-500"><?= esc($t['created_at']) ?></td>
            <td class="text-right">
              <form method="post" action="<?= site_url('admin/support/'.$t['id']) ?>" class="inline">
                <?= csrf_field() ?>
                <select name="status" onchange="this.form.submit()" class="text-xs px-2 py-1 rounded border border-slate-300">
                  <?php foreach (['open','in_progress','resolved','closed'] as $st): ?>
                    <option value="<?= $st ?>" <?= $t['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>