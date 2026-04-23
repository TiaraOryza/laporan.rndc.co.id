<?php
use function App\{e, fmt_date, fmt_datetime, status_label, priority_label, role_label, category_label, review_status_label, config};
$pg = (int)($report['progress'] ?? 0);
$cat = $report['category'] ?? 'harian';
$docId = strtoupper(substr($report['id'], 0, 8));
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan <?= e(category_label($cat)) ?> — <?= e($report['title']) ?></title>
<style>
@page { size: A4; margin: 14mm 14mm 18mm 14mm; }
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; font-family: "Helvetica Neue", Arial, "Segoe UI", sans-serif; color: #1e293b; font-size: 11pt; line-height: 1.5; background: #fff; }
body { padding: 0; }

.page { max-width: 180mm; margin: 0 auto; }
.header { border-bottom: 3px solid #1e3a8a; padding-bottom: 10pt; margin-bottom: 14pt; display: flex; justify-content: space-between; align-items: flex-start; }
.header-left .brand { font-size: 16pt; font-weight: 800; color: #1e3a8a; letter-spacing: -0.3pt; }
.header-left .tagline { font-size: 9pt; color: #64748b; margin-top: 2pt; }
.header-right { text-align: right; }
.header-right .doctype { font-size: 10pt; color: #475569; font-weight: 600; text-transform: uppercase; letter-spacing: 1pt; }
.header-right .docid { font-size: 9pt; color: #94a3b8; margin-top: 2pt; font-family: "SFMono-Regular", Consolas, monospace; }

.title-block { margin-bottom: 14pt; }
.title-block h1 { font-size: 18pt; font-weight: 700; margin: 0 0 6pt 0; line-height: 1.25; color: #0f172a; }
.title-block .subtitle { font-size: 10pt; color: #64748b; }

.badges { margin-top: 8pt; }
.badge { display: inline-block; padding: 2pt 8pt; border-radius: 12pt; font-size: 8.5pt; font-weight: 600; margin-right: 4pt; }
.badge-cat { background: #e0f2fe; color: #0369a1; }
.badge-status-done { background: #d1fae5; color: #065f46; }
.badge-status-ip   { background: #dbeafe; color: #1e40af; }
.badge-status-pd   { background: #fef3c7; color: #92400e; }
.badge-priority-low    { background: #f1f5f9; color: #475569; }
.badge-priority-normal { background: #dbeafe; color: #1e40af; }
.badge-priority-high   { background: #fef3c7; color: #92400e; }
.badge-priority-urgent { background: #fee2e2; color: #991b1b; }

/* Metadata grid */
.meta { display: table; width: 100%; border-collapse: collapse; margin-bottom: 14pt; border: 1pt solid #e2e8f0; border-radius: 4pt; overflow: hidden; }
.meta-row { display: table-row; }
.meta-cell { display: table-cell; padding: 6pt 9pt; border-bottom: 0.5pt solid #e2e8f0; font-size: 10pt; vertical-align: top; width: 50%; }
.meta-cell:first-child { border-right: 0.5pt solid #e2e8f0; }
.meta-row:last-child .meta-cell { border-bottom: none; }
.meta-label { color: #64748b; font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.5pt; font-weight: 600; display: block; margin-bottom: 2pt; }
.meta-value { color: #0f172a; font-weight: 500; }

/* Progress bar */
.pbar { display: inline-block; vertical-align: middle; width: 90pt; height: 5pt; background: #e2e8f0; border-radius: 3pt; overflow: hidden; }
.pbar-fill { height: 100%; background: #059669; }
.pbar-fill.lvl-0 { background: #94a3b8; }
.pbar-fill.lvl-1 { background: #f59e0b; }
.pbar-fill.lvl-2 { background: #3b82f6; }
.pbar-fill.lvl-3 { background: #059669; }

/* Sections */
h2 { font-size: 11.5pt; font-weight: 700; color: #1e3a8a; margin: 14pt 0 6pt 0; padding-bottom: 4pt; border-bottom: 1.5pt solid #e2e8f0; letter-spacing: 0.3pt; text-transform: uppercase; }
.section p { margin: 0; white-space: pre-wrap; line-height: 1.6; color: #334155; }

/* Photos */
.photos { display: flex; flex-wrap: wrap; gap: 6pt; margin-top: 6pt; }
.photo { flex: 0 0 calc((100% - 12pt) / 3); height: 80pt; background: #f1f5f9; border: 0.5pt solid #cbd5e1; border-radius: 3pt; overflow: hidden; }
.photo img { width: 100%; height: 100%; object-fit: cover; display: block; }

/* Review log */
.review-table { width: 100%; border-collapse: collapse; font-size: 9.5pt; margin-top: 4pt; }
.review-table th, .review-table td { padding: 5pt 8pt; text-align: left; border-bottom: 0.5pt solid #e2e8f0; vertical-align: top; }
.review-table th { background: #f8fafc; font-size: 8.5pt; color: #64748b; text-transform: uppercase; letter-spacing: 0.4pt; font-weight: 600; }
.review-table td { color: #334155; }

/* Signatures */
.signatures { display: table; width: 100%; margin-top: 28pt; page-break-inside: avoid; }
.sig-block { display: table-cell; width: 33.33%; text-align: center; font-size: 10pt; padding: 0 6pt; }
.sig-label { color: #475569; font-weight: 500; margin-bottom: 2pt; }
.sig-role { font-size: 9pt; color: #94a3b8; margin-bottom: 52pt; }
.sig-line { border-top: 1pt solid #475569; margin: 0 10pt; padding-top: 4pt; font-weight: 600; color: #0f172a; }
.sig-subtext { font-size: 8.5pt; color: #64748b; margin-top: 2pt; }

.footer { margin-top: 20pt; padding-top: 8pt; border-top: 0.5pt solid #cbd5e1; font-size: 8pt; color: #94a3b8; display: flex; justify-content: space-between; }

/* Print tweaks */
.no-print { display: none; }
@media screen {
  body { background: #f1f5f9; padding: 20pt 0; }
  .page { background: white; padding: 14mm; margin: 0 auto 12pt; box-shadow: 0 2pt 12pt rgba(0,0,0,0.08); }
  .no-print { display: block; position: fixed; top: 10pt; right: 10pt; background: #1e3a8a; color: white; padding: 8pt 14pt; border-radius: 4pt; font-size: 10pt; cursor: pointer; border: none; z-index: 100; }
  .no-print:hover { background: #1e40af; }
}
@media print {
  .no-print { display: none !important; }
  .page { box-shadow: none; padding: 0; max-width: none; }
  a { color: inherit; text-decoration: none; }
}
</style>
</head>
<body>
<button class="no-print" onclick="window.print()">🖨️ Cetak / Simpan sebagai PDF</button>

<div class="page">

  <div class="header">
    <div class="header-left">
      <div class="brand"><?= e(config('app_name')) ?></div>
      <div class="tagline">Laporan Pekerjaan IT</div>
    </div>
    <div class="header-right">
      <div class="doctype">Laporan <?= e(category_label($cat)) ?></div>
      <div class="docid">No. Dok: RPT-<?= e($docId) ?></div>
      <div class="docid"><?= e(fmt_date($report['report_date'], 'd F Y')) ?></div>
    </div>
  </div>

  <div class="title-block">
    <h1><?= e($report['title']) ?></h1>
    <?php $statusClass = ['done'=>'badge-status-done','in_progress'=>'badge-status-ip','pending'=>'badge-status-pd'][$report['status']] ?? 'badge-status-pd'; ?>
    <div class="badges">
      <span class="badge badge-cat">Kategori: <?= e(category_label($cat)) ?></span>
      <span class="badge <?= $statusClass ?>">Status: <?= e(status_label($report['status'])) ?></span>
      <span class="badge badge-priority-<?= e($report['priority'] ?? 'normal') ?>">Prioritas: <?= e(priority_label($report['priority'] ?? 'normal')) ?></span>
      <?php if (!empty($report['review_status']) && $report['review_status'] !== 'draft'): ?>
        <span class="badge badge-cat">Review: <?= e(review_status_label($report['review_status'])) ?></span>
      <?php endif; ?>
    </div>
  </div>

  <h2>Informasi Laporan</h2>
  <div class="meta">
    <div class="meta-row">
      <div class="meta-cell">
        <span class="meta-label">Tanggal Laporan</span>
        <span class="meta-value"><?= e(fmt_date($report['report_date'], 'l, d F Y')) ?></span>
      </div>
      <div class="meta-cell">
        <span class="meta-label">Pembuat Laporan</span>
        <span class="meta-value">
          <?= e($report['user_name']) ?>
          <?php if (!empty($report['user_role'])): ?>
            <span style="color:#64748b;">— <?= e(role_label($report['user_role'])) ?></span>
          <?php endif; ?>
        </span>
      </div>
    </div>
    <div class="meta-row">
      <div class="meta-cell">
        <span class="meta-label">Proyek</span>
        <span class="meta-value"><?= e($report['project_name'] ?? '—') ?></span>
      </div>
      <div class="meta-cell">
        <span class="meta-label">PIC Proyek</span>
        <span class="meta-value">
          <?= e($report['pic_name'] ?? '—') ?>
          <?php if (!empty($report['pic_role'])): ?>
            <span style="color:#64748b;">— <?= e(role_label($report['pic_role'])) ?></span>
          <?php endif; ?>
        </span>
      </div>
    </div>
    <div class="meta-row">
      <div class="meta-cell">
        <span class="meta-label">Lokasi</span>
        <span class="meta-value"><?= $report['location'] ? e($report['location']) : '—' ?></span>
      </div>
      <div class="meta-cell">
        <span class="meta-label">Durasi Pengerjaan</span>
        <span class="meta-value"><?= $report['duration'] ? e($report['duration']) : '—' ?></span>
      </div>
    </div>
    <div class="meta-row">
      <div class="meta-cell">
        <span class="meta-label">Progress</span>
        <span class="meta-value">
          <?php $lvl = $pg >= 100 ? 3 : ($pg >= 60 ? 2 : ($pg >= 25 ? 1 : 0)); ?>
          <span class="pbar"><span class="pbar-fill lvl-<?= $lvl ?>" style="width:<?= $pg ?>%"></span></span>
          <strong style="margin-left:6pt;"><?= $pg ?>%</strong>
        </span>
      </div>
      <div class="meta-cell">
        <span class="meta-label">Dibuat pada</span>
        <span class="meta-value"><?= e(fmt_datetime($report['created_at'])) ?></span>
      </div>
    </div>
  </div>

  <h2>Deskripsi Masalah / Pekerjaan</h2>
  <div class="section"><p><?= e($report['description']) ?></p></div>

  <?php if ($report['solution']): ?>
    <h2>Solusi / Tindakan</h2>
    <div class="section"><p><?= e($report['solution']) ?></p></div>
  <?php endif; ?>

  <?php if (!empty($photos)): ?>
    <h2>Dokumentasi (<?= count($photos) ?> foto)</h2>
    <div class="photos">
      <?php foreach ($photos as $ph): ?>
        <div class="photo"><img src="<?= e($ph['url']) ?>" alt="Dokumentasi"></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($reviews)): ?>
    <h2>Riwayat Aktivitas &amp; Review</h2>
    <table class="review-table">
      <thead>
        <tr>
          <th style="width: 30%;">Aksi</th>
          <th style="width: 28%;">Oleh</th>
          <th style="width: 20%;">Waktu</th>
          <th>Catatan</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reviews as $rv): ?>
          <tr>
            <td><strong><?= e(review_status_label($rv['action'])) ?></strong></td>
            <td>
              <?= e($rv['reviewer_name'] ?? 'Sistem') ?>
              <?php if (!empty($rv['reviewer_role'])): ?>
                <div style="font-size:8pt;color:#94a3b8;"><?= e(role_label($rv['reviewer_role'])) ?></div>
              <?php endif; ?>
            </td>
            <td><?= e(fmt_datetime($rv['created_at'])) ?></td>
            <td><?= $rv['notes'] ? nl2br(e($rv['notes'])) : '<span style="color:#94a3b8;font-style:italic;">—</span>' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <div class="signatures">
    <div class="sig-block">
      <div class="sig-label">Dibuat oleh,</div>
      <div class="sig-role">Pembuat Laporan</div>
      <div class="sig-line"><?= e($report['user_name']) ?></div>
      <div class="sig-subtext"><?= e(role_label($report['user_role'] ?? 'staff')) ?></div>
    </div>
    <div class="sig-block">
      <div class="sig-label">Mengetahui,</div>
      <div class="sig-role">PIC Proyek</div>
      <div class="sig-line"><?= e($report['pic_name'] ?? '—') ?></div>
      <div class="sig-subtext"><?= $report['pic_role'] ? e(role_label($report['pic_role'])) : '—' ?></div>
    </div>
    <div class="sig-block">
      <div class="sig-label">Menyetujui,</div>
      <div class="sig-role">Direktur / Atasan</div>
      <div class="sig-line">_________________</div>
      <div class="sig-subtext">Direktur</div>
    </div>
  </div>

  <div class="footer">
    <span>Laporan ini digenerate otomatis oleh sistem <?= e(config('app_name')) ?>.</span>
    <span>Dicetak: <?= e(fmt_datetime(date('Y-m-d H:i:s'))) ?></span>
  </div>

</div>

<script>
  // Auto-open print dialog untuk langsung "Save as PDF"
  window.addEventListener('load', () => setTimeout(() => window.print(), 300));
</script>
</body>
</html>
