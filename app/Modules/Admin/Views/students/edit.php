<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<div class="mb-4">
  <a href="<?= site_url('admin/students') ?>" class="text-sm text-indigo-600">← Back to Students</a>
  <h1 class="text-2xl font-bold text-slate-800 mt-2">Edit Student: <?= esc($student['student_code']) ?></h1>
</div>

<form method="post" action="<?= site_url('admin/students/'.$student['public_id']) ?>" class="space-y-4 max-w-4xl">
  <?= csrf_field() ?>
  <div class="card p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div><label class="text-sm font-medium text-slate-600">Name *</label>
        <input name="name" required value="<?= esc($student['name']) ?>" class="input mt-1"></div>
      <div><label class="text-sm font-medium text-slate-600">DOB</label>
        <input type="date" name="dob" value="<?= esc($student['dob']) ?>" class="input mt-1"></div>
      <div><label class="text-sm font-medium text-slate-600">Gender</label>
        <select name="gender" class="input mt-1">
          <?php foreach (['male','female','other'] as $g): ?>
            <option value="<?= $g ?>" <?= $student['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
          <?php endforeach; ?>
        </select></div>
      <div><label class="text-sm font-medium text-slate-600">Phone</label>
        <input name="phone" value="<?= esc($student['phone']) ?>" class="input mt-1"></div>
      <div><label class="text-sm font-medium text-slate-600">Email</label>
        <input type="email" name="email" value="<?= esc($student['email']) ?>" class="input mt-1"></div>
      <div><label class="text-sm font-medium text-slate-600">Status</label>
        <select name="status" class="input mt-1">
          <?php foreach (['active','inactive','completed','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $student['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="md:col-span-2"><label class="text-sm font-medium text-slate-600">Address</label>
        <input name="address_line1" value="<?= esc($student['address_line1']) ?>" class="input mt-1"></div>
      <div><label class="text-sm font-medium text-slate-600">City</label>
        <input name="city" value="<?= esc($student['city']) ?>" class="input mt-1"></div>
      <div><label class="text-sm font-medium text-slate-600">State</label>
        <input name="state" value="<?= esc($student['state']) ?>" class="input mt-1"></div>
      <div><label class="text-sm font-medium text-slate-600">Pincode</label>
        <input name="pincode" value="<?= esc($student['pincode']) ?>" class="input mt-1"></div>
    </div>
  </div>
  <div class="flex gap-2">
    <button class="btn-primary">Save Changes</button>
    <a href="<?= site_url('admin/students') ?>" class="px-4 py-2 text-sm text-slate-600">Cancel</a>
  </div>
</form>

<?= $this->endSection() ?>