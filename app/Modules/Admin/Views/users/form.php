<?php echo $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?php echo $this->section('content') ?>

<div class="mb-4">
  <a href="<?php echo site_url('admin/users') ?>" class="text-sm text-indigo-600">← All Users</a>
  <h1 class="text-2xl font-bold text-slate-800 mt-2">New User</h1>
</div>

<form method="post" action="<?php echo site_url('admin/users') ?>" class="card p-6 space-y-4 max-w-2xl">
  <?php echo csrf_field() ?>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="text-sm font-medium text-slate-600">Full Name *</label>
      <input name="name" required value="<?php echo old('name') ?>" class="input mt-1">
    </div>
    <div>
      <label class="text-sm font-medium text-slate-600">Email *</label>
      <input type="email" name="email" required value="<?php echo old('email') ?>" class="input mt-1">
    </div>
    <div>
      <label class="text-sm font-medium text-slate-600">Phone</label>
      <input name="phone" value="<?php echo old('phone') ?>" class="input mt-1">
    </div>
    <div>
      <label class="text-sm font-medium text-slate-600">Role *</label>
      <select name="role_id" required class="input mt-1">
        <option value="">— Select role —</option>
        <?php foreach ($roles as $r): ?>
          <option value="<?php echo $r['id'] ?>"><?php echo esc($r['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="md:col-span-2">
      <label class="text-sm font-medium text-slate-600">Branch <span class="text-xs text-slate-400">(optional — for branch-scoped roles)</span></label>
      <select name="branch_id" class="input mt-1">
        <option value="">All branches (tenant-wide)</option>
        <?php foreach ($branches as $b): ?>
          <option value="<?php echo $b['id'] ?>"><?php echo esc($b['name']) ?> (<?php echo esc($b['branch_code']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-800">
    <i class="fas fa-envelope mr-1"></i>
    A temporary password will be generated and emailed to the user along with their sign-in details.
  </div>

  <div class="flex gap-2 pt-2 border-t border-slate-200">
    <button class="btn-primary">Create User</button>
    <a href="<?php echo site_url('admin/users') ?>" class="px-4 py-2 text-sm text-slate-600">Cancel</a>
  </div>
</form>

<?php echo $this->endSection() ?>