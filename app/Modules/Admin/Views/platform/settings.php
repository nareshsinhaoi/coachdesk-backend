<?php echo $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?php echo $this->section('content') ?>

<h1 class="text-2xl font-bold text-slate-800 mb-4">Platform Settings</h1>

<form method="post" action="<?php echo site_url('admin/platform-settings') ?>" class="card p-6 space-y-4 max-w-2xl">
  <?php echo csrf_field() ?>

  <div>
    <label class="text-sm font-medium text-slate-600">Signup Enabled</label>
    <select name="signup_enabled" class="input mt-1">
      <option value="1" <?php echo ($settings['signup_enabled']['value'] ?? 1) == 1 ? 'selected' : '' ?>>Yes — allow new institutes to self-register</option>
      <option value="0" <?php echo ($settings['signup_enabled']['value'] ?? 1) == 0 ? 'selected' : '' ?>>No — invite only</option>
    </select>
  </div>

  <div>
    <label class="text-sm font-medium text-slate-600">Support Email</label>
    <input type="email" name="support_email"
           value="<?php echo esc($settings['support_email']['value'] ?? 'support@coachdesk.in') ?>"
           class="input mt-1">
  </div>

  <div>
    <label class="text-sm font-medium text-slate-600">Trial Period (days)</label>
    <input type="number" name="trial_days"
           value="<?php echo esc($settings['trial_days']['value'] ?? 14) ?>"
           class="input mt-1">
  </div>

  <div>
    <label class="text-sm font-medium text-slate-600">Maintenance Mode</label>
    <select name="maintenance_mode" class="input mt-1">
      <option value="0" <?php echo ($settings['maintenance_mode']['value'] ?? 0) == 0 ? 'selected' : '' ?>>Off — platform live</option>
      <option value="1" <?php echo ($settings['maintenance_mode']['value'] ?? 0) == 1 ? 'selected' : '' ?>>On — only super admin can log in</option>
    </select>
    <p class="text-xs text-slate-500 mt-1">When enabled, all tenant users see a maintenance page.</p>
  </div>

  <div class="pt-2 border-t border-slate-200">
    <button class="btn-primary">Save Settings</button>
  </div>
</form>

<?php echo $this->endSection() ?>