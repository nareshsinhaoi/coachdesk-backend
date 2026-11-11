<?php echo $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?php echo $this->section('content') ?>

<h1 class="text-2xl font-bold text-slate-800 mb-4">Reports</h1>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
  <div class="card p-5">
    <div class="font-semibold text-slate-700 mb-3">Fee Collection (Last 12 months)</div>
    <canvas id="feeChart" height="200"></canvas>
  </div>
  <div class="card p-5">
    <div class="font-semibold text-slate-700 mb-3">Attendance by Batch</div>
    <canvas id="attChart" height="200"></canvas>
  </div>
</div>

<script>
const feeData = <?php echo json_encode($feeMonthly) ?>;
new Chart(document.getElementById('feeChart'), {
  type: 'line',
  data: {
    labels: feeData.map(d => d.ym).reverse(),
    datasets: [{ label: '₹', data: feeData.map(d => d.total).reverse(), borderColor: '#4f46e5', fill: true, backgroundColor: 'rgba(79,70,229,.1)' }]
  },
  options: { plugins: { legend: { display: false } } }
});

const attData = <?php echo json_encode($attendanceByBatch) ?>;
new Chart(document.getElementById('attChart'), {
  type: 'bar',
  data: {
    labels: attData.map(d => d.batch_name),
    datasets: [
      { label: 'Present', data: attData.map(d => d.present), backgroundColor: '#10b981' },
      { label: 'Absent',  data: attData.map(d => d.absent),  backgroundColor: '#f43f5e' }
    ]
  },
  options: { scales: { x: { stacked: false }, y: { beginAtZero: true } } }
});
</script>

<?php echo $this->endSection() ?>