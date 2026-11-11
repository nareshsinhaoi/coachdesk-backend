<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<div class="mb-4">
  <a href="<?= site_url('admin/students') ?>" class="text-sm text-indigo-600">← Back to Students</a>
  <h1 class="text-2xl font-bold text-slate-800 mt-2">Admit New Student</h1>
</div>

<form method="post" action="<?= site_url('admin/students') ?>" class="space-y-4 max-w-4xl">
  <?= csrf_field() ?>

  <div class="card p-6">
    <h2 class="font-semibold text-slate-700 mb-4">Student Details</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium text-slate-600">Full Name *</label>
        <input name="name" required value="<?= old('name') ?>" class="input mt-1">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-600">Date of Birth</label>
        <input type="date" name="dob" value="<?= old('dob') ?>" class="input mt-1">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-600">Gender</label>
        <select name="gender" class="input mt-1">
          <option value="">— Select —</option>
          <option value="male">Male</option>
          <option value="female">Female</option>
          <option value="other">Other</option>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium text-slate-600">Phone</label>
        <input name="phone" value="<?= old('phone') ?>" class="input mt-1">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-600">Email</label>
        <input type="email" name="email" value="<?= old('email') ?>" class="input mt-1">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-600">Admission Date</label>
        <input type="date" name="admission_date" value="<?= old('admission_date', date('Y-m-d')) ?>" class="input mt-1">
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium text-slate-600">Address</label>
        <input name="address_line1" value="<?= old('address_line1') ?>" class="input mt-1">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-600">City</label>
        <input name="city" value="<?= old('city') ?>" class="input mt-1">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-600">State</label>
        <input name="state" value="<?= old('state') ?>" class="input mt-1">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-600">Pincode</label>
        <input name="pincode" value="<?= old('pincode') ?>" class="input mt-1">
      </div>
    </div>
  </div>

  <div class="card p-6">
    <h2 class="font-semibold text-slate-700 mb-4">Enrollment</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium text-slate-600">Branch *</label>
        <select name="branch_id" required class="input mt-1">
          <option value="">— Select —</option>
          <?php foreach ($branches as $b): ?>
            <option value="<?= $b['id'] ?>" <?= old('branch_id') == $b['id'] ? 'selected' : '' ?>>
              <?= esc($b['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium text-slate-600">Batch (optional)</label>
        <select name="batch_id" class="input mt-1">
          <option value="">— Not assigned —</option>
          <?php foreach ($batches as $b): ?>
            <option value="<?= $b['id'] ?>"><?= esc($b['batch_name']) ?> (<?= esc($b['batch_code']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <div class="card p-6">
    <h2 class="font-semibold text-slate-700 mb-4">Guardian</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium text-slate-600">Guardian Name</label>
        <input name="guardian_name" value="<?= old('guardian_name') ?>" class="input mt-1">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-600">Relationship</label>
        <select name="guardian_relationship" class="input mt-1">
          <option value="father">Father</option>
          <option value="mother">Mother</option>
          <option value="guardian">Guardian</option>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium text-slate-600">Guardian Phone</label>
        <input name="guardian_phone" value="<?= old('guardian_phone') ?>" class="input mt-1">
      </div>
      <div>
        <label class="text-sm font-medium text-slate-600">Guardian Email</label>
        <input name="guardian_email" value="<?= old('guardian_email') ?>" class="input mt-1">
      </div>
    </div>
  </div>

  <div class="flex gap-2">
    <button class="btn-primary">Admit Student</button>
    <a href="<?= site_url('admin/students') ?>" class="px-4 py-2 text-sm text-slate-600">Cancel</a>
  </div>
</form>

<?= $this->endSection() ?>