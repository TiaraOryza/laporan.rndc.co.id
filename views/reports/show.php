<?php
use App\Auth;
use function App\{e, csrf_field, fmt_date, fmt_datetime, status_label, status_badge_class, priority_label, priority_badge_class, progress_bar_class, review_status_label, review_status_badge_class, review_actions_for_role, role_label, activity_sentence, category_label, category_badge_class};
use const App\REVIEW_STATUSES;
?>
<div class="mb-4">
  <a href="/reports" class="text-sm text-slate-500 hover:text-slate-700">← Kembali ke Daftar</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden max-w-4xl">
  <div class="p-6 border-b border-slate-100">
    <div class="flex items-start justify-between gap-3 flex-wrap">
      <div class="min-w-0">
        <div class="flex items-center gap-2 flex-wrap mb-2">
          <?php $cat = $report['category'] ?? 'harian'; ?>
          <span class="text-xs px-2 py-1 rounded-full <?= category_badge_class($cat) ?>">Laporan <?= e(category_label($cat)) ?></span>
          <span class="text-xs px-2 py-1 rounded-full <?= status_badge_class($report['status']) ?>"><?= e(status_label($report['status'])) ?></span>
          <?php $rs = $report['review_status'] ?? 'draft'; ?>
          <span class="text-xs px-2 py-1 rounded-full <?= review_status_badge_class($rs) ?>"><?= e(review_status_label($rs)) ?></span>
          <span class="text-xs text-slate-500"><?= e(fmt_date($report['report_date'], 'l, d F Y')) ?></span>
        </div>
        <h1 class="text-2xl font-bold"><?= e($report['title']) ?></h1>
        <div class="mt-2 flex items-center gap-4 text-sm text-slate-500 flex-wrap">
          <span class="inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7z"/></svg>
            <?= e($report['user_name']) ?>
          </span>
          <?php if ($report['project_name']): ?>
          <span class="inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18"/></svg>
            <?= e($report['project_name']) ?>
          </span>
          <?php endif; ?>
          <span class="inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
            <?= e(fmt_datetime($report['created_at'])) ?>
          </span>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <a href="/reports/<?= e($report['id']) ?>/pdf" target="_blank"
           class="px-3 py-2 text-sm rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white inline-flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg>
          Unduh PDF
        </a>
        <?php if (Auth::can('reports.edit', $report)): ?>
          <a href="/reports/<?= e($report['id']) ?>/edit" class="px-3 py-2 text-sm rounded-lg bg-slate-100 hover:bg-slate-200">Edit</a>
        <?php endif; ?>
        <?php if (Auth::can('reports.delete', $report)): ?>
          <form method="POST" action="/reports/<?= e($report['id']) ?>/delete" onsubmit="return confirm('Hapus laporan ini? Tindakan ini tidak bisa dibatalkan.');">
            <?= csrf_field() ?>
            <button type="submit" class="px-3 py-2 text-sm rounded-lg bg-red-50 hover:bg-red-100 text-red-700">Hapus</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="p-6 space-y-5">
    <!-- Meta grid: progress / priority / duration / location -->
    <?php
      $progress = (int)($report['progress'] ?? 0);
      $priority = $report['priority'] ?? 'normal';
    ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pb-5 border-b border-slate-100">
      <div>
        <div class="text-xs uppercase tracking-wider text-slate-500 mb-1">Progress</div>
        <div class="flex items-center gap-2">
          <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
            <div class="h-full <?= progress_bar_class($progress) ?> rounded-full transition-all" style="width: <?= $progress ?>%"></div>
          </div>
          <span class="text-sm font-semibold text-slate-700 w-10 text-right"><?= $progress ?>%</span>
        </div>
      </div>
      <div>
        <div class="text-xs uppercase tracking-wider text-slate-500 mb-1">Prioritas</div>
        <span class="text-xs px-2 py-1 rounded-full <?= priority_badge_class($priority) ?>"><?= e(priority_label($priority)) ?></span>
      </div>
      <div>
        <div class="text-xs uppercase tracking-wider text-slate-500 mb-1">Durasi</div>
        <div class="text-sm text-slate-700"><?= $report['duration'] ? e($report['duration']) : '<span class="text-slate-400">—</span>' ?></div>
      </div>
      <div>
        <div class="text-xs uppercase tracking-wider text-slate-500 mb-1">Lokasi</div>
        <div class="text-sm text-slate-700"><?= $report['location'] ? e($report['location']) : '<span class="text-slate-400">—</span>' ?></div>
      </div>
    </div>

    <div>
      <h3 class="text-xs uppercase tracking-wider text-slate-500 mb-1">Deskripsi Masalah / Pekerjaan</h3>
      <p class="whitespace-pre-wrap text-slate-700 leading-relaxed"><?= e($report['description']) ?></p>
    </div>
    <?php if ($report['solution']): ?>
    <div class="border-t border-slate-100 pt-5">
      <h3 class="text-xs uppercase tracking-wider text-slate-500 mb-1">Solusi / Tindakan</h3>
      <p class="whitespace-pre-wrap text-slate-700 leading-relaxed"><?= e($report['solution']) ?></p>
    </div>
    <?php endif; ?>
    <?php if (!empty($photos)): ?>
    <div class="border-t border-slate-100 pt-5">
      <h3 class="text-xs uppercase tracking-wider text-slate-500 mb-2">Foto Dokumentasi (<?= count($photos) ?>)</h3>
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
        <?php foreach ($photos as $ph): ?>
          <a href="<?= e($ph['url']) ?>" target="_blank" class="block">
            <img src="<?= e($ph['url']) ?>" alt="Dokumentasi" class="w-full h-32 rounded-lg object-cover border border-slate-200 hover:border-blue-400 transition">
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- ACTIVITY HISTORY + review form (jika user boleh review) -->
    <div class="border-t border-slate-100 pt-5">
      <h3 class="text-xs uppercase tracking-wider text-slate-500 mb-3">Riwayat Aktivitas</h3>

      <?php if (empty($reviews)): ?>
        <p class="text-sm text-slate-400 italic mb-4">Belum ada aktivitas tercatat.</p>
      <?php else: ?>
        <ol class="relative border-l border-slate-200 ml-3 space-y-5 mb-5 pl-6">
          <?php foreach ($reviews as $rv):
            $name = $rv['reviewer_name'] ?? 'Sistem';
            $action = $rv['action'];
            $isReview = in_array($action, ['reviewing', 'approved', 'revision'], true);
            // Defensif: skip legacy 'updated' tanpa catatan (tidak memberi informasi)
            if ($action === 'updated' && empty($rv['notes'])) continue;
          ?>
            <li class="relative">
              <span class="absolute -left-[29px] top-1 w-3 h-3 rounded-full bg-white border-2 border-slate-400"></span>
              <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs px-2 py-1 rounded-full <?= review_status_badge_class($action) ?>">
                  <?= e(review_status_label($action)) ?>
                </span>
                <span class="text-xs text-slate-600">
                  oleh <strong><?= e($name) ?></strong>
                  <?php if (!empty($rv['reviewer_role'])): ?>
                    <span class="text-slate-400">(<?= e(role_label($rv['reviewer_role'])) ?>)</span>
                  <?php endif; ?>
                </span>
                <span class="text-xs text-slate-400"><?= e(fmt_datetime($rv['created_at'])) ?></span>
              </div>
              <?php if (!empty($rv['notes'])): ?>
                <p class="mt-1 text-sm text-slate-700 whitespace-pre-wrap bg-slate-50 rounded-lg px-3 py-2"><?= e($rv['notes']) ?></p>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>

      <?php
        // Tentukan aksi yg boleh dilakukan user saat ini utk laporan ini
        $isProjectPic = ($user['role'] === 'pic' && ($report['project_pic_id'] ?? null) === $user['id']);
        $allowedActions = Auth::can('reports.review', $report)
          ? review_actions_for_role($user['role'], $isProjectPic)
          : [];
      ?>
      <?php if ($allowedActions): ?>
        <form method="POST" action="/reports/<?= e($report['id']) ?>/review" class="bg-slate-50 rounded-lg p-4 space-y-3">
          <?= csrf_field() ?>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Tambahkan Review</label>
            <select name="action" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white">
              <option value="">— Pilih aksi review —</option>
              <?php foreach ($allowedActions as $act): ?>
                <option value="<?= e($act) ?>"><?= e(review_status_label($act)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Catatan Review <span class="text-slate-400 text-xs">(opsional)</span></label>
            <textarea name="notes" rows="3" placeholder="Catatan untuk reviewer berikutnya atau pembuat laporan..."
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white"></textarea>
          </div>
          <div class="flex justify-end">
            <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium">
              Kirim Review
            </button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
