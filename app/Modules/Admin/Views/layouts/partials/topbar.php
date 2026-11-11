<?php $u = current_user(); ?>
<header class="h-16 bg-white border-b border-slate-200 flex items-center px-6 gap-4 shrink-0">
  <button id="sidebarToggle" class="md:hidden text-slate-600 text-xl"><i class="fas fa-bars"></i></button>
  <div class="flex-1">
    <input type="search" placeholder="Search…" class="input max-w-md" style="max-width:420px;">
  </div>

  <?php /* ?>
  <div class="flex items-center gap-3">
    <a href="<?php echo site_url('admin/fees/collect') ?>" class="btn-primary text-sm"><i class="fas fa-plus mr-1"></i> Collect Fee</a>
    <div class="w-9 h-9 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold text-sm">
      <?php echo esc(initials($u->name ?? 'U')) ?>
    </div>
  </div>
  <?php */ ?>
  
  <div class="flex items-center gap-3">
    <a href="<?php echo site_url('admin/fees/collect') ?>" class="btn-primary text-sm">
      <i class="fas fa-plus mr-1"></i> Collect Fee
    </a>

    <div class="relative" id="userMenuWrapper">
      <button id="userMenuBtn" class="w-9 h-9 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold text-sm hover:bg-indigo-700">
        <?php echo esc(initials($u->name ?? 'U')) ?>
      </button>
      <div id="userMenu" class="hidden absolute right-0 mt-2 w-56 bg-white border border-slate-200 rounded-lg shadow-lg py-1 z-50">
        <div class="px-4 py-2 border-b border-slate-100">
          <div class="text-sm font-semibold text-slate-800 truncate"><?php echo esc($u->name ?? '') ?></div>
          <div class="text-xs text-slate-500 truncate"><?php echo esc($u->email ?? '') ?></div>
        </div>
        <a href="<?php echo site_url('admin/profile') ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
          <i class="fas fa-user text-slate-400 w-4 mr-2"></i> My Profile
        </a>
        <a href="<?php echo site_url('admin/profile/change-password') ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
          <i class="fas fa-key text-slate-400 w-4 mr-2"></i> Change Password
        </a>
        <div class="border-t border-slate-100 my-1"></div>
        <a href="<?php echo site_url('admin/logout') ?>" class="block px-4 py-2 text-sm text-rose-600 hover:bg-rose-50">
          <i class="fas fa-right-from-bracket w-4 mr-2"></i> Logout
        </a>
      </div>
    </div>
  </div>

  <script>
    (function() {
      const btn = document.getElementById('userMenuBtn');
      const menu = document.getElementById('userMenu');
      if (!btn) return;
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.classList.toggle('hidden');
      });
      document.addEventListener('click', () => menu.classList.add('hidden'));
    })();
  </script>
</header>