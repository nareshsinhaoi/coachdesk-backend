<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register · CoachDesk</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; }</style>
</head>
<body class="min-h-screen flex items-center justify-center p-6 py-12">

<div class="w-full max-w-4xl grid md:grid-cols-5 bg-white rounded-2xl shadow-xl overflow-hidden">

  <!-- Left panel -->
  <div class="hidden md:flex md:col-span-2 flex-col justify-between p-8 bg-gradient-to-br from-indigo-600 to-violet-700 text-white">
    <div>
      <div class="text-2xl font-bold flex items-center gap-2">
        <span class="inline-block w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center">🎓</span>
        CoachDesk
      </div>
      <p class="mt-4 text-white/85 text-sm leading-relaxed">
        Join hundreds of coaching institutes using CoachDesk to manage students, fees, attendance and exams in one platform.
      </p>
    </div>
    <ul class="space-y-3 text-sm text-white/90">
      <li>✓ Start with a free trial</li>
      <li>✓ No credit card required</li>
      <li>✓ Onboarding within 24 hours</li>
      <li>✓ Free data migration</li>
    </ul>
    <p class="text-xs text-white/60">© <?php echo date('Y') ?> CoachDesk</p>
  </div>

  <!-- Right form -->
  <div class="md:col-span-3 p-8">
    <h1 class="text-2xl font-bold text-slate-800">Get started with CoachDesk</h1>
    <p class="text-sm text-slate-500 mt-1">Tell us about your institute and we'll reach out within 24 hours.</p>

    <?php echo flash_alert() ?>

    <form method="post" action="<?php echo site_url('admin/register') ?>" class="mt-6 space-y-4">
      <?php echo csrf_field() ?>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Institute Name *</label>
          <input type="text" name="institute_name" required value="<?php echo old('institute_name') ?>"
                 class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                 placeholder="ABC Coaching Institute">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Your Name *</label>
          <input type="text" name="owner_name" required value="<?php echo old('owner_name') ?>"
                 class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                 placeholder="Rajesh Kumar">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Email *</label>
          <input type="email" name="email" required value="<?php echo old('email') ?>"
                 class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                 placeholder="owner@abc.edu">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Phone *</label>
          <input type="tel" name="phone" required value="<?php echo old('phone') ?>"
                 class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                 placeholder="9876543210">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">City</label>
          <input type="text" name="city" value="<?php echo old('city') ?>"
                 class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                 placeholder="Greater Noida">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Institute Type</label>
          <select name="institute_type"
                  class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
            <option value="">— Select —</option>
            <option value="Coaching Center">Coaching Center</option>
            <option value="Competitive Exams">Competitive Exams (IIT-JEE/NEET)</option>
            <option value="Government Exams">Government Exams (SSC/Banking)</option>
            <option value="Computer Training">Computer Training</option>
            <option value="Language/Skill">Language / Skill Development</option>
            <option value="Other">Other</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Number of Students</label>
        <select name="students_range"
                class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
          <option value="">— Select —</option>
          <option value="< 100">Less than 100</option>
          <option value="100-300">100 – 300</option>
          <option value="300-1000">300 – 1,000</option>
          <option value="1000+">1,000+</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Anything you'd like us to know?</label>
        <textarea name="message" rows="3"
                  class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                  placeholder="Tell us about your institute, current pain points, or specific needs…"><?php echo old('message') ?></textarea>
      </div>

      <button class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition">
        Request Demo / Signup
      </button>

      <p class="text-xs text-slate-500 text-center">
        By submitting this form you agree to be contacted by the CoachDesk team.
      </p>
    </form>

    <div class="mt-6 pt-6 border-t border-slate-200 text-center text-sm">
      Already have an account?
      <a href="<?php echo site_url('admin/login') ?>" class="text-indigo-600 hover:text-indigo-700 font-medium">Sign in</a>
    </div>
  </div>

</div>

</body>
</html>