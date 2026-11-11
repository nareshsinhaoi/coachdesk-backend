<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<div class="mb-4">
  <a href="<?= site_url('admin/plans') ?>" class="text-sm text-indigo-600">← All Plans</a>
  <h1 class="text-2xl font-bold text-slate-800 mt-2"><?= esc($title) ?></h1>
</div>

<form method="post"
      action="<?= $plan ? site_url('admin/plans/'.$plan['id']) : site_url('admin/plans') ?>"
      class="card p-6 space-y-4 max-w-2xl">
  <?= csrf_field() ?>

  <div>
    <label class="text-sm font-medium text-slate-600">Plan Name *</label>
    <input name="name" required value="<?= old('name', $plan['name'] ?? '') ?>" class="input mt-1">
  </div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="text-sm font-medium text-slate-600">Price (₹)</label>
      <input type="number" step="0.01" name="price" value="<?= old('price', $plan['price'] ?? '0') ?>" class="input mt-1">
    </div>
    <div>
      <label class="text-sm font-medium text-slate-600">Billing Cycle</label>
      <select name="billing_cycle" class="input mt-1">
        <?php foreach (['monthly','quarterly','yearly'] as $c): ?>
          <option value="<?= $c ?>" <?= ($plan['billing_cycle'] ?? '') === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="grid grid-cols-3 gap-4">
    <div>
      <label class="text-sm font-medium text-slate-600">Max Students</label>
      <input type="number" name="max_students" value="<?= old('max_students', $plan['max_students'] ?? 100) ?>" class="input mt-1">
    </div>
    <div>
      <label class="text-sm font-medium text-slate-600">Max Staff</label>
      <input type="number" name="max_staff" value="<?= old('max_staff', $plan['max_staff'] ?? 5) ?>" class="input mt-1">
    </div>
    <div>
      <label class="text-sm font-medium text-slate-600">Max Branches</label>
      <input type="number" name="max_branches" value="<?= old('max_branches', $plan['max_branches'] ?? 1) ?>" class="input mt-1">
    </div>
  </div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="text-sm font-medium text-slate-600">Sort Order</label>
      <input type="number" name="sort_order" value="<?= old('sort_order', $plan['sort_order'] ?? 0) ?>" class="input mt-1">
    </div>
    <?php if ($plan): ?>
    <div>
      <label class="text-sm font-medium text-slate-600">Active</label>
      <select name="is_active" class="input mt-1">
        <option value="1" <?= ($plan['is_active'] ?? 1) == 1 ? 'selected' : '' ?>>Yes</option>
        <option value="0" <?= ($plan['is_active'] ?? 1) == 0 ? 'selected' : '' ?>>No</option>
      </select>
    </div>
    <?php endif; ?>
  </div>

  <div class="flex gap-2">
    <button class="btn-primary"><?= $plan ? 'Save Changes' : 'Create Plan' ?></button>
    <a href="<?= site_url('admin/plans') ?>" class="px-4 py-2 text-sm text-slate-600">Cancel</a>
  </div>
</form>

<?= $this->endSection() ?>