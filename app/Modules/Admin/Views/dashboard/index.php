<?php echo $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?php echo $this->section('content') ?>

<?php $s = $stats; ?>

<!-- KPI row -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-500 uppercase">Total Students</div>
    <div class="text-3xl font-bold text-slate-800 mt-1"><?php echo number_format($s['students']['total'] ?? 0) ?></div>
    <div class="text-xs text-emerald-600 mt-1"><?php echo $s['students']['new_this_month'] ?? 0 ?> new this month</div>
  </div>
  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-500 uppercase">Active Batches</div>
    <div class="text-3xl font-bold text-slate-800 mt-1"><?php echo $s['batches']['running'] ?? 0 ?></div>
    <div class="text-xs text-slate-500 mt-1"><?php echo $s['batches']['upcoming'] ?? 0 ?> upcoming</div>
  </div>
  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-500 uppercase">Collected (Month)</div>
    <div class="text-3xl font-bold text-emerald-600 mt-1"><?php echo money($s['fees']['collected_month'] ?? 0) ?></div>
    <div class="text-xs text-slate-500 mt-1">Today: <?php echo money($s['fees']['collected_today'] ?? 0) ?></div>
  </div>
  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-500 uppercase">Outstanding</div>
    <div class="text-3xl font-bold text-rose-600 mt-1"><?php echo money($s['fees']['outstanding'] ?? 0) ?></div>
    <div class="text-xs text-slate-500 mt-1"><?php echo $s['fees']['defaulters_count'] ?? 0 ?> defaulters</div>
  </div>
</div>

<!-- Attendance + Teachers strip -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-500 uppercase mb-3">Today's Attendance</div>
    <div class="flex items-baseline gap-2">
      <span class="text-2xl font-bold text-emerald-600"><?php echo $s['attendance_today']['present'] ?? 0 ?></span>
      <span class="text-xs text-slate-500">present ·</span>
      <span class="text-2xl font-bold text-rose-600"><?php echo $s['attendance_today']['absent'] ?? 0 ?></span>
      <span class="text-xs text-slate-500">absent</span>
    </div>
    <div class="mt-2 h-2 bg-slate-100 rounded-full overflow-hidden flex">
      <?php $tot = max(1, $s['attendance_today']['total_marked'] ?? 0); ?>
      <div class="bg-emerald-500" style="width:<?php echo round(100 * ($s['attendance_today']['present'] ?? 0) / $tot) ?>%"></div>
      <div class="bg-amber-400"  style="width:<?php echo round(100 * ($s['attendance_today']['late']    ?? 0) / $tot) ?>%"></div>
      <div class="bg-rose-500"   style="width:<?php echo round(100 * ($s['attendance_today']['absent']  ?? 0) / $tot) ?>%"></div>
    </div>
  </div>

  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-500 uppercase mb-3">Teachers</div>
    <div class="text-3xl font-bold text-slate-800"><?php echo $s['teachers']['total'] ?? 0 ?></div>
    <div class="text-xs text-slate-500 mt-1">Active teaching staff</div>
  </div>

  <div class="card p-5">
    <div class="text-xs font-semibold text-slate-500 uppercase mb-3">Batches Overview</div>
    <div class="flex gap-4 text-sm">
      <span class="text-slate-700"><b><?php echo $s['batches']['running'] ?? 0 ?></b> running</span>
      <span class="text-slate-700"><b><?php echo $s['batches']['upcoming'] ?? 0 ?></b> upcoming</span>
      <span class="text-slate-700"><b><?php echo $s['batches']['completed'] ?? 0 ?></b> done</span>
    </div>
  </div>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
  <div class="card p-5">
    <div class="font-semibold text-slate-700 mb-3">Monthly Fee Collection</div>
    <canvas id="chartCollection" height="180"></canvas>
  </div>
  <div class="card p-5">
    <div class="font-semibold text-slate-700 mb-3">New Admissions</div>
    <canvas id="chartAdmissions" height="180"></canvas>
  </div>
</div>

<!-- Recent Payments + Upcoming Exams -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
  <div class="card p-5">
    <div class="font-semibold text-slate-700 mb-3">Recent Payments</div>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Receipt</th><th>Student</th><th>Amount</th><th>Method</th><th>Date</th></tr></thead>
        <tbody>
          <?php if (empty($s['recent_payments'])): ?>
            <tr><td colspan="5" class="text-center text-slate-400 py-4">No payments yet.</td></tr>
          <?php else: foreach ($s['recent_payments'] as $p): ?>
            <tr>
              <td class="font-mono text-xs"><?php echo esc($p['receipt_number']) ?></td>
              <td><?php echo esc($p['student_name']) ?></td>
              <td class="font-semibold text-emerald-600"><?php echo money($p['amount']) ?></td>
              <td><span class="badge badge-blue"><?php echo esc($p['method']) ?></span></td>
              <td class="text-xs text-slate-500"><?php echo esc($p['payment_date']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card p-5">
    <div class="font-semibold text-slate-700 mb-3">Upcoming Exams</div>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Exam</th><th>Batch</th><th>Date</th></tr></thead>
        <tbody>
          <?php if (empty($s['upcoming_exams'])): ?>
            <tr><td colspan="3" class="text-center text-slate-400 py-4">No upcoming exams.</td></tr>
          <?php else: foreach ($s['upcoming_exams'] as $e): ?>
            <tr>
              <td><?php echo esc($e['exam_name']) ?></td>
              <td><?php echo esc($e['batch_name']) ?></td>
              <td class="text-xs text-slate-500"><?php echo esc($e['exam_date']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
const collectionData = <?php echo json_encode($s['monthly_collection'] ?? []) ?>;
const admissionsData = <?php echo json_encode($s['admissions_chart'] ?? []) ?>;

new Chart(document.getElementById('chartCollection'), {
  type: 'line',
  data: {
    labels: collectionData.map(d => d.month),
    datasets: [{
      label: '₹ Collected',
      data: collectionData.map(d => d.total),
      borderColor: '#4f46e5',
      backgroundColor: 'rgba(79,70,229,.1)',
      fill: true, tension: .35
    }]
  },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('chartAdmissions'), {
  type: 'bar',
  data: {
    labels: admissionsData.map(d => d.month),
    datasets: [{
      label: 'Admissions',
      data: admissionsData.map(d => d.count),
      backgroundColor: '#10b981',
      borderRadius: 6
    }]
  },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>

<?php echo $this->endSection() ?>