<?php echo $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?php echo $this->section('content') ?>

<div class="mb-4">
  <a href="<?php echo site_url('admin/profile') ?>" class="text-sm text-indigo-600">← Back to Profile</a>
  <h1 class="text-2xl font-bold text-slate-800 mt-2">Change Password</h1>
</div>

<form method="post" action="<?php echo site_url('admin/profile/change-password') ?>" class="card p-6 space-y-4 max-w-xl">
  <?php echo csrf_field() ?>

  <div>
    <label class="text-sm font-medium text-slate-600">Current Password *</label>
    <input type="password" name="current_password" required class="input mt-1" autocomplete="current-password">
    <p class="text-xs text-slate-500 mt-1">Your existing password.</p>
  </div>

  <div>
    <label class="text-sm font-medium text-slate-600">New Password *</label>
    <input type="password" name="new_password" required minlength="8" class="input mt-1" autocomplete="new-password">
    <p class="text-xs text-slate-500 mt-1">Minimum 8 characters. Use a mix of letters, numbers, and symbols.</p>
  </div>

  <div>
    <label class="text-sm font-medium text-slate-600">Confirm New Password *</label>
    <input type="password" name="confirm_password" required minlength="8" class="input mt-1" autocomplete="new-password">
  </div>

  <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-800">
    <i class="fas fa-info-circle mr-1"></i>
    After changing your password, you'll be signed out from all other devices. A confirmation email will be sent to <strong><?php echo esc(current_user()->email) ?></strong>.
  </div>

  <div class="flex gap-2 pt-2 border-t border-slate-200">
    <button class="btn-primary">Change Password</button>
    <a href="<?php echo site_url('admin/profile') ?>" class="px-4 py-2 text-sm text-slate-600">Cancel</a>
  </div>
</form>

<?php echo $this->endSection() ?>