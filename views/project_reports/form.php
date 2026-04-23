<?php
use function App\{e, csrf_field};
$r = $report ?? null;
?>
<div class="mb-6">
  <a href="/project-reports" class="text-sm text-slate-500 hover:text-slate-700">← Kembali ke Daftar</a>
  <h1 class="text-2xl font-bold mt-1"><?= $r ? 'Edit Laporan Proyek' : 'Buat Laporan Proyek' ?></h1>
  <p class="text-sm text-slate-500">Ringkasan status/milestone/hasil proyek yang Anda pegang.</p>
</div>

<form method="POST" action="<?= e($action) ?>" class="bg-white rounded-xl border border-slate-200 p-6 space-y-5 max-w-3xl">
  <?= csrf_field() ?>

  <div class="grid md:grid-cols-3 gap-4">
    <div class="md:col-span-2">
      <label class="block text-sm font-medium text-slate-700 mb-1">Proyek <span class="text-red-500">*</span></label>
      <select name="project_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">— Pilih proyek —</option>
        <?php foreach ($projects as $p): ?>
          <option value="<?= e($p['id']) ?>" <?= ($r['project_id'] ?? '') === $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
      <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <?php foreach (['done' => 'Selesai', 'in_progress' => 'Dikerjakan', 'pending' => 'Pending'] as $k => $v): ?>
          <option value="<?= $k ?>" <?= ($r['status'] ?? 'done') === $k ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Judul Laporan <span class="text-red-500">*</span></label>
    <input type="text" name="title" required maxlength="200"
      value="<?= e($r['title'] ?? '') ?>"
      placeholder="mis: Laporan Mingguan Pentest JGC — Week 3"
      class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
  </div>

  <div class="grid md:grid-cols-3 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Periode: Dari</label>
      <input type="date" name="period_from"
        value="<?= e($r['period_from'] ?? '') ?>"
        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Periode: Sampai</label>
      <input type="date" name="period_to"
        value="<?= e($r['period_to'] ?? '') ?>"
        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div x-data="{ p: <?= (int)($r['progress'] ?? 0) ?> }">
      <label class="block text-sm font-medium text-slate-700 mb-1">
        Progress <span class="text-slate-500 text-xs">(<span x-text="p"></span>%)</span>
      </label>
      <input type="range" name="progress" min="0" max="100" step="5"
        x-model.number="p"
        class="w-full">
    </div>
  </div>
  <p class="text-xs text-slate-500 -mt-2">Periode boleh dikosongkan kalau laporan bersifat umum.</p>

  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Ringkasan / Isi Laporan <span class="text-red-500">*</span></label>
    <textarea name="summary" required rows="10"
      placeholder="Tulis ringkasan proyek: progress yang dicapai, kendala, rencana berikutnya, keputusan penting..."
      class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= e($r['summary'] ?? '') ?></textarea>
  </div>

  <div class="flex items-center justify-end gap-3 pt-2">
    <a href="/project-reports" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm">Batal</a>
    <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium">
      <?= e($submit_label) ?>
    </button>
  </div>
</form>
