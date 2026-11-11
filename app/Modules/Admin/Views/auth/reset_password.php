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


<title>Reset Password · CoachDesk</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; }</style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">

<div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8">

  <div class="text-center mb-6">
    <div class="inline-flex w-14 h-14 rounded-2xl bg-indigo-100 items-center justify-center mb-3">
      <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
      </svg>
    </div>
    <h1 class="text-2xl font-bold text-slate-800">Set new password</h1>
    <p class="text-sm text-slate-500 mt-1">Choose a strong password for your account</p>
  </div>

  <?php echo flash_alert() ?>

  <form method="post" action="<?php echo site_url('admin/reset-password/'.$token) ?>" class="space-y-4">
    <?php echo csrf_field() ?>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">New Password</label>
      <input type="password" name="password" required minlength="8"
             class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
             placeholder="••••••••">
      <p class="text-xs text-slate-500 mt-1">Minimum 8 characters. Use a mix of letters, numbers, and symbols.</p>
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Confirm Password</label>
      <input type="password" name="confirm_password" required minlength="8"
             class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
             placeholder="••••••••">
    </div>
    <button class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition">
      Reset Password
    </button>
  </form>

  <div class="mt-6 text-center text-sm">
    <a href="<?php echo site_url('admin/login') ?>" class="text-indigo-600 hover:text-indigo-700">← Back to login</a>
  </div>

</div>

</body>
</html>