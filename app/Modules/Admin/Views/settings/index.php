<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<h1 class="text-2xl font-bold text-slate-800 mb-4">Settings</h1>

<form method="post" action="<?= site_url('admin/settings') ?>" class="space-y-4 max-w-3xl">
  <?= csrf_field() ?>

  <div class="card p-6">
    <h2 class="font-semibold text-slate-700 mb-4">Institute Profile</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div><label class="text-sm font-medium text-slate-600">Name</label>
        <input name="name" value="<?= esc($tenant['name'] ?? '') ?>" class="input mt-1"></div>
      <div><label class="text-sm font-medium text-slate-600">Phone</label>
        <input name="phone" value="<?= esc($tenant['phone'] ?? '') ?>" class="input mt-1"></div>
      <div><label class="text-sm font-medium text-slate-600">Email</label>
        <input name="email" value="<?= esc($tenant['email'] ?? '') ?>" class="input mt-1"></div>
      <div><label class="text-sm font-medium text-slate-600">City</label>
        <input name="city" value="<?= esc($tenant['city'] ?? '') ?>" class="input mt-1"></div>
      <div class="md:col-span-2"><label class="text-sm font-medium text-slate-600">Address</label>
        <input name="address_line1" value="<?= esc($tenant['address_line1'] ?? '') ?>" class="input mt-1"></div>
      <div><label class="text-sm font-medium text-slate-600">State</label>
        <input name="state" value="<?= esc($tenant['state'] ?? '') ?>" class="input mt-1"></div>
    </div>
  </div>

  <div class="card p-6">
    <h2 class="font-semibold text-slate-700 mb-4">Branding</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div><label class="text-sm font-medium text-slate-600">Primary Color</label>
        <input type="color" name="primary_color" value="<?= esc($settings['branding']['primary_color'] ?? '#4f46e5') ?>" class="input mt-1 h-10"></div>
      <div><label class="text-sm font-medium text-slate-600">Receipt Footer</label>
        <input name="receipt_footer" value="<?= esc($settings['branding']['receipt_footer'] ?? '') ?>" class="input mt-1"></div>
    </div>
  </div>

  <button class="btn-primary">Save Settings</button>
</form>

<?= $this->endSection() ?>