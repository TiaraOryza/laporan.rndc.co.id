<?php
use App\Auth;
use function App\{e, fmt_date, status_label, status_badge_class, progress_bar_class, category_label, category_badge_class};
?>
<div class="mb-6 flex items-center justify-between flex-wrap gap-3">
  <div>
    <h1 class="text-2xl font-bold">Laporan Harian</h1>
    <p class="text-sm text-slate-500">Riwayat dan daftar laporan.</p>
  </div>
  <a href="/reports/create" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-sm text-sm font-medium">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
    Buat Laporan
  </a>
</div>

<?php $seeAll = Auth::can('reports.viewAll'); ?>
<form method="GET" action="/reports" class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
  <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-12 items-end">
    <div class="lg:col-span-2">
      <label class="block text-xs text-slate-500 mb-1">Dari</label>
      <input type="date" name="from" value="<?= e($filter['from']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div class="lg:col-span-2">
      <label class="block text-xs text-slate-500 mb-1">Sampai</label>
      <input type="date" name="to" value="<?= e($filter['to']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
    </div>
    <?php if ($seeAll): ?>
    <div class="lg:col-span-2">
      <label class="block text-xs text-slate-500 mb-1">Staff IT</label>
      <select name="user_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">Semua</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= e($u['id']) ?>" <?= $filter['user_id'] === $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="lg:col-span-2">
      <label class="block text-xs text-slate-500 mb-1">Proyek</label>
      <select name="project_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">Semua</option>
        <?php foreach ($projects as $p): ?>
          <option value="<?= e($p['id']) ?>" <?= $filter['project_id'] === $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="lg:col-span-2">
      <label class="block text-xs text-slate-500 mb-1">Status</label>
      <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">Semua</option>
        <option value="done"        <?= $filter['status'] === 'done' ? 'selected' : '' ?>>Selesai</option>
        <option value="in_progress" <?= $filter['status'] === 'in_progress' ? 'selected' : '' ?>>Dikerjakan</option>
        <option value="pending"     <?= $filter['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
      </select>
    </div>
    <div class="lg:col-span-2">
      <label class="block text-xs text-slate-500 mb-1">Kategori</label>
      <select name="category" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">Semua</option>
        <option value="harian"   <?= $filter['category'] === 'harian'   ? 'selected' : '' ?>>Harian</option>
        <option value="mingguan" <?= $filter['category'] === 'mingguan' ? 'selected' : '' ?>>Mingguan</option>
        <option value="bulanan"  <?= $filter['category'] === 'bulanan'  ? 'selected' : '' ?>>Bulanan</option>
      </select>
    </div>
    <?php if ($seeAll): ?>
    <div class="sm:col-span-2 lg:col-span-12">
      <label class="block text-xs text-slate-500 mb-1">Cari kata kunci (judul / deskripsi / solusi)</label>
      <div class="flex gap-2">
        <input type="search" name="q" value="<?= e($filter['q']) ?>" placeholder="mis: printer, vpn, jaringan..."
          class="flex-1 min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-sm whitespace-nowrap">Terapkan</button>
        <a href="/reports" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm whitespace-nowrap">Reset</a>
      </div>
    </div>
    <?php else: ?>
    <div class="sm:col-span-2 lg:col-span-4">
      <label class="block text-xs text-slate-500 mb-1">Cari kata kunci (judul / deskripsi / solusi)</label>
      <div class="flex gap-2">
        <input type="search" name="q" value="<?= e($filter['q']) ?>" placeholder="mis: printer, vpn, jaringan..."
          class="flex-1 min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-sm whitespace-nowrap">Terapkan</button>
        <a href="/reports" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm whitespace-nowrap">Reset</a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</form>

<?php if (!$reports): ?>
  <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500">
    Tidak ada laporan untuk filter ini.
  </div>
<?php else: ?>
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-slate-600 text-left">
        <tr>
          <th class="px-4 py-3 font-medium">Tanggal</th>
          <th class="px-4 py-3 font-medium">Kategori</th>
          <th class="px-4 py-3 font-medium">Judul / Deskripsi</th>
          <th class="px-4 py-3 font-medium">Proyek</th>
          <th class="px-4 py-3 font-medium">PIC</th>
          <th class="px-4 py-3 font-medium">Staff</th>
          <th class="px-4 py-3 font-medium">Progress</th>
          <th class="px-4 py-3 font-medium">Status</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($reports as $r): ?>
        <tr class="hover:bg-slate-50">
          <td class="px-4 py-3 whitespace-nowrap text-slate-600"><?= e(fmt_date($r['report_date'])) ?></td>
          <td class="px-4 py-3">
            <?php $cat = $r['category'] ?? 'harian'; ?>
            <span class="text-xs px-2 py-1 rounded-full <?= category_badge_class($cat) ?>"><?= e(category_label($cat)) ?></span>
          </td>
          <td class="px-4 py-3">
            <div class="font-medium text-slate-900 flex items-center gap-2">
              <?php if ($r['photo_url']): ?>
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 0 1 2.828 0L16 16m-2-2l1.586-1.586a2 2 0 0 1 2.828 0L20 14M14 8h.01M6 20h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg>
              <?php endif; ?>
              <?= e($r['title']) ?>
            </div>
            <div class="text-slate-500 line-clamp-1"><?= e(mb_substr($r['description'], 0, 140)) ?></div>
          </td>
          <td class="px-4 py-3 text-slate-600"><?= e($r['project_name'] ?? '-') ?></td>
          <td class="px-4 py-3 text-slate-600"><?= e($r['pic_name'] ?? '-') ?></td>
          <td class="px-4 py-3 text-slate-600"><?= e($r['user_name']) ?></td>
          <td class="px-4 py-3">
            <?php $pg = (int)($r['progress'] ?? 0); ?>
            <div class="flex items-center gap-2">
              <div class="w-16 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full <?= progress_bar_class($pg) ?>" style="width: <?= $pg ?>%"></div>
              </div>
              <span class="text-xs text-slate-600 w-8"><?= $pg ?>%</span>
            </div>
          </td>
          <td class="px-4 py-3"><span class="text-xs px-2 py-1 rounded-full <?= status_badge_class($r['status']) ?>"><?= e(status_label($r['status'])) ?></span></td>
          <td class="px-4 py-3 text-right">
            <a href="/reports/<?= e($r['id']) ?>" class="text-blue-600 hover:text-blue-800 text-sm">Detail</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="mt-3 text-xs text-slate-500">Menampilkan <?= count($reports) ?> laporan (maks. 200 terbaru).</div>
<?php endif; ?>
