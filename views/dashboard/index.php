<?php
use function App\{e, fmt_date, status_label, status_badge_class};
?>
<div class="mb-6 flex items-center justify-between flex-wrap gap-3">
  <div>
    <h1 class="text-2xl font-bold">Dashboard</h1>
    <p class="text-sm text-slate-500">Selamat datang kembali, <?= e($user['name']) ?> 👋</p>
  </div>
  <a href="/reports/create" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-sm text-sm font-medium">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
    Buat Laporan Harian
  </a>
</div>

<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
  <?php
  $cards = [
    ['Hari Ini',     $stats['today'],    'bg-blue-500',    'M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'],
    ['Minggu Ini',   $stats['week'],     'bg-emerald-500', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z'],
    ['Bulan Ini',    $stats['month'],    'bg-purple-500',  'M9 19v-6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v6M3 21h18M5 21V7l7-4 7 4v14'],
    ['Pengguna',     $stats['users'],    'bg-amber-500',   'M17 20h5v-2a4 4 0 0 0-3-3.87M9 20H4v-2a4 4 0 0 1 3-3.87m6-5.13a4 4 0 1 1-8 0 4 4 0 0 1 8 0zm6 0a4 4 0 1 1-8 0 4 4 0 0 1 8 0z'],
    ['Proyek Aktif', $stats['projects'], 'bg-rose-500',    'M3 7h18M3 12h18M3 17h18'],
  ];
  foreach ($cards as [$label, $value, $bg, $icon]): ?>
  <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
    <div class="w-10 h-10 rounded-lg <?= $bg ?> text-white flex items-center justify-center flex-none">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $icon ?>"/></svg>
    </div>
    <div>
      <div class="text-xs text-slate-500"><?= e($label) ?></div>
      <div class="text-2xl font-bold"><?= (int)$value ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="grid lg:grid-cols-3 gap-4 mb-6">
  <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-5">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-semibold">Aktivitas 7 Hari Terakhir</h2>
      <span class="text-xs text-slate-400">Total laporan per hari</span>
    </div>
    <div class="relative h-64">
      <canvas id="chartDaily"></canvas>
    </div>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-5">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-semibold">Proyek Teraktif</h2>
      <span class="text-xs text-slate-400">30 hari</span>
    </div>
    <?php if (!$byProject): ?>
      <p class="text-sm text-slate-500 italic">Belum ada data.</p>
    <?php else: ?>
      <ul class="space-y-2">
        <?php $max = max(array_column($byProject, 'total')) ?: 1; foreach ($byProject as $p): ?>
        <li>
          <div class="flex items-center justify-between text-sm mb-1">
            <span class="truncate"><?= e($p['name']) ?></span>
            <span class="font-semibold text-slate-600"><?= (int)$p['total'] ?></span>
          </div>
          <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
            <div class="h-full bg-blue-500" style="width: <?= round($p['total'] / $max * 100) ?>%"></div>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<div class="bg-white rounded-xl border border-slate-200">
  <div class="flex items-center justify-between p-5 border-b border-slate-100">
    <h2 class="font-semibold">Laporan Terbaru</h2>
    <a href="/reports" class="text-sm text-blue-600 hover:text-blue-800">Lihat semua →</a>
  </div>
  <?php if (!$recent): ?>
    <div class="p-8 text-center text-sm text-slate-500">
      Belum ada laporan. <a href="/reports/create" class="text-blue-600 hover:underline">Buat laporan pertama</a>.
    </div>
  <?php else: ?>
    <div class="divide-y divide-slate-100">
      <?php foreach ($recent as $r): ?>
      <a href="/reports/<?= e($r['id']) ?>" class="block p-4 hover:bg-slate-50">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap mb-1">
              <span class="text-xs px-2 py-0.5 rounded-full <?= status_badge_class($r['status']) ?>"><?= e(status_label($r['status'])) ?></span>
              <span class="text-xs text-slate-500"><?= e(fmt_date($r['report_date'])) ?></span>
              <?php if ($r['project_name']): ?>
                <span class="text-xs text-slate-500">• <?= e($r['project_name']) ?></span>
              <?php endif; ?>
            </div>
            <h3 class="font-medium truncate"><?= e($r['title']) ?></h3>
            <p class="text-sm text-slate-500 line-clamp-1"><?= e(mb_substr($r['description'], 0, 140)) ?></p>
          </div>
          <div class="text-right flex-none">
            <div class="text-xs text-slate-500"><?= e($r['user_name']) ?></div>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('chartDaily');
  if (!el) return;
  const labels = <?= json_encode(array_map(fn($d) => date('d/m', strtotime($d['date'])), $chart)) ?>;
  const data = <?= json_encode(array_column($chart, 'count')) ?>;
  new Chart(el, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Laporan',
        data,
        borderColor: 'rgb(37, 99, 235)',
        backgroundColor: 'rgba(37, 99, 235, 0.12)',
        fill: true,
        tension: 0.4,
        pointBackgroundColor: 'rgb(37, 99, 235)',
        pointRadius: 4,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 } }
      }
    }
  });
});
</script>
