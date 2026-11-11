<?php echo $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?php echo $this->section('content') ?>

<?php
// Status badge helper
// $statusBadge = function ($status) {
//     return match ($status) {
//         'active'   => 'badge-green',
//         'inactive' => 'badge-gray',
//         'locked'   => 'badge-red',
//         default    => 'badge-gray',
//     };
// };
$statusBadge = function ($status) {
    switch ($status) {
        case 'active':
            return 'badge-green';

        case 'inactive':
            return 'badge-gray';

        case 'locked':
            return 'badge-red';

        default:
            return 'badge-gray';
    }
};
?>

<!-- Header -->
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
  <div>
    <h1 class="text-2xl font-bold text-slate-800">Users</h1>
    <p class="text-sm text-slate-500">
      <?php echo count($users) ?> user<?php echo count($users) !== 1 ? 's' : '' ?>
      <?php if (is_super_admin()): ?>across all institutes<?php else: ?>in your institute<?php endif; ?>
    </p>
  </div>
  <?php if (can('users.create')): ?>
    <a href="<?php echo site_url('admin/users/create') ?>" class="btn-primary text-sm">
      <i class="fas fa-plus mr-1"></i> New User
    </a>
  <?php endif; ?>
</div>

<!-- Filters -->
<form method="get" class="card p-3 mb-4 flex gap-2 flex-wrap items-center">
  <input type="search" name="search" value="<?php echo esc($_GET['search'] ?? '') ?>"
         placeholder="Search name or email…" class="input flex-1 min-w-[220px]">

  <?php if (is_super_admin()): ?>
    <select name="tenant_id" class="input w-auto">
      <option value="">All Institutes</option>
      <?php foreach (($tenants ?? []) as $t): ?>
        <option value="<?php echo $t['id'] ?>" <?php echo ($_GET['tenant_id'] ?? '') == $t['id'] ? 'selected' : '' ?>>
          <?php echo esc($t['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  <?php endif; ?>

  <select name="status" class="input w-auto">
    <option value="">All Status</option>
    <?php foreach (['active', 'inactive', 'locked'] as $s): ?>
      <option value="<?php echo $s ?>" <?php echo ($_GET['status'] ?? '') === $s ? 'selected' : '' ?>>
        <?php echo ucfirst($s) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <button class="btn-primary text-sm">Filter</button>
  <?php if (! empty($_GET['search']) || ! empty($_GET['status']) || ! empty($_GET['tenant_id'])): ?>
    <a href="<?php echo site_url('admin/users') ?>" class="text-sm text-slate-600 self-center">Clear</a>
  <?php endif; ?>
</form>

<!-- Users table -->
<div class="card">
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <?php if (is_super_admin()): ?>
            <th>Institute</th>
          <?php endif; ?>
          <th>User</th>
          <th>Phone</th>
          <th>Roles</th>
          <th>Status</th>
          <th>Last Login</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr>
            <td colspan="<?php echo is_super_admin() ? 7 : 6 ?>" class="text-center text-slate-400 py-8">
              <?php if (! empty($_GET['search']) || ! empty($_GET['status'])): ?>
                No users match your filters.
                <a href="<?php echo site_url('admin/users') ?>" class="text-indigo-600 ml-1">Clear filters</a>
              <?php else: ?>
                No users yet.
                <?php if (can('users.create')): ?>
                  <a href="<?php echo site_url('admin/users/create') ?>" class="text-indigo-600 ml-1">Create the first one →</a>
                <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($users as $u): ?>
            <tr>
              <?php if (is_super_admin()): ?>
                <td class="text-xs">
                  <?php if (! empty($u['tenant_name'])): ?>
                    <div class="font-medium text-slate-700"><?php echo esc($u['tenant_name']) ?></div>
                    <div class="font-mono text-slate-400"><?php echo esc($u['tenant_code'] ?? '') ?></div>
                  <?php else: ?>
                    <span class="badge badge-blue text-xs">Platform</span>
                  <?php endif; ?>
                </td>
              <?php endif; ?>

              <!-- User -->
              <td>
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-bold shrink-0">
                    <?php echo esc(initials($u['name'])) ?>
                  </div>
                  <div>
                    <div class="font-medium text-slate-800"><?php echo esc($u['name']) ?></div>
                    <div class="text-xs text-slate-500"><?php echo esc($u['email']) ?></div>
                  </div>
                </div>
              </td>

              <!-- Phone -->
              <td class="text-sm text-slate-600">
                <?php echo esc($u['phone'] ?: '—') ?>
              </td>

              <!-- Roles -->
              <td>
                <?php
                  $roles = ! empty($u['roles']) ? explode(',', $u['roles']) : [];
                  if (empty($roles)): ?>
                    <span class="text-xs text-slate-400">—</span>
                <?php else: ?>
                  <?php foreach ($roles as $r): ?>
                    <span class="badge badge-blue text-xs"><?php echo esc(trim($r)) ?></span>
                  <?php endforeach; ?>
                <?php endif; ?>
              </td>

              <!-- Status -->
              <td>
                <span class="badge <?php echo $statusBadge($u['status']) ?> text-xs">
                  <?php echo esc($u['status']) ?>
                </span>
              </td>

              <!-- Last login -->
              <td class="text-xs text-slate-500">
                <?php if (! empty($u['last_login_at'])): ?>
                  <?php echo esc(substr($u['last_login_at'], 0, 16)) ?>
                <?php else: ?>
                  <span class="text-slate-400">Never</span>
                <?php endif; ?>
              </td>

              <!-- Actions -->
              <td class="text-right whitespace-nowrap">
                <?php if (is_super_admin() || can('users.edit')): ?>
                  <!-- Admins with user-edit permission: reset password + force logout -->
                  <form method="post"
                        action="<?php echo site_url('admin/users/' . $u['id'] . '/reset-password') ?>"
                        class="inline"
                        onsubmit="return confirm('Reset password for <?php echo esc($u['name']) ?>?\n\nA new temporary password will be generated, emailed to the user, and shown once here.')">
                    <?php echo csrf_field() ?>
                    <button class="text-amber-600 hover:text-amber-700 text-xs font-medium" title="Reset password">
                      <i class="fas fa-key"></i> Reset Pwd
                    </button>
                  </form>

                  <form method="post"
                        action="<?php echo site_url('admin/users/' . $u['id'] . '/force-logout') ?>"
                        class="inline ml-3"
                        onsubmit="return confirm('Force logout <?php echo esc($u['name']) ?> from all devices?')">
                    <?php echo csrf_field() ?>
                    <button class="text-rose-600 hover:text-rose-700 text-xs font-medium" title="Revoke all sessions">
                      <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                  </form>

                <?php elseif (can('users.edit')): ?>
                  <!-- Owner / Branch Admin: edit user -->
                  <a href="<?php echo site_url('admin/users/' . $u['id'] . '/edit') ?>"
                     class="text-indigo-600 hover:text-indigo-700 text-xs font-medium"
                     title="Edit user">
                    <i class="fas fa-edit"></i> Edit
                  </a>

                  <?php if (can('users.delete')): ?>
                    <form method="post"
                          action="<?php echo site_url('admin/users/' . $u['id'] . '/delete') ?>"
                          class="inline ml-3"
                          onsubmit="return confirm('Remove <?php echo esc($u['name']) ?>? They will lose access immediately.')">
                      <?php echo csrf_field() ?>
                      <button class="text-rose-600 hover:text-rose-700 text-xs font-medium" title="Remove user">
                        <i class="fas fa-trash"></i> Remove
                      </button>
                    </form>
                  <?php endif; ?>

                <?php else: ?>
                  <span class="text-xs text-slate-400">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Pagination (if pagination data passed from controller) -->
<?php if (! empty($pagination) && ($pagination['totalPages'] ?? 1) > 1): ?>
  <nav class="mt-4 flex justify-center gap-1 flex-wrap">
    <?php
      $qs = 'search=' . urlencode($_GET['search'] ?? '')
          . '&status=' . urlencode($_GET['status'] ?? '');
      if (is_super_admin() && ! empty($_GET['tenant_id'])) {
          $qs .= '&tenant_id=' . (int) $_GET['tenant_id'];
      }
    ?>
    <?php if (($pagination['page'] ?? 1) > 1): ?>
      <a href="?page=<?php echo $pagination['page'] - 1 ?>&<?php echo $qs ?>"
         class="px-3 py-1.5 text-sm rounded-lg border bg-white border-slate-200 text-slate-700 hover:bg-slate-50">
        ← Prev
      </a>
    <?php endif; ?>

    <?php
      $cur  = (int) ($pagination['page'] ?? 1);
      $tot  = (int) $pagination['totalPages'];
      $from = max(1, $cur - 2);
      $to   = min($tot, $cur + 2);
    ?>
    <?php if ($from > 1): ?>
      <a href="?page=1&<?php echo $qs ?>"
         class="px-3 py-1.5 text-sm rounded-lg border bg-white border-slate-200 text-slate-700 hover:bg-slate-50">1</a>
      <?php if ($from > 2): ?><span class="px-2 py-1.5 text-sm text-slate-400">…</span><?php endif; ?>
    <?php endif; ?>

    <?php for ($i = $from; $i <= $to; $i++): ?>
      <a href="?page=<?php echo $i ?>&<?php echo $qs ?>"
         class="px-3 py-1.5 text-sm rounded-lg border <?php echo $i === $cur ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50' ?>">
        <?php echo $i ?>
      </a>
    <?php endfor; ?>

    <?php if ($to < $tot): ?>
      <?php if ($to < $tot - 1): ?><span class="px-2 py-1.5 text-sm text-slate-400">…</span><?php endif; ?>
      <a href="?page=<?php echo $tot ?>&<?php echo $qs ?>"
         class="px-3 py-1.5 text-sm rounded-lg border bg-white border-slate-200 text-slate-700 hover:bg-slate-50"><?php echo $tot ?></a>
    <?php endif; ?>

    <?php if ($cur < $tot): ?>
      <a href="?page=<?php echo $cur + 1 ?>&<?php echo $qs ?>"
         class="px-3 py-1.5 text-sm rounded-lg border bg-white border-slate-200 text-slate-700 hover:bg-slate-50">
        Next →
      </a>
    <?php endif; ?>
  </nav>
<?php endif; ?>

<?php echo $this->endSection() ?>