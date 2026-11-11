<?= $this->extend('App\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<div class="mb-4">
  <a href="<?= site_url('admin/teachers') ?>" class="text-sm text-indigo-600">← Back</a>
  <h1 class="text-2xl font-bold text-slate-800 mt-2">Add Teacher</h1>
</div>

<form method="post" action="<?= site_url('admin/teachers') ?>" class="card p-6 space-y-4 max-w-2xl">
  <?= csrf_field() ?>
  <div class="grid grid-cols-2 gap-4">
    <div class="col-span-2"><label class="text-sm font-medium text-slate-600">Name *</label>
      <input name="name" required class="input mt-1"></div>
    <div><label class="text-sm font-medium text-slate-600">Qualification</label>
      <input name="qualification" class="input mt-1" placeholder="M.Sc Maths"></div>
    <div><label class="text-sm font-medium text-slate-600">Experience</label>
      <input name="experience" class="input mt-1" placeholder="5 years"></div>
    <div><label class="text-sm font-medium text-slate-600">Phone</label>
      <input name="phone" class="input mt-1"></div>
    <div><label class="text-sm font-medium text-slate-600">Email</label>
      <input type="email" name="email" class="input mt-1"></div>
    <div class="col-span-2"><label class="text-sm font-medium text-slate-600">Joining Date</label>
      <input type="date" name="joining_date" value="<?= date('Y-m-d') ?>" class="input mt-1"></div>
  </div>
  <button class="btn-primary">Add Teacher</button>
</form>

<?= $this->endSection() ?>