<?php echo $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?php echo $this->section('content') ?>

<h1 class="text-2xl font-bold text-slate-800 mb-4">My Profile</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

  <!-- Profile card -->
  <div class="card p-6 lg:col-span-2">
    <div class="flex items-center gap-4 mb-6">
      <div class="w-16 h-16 rounded-full bg-indigo-600 text-white flex items-center justify-center text-2xl font-bold">
        <?php echo esc(initials($user->name)) ?>
      </div>
      <div>
        <div class="text-xl font-bold text-slate-800"><?php echo esc($user->name) ?></div>
        <div class="text-sm text-slate-500"><?php echo esc($user->email) ?></div>
      </div>
    </div>

    <form method="post" action="<?php echo site_url('admin/profile') ?>" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <?php echo csrf_field() ?>
      <div>
        <label class="text-sm font-medium text-slate-600">Full Name</label>
        <input name="name" value="<?php echo esc($user->name) ?>" class="input mt-1">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-600">Phone</label>
        <input name="phone" value="<?php echo esc($user->phone ?? '') ?>" class="input mt-1">
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium text-slate-600">Email <span class="text-xs text-slate-400">(contact admin to change)</span></label>
        <input value="<?php echo esc($user->email) ?>" disabled class="input mt-1 bg-slate-50 text-slate-500">
      </div>
      <div class="md:col-span-2">
        <button class="btn-primary">Save Changes</button>
      </div>
    </form>
  </div>

  <!-- Security card -->
  <div class="card p-6">
    <div class="font-semibold text-slate-700 mb-3">Security</div>
    <p class="text-sm text-slate-500 mb-4">Keep your account secure by changing your password regularly.</p>
    <a href="<?php echo site_url('admin/profile/change-password') ?>" class="btn-primary w-full text-center block">
      <i class="fas fa-key mr-1"></i> Change Password
    </a>

    <div class="mt-6 pt-6 border-t border-slate-200">
      <div class="text-xs uppercase tracking-wider text-slate-500 mb-2">Your Roles</div>
      <?php foreach ($roles as $r): ?>
        <div class="flex items-center gap-2 text-sm py-1">
          <span class="badge badge-blue"><?php echo esc($r['name']) ?></span>
          <?php if (! empty($r['branch_id'])): ?>
            <span class="text-xs text-slate-400">Branch-scoped</span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <?php if (empty($roles)): ?>
        <p class="text-sm text-slate-400">No roles assigned.</p>
      <?php endif; ?>
    </div>

    <div class="mt-6 pt-6 border-t border-slate-200 text-xs text-slate-500 space-y-1">
      <div>Last login: <?php echo $user->last_login_at ? esc($user->last_login_at) : 'Never' ?></div>
      <div>Account created: <?php echo esc(substr($user->created_at, 0, 10)) ?></div>
    </div>
  </div>

</div>

<?php echo $this->endSection() ?>