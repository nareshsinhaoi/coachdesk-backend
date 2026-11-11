<?php $u = current_user();
$cur = uri_string(); ?>
<aside class="w-64 bg-slate-900 text-white flex flex-col shrink-0 hidden md:flex">

  <div class="p-4 border-b border-white/10">
    <div class="flex items-center text-lg font-bold tracking-tight bg-slate-200 w-full rounded-lg text-slate-200 shadow-sm">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center text-4xl">
            🎓
        </div>
        <div class="flex flex-col leading-tight">
            <span class="text-xl font-extrabold tracking-tight text-slate-900">
                Coach<span class="text-indigo-600">Desk</span>
            </span>
        </div>
    </div>
    <div class="mt-3 text-xs text-slate-400">
        <?php echo esc($u->name ?? '') ?><br>
        <span class="text-slate-500">
            <?php echo esc($u->email ?? '') ?>
        </span>
    </div>
  </div>

  <nav class="flex-1 overflow-y-auto p-3 space-y-1">

    <?php if (is_super_admin()): ?>

      <!-- SUPER ADMIN MENU -->
      <a href="<?php echo site_url('admin/dashboard') ?>" class="sidebar-link <?php echo $cur === 'admin/dashboard' ? 'active' : '' ?>">
        <i class="fas fa-gauge"></i> Platform Dashboard
      </a>
      <a href="<?php echo site_url('admin/tenants') ?>" class="sidebar-link <?php echo str_starts_with($cur, 'admin/tenants') ? 'active' : '' ?>">
        <i class="fas fa-building"></i> Institutes
      </a>
      <a href="<?php echo site_url('admin/plans') ?>" class="sidebar-link <?php echo str_starts_with($cur, 'admin/plans') ? 'active' : '' ?>">
        <i class="fas fa-box"></i> Subscription Plans
      </a>
      <a href="<?php echo site_url('admin/subscriptions') ?>" class="sidebar-link <?php echo str_starts_with($cur, 'admin/subscriptions') ? 'active' : '' ?>">
        <i class="fas fa-credit-card"></i> Subscriptions
      </a>
      <a href="<?php echo site_url('admin/support') ?>" class="sidebar-link <?php echo str_starts_with($cur, 'admin/support') ? 'active' : '' ?>">
        <i class="fas fa-headset"></i> Support Tickets
      </a>
      <a href="<?php echo site_url('admin/reports/revenue') ?>" class="sidebar-link <?php echo str_starts_with($cur, 'admin/reports/revenue') ? 'active' : '' ?>">
        <i class="fas fa-chart-line"></i> Revenue Report
      </a>
      <a href="<?php echo site_url('admin/audit-logs') ?>" class="sidebar-link <?php echo str_starts_with($cur, 'admin/audit-logs') ? 'active' : '' ?>">
        <i class="fas fa-clipboard-list"></i> Audit Logs
      </a>
      <a href="<?php echo site_url('admin/platform-settings') ?>" class="sidebar-link <?php echo str_starts_with($cur, 'admin/platform-settings') ? 'active' : '' ?>">
        <i class="fas fa-gear"></i> Platform Settings
      </a>
      <a href="<?php echo site_url('admin/broadcast') ?>" class="sidebar-link <?php echo str_starts_with($cur, 'admin/broadcast') ? 'active' : '' ?>">
        <i class="fas fa-bullhorn"></i> Broadcast
      </a>

    <?php else: ?>

      <!-- TENANT MENU (existing) -->
      <a href="<?php echo site_url('admin/dashboard') ?>" class="sidebar-link <?php echo $cur === 'admin/dashboard' ? 'active' : '' ?>">
        <i class="fas fa-gauge"></i> Dashboard
      </a>
      <?php if (can('students.view')): ?>
        <a href="<?php echo site_url('admin/students') ?>" class="sidebar-link <?php echo str_starts_with($cur, 'admin/students') ? 'active' : '' ?>">
          <i class="fas fa-user-graduate"></i> Students
        </a>
      <?php endif; ?>
    <?php endif; ?>
    <?php /*?>
    <a href="<?php echo site_url('admin/dashboard') ?>" class="sidebar-link <?php echo $cur === 'admin/dashboard' ? 'active' : '' ?>">
      <i class="fas fa-gauge"></i> Dashboard
    </a>

    <?php if (can('students.view')): ?>
    <a href="<?php echo site_url('admin/students') ?>" class="sidebar-link <?php echo str_starts_with($cur,'admin/students') ? 'active' : '' ?>">
      <i class="fas fa-user-graduate"></i> Students
    </a>
    <?php endif; */ ?>

    <?php if (can('courses.view')): ?>
      <a href="<?php echo site_url('admin/courses') ?>" class="sidebar-link <?php echo str_starts_with($cur, 'admin/courses') ? 'active' : '' ?>">
        <i class="fas fa-book"></i> Courses
      </a>
    <?php endif; ?>

    <?php if (can('batches.view')): ?>
      <a href="<?php echo site_url('admin/batches') ?>" class="sidebar-link <?php echo str_starts_with($cur, 'admin/batches') ? 'active' : '' ?>">
        <i class="fas fa-layer-group"></i> Batches
      </a>
    <?php endif; ?>

    <?php if (can('teachers.view')): ?>
      <a href="<?php echo site_url('admin/teachers') ?>" class="sidebar-link <?php echo str_starts_with($cur, 'admin/teachers') ? 'active' : '' ?>">
        <i class="fas fa-chalkboard-user"></i> Teachers
      </a>
    <?php endif; ?>

    <?php if (can('attendance.view')): ?>
      <a href="<?php echo site_url('admin/attendance') ?>" class="sidebar-link <?php echo str_starts_with($cur, 'admin/attendance') ? 'active' : '' ?>">
        <i class="fas fa-clipboard-check"></i> Attendance
      </a>
    <?php endif; ?>

    <?php if (can('fees.view')): ?>
      <a href="<?php echo site_url('admin/fees') ?>" class="sidebar-link <?php echo str_starts_with($cur, 'admin/fees') ? 'active' : '' ?>">
        <i class="fas fa-indian-rupee-sign"></i> Fees
      </a>
    <?php endif; ?>

    <?php if (can('exams.view')): ?>
      <a href="<?php echo site_url('admin/exams') ?>" class="sidebar-link <?php echo str_starts_with($cur, 'admin/exams') ? 'active' : '' ?>">
        <i class="fas fa-file-pen"></i> Exams
      </a>
    <?php endif; ?>

    <?php if (can('reports.view')): ?>
      <a href="<?php echo site_url('admin/reports') ?>" class="sidebar-link <?php echo str_starts_with($cur, 'admin/reports') ? 'active' : '' ?>">
        <i class="fas fa-chart-line"></i> Reports
      </a>
    <?php endif; ?>

    <div class="pt-3 mt-3 border-t border-white/10"></div>

    <?php if (can('branches.view')): ?>
      <a href="<?php echo site_url('admin/branches') ?>" class="sidebar-link <?php echo str_starts_with($cur, 'admin/branches') ? 'active' : '' ?>">
        <i class="fas fa-code-branch"></i> Branches
      </a>
    <?php endif; ?>

    <?php if (can('users.view')): ?>
      <a href="<?php echo site_url('admin/users') ?>" class="sidebar-link <?php echo str_starts_with($cur, 'admin/users') ? 'active' : '' ?>">
        <i class="fas fa-users"></i> Users
      </a>
    <?php endif; ?>

    <?php if (can('settings.manage')): ?>
      <a href="<?php echo site_url('admin/settings') ?>" class="sidebar-link <?php echo str_starts_with($cur, 'admin/settings') ? 'active' : '' ?>">
        <i class="fas fa-gear"></i> Settings
      </a>
    <?php endif; ?>

  </nav>

  <!-- <div class="p-3 border-t border-white/10">
    <a href="<?php echo site_url('admin/logout') ?>" class="sidebar-link">
      <i class="fas fa-right-from-bracket"></i> Logout
    </a>
  </div> -->
  <div class="p-3 border-t border-white/10">
    <a href="<?php echo site_url('admin/profile/change-password') ?>" class="sidebar-link">
      <i class="fas fa-key"></i> Change Password
    </a>
    <a href="<?php echo site_url('admin/logout') ?>" class="sidebar-link">
      <i class="fas fa-right-from-bracket"></i> Logout
    </a>
  </div>

</aside>