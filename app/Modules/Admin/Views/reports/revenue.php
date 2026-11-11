<?php echo $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?php echo $this->section('content') ?>

<h1 class="text-2xl font-bold text-slate-800 mb-4">Revenue Report</h1>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
  <div class="card p-5">
    <div class="font-semibold text-slate-700 mb-3">Monthly Revenue (last 12 months)</div>
    <canvas id="monthlyChart" height="200"></canvas>
  </div>
  <div class="card p-5">
    <div class="font-semibold text-slate-700 mb-3">Revenue by Plan</div>
    <canvas id="planChart" height="200"></canvas>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
  <div class="card">
    <div class="p-4 border-b border-slate-200 font-semibold text-slate-700">Monthly Breakdown</div>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Month</th><th>Transactions</th><th>Total</th></tr></thead>
        <tbody>
          <?php if (empty($monthly)): ?>
            <tr><td colspan="3" class="text-center text-slate-400 py-6">No payments yet.</td></tr>
          <?php else: foreach ($monthly as $m): ?>
            <tr>
              <td class="font-mono text-xs"><?php echo esc($m['ym']) ?></td>
              <td><?php echo esc($m['cnt']) ?></td>
              <td class="font-semibold text-emerald-600"><?php echo money($m['total']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="p-4 border-b border-slate-200 font-semibold text-slate-700">By Plan</div>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Plan</th><th>Active Subscriptions</th><th>MRR</th></tr></thead>
        <tbody>
          <?php foreach ($byPlan as $p): ?>
            <tr>
              <td class="font-medium"><?php echo esc($p['name']) ?></td>
              <td><?php echo esc($p['subs']) ?></td>
              <td class="font-semibold"><?php echo money($p['mrr'] ?? 0) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
const monthlyData = <?php echo json_encode(array_reverse($monthly)) ?>;
new Chart(document.getElementById('monthlyChart'), {
  type: 'line',
  data: {
    labels: monthlyData.map(d => d.ym),
    datasets: [{
      label: '₹ Revenue',
      data: monthlyData.map(d => parseFloat(d.total)),
      borderColor: '#4f46e5',
      backgroundColor: 'rgba(79,70,229,.1)',
      fill: true, tension: .35
    }]
  },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

const planData = <?php echo json_encode($byPlan) ?>;
new Chart(document.getElementById('planChart'), {
  type: 'doughnut',
  data: {
    labels: planData.map(d => d.name),
    datasets: [{
      data: planData.map(d => parseFloat(d.mrr || 0)),
      backgroundColor: ['#4f46e5','#10b981','#f59e0b','#ef4444']
    }]
  }
});
</script>

<?php echo $this->endSection() ?>