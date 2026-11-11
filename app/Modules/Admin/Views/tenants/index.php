<?php echo $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?php echo $this->section('content') ?>


<div class="flex items-center justify-between mb-4">
  <div>
    <h1 class="text-2xl font-bold text-slate-800">All Institutes</h1>
    <p class="text-sm text-slate-500"><?php echo count($tenants) ?> registered on CoachDesk</p>
  </div>
  <a href="<?php echo site_url('admin/tenants/create') ?>" class="btn-primary text-sm">
    <i class="fas fa-plus mr-1"></i> New Institute
  </a>
</div>


<div class="card">
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th>Code</th><th>Institute</th><th>Plan</th>
          <th>Students</th><th>Branches</th><th>Users</th>
          <th>Expires</th><th>Status</th><th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($tenants)): ?>
          <tr><td colspan="9" class="text-center text-slate-400 py-6">No institutes yet.</td></tr>
        <?php else: foreach ($tenants as $t): ?>
          <tr>
            <td class="font-mono text-xs"><?php echo esc($t['code']) ?></td>
            <td>
              <div class="font-medium"><?php echo esc($t['name']) ?></div>
              <div class="text-xs text-slate-500"><?php echo esc($t['email']) ?></div>
            </td>
            <td><span class="badge badge-blue"><?php echo esc($t['current_plan'] ?? '—') ?></span></td>
            <td><?php echo $t['student_count'] ?></td>
            <td><?php echo $t['branch_count'] ?></td>
            <td><?php echo $t['user_count'] ?></td>
            <td class="text-xs text-slate-500"><?php echo esc($t['plan_expires'] ?? '—') ?></td>
            <td>
              <span class="badge <?php echo $t['status']==='active' ? 'badge-green' : ($t['status']==='trial' ? 'badge-blue' : ($t['status']==='suspended' ? 'badge-amber' : 'badge-gray')) ?>">
                <?php echo esc($t['status']) ?>
              </span>
            </td>
            <td class="text-right">
              <a href="<?php echo site_url('admin/tenants/'.$t['id']) ?>" class="text-indigo-600 text-xs font-medium">View</a>
            </td>
            
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php echo $this->endSection() ?>