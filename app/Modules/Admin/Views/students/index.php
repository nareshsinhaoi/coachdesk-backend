<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<div class="flex items-center justify-between mb-4">
  <div>
    <h1 class="text-2xl font-bold text-slate-800">Students</h1>
    <p class="text-sm text-slate-500"><?= number_format($pagination['total']) ?> total enrolled</p>
  </div>
  <?php if (can('students.create')): ?>
  <a href="<?= site_url('admin/students/create') ?>" class="btn-primary text-sm">
    <i class="fas fa-plus mr-1"></i> Admit Student
  </a>
  <?php endif; ?>
</div>

<form method="get" class="card p-3 mb-4 flex gap-2 flex-wrap">
  <input type="search" name="search" value="<?= esc($filters['search'] ?? '') ?>" placeholder="Search name, code, phone…" class="input flex-1 min-w-[220px]">
  <select name="status" class="input w-auto">
    <option value="">All Status</option>
    <?php foreach (['active','inactive','completed','cancelled'] as $s): ?>
      <option <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn-primary text-sm">Filter</button>
</form>

<div class="card">
  <div class="table-wrap">
    <table class="data">
      <thead><tr>
        <th>Student Code</th><th>Name</th><th>Phone</th><th>Branch</th>
        <th>Admission</th><th>Status</th><th class="text-right">Actions</th>
      </tr></thead>
      <tbody>
        <?php if (empty($students)): ?>
          <tr><td colspan="7" class="text-center text-slate-400 py-8">No students found.</td></tr>
        <?php else: foreach ($students as $s): ?>
          <tr>
            <td class="font-mono text-xs"><?= esc($s['student_code']) ?></td>
            <td>
              <div class="font-medium"><?= esc($s['name']) ?></div>
              <div class="text-xs text-slate-500"><?= esc($s['email'] ?? '') ?></div>
            </td>
            <td><?= esc($s['phone'] ?? '—') ?></td>
            <td><?= esc($s['branch_name'] ?? '—') ?></td>
            <td class="text-xs text-slate-500"><?= esc($s['admission_date'] ?? '—') ?></td>
            <td>
              <span class="badge <?= $s['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>">
                <?= esc($s['status']) ?>
              </span>
            </td>
            <td class="text-right">
              <?php if (can('students.edit')): ?>
              <a href="<?= site_url('admin/students/'.$s['public_id'].'/edit') ?>" class="text-indigo-600 text-xs font-medium mr-3">Edit</a>
              <?php endif; ?>
              <?php if (can('students.delete')): ?>
              <form method="post" action="<?= site_url('admin/students/'.$s['public_id'].'/delete') ?>" class="inline" onsubmit="return confirm('Remove this student?')">
                <?= csrf_field() ?>
                <button class="text-rose-600 text-xs font-medium">Delete</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (($pagination['totalPages'] ?? 1) > 1): ?>
<nav class="mt-4 flex justify-center gap-1">
  <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
    <a href="?page=<?= $i ?>&search=<?= urlencode($filters['search'] ?? '') ?>&status=<?= urlencode($filters['status'] ?? '') ?>"
       class="px-3 py-1.5 text-sm rounded-lg border <?= $i === (int)$pagination['page'] ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white border-slate-200 text-slate-700' ?>">
      <?= $i ?>
    </a>
  <?php endfor; ?>
</nav>
<?php endif; ?>

<?= $this->endSection() ?>