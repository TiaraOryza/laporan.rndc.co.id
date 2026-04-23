<?php
use App\Auth;
use function App\{e, config, flash_get, csrf_token};
$__user = Auth::user();
$__current = $_SERVER['REQUEST_URI'] ?? '/';
$__path = parse_url($__current, PHP_URL_PATH) ?: '/';
?><!DOCTYPE html>
<html lang="id" class="h-full">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e(config('app_name')) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
  [x-cloak] { display: none !important; }
  body { font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
</style>
</head>
<body class="min-h-full bg-slate-50 text-slate-800">

<?php if ($__user): ?>
<div x-data="{ open: false }" class="min-h-screen flex flex-col md:flex-row">
  <!-- Sidebar -->
  <aside
    :class="open ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
    class="fixed md:static inset-y-0 left-0 w-64 bg-slate-900 text-slate-100 z-30 transform transition-transform md:translate-x-0 flex flex-col">
    <div class="px-5 py-4 border-b border-slate-800">
      <div class="text-lg font-bold tracking-tight"><?= e(config('app_name')) ?></div>
      <div class="text-xs text-slate-400">Laporan Pekerjaan IT</div>
    </div>
    <nav class="flex-1 px-3 py-3 space-y-1 overflow-y-auto text-sm">
      <?php
      $nav = [
        ['/', 'Dashboard', 'M3 10l9-7 9 7v10a2 2 0 0 1-2 2h-4v-6H9v6H5a2 2 0 0 1-2-2V10z'],
        ['/reports', 'Laporan Harian', 'M9 5H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-3M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2'],
        ['/summary', 'Rangkuman', 'M9 17v-2a4 4 0 0 1 4-4h4M3 12h6m-3-3v6m9 3l3 3m0-6l-3 3'],
      ];
      if ($__user['role'] !== 'employee') {
        $nav[] = ['/projects', 'Proyek', 'M3 7h18M3 12h18M3 17h18'];
      }
      if ($__user['role'] === 'admin') {
        $nav[] = ['/users', 'Pengguna', 'M17 20h5v-2a4 4 0 0 0-3-3.87M9 20H4v-2a4 4 0 0 1 3-3.87m6-5.13a4 4 0 1 1-8 0 4 4 0 0 1 8 0zm6 0a4 4 0 1 1-8 0 4 4 0 0 1 8 0z'];
      }
      foreach ($nav as [$href, $label, $icon]):
        $active = ($href === '/' && $__path === '/') || ($href !== '/' && str_starts_with($__path, $href));
      ?>
      <a href="<?= e($href) ?>"
         class="flex items-center gap-3 px-3 py-2 rounded-lg transition <?= $active ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
        <svg class="w-5 h-5 flex-none" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $icon ?>"/></svg>
        <span><?= e($label) ?></span>
      </a>
      <?php endforeach; ?>
    </nav>
    <div class="px-3 py-3 border-t border-slate-800">
      <a href="/profile" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white text-sm">
        <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-sm font-semibold">
          <?= e(strtoupper(mb_substr($__user['name'], 0, 1))) ?>
        </div>
        <div class="min-w-0">
          <div class="truncate text-sm text-white"><?= e($__user['name']) ?></div>
          <div class="truncate text-xs text-slate-400"><?= e(\App\role_label($__user['role'])) ?></div>
        </div>
      </a>
      <form method="POST" action="/logout" class="mt-2">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <button type="submit" class="w-full text-left px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white text-sm flex items-center gap-3">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1"/></svg>
          Logout
        </button>
      </form>
    </div>
  </aside>
  <div x-show="open" @click="open=false" x-cloak class="fixed inset-0 bg-black/50 z-20 md:hidden"></div>

  <!-- Main -->
  <div class="flex-1 flex flex-col min-w-0">
    <header class="bg-white border-b border-slate-200 px-4 py-3 flex items-center gap-3 md:hidden sticky top-0 z-10">
      <button @click="open = !open" class="p-2 rounded-md hover:bg-slate-100">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <div class="font-semibold truncate"><?= e(config('app_name')) ?></div>
    </header>

    <main class="flex-1 p-4 md:p-8 max-w-7xl w-full mx-auto">
      <?php
        $success = flash_get('success');
        $error   = flash_get('error');
      ?>
      <?php if ($success): ?>
        <div x-data="{s:true}" x-show="s" x-cloak class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 flex items-start gap-3">
          <svg class="w-5 h-5 mt-0.5 flex-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          <div class="flex-1"><?= $success /* already escaped on set */ ?></div>
          <button @click="s=false" class="text-emerald-600 hover:text-emerald-800">✕</button>
        </div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div x-data="{s:true}" x-show="s" x-cloak class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3 flex items-start gap-3">
          <svg class="w-5 h-5 mt-0.5 flex-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M4.93 19h14.14a2 2 0 0 0 1.73-3L13.73 4a2 2 0 0 0-3.46 0L3.2 16a2 2 0 0 0 1.73 3z"/></svg>
          <div class="flex-1"><?= $error ?></div>
          <button @click="s=false" class="text-red-600 hover:text-red-800">✕</button>
        </div>
      <?php endif; ?>

      <?php require __DIR__ . '/' . $__view_path . '.php'; ?>
    </main>
    <footer class="text-center text-xs text-slate-400 py-4">© <?= date('Y') ?> <?= e(config('app_name')) ?></footer>
  </div>
</div>
<?php else: ?>
  <?php require __DIR__ . '/' . $__view_path . '.php'; ?>
<?php endif; ?>

</body>
</html>
