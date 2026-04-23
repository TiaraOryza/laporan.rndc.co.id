<?php
use App\Auth;
use function App\{e, csrf_field, role_label};
use const App\PROJECT_STATUSES;
$p = $project ?? null;
$currentUser = Auth::user();
$isPicCreating = !$p && ($currentUser['role'] ?? '') === 'pic';
$currentStatus = $p['status'] ?? 'active';
?>
<div class="mb-6">
  <a href="/projects" class="text-sm text-slate-500 hover:text-slate-700">← Kembali ke Daftar</a>
  <h1 class="text-2xl font-bold mt-1"><?= $p ? 'Edit Proyek' : 'Tambah Proyek' ?></h1>
</div>

<form method="POST" action="<?= e($action) ?>" class="bg-white rounded-xl border border-slate-200 p-6 space-y-5 max-w-2xl">
  <?= csrf_field() ?>
  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Proyek <span class="text-red-500">*</span></label>
    <input type="text" name="name" required maxlength="200"
      value="<?= e($p['name'] ?? '') ?>"
      class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
  </div>
  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
    <textarea name="description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= e($p['description'] ?? '') ?></textarea>
  </div>
  <?php if ($isPicCreating): ?>
    <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-sm text-slate-600">
      PIC akan otomatis di-set ke Anda (<strong><?= e($currentUser['name']) ?></strong>).
      Hubungi admin jika perlu mengalihkan PIC ke orang lain.
    </div>
  <?php else: ?>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">PIC (Person in Charge)</label>
      <select name="pic_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">— Tidak ada PIC —</option>
        <?php foreach ($pics as $u): ?>
          <option value="<?= e($u['id']) ?>" <?= ($p['pic_id'] ?? '') === $u['id'] ? 'selected' : '' ?>>
            <?= e($u['name']) ?> — <?= e(role_label($u['role'])) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  <?php endif; ?>

  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Status Proyek</label>
    <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
      <?php foreach (PROJECT_STATUSES as $key => $label): ?>
        <option value="<?= e($key) ?>" <?= $currentStatus === $key ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <p class="mt-1 text-xs text-slate-500">Tahap proyek saat ini: perencanaan → berjalan → ditahan/selesai/dibatalkan.</p>
  </div>
  <div class="flex items-center justify-end gap-3 pt-2">
    <a href="/projects" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm">Batal</a>
    <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium">
      <?= e($submit_label) ?>
    </button>
  </div>
</form>
