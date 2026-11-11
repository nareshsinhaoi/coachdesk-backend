<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<div class="mb-4">
  <a href="<?= site_url('admin/tenants') ?>" class="text-sm text-indigo-600">← All Institutes</a>
  <h1 class="text-2xl font-bold text-slate-800 mt-2"><?= esc($title) ?></h1>
</div>

<form method="post" action="<?= $tenant ? site_url('admin/tenants/'.$tenant['id']) : site_url('admin/tenants') ?>" class="space-y-4 max-w-4xl">
  <?= csrf_field() ?>

  <!-- INSTITUTE -->
  <div class="card p-6">
    <h2 class="font-semibold text-slate-700 mb-4">Institute Details</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div><label class="text-sm font-medium text-slate-600">Name *</label>
        <input name="name" required value="<?= old('name', $tenant['name'] ?? '') ?>" class="input mt-1"></div>
      <div><label class="text-sm font-medium text-slate-600">Code * <span class="text-xs text-slate-400">(unique slug)</span></label>
        <input name="code" required value="<?= old('code', $tenant['code'] ?? '') ?>" <?= $tenant ? 'readonly' : '' ?> class="input mt-1 font-mono"></div>
      <div><label class="text-sm font-medium text-slate-600">Email *</label>
        <input type="email" name="email" required value="<?= old('email', $tenant['email'] ?? '') ?>" class="input mt-1"></div>
      <div><label class="text-sm font-medium text-slate-600">Phone</label>
        <input name="phone" value="<?= old('phone', $tenant['phone'] ?? '') ?>" class="input mt-1"></div>
      <div class="md:col-span-2"><label class="text-sm font-medium text-slate-600">Website</label>
        <input name="website" value="<?= old('website', $tenant['website'] ?? '') ?>" class="input mt-1"></div>
      <div class="md:col-span-2"><label class="text-sm font-medium text-slate-600">Address</label>
        <input name="address_line1" value="<?= old('address_line1', $tenant['address_line1'] ?? '') ?>" class="input mt-1"></div>
      <div><label class="text-sm font-medium text-slate-600">City</label>
        <input name="city" value="<?= old('city', $tenant['city'] ?? '') ?>" class="input mt-1"></div>
      <div><label class="text-sm font-medium text-slate-600">State</label>
        <input name="state" value="<?= old('state', $tenant['state'] ?? '') ?>" class="input mt-1"></div>
      <div><label class="text-sm font-medium text-slate-600">Pincode</label>
        <input name="pincode" value="<?= old('pincode', $tenant['pincode'] ?? '') ?>" class="input mt-1"></div>
    </div>
  </div>

  <?php if (! $tenant): ?>
  <!-- OWNER (only on create) -->
  <div class="card p-6">
    <h2 class="font-semibold text-slate-700 mb-4">Owner Account</h2>
    <p class="text-xs text-slate-500 mb-3">A temporary password will be auto-generated. Share with the institute owner via a secure channel.</p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div><label class="text-sm font-medium text-slate-600">Owner Name *</label>
        <input name="owner_name" required value="<?= old('owner_name') ?>" class="input mt-1"></div>
      <div><label class="text-sm font-medium text-slate-600">Owner Email *</label>
        <input type="email" name="owner_email" required value="<?= old('owner_email') ?>" class="input mt-1"></div>
      <div><label class="text-sm font-medium text-slate-600">Owner Phone</label>
        <input name="owner_phone" value="<?= old('owner_phone') ?>" class="input mt-1"></div>
    </div>
  </div>
  <?php endif; ?>

  <!-- SUBSCRIPTION -->
  <div class="card p-6">
    <h2 class="font-semibold text-slate-700 mb-4">Subscription</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium text-slate-600">Plan *</label>
        <select name="plan_id" required class="input mt-1">
          <?php foreach ($plans as $p): ?>
            <option value="<?= $p['id'] ?>" <?= ($currentPlanId ?? 0) == $p['id'] ? 'selected' : '' ?>>
              <?= esc($p['name']) ?> — <?= money($p['price']) ?>/<?= esc($p['billing_cycle']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium text-slate-600">Status</label>
        <select name="status" class="input mt-1">
          <?php foreach (['trial','active','suspended','expired','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= ($tenant['status'] ?? 'trial') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <div class="flex gap-2">
    <button class="btn-primary"><?= $tenant ? 'Save Changes' : 'Create Institute' ?></button>
    <a href="<?= site_url('admin/tenants') ?>" class="px-4 py-2 text-sm text-slate-600">Cancel</a>
  </div>
</form>

<?= $this->endSection() ?>