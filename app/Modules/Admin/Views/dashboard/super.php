<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>
<?php $s = $stats; ?>

<h1 class="text-2xl font-bold text-slate-800 mb-1">Super Admin Dashboard</h1>
<p class="text-sm text-slate-500 mb-4">Platform-wide metrics across all institutes</p>

<!-- KPI row -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-500 uppercase">Total Institutes</div>
    <div class="text-3xl font-bold text-slate-800 mt-1"><?= $s['tenants']['total'] ?></div>
    <div class="text-xs text-emerald-600 mt-1">
      <?= $s['tenants']['active'] ?> active · <?= $s['tenants']['trial'] ?> trial
    </div>
  </div>
  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-500 uppercase">Total Students</div>
    <div class="text-3xl font-bold text-slate-800 mt-1"><?= number_format($s['students_total']) ?></div>
    <div class="text-xs text-slate-500 mt-1">across all institutes</div>
  </div>
  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-500 uppercase">MRR (this month)</div>
    <div class="text-3xl font-bold text-emerald-600 mt-1"><?= money($s['mrr']) ?></div>
    <div class="text-xs text-slate-500 mt-1">Total: <?= money($s['revenue_total']) ?></div>
  </div>
  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-500 uppercase">Platform Users</div>
    <div class="text-3xl font-bold text-slate-800 mt-1"><?= number_format($s['users_total']) ?></div>
    <div class="text-xs text-slate-500 mt-1">excl. super admin</div>
  </div>
</div>

<!-- Tenant status strip -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
  <div class="card p-4 text-center">
    <div class="text-2xl font-bold text-emerald-600"><?= $s['tenants']['active'] ?></div>
    <div class="text-xs text-slate-500">Active</div>
  </div>
  <div class="card p-4 text-center">
    <div class="text-2xl font-bold text-blue-600"><?= $s['tenants']['trial'] ?></div>
    <div class="text-xs text-slate-500">Trial</div>
  </div>
  <div class="card p-4 text-center">
    <div class="text-2xl font-bold text-amber-600"><?= $s['tenants']['suspended'] ?></div>
    <div class="text-xs text-slate-500">Suspended</div>
  </div>
  <div class="card p-4 text-center">
    <div class="text-2xl font-bold text-rose-600"><?= $s['tenants']['expired'] ?></div>
    <div class="text-xs text-slate-500">Expired</div>
  </div>
</div>

<!-- Signups chart -->
<div class="card p-5 mt-4">
  <div class="font-semibold text-slate-700 mb-3">Institute Signups (last 6 months)</div>
  <canvas id="signupsChart" height="120"></canvas>
</div>

<!-- All tenants table -->
<div class="card mt-4">
  <div class="p-4 border-b border-slate-200 font-semibold text-slate-700">All Institutes</div>
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th>Code</th>
          <th>Institute</th>
          <th>Students</th>
          <th>Branches</th>
          <th>Status</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($s['per_tenant'])): ?>
          <tr><td colspan="6" class="text-center text-slate-400 py-6">No tenants yet.</td></tr>
        <?php else: foreach ($s['per_tenant'] as $t): ?>
          <tr>
            <td class="font-mono text-xs"><?= esc($t['code']) ?></td>
            <td class="font-medium"><?= esc($t['name']) ?></td>
            <td><?= number_format($t['student_count']) ?></td>
            <td><?= $t['branch_count'] ?></td>
            <td>
              <span class="badge
                <?= $t['status']==='active' ? 'badge-green' :
                    ($t['status']==='trial' ? 'badge-blue' :
                    ($t['status']==='suspended' ? 'badge-amber' : 'badge-gray')) ?>">
                <?= esc($t['status']) ?>
              </span>
            </td>
            <td class="text-right">
              <a href="<?= site_url('admin/tenants/'.$t['id']) ?>"
                 class="text-indigo-600 text-xs font-medium">View</a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Expiring soon -->
<?php if (! empty($s['expiring'])): ?>
<div class="card mt-4">
  <div class="p-4 border-b border-slate-200 font-semibold text-amber-700">
    ⚠ Expiring in next 7 days
  </div>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Institute</th><th>Code</th><th>Expires</th></tr></thead>
      <tbody>
        <?php foreach ($s['expiring'] as $e): ?>
          <tr>
            <td><?= esc($e['name']) ?></td>
            <td class="font-mono text-xs"><?= esc($e['code']) ?></td>
            <td class="text-amber-600 text-xs"><?= esc($e['end_date']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<script>
const signups = <?= json_encode($s['signups_chart']) ?>;
new Chart(document.getElementById('signupsChart'), {
  type: 'bar',
  data: {
    labels: signups.map(d => d.ym),
    datasets: [{
      label: 'New institutes',
      data: signups.map(d => d.c),
      backgroundColor: '#4f46e5',
      borderRadius: 6
    }]
  },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>

<?= $this->endSection() ?>