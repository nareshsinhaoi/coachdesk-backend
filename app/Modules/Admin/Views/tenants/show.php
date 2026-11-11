<?php echo $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?php echo $this->section('content') ?>

<?php
// Helper: status → badge class
// $statusBadge = function ($status) {
//     return match ($status) {
//         'active'    => 'badge-green',
//         'trial'     => 'badge-blue',
//         'suspended' => 'badge-amber',
//         'expired'   => 'badge-red',
//         'cancelled' => 'badge-gray',
//         default     => 'badge-gray',
//     };
// };
$statusBadge = function ($status) {
    switch ($status) {
        case 'active': return 'badge-green';
        case 'trial': return 'badge-blue';
        case 'suspended': return 'badge-amber';
        case 'expired': return 'badge-red';
        case 'cancelled': return 'badge-gray';
        default:
            return 'badge-gray';
    }
};
?>

<!-- Breadcrumb + header -->
<div class="mb-4">
  <a href="<?php echo site_url('admin/tenants') ?>" class="text-sm text-indigo-600">← All Institutes</a>

  <div class="flex items-start justify-between mt-2 flex-wrap gap-3">
    <div>
      <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-3">
        <?php echo esc($tenant['name']) ?>
        <span class="badge <?php echo $statusBadge($tenant['status']) ?> text-xs">
          <?php echo esc($tenant['status']) ?>
        </span>
      </h1>
      <p class="text-sm text-slate-500 mt-1">
        <span class="font-mono"><?php echo esc($tenant['code']) ?></span>
        · <?php echo esc($tenant['email']) ?>
        <?php if (! empty($tenant['phone'])): ?>
          · <?php echo esc($tenant['phone']) ?>
        <?php endif; ?>
      </p>
    </div>

    <!-- Action buttons -->
    <div class="flex gap-2 flex-wrap">

      <a href="<?php echo site_url('admin/tenants/'.$tenant['id'].'/edit') ?>"
         class="px-4 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg">
        <i class="fas fa-edit mr-1"></i> Edit
      </a>

      <?php if (in_array($tenant['status'], ['active','trial'])): ?>
        <form method="post" action="<?php echo site_url('admin/tenants/'.$tenant['id'].'/status') ?>"
              class="inline"
              onsubmit="return confirm('Suspend <?php echo esc($tenant['name']) ?>?\n\nAll users of this institute will be logged out immediately.')">
          <?php echo csrf_field() ?>
          <input type="hidden" name="status" value="suspended">
          <button class="px-4 py-2 text-sm bg-amber-500 hover:bg-amber-600 text-white rounded-lg">
            <i class="fas fa-pause mr-1"></i> Suspend
          </button>
        </form>
      <?php else: ?>
        <form method="post" action="<?php echo site_url('admin/tenants/'.$tenant['id'].'/status') ?>" class="inline">
          <?php echo csrf_field() ?>
          <input type="hidden" name="status" value="active">
          <button class="px-4 py-2 text-sm bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg">
            <i class="fas fa-play mr-1"></i> Activate
          </button>
        </form>
      <?php endif; ?>

      <form method="post" action="<?php echo site_url('admin/tenants/'.$tenant['id'].'/delete') ?>"
            class="inline"
            onsubmit="return confirm('Delete <?php echo esc($tenant['name']) ?>?\n\nThis is a soft delete — data is retained for 90 days. All users lose access immediately.')">
        <?php echo csrf_field() ?>
        <button class="px-4 py-2 text-sm bg-rose-500 hover:bg-rose-600 text-white rounded-lg">
          <i class="fas fa-trash mr-1"></i> Delete
        </button>
      </form>

    </div>
  </div>
</div>

<?php if ($tenant['status'] === 'suspended'): ?>
  <div class="alert alert-warning mb-4">
    <strong>⚠ This institute is suspended.</strong>
    All users are logged out and cannot access the platform. Click <strong>Activate</strong> above to restore access.
  </div>
<?php elseif ($tenant['status'] === 'trial' && ! empty($tenant['trial_ends_at'])): ?>
  <div class="alert alert-info mb-4">
    <strong>Trial ends:</strong> <?php echo esc($tenant['trial_ends_at']) ?>
    (<?php echo max(0, (int) ((strtotime($tenant['trial_ends_at']) - time()) / 86400)) ?> days remaining)
  </div>
<?php elseif ($tenant['status'] === 'expired'): ?>
  <div class="alert alert-error mb-4">
    <strong>Subscription expired.</strong> Users cannot log in until the subscription is renewed.
  </div>
<?php endif; ?>

<!-- KPI cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
  <div class="card p-4">
    <div class="text-xs uppercase tracking-wider text-slate-500">Students</div>
    <div class="text-3xl font-bold text-slate-800 mt-1"><?php echo number_format($stats['students']) ?></div>
  </div>
  <div class="card p-4">
    <div class="text-xs uppercase tracking-wider text-slate-500">Branches</div>
    <div class="text-3xl font-bold text-slate-800 mt-1"><?php echo $stats['branches'] ?></div>
  </div>
  <div class="card p-4">
    <div class="text-xs uppercase tracking-wider text-slate-500">Users</div>
    <div class="text-3xl font-bold text-slate-800 mt-1"><?php echo $stats['users'] ?></div>
  </div>
  <div class="card p-4">
    <div class="text-xs uppercase tracking-wider text-slate-500">Courses</div>
    <div class="text-3xl font-bold text-slate-800 mt-1"><?php echo $stats['courses'] ?></div>
  </div>
</div>

<!-- Subscription + contact info row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

  <!-- Subscription -->
  <div class="card p-5 lg:col-span-2">
    <div class="text-xs uppercase tracking-wider text-slate-500 mb-2">Current Subscription</div>
    <?php if ($subscription): ?>
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <div class="text-xl font-bold text-slate-800"><?php echo esc($subscription['plan_name']) ?></div>
          <div class="text-sm text-slate-500 mt-0.5">
            <?php echo money($subscription['price']) ?> / <?php echo esc($subscription['plan_name']) ?>
            <?php if (! empty($subscription['auto_renew'])): ?> · auto-renew on<?php endif; ?>
          </div>
        </div>
        <div class="text-right">
          <span class="badge <?php echo $statusBadge($subscription['status']) ?> text-xs">
            <?php echo esc($subscription['status']) ?>
          </span>
          <div class="text-xs text-slate-500 mt-1">
            <?php echo esc($subscription['start_date']) ?> → <?php echo esc($subscription['end_date']) ?>
          </div>
        </div>
      </div>
    <?php else: ?>
      <p class="text-sm text-slate-400">No active subscription.</p>
    <?php endif; ?>
  </div>

  <!-- Contact / address -->
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500 mb-2">Contact</div>
    <div class="text-sm text-slate-700 space-y-1">
      <?php if (! empty($tenant['website'])): ?>
        <div><i class="fas fa-globe text-slate-400 w-4"></i> <?php echo esc($tenant['website']) ?></div>
      <?php endif; ?>
      <?php if (! empty($tenant['address_line1'])): ?>
        <div><i class="fas fa-map-marker-alt text-slate-400 w-4"></i>
          <?php echo esc($tenant['address_line1']) ?>
          <?php if (! empty($tenant['city'])): ?>, <?php echo esc($tenant['city']) ?><?php endif; ?>
          <?php if (! empty($tenant['state'])): ?>, <?php echo esc($tenant['state']) ?><?php endif; ?>
        </div>
      <?php endif; ?>
      <div class="text-xs text-slate-400 mt-2">
        Created <?php echo esc(substr($tenant['created_at'], 0, 10)) ?>
      </div>
    </div>
  </div>

</div>

<!-- Branches + Users -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

  <!-- Branches -->
  <div class="card">
    <div class="p-4 border-b border-slate-200 font-semibold text-slate-700 flex items-center justify-between">
      <span>Branches</span>
      <span class="text-xs text-slate-500"><?php echo count($branches) ?> total</span>
    </div>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr><th>Code</th><th>Name</th><th>City</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php if (empty($branches)): ?>
            <tr><td colspan="4" class="text-center text-slate-400 py-6">No branches yet.</td></tr>
          <?php else: foreach ($branches as $b): ?>
            <tr>
              <td class="font-mono text-xs"><?php echo esc($b['branch_code']) ?></td>
              <td class="font-medium"><?php echo esc($b['name']) ?></td>
              <td class="text-sm text-slate-500"><?php echo esc($b['city'] ?? '—') ?></td>
              <td>
                <span class="badge <?php echo $b['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>">
                  <?php echo esc($b['status']) ?>
                </span>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Users -->
  <div class="card">
    <div class="p-4 border-b border-slate-200 font-semibold text-slate-700 flex items-center justify-between">
      <span>Users</span>
      <span class="text-xs text-slate-500"><?php echo count($users) ?> total</span>
    </div>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr>
            <th>User</th>
            <th>Roles</th>
            <th>Last Login</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
            <tr><td colspan="4" class="text-center text-slate-400 py-6">No users yet.</td></tr>
          <?php else: foreach ($users as $u): ?>
            <tr>
              <td>
                <div class="font-medium text-sm"><?php echo esc($u['name']) ?></div>
                <div class="text-xs text-slate-500"><?php echo esc($u['email']) ?></div>
              </td>
              <td>
                <?php
                  $roles = $u['roles'] ? explode(',', $u['roles']) : [];
                  foreach ($roles as $r):
                ?>
                  <span class="badge badge-blue text-xs"><?php echo esc($r) ?></span>
                <?php endforeach; ?>
                <?php if (empty($roles)): ?>
                  <span class="text-xs text-slate-400">—</span>
                <?php endif; ?>
              </td>
              <td class="text-xs text-slate-500">
                <?php echo $u['last_login_at'] ? esc(substr($u['last_login_at'], 0, 16)) : 'Never' ?>
              </td>
              <td class="text-right whitespace-nowrap">
                <form method="post"
                      action="<?php echo site_url('admin/users/'.$u['id'].'/reset-password') ?>"
                      class="inline"
                      onsubmit="return confirm('Reset password for <?php echo esc($u['name']) ?>?\n\nA new temporary password will be generated and shown to you once.')">
                  <?php echo csrf_field() ?>
                  <button class="text-amber-600 hover:text-amber-700 text-xs font-medium"
                          title="Reset password">
                    <i class="fas fa-key"></i> Reset Pwd
                  </button>
                </form>
                <form method="post"
                      action="<?php echo site_url('admin/users/'.$u['id'].'/force-logout') ?>"
                      class="inline ml-3"
                      onsubmit="return confirm('Force-logout <?php echo esc($u['name']) ?> from all devices?')">
                  <?php echo csrf_field() ?>
                  <button class="text-rose-600 hover:text-rose-700 text-xs font-medium"
                          title="Revoke all sessions">
                    <i class="fas fa-sign-out-alt"></i> Logout
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php echo $this->endSection() ?>