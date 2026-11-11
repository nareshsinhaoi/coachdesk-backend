<?php echo $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?php echo $this->section('content') ?>

<div class="mb-4">
  <a href="<?php echo site_url('admin/dashboard') ?>" class="text-sm text-indigo-600">← Dashboard</a>
  <h1 class="text-2xl font-bold text-slate-800 mt-2">Broadcast to All Institutes</h1>
  <p class="text-sm text-slate-500">Send an in-app notification to every active user across all tenants.</p>
</div>

<div class="alert alert-warning mb-4">
  <strong>⚠ Use sparingly.</strong> This creates one notification per active user. For 10 institutes × 10 users = 100 rows. All will see it immediately.
</div>

<form method="post" action="<?php echo site_url('admin/broadcast') ?>" class="card p-6 space-y-4 max-w-3xl">
  <?php echo csrf_field() ?>

  <div>
    <label class="text-sm font-medium text-slate-600">Title *</label>
    <input name="title" required maxlength="200" class="input mt-1"
           placeholder="e.g. Scheduled Maintenance on 15 Nov">
  </div>

  <div>
    <label class="text-sm font-medium text-slate-600">Message *</label>
    <textarea name="message" required rows="6" maxlength="2000" class="input mt-1"
              placeholder="CoachDesk will be unavailable on 15 Nov from 2-4 AM IST for maintenance."></textarea>
    <p class="text-xs text-slate-500 mt-1">Appears in the notifications bell for every tenant user.</p>
  </div>

  <div class="pt-2 border-t border-slate-200 flex gap-2">
    <button class="btn-primary"
            onclick="return confirm('Send this broadcast to ALL active users of ALL institutes?')">
      <i class="fas fa-bullhorn mr-1"></i> Send Broadcast
    </button>
    <a href="<?php echo site_url('admin/dashboard') ?>" class="px-4 py-2 text-sm text-slate-600">Cancel</a>
  </div>
</form>

<?php echo $this->endSection() ?>