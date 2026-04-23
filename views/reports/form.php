<?php
use function App\{e, csrf_field, config};
use const App\REPORT_PRIORITIES;
use const App\REPORT_CATEGORIES;
$r = $report ?? null;
?>
<div class="mb-6">
  <a href="/reports" class="text-sm text-slate-500 hover:text-slate-700">← Kembali ke Daftar</a>
  <h1 class="text-2xl font-bold mt-1"><?= $r ? 'Edit Laporan' : 'Buat Laporan Harian' ?></h1>
  <p class="text-sm text-slate-500">Catat pekerjaan / masalah / solusi yang Anda kerjakan.</p>
</div>

<form method="POST" action="<?= e($action) ?>" enctype="multipart/form-data" class="bg-white rounded-xl border border-slate-200 p-6 space-y-5 max-w-4xl">
  <?= csrf_field() ?>

  <div class="grid md:grid-cols-3 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Laporan <span class="text-red-500">*</span></label>
      <input type="date" name="report_date" required
        value="<?= e($r['report_date'] ?? date('Y-m-d')) ?>"
        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Proyek / PIC</label>
      <select name="project_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">— Tidak terkait proyek —</option>
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
    <label class="block text-sm font-medium text-slate-700 mb-1">Kategori Laporan</label>
    <div class="flex gap-2 flex-wrap">
      <?php foreach (REPORT_CATEGORIES as $k => $v):
        $checked = ($r['category'] ?? 'harian') === $k;
      ?>
        <label class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border cursor-pointer <?= $checked ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-300 text-slate-600 hover:bg-slate-50' ?>">
          <input type="radio" name="category" value="<?= e($k) ?>" <?= $checked ? 'checked' : '' ?> class="accent-blue-600">
          <span class="text-sm"><?= e($v) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
    <p class="mt-1 text-xs text-slate-500">Pilih jenis laporan: harian untuk pekerjaan per-hari, mingguan/bulanan untuk rekap.</p>
  </div>

  <div class="grid md:grid-cols-4 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Prioritas</label>
      <select name="priority" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <?php foreach (REPORT_PRIORITIES as $k => $v): ?>
          <option value="<?= e($k) ?>" <?= ($r['priority'] ?? 'normal') === $k ? 'selected' : '' ?>><?= e($v) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div x-data="{ p: <?= (int)($r['progress'] ?? 0) ?> }">
      <label class="block text-sm font-medium text-slate-700 mb-1">
        Progress <span class="text-slate-500 text-xs">(<span x-text="p"></span>%)</span>
      </label>
      <input type="range" name="progress" min="0" max="100" step="5"
        x-model.number="p"
        class="w-full">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Durasi Pengerjaan</label>
      <input type="text" name="duration" maxlength="50"
        value="<?= e($r['duration'] ?? '') ?>"
        placeholder="mis: 2 jam, 30 menit, 1.5 hari"
        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Lokasi</label>
      <input type="text" name="location" maxlength="100"
        value="<?= e($r['location'] ?? '') ?>"
        placeholder="mis: Ruang Server Lt.2"
        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
  </div>

  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Judul Laporan <span class="text-red-500">*</span></label>
    <input type="text" name="title" required maxlength="200"
      value="<?= e($r['title'] ?? '') ?>"
      placeholder="Contoh: Perbaikan printer divisi Finance"
      class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
  </div>

  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi Masalah / Pekerjaan <span class="text-red-500">*</span></label>
    <textarea name="description" required rows="4"
      placeholder="Jelaskan masalah atau pekerjaan yang dilakukan..."
      class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= e($r['description'] ?? '') ?></textarea>
  </div>

  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Solusi / Tindakan</label>
    <textarea name="solution" rows="4"
      placeholder="Tuliskan solusi, langkah perbaikan, atau hasil..."
      class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= e($r['solution'] ?? '') ?></textarea>
    <p class="mt-1 text-xs text-slate-500">Catatan solusi membantu knowledge base tim untuk masalah berulang.</p>
  </div>

  <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Foto Dokumentasi</label>

    <?php if (!empty($photos ?? [])): ?>
      <div class="mb-3 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
        <?php foreach ($photos as $ph): ?>
          <label class="relative block group cursor-pointer">
            <img src="<?= e($ph['url']) ?>" alt="Foto" class="w-full h-24 rounded-lg object-cover border border-slate-200">
            <div class="absolute inset-0 rounded-lg bg-black/0 group-hover:bg-black/30 transition flex items-end p-2">
              <span class="text-xs bg-white/90 px-2 py-1 rounded inline-flex items-center gap-1 text-slate-700">
                <input type="checkbox" name="remove_photos[]" value="<?= e($ph['id']) ?>" class="rounded">
                Hapus
              </span>
            </div>
          </label>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <input type="file" name="photos[]" accept="image/*" multiple id="photos-input"
      class="block w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">

    <!-- Preview thumbnails dari file yang dipilih (bisa akumulasi lintas beberapa kali "Choose Files") -->
    <div id="photos-preview" class="mt-3 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3"></div>

    <p class="mt-1 text-xs text-slate-500">
      JPG, PNG, WebP, GIF. Maks <?= (int)config('max_upload_mb', 5) ?> MB per file.
      Bisa pilih banyak foto sekaligus atau tambah bertahap — klik ✕ di thumbnail untuk batalkan salah satu.
    </p>
  </div>

  <script>
  (function () {
    const input = document.getElementById('photos-input');
    const preview = document.getElementById('photos-preview');
    if (!input || !preview) return;

    // Accumulator for selected files across multiple "Choose Files" clicks.
    // A bare <input type="file"> REPLACES its file list on each pick; we keep
    // our own DataTransfer and mirror it back to the input before submit.
    let store = new DataTransfer();

    // De-dupe key so picking the exact same file twice doesn't add it twice.
    const keyOf = (f) => f.name + '|' + f.size + '|' + f.lastModified;

    input.addEventListener('change', () => {
      const existing = new Set([...store.files].map(keyOf));
      for (const f of input.files) {
        if (!f.type.startsWith('image/')) continue;
        if (existing.has(keyOf(f))) continue;
        store.items.add(f);
      }
      syncInput();
      render();
    });

    function syncInput() {
      // Reflect accumulated files back onto the native input so form submit
      // sends all of them under name="photos[]".
      input.files = store.files;
    }

    function removeAt(idx) {
      const keep = [...store.files].filter((_, i) => i !== idx);
      store = new DataTransfer();
      keep.forEach(f => store.items.add(f));
      syncInput();
      render();
    }

    function render() {
      preview.innerHTML = '';
      [...store.files].forEach((file, idx) => {
        const url = URL.createObjectURL(file);
        const wrap = document.createElement('div');
        wrap.className = 'relative';
        wrap.innerHTML =
          '<img class="w-full h-24 rounded-lg object-cover border border-emerald-300">' +
          '<button type="button" class="remove-btn absolute top-1 right-1 w-6 h-6 flex items-center justify-center rounded-full bg-white/90 hover:bg-red-100 text-slate-600 hover:text-red-600 border border-slate-200 text-sm leading-none" title="Batalkan file ini">&times;</button>' +
          '<div class="absolute bottom-1 left-1 bg-emerald-600 text-white text-[10px] px-1.5 py-0.5 rounded">Baru</div>' +
          '<div class="text-[10px] text-slate-500 truncate mt-1"></div>';
        const img = wrap.querySelector('img');
        img.src = url;
        img.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
        wrap.querySelector('div.text-\\[10px\\].truncate').textContent = file.name;
        wrap.querySelector('.remove-btn').addEventListener('click', () => removeAt(idx));
        preview.appendChild(wrap);
      });
    }
  })();
  </script>

  <div class="flex items-center justify-end gap-3 pt-2">
    <a href="/reports" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm">Batal</a>
    <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium">
      <?= e($submit_label) ?>
    </button>
  </div>
</form>
