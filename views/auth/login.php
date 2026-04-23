<?php use function App\{e, config, csrf_field, old};
$isDev = config('app_env') === 'development';
?>
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-blue-900 p-4">
  <div class="w-full max-w-md">
    <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
      <div class="px-8 py-6 bg-slate-900 text-white">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg>
          </div>
          <div>
            <h1 class="text-xl font-bold"><?= e(config('app_name')) ?></h1>
            <p class="text-xs text-slate-300">Laporan Pekerjaan Harian IT</p>
          </div>
        </div>
      </div>
      <div class="px-8 py-8">
        <h2 class="text-xl font-semibold mb-1">Selamat datang</h2>
        <p class="text-sm text-slate-500 mb-6">Masuk dengan akun Anda untuk melanjutkan.</p>

        <?php if (!empty($error)): ?>
          <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-700 px-3 py-2 text-sm"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
          <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 px-3 py-2 text-sm"><?= e($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="/login" class="space-y-4">
          <?= csrf_field() ?>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input type="email" name="email" required value="<?= e(old('email')) ?>"
              class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="admin@rndc.co.id" autofocus>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
            <input type="password" name="password" required
              class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="••••••••">
          </div>
          <button type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-medium py-2.5 rounded-lg transition">
            Masuk
          </button>
        </form>
        <?php if ($isDev): ?>
        <div class="mt-6 text-center text-xs text-slate-400">
          Akun default: <span class="font-mono">admin@rndc.co.id / admin123</span>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>













