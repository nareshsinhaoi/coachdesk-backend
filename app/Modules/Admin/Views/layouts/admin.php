<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
<meta charset="UTF-8"> 
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title><?php echo esc($title ?? 'Dashboard') ?> · CoachDesk</title>

<link rel="icon" type="image/png" href="<?php echo site_url('favicon-96x96.png') ?>" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="<?php echo site_url('favicon.svg') ?>" />
<link rel="shortcut icon" href="<?php echo site_url('favicon.ico') ?>" />
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo site_url('apple-touch-icon.png') ?>" />
<link rel="manifest" href="<?php echo site_url('site.webmanifest') ?>" />

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
  :root { --primary: #4f46e5; }
  body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; }
  .sidebar-link { display:flex; align-items:center; gap:.75rem; padding:.6rem .9rem; border-radius:.5rem; color:#cbd5e1; font-size:.875rem; transition:.15s; }
  .sidebar-link:hover, .sidebar-link.active { background:rgba(255,255,255,.08); color:#fff; }
  .sidebar-link.active { background:rgba(99,102,241,.25); color:#fff; }
  .sidebar-link i { width:18px; text-align:center; }
  .card { background:#fff; border-radius:.75rem; box-shadow:0 1px 2px rgba(0,0,0,.04); border:1px solid #e2e8f0; }
  .btn-primary { background:#4f46e5; color:#fff; padding:.55rem 1rem; border-radius:.5rem; font-weight:500; transition:.15s; }
  .btn-primary:hover { background:#4338ca; }
  .input { width:100%; padding:.55rem .75rem; border:1px solid #cbd5e1; border-radius:.5rem; font-size:.875rem; outline:none; }
  .input:focus { border-color:#4f46e5; box-shadow:0 0 0 3px rgba(99,102,241,.15); }
  .badge { display:inline-block; padding:.15rem .6rem; font-size:.7rem; border-radius:9999px; font-weight:600; }
  .badge-green { background:#dcfce7; color:#166534; }
  .badge-red { background:#fee2e2; color:#991b1b; }
  .badge-amber { background:#fef3c7; color:#92400e; }
  .badge-blue { background:#dbeafe; color:#1e40af; }
  .badge-gray { background:#f1f5f9; color:#475569; }
  .table-wrap { overflow-x:auto; }
  table.data { width:100%; font-size:.875rem; border-collapse:collapse; }
  table.data th { text-align:left; padding:.75rem 1rem; background:#f8fafc; font-weight:600; color:#475569; border-bottom:1px solid #e2e8f0; font-size:.75rem; text-transform:uppercase; letter-spacing:.03em; }
  table.data td { padding:.75rem 1rem; border-bottom:1px solid #f1f5f9; color:#334155; }
  table.data tr:hover td { background:#f8fafc; }
  .alert { padding:.75rem 1rem; border-radius:.5rem; font-size:.875rem; margin-bottom:1rem; }
  .alert-success { background:#dcfce7; color:#166534; }
  .alert-error { background:#fee2e2; color:#991b1b; }
  .alert-info { background:#dbeafe; color:#1e40af; }
  .alert-warning { background:#fef3c7; color:#92400e; }
</style>
</head>
<body class="h-full">

<div class="flex h-screen overflow-hidden">

  <?php echo view('App\Modules\Admin\Views\layouts\partials\sidebar') ?>

  <div class="flex-1 flex flex-col overflow-hidden">

    <?php echo view('App\Modules\Admin\Views\layouts\partials\topbar') ?>

    <main class="flex-1 overflow-y-auto p-6">
      <?php echo flash_alert() ?>
      <?php echo $this->renderSection('content') ?>
    </main>

  </div>

</div>

</body>
</html>