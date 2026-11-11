<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<h1 class="text-2xl font-bold text-slate-800 mb-4">Subscriptions</h1>

<div class="card mb-4">
  <div class="p-4 border-b border-slate-200 font-semibold text-slate-700">Active Subscriptions</div>
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr><th>Institute</th><th>Plan</th><th>Price</th><th>Start</th><th>End</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php if (empty($subs)): ?>
          <tr><td colspan="6" class="text-center text-slate-400 py-6">No subscriptions yet.</td></tr>
        <?php else: foreach ($subs as $s): ?>
          <tr>
            <td>
              <div class="font-medium"><?= esc($s['tenant_name']) ?></div>
              <div class="text-xs text-slate-500 font-mono"><?= esc($s['tenant_code']) ?></div>
            </td>
            <td><span class="badge badge-blue"><?= esc($s['plan_name']) ?></span></td>
            <td><?= money($s['price']) ?></td>
            <td class="text-xs"><?= esc($s['start_date']) ?></td>
            <td class="text-xs"><?= esc($s['end_date']) ?></td>
            <td>
              <span class="badge <?= $s['status']==='active' ? 'badge-green' : ($s['status']==='trial' ? 'badge-blue' : 'badge-gray') ?>">
                <?= esc($s['status']) ?>
              </span>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="p-4 border-b border-slate-200 font-semibold text-slate-700">Recent Subscription Payments</div>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Institute</th><th>Amount</th><th>Method</th><th>Status</th><th>Paid At</th></tr></thead>
      <tbody>
        <?php if (empty($payments)): ?>
          <tr><td colspan="5" class="text-center text-slate-400 py-6">No payments recorded.</td></tr>
        <?php else: foreach ($payments as $p): ?>
          <tr>
            <td><?= esc($p['tenant_name']) ?></td>
            <td class="text-emerald-600 font-semibold"><?= money($p['amount']) ?></td>
            <td><span class="badge badge-blue"><?= esc($p['method']) ?></span></td>
            <td>
              <span class="badge <?= $p['status']==='success' ? 'badge-green' : 'badge-amber' ?>">
                <?= esc($p['status']) ?>
              </span>
            </td>
            <td class="text-xs text-slate-500"><?= esc($p['paid_at'] ?? '—') ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>