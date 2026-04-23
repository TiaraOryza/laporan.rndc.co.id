<?php
use App\Auth;
use function App\{e, csrf_field, fmt_date, fmt_datetime, role_label, status_label, status_badge_class, progress_bar_class};
?>
<div class="mb-4">
  <a href="/project-reports" class="text-sm text-slate-500 hover:text-slate-700">← Kembali ke Daftar</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden max-w-4xl">
  <div class="p-6 border-b border-slate-100">
    <div class="flex items-start justify-between gap-3 flex-wrap">
      <div class="min-w-0">
        <div class="flex items-center gap-2 flex-wrap mb-2">
          <span class="text-xs px-2 py-1 rounded-full <?= status_badge_class($report['status'] ?? 'done') ?>"><?= e(status_label($report['status'] ?? 'done')) ?></span>
        </div>
        <h1 class="text-2xl font-bold"><?= e($report['title']) ?></h1>
        <div class="mt-2 flex items-center gap-4 text-sm text-slate-500 flex-wrap">
          <span class="inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18"/></svg>
            <?= e($report['project_name'] ?? '-') ?>
          </span>
          <span class="inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7z"/></svg>
            <?= e($report['author_name']) ?>
            <?php if (!empty($report['author_role'])): ?>
              <span class="text-xs text-slate-400">(<?= e(role_label($report['author_role'])) ?>)</span>
            <?php endif; ?>
          </span>
          <?php if ($report['period_from'] || $report['period_to']): ?>
            <span class="inline-flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg>
              <?= e($report['period_from'] ? fmt_date($report['period_from']) : '…') ?>
              —
              <?= e($report['period_to'] ? fmt_date($report['period_to']) : '…') ?>
            </span>
          <?php endif; ?>
          <span class="inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
            Dibuat <?= e(fmt_datetime($report['created_at'])) ?>
          </span>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <?php if (Auth::can('projectReports.edit', $report)): ?>
          <a href="/project-reports/<?= e($report['id']) ?>/edit" class="px-3 py-2 text-sm rounded-lg bg-slate-100 hover:bg-slate-200">Edit</a>
        <?php endif; ?>
        <?php if (Auth::can('projectReports.delete')): ?>
          <form method="POST" action="/project-reports/<?= e($report['id']) ?>/delete" onsubmit="return confirm('Hapus laporan proyek ini?');">
            <?= csrf_field() ?>
            <button type="submit" class="px-3 py-2 text-sm rounded-lg bg-red-50 hover:bg-red-100 text-red-700">Hapus</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="p-6 space-y-5">
    <?php $pg = (int)($report['progress'] ?? 0); ?>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 pb-5 border-b border-slate-100">
      <div>
        <div class="text-xs uppercase tracking-wider text-slate-500 mb-1">Progress Proyek</div>
        <div class="flex items-center gap-2">
          <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
            <div class="h-full <?= progress_bar_class($pg) ?> rounded-full transition-all" style="width: <?= $pg ?>%"></div>
          </div>
          <span class="text-sm font-semibold text-slate-700 w-10 text-right"><?= $pg ?>%</span>
        </div>
      </div>
      <div>
        <div class="text-xs uppercase tracking-wider text-slate-500 mb-1">Status</div>
        <span class="text-xs px-2 py-1 rounded-full <?= status_badge_class($report['status'] ?? 'done') ?>"><?= e(status_label($report['status'] ?? 'done')) ?></span>
      </div>
      <div>
        <div class="text-xs uppercase tracking-wider text-slate-500 mb-1">Terakhir Diperbarui</div>
        <div class="text-sm text-slate-700"><?= e(fmt_datetime($report['updated_at'] ?? $report['created_at'])) ?></div>
      </div>
    </div>

    <div>
      <h3 class="text-xs uppercase tracking-wider text-slate-500 mb-1">Ringkasan</h3>
      <p class="whitespace-pre-wrap text-slate-700 leading-relaxed"><?= e($report['summary']) ?></p>
    </div>
  </div>
</div>
