<?php
use App\Auth;
use function App\{e, fmt_date, status_label, status_badge_class};

$qs = function(array $extra = []) use ($period, $from, $to, $userFilter, $projectFilter) {
  return http_build_query(array_filter(array_merge([
    'period'=>$period, 'from'=>$from, 'to'=>$to, 'user_id'=>$userFilter, 'project_id'=>$projectFilter,
  ], $extra)));
};
?>
<div class="mb-6 flex items-center justify-between flex-wrap gap-3">
  <div>
    <h1 class="text-2xl font-bold">Rangkuman Laporan</h1>
    <p class="text-sm text-slate-500"><?= e($label) ?></p>
  </div>
  <div class="flex items-center gap-2">
    <a href="/summary?<?= e($qs(['format' => 'print'])) ?>" target="_blank"
       class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg shadow-sm text-sm font-medium">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4H7v4a2 2 0 0 0 2 2zm8-12V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4h10z"/></svg>
      Cetak / Simpan PDF
    </a>
  </div>
</div>

<form method="GET" action="/summary" class="bg-white rounded-xl border border-slate-200 p-4 mb-4 space-y-3">
  <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5 items-end">
    <div>
      <label class="block text-xs text-slate-500 mb-1">Periode</label>
      <select name="period" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" onchange="this.form.submit()">
        <option value="daily"   <?= $period==='daily'   ? 'selected' : '' ?>>Harian</option>
        <option value="weekly"  <?= $period==='weekly'  ? 'selected' : '' ?>>Mingguan</option>
        <option value="monthly" <?= $period==='monthly' ? 'selected' : '' ?>>Bulanan</option>
        <option value="custom"  <?= $period==='custom'  ? 'selected' : '' ?>>Kustom</option>
      </select>
    </div>
    <div>
      <label class="block text-xs text-slate-500 mb-1">Tanggal Acuan / Mulai</label>
      <input type="date" name="from" value="<?= e($from) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
    </div>
    <?php if ($period === 'custom'): ?>
    <div>
      <label class="block text-xs text-slate-500 mb-1">Sampai</label>
      <input type="date" name="to" value="<?= e($to) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
    </div>
    <?php endif; ?>
    <?php if (Auth::can('reports.viewAll')): ?>
    <div>
      <label class="block text-xs text-slate-500 mb-1">Staff</label>
      <select name="user_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">Semua</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= e($u['id']) ?>" <?= $userFilter===$u['id']?'selected':'' ?>><?= e($u['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div>
      <label class="block text-xs text-slate-500 mb-1">Proyek</label>
      <select name="project_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">Semua</option>
        <?php foreach ($projects as $p): ?>
          <option value="<?= e($p['id']) ?>" <?= $projectFilter===$p['id']?'selected':'' ?>><?= e($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="flex justify-end">
    <button type="submit" class="px-5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-sm">Terapkan Filter</button>
  </div>
</form>

<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="text-xs text-slate-500">Total Laporan</div>
    <div class="text-2xl font-bold"><?= (int)$stats['total'] ?></div>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="text-xs text-slate-500">Selesai</div>
    <div class="text-2xl font-bold text-emerald-600"><?= (int)($stats['done'] ?? 0) ?></div>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="text-xs text-slate-500">Dikerjakan</div>
    <div class="text-2xl font-bold text-sky-600"><?= (int)($stats['in_progress'] ?? 0) ?></div>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="text-xs text-slate-500">Pending</div>
    <div class="text-2xl font-bold text-amber-600"><?= (int)($stats['pending'] ?? 0) ?></div>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="text-xs text-slate-500">Staff / Proyek</div>
    <div class="text-2xl font-bold"><?= (int)$stats['users'] ?> / <?= (int)$stats['projects'] ?></div>
  </div>
</div>

<?php if (!$reports): ?>
  <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500">
    Tidak ada laporan pada periode ini.
  </div>
<?php else: ?>
  <?php foreach ($byDate as $date => $items): ?>
  <div class="bg-white rounded-xl border border-slate-200 mb-4 overflow-hidden">
    <div class="px-5 py-3 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
      <h3 class="font-semibold"><?= e(fmt_date($date, 'l, d F Y')) ?></h3>
      <span class="text-sm text-slate-500"><?= count($items) ?> laporan</span>
    </div>
    <ul class="divide-y divide-slate-100">
      <?php foreach ($items as $r): ?>
      <li class="p-5">
        <div class="flex items-start justify-between gap-3 flex-wrap mb-1">
          <a href="/reports/<?= e($r['id']) ?>" class="font-medium text-slate-900 hover:text-blue-600"><?= e($r['title']) ?></a>
          <span class="text-xs px-2 py-1 rounded-full <?= status_badge_class($r['status']) ?>"><?= e(status_label($r['status'])) ?></span>
        </div>
        <div class="text-xs text-slate-500 flex gap-3 flex-wrap mb-2">
          <span>👤 <?= e($r['user_name']) ?></span>
          <?php if ($r['project_name']): ?><span>📌 <?= e($r['project_name']) ?></span><?php endif; ?>
        </div>
        <p class="text-sm text-slate-700 whitespace-pre-wrap"><?= e(mb_substr($r['description'], 0, 400)) ?><?= mb_strlen($r['description']) > 400 ? '…' : '' ?></p>
        <?php if ($r['solution']): ?>
          <p class="mt-2 text-sm"><span class="font-semibold text-slate-700">Solusi:</span> <span class="text-slate-700 whitespace-pre-wrap"><?= e(mb_substr($r['solution'], 0, 400)) ?><?= mb_strlen($r['solution']) > 400 ? '…' : '' ?></span></p>
        <?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endforeach; ?>
<?php endif; ?>
