<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<link rel="icon" type="image/png" href="<?php echo site_url('favicon-96x96.png') ?>" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="<?php echo site_url('favicon.svg') ?>" />
<link rel="shortcut icon" href="<?php echo site_url('favicon.ico') ?>" />
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo site_url('apple-touch-icon.png') ?>" />
<link rel="manifest" href="<?php echo site_url('site.webmanifest') ?>" />

<title>Login · CoachDesk</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; }
</style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">

<div class="w-full max-w-5xl grid md:grid-cols-2 bg-white rounded-2xl shadow-xl overflow-hidden">

  <!-- Left: Brand -->
  <div class="hidden md:flex flex-col justify-between p-10 bg-gradient-to-br from-indigo-600 to-violet-700 text-white">
    <div>
      <div class="text-2xl font-bold flex items-center gap-2">
        <span class="inline-block w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center">🎓</span>
        CoachDesk
      </div>
      <p class="mt-4 text-white/80 text-sm">Manage students, batches, attendance, fees and exams — all in one place.</p>
    </div>
    <ul class="space-y-3 text-sm text-white/90">
      <li>✓ Multi-branch support</li>
      <li>✓ Attendance &amp; fees tracking</li>
      <li>✓ Reports &amp; analytics</li>
      <li>✓ Role-based access</li>
    </ul>
    <p class="text-xs text-white/60">© <?php echo date('Y') ?> CoachDesk</p>
  </div>

  <!-- Right: Form -->
  <div class="p-10">
    <h1 class="text-2xl font-bold text-slate-800">Welcome back</h1>
    <p class="text-slate-500 text-sm mt-1">Sign in to your institute dashboard</p>

    <?php echo flash_alert() ?>

    <form method="post" action="<?php echo site_url('admin/login') ?>" class="mt-6 space-y-4">
      <?php echo csrf_field() ?>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
        <input type="email" name="email" required
               value="<?php echo old('email') ?>"
               class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
               placeholder="owner@abc.edu">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
        <input type="password" name="password" required
               class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
               placeholder="••••••••">
      </div>
      <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition">
        Sign in
      </button>
    </form>

    <div class="mt-6 flex items-center justify-between text-sm">
      <a href="<?php echo site_url('admin/forgot-password') ?>" class="text-indigo-600 hover:text-indigo-700">
        Forgot password?
      </a>
      <a href="<?php echo site_url('admin/register') ?>" class="text-indigo-600 hover:text-indigo-700">
        Register institute →
      </a>
    </div>

    <div class="mt-4 p-3 bg-slate-50 rounded-lg border border-slate-200 text-xs text-slate-600">
      <strong>Demo:</strong> owner@abc.edu / Password@123 &nbsp;·&nbsp; super@coachdesk.in / Password@123
    </div>
    
  </div>

</div>

</body>
</html>