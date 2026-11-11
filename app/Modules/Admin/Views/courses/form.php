<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<div class="mb-4">
  <a href="<?= site_url('admin/courses') ?>" class="text-sm text-indigo-600">← Back</a>
  <h1 class="text-2xl font-bold text-slate-800 mt-2"><?= esc($title) ?></h1>
</div>

<form method="post" action="<?= $course ? site_url('admin/courses/'.$course['public_id']) : site_url('admin/courses') ?>" class="card p-6 space-y-4 max-w-2xl">
  <?= csrf_field() ?>
  <div>
    <label class="text-sm font-medium text-slate-600">Course Name *</label>
    <input name="course_name" required value="<?= old('course_name', $course['course_name'] ?? '') ?>" class="input mt-1">
  </div>
  <div>
    <label class="text-sm font-medium text-slate-600">Course Code *</label>
    <input name="course_code" required value="<?= old('course_code', $course['course_code'] ?? '') ?>"
           <?= $course ? 'readonly' : '' ?> class="input mt-1 font-mono">
  </div>
  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="text-sm font-medium text-slate-600">Duration</label>
      <input name="duration" value="<?= old('duration', $course['duration'] ?? '') ?>" class="input mt-1" placeholder="12 Months">
    </div>
    <div>
      <label class="text-sm font-medium text-slate-600">Fees (₹)</label>
      <input type="number" step="0.01" name="fees" value="<?= old('fees', $course['fees'] ?? '0') ?>" class="input mt-1">
    </div>
  </div>
  <div>
    <label class="text-sm font-medium text-slate-600">Description</label>
    <textarea name="description" rows="3" class="input mt-1"><?= old('description', $course['description'] ?? '') ?></textarea>
  </div>
  <div>
    <label class="text-sm font-medium text-slate-600">Status</label>
    <select name="status" class="input mt-1">
      <option value="active" <?= ($course['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="inactive" <?= ($course['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
    </select>
  </div>
  <div class="flex gap-2">
    <button class="btn-primary">Save</button>
    <a href="<?= site_url('admin/courses') ?>" class="px-4 py-2 text-sm text-slate-600">Cancel</a>
  </div>
</form>

<?= $this->endSection() ?>