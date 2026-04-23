<?php
use function App\{e, config, fmt_date, status_label};
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rangkuman Laporan - <?= e($label) ?></title>
<style>
  * { box-sizing: border-box; }
  body { font-family: "Segoe UI", Arial, sans-serif; color: #1e293b; margin: 0; padding: 24px; }
  .page { max-width: 800px; margin: 0 auto; }
  h1 { margin: 0 0 4px; font-size: 22px; }
  .sub { color: #64748b; font-size: 13px; }
  .meta { display: flex; gap: 20px; font-size: 12px; color: #64748b; margin: 6px 0 18px; flex-wrap: wrap; }
  .stats { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin-bottom: 18px; }
  .stat { border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; }
  .stat .label { font-size: 11px; color: #64748b; }
  .stat .value { font-size: 20px; font-weight: 700; }
  .day { border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 12px; break-inside: avoid; page-break-inside: avoid; }
  .day-head { background: #f8fafc; padding: 8px 14px; border-bottom: 1px solid #e2e8f0; font-weight: 600; display: flex; justify-content: space-between; font-size: 13px; }
  .item { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; break-inside: avoid; page-break-inside: avoid; }
  .item:last-child { border-bottom: none; }
  .item .title { font-weight: 600; font-size: 14px; margin-bottom: 2px; }
  .item .meta-line { font-size: 11px; color: #64748b; margin-bottom: 6px; }
  .item p { margin: 4px 0; font-size: 12px; line-height: 1.5; white-space: pre-wrap; }
  .badge { display: inline-block; font-size: 10px; padding: 2px 6px; border-radius: 10px; font-weight: 600; }
  .b-done { background: #d1fae5; color: #065f46; }
  .b-in_progress { background: #e0f2fe; color: #075985; }
  .b-pending { background: #fef3c7; color: #92400e; }
  .foot { margin-top: 24px; font-size: 11px; color: #94a3b8; text-align: center; }
  .actions { position: fixed; top: 10px; right: 10px; }
  .actions button { padding: 8px 14px; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
  @media print {
    .actions { display: none; }
    body { padding: 12px; }
  }
  @page { size: A4; margin: 14mm; }
</style>
</head>
<body>
<div class="actions">
  <button onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
</div>

<div class="page">
  <h1><?= e(config('app_name')) ?></h1>
  <div class="sub">Rangkuman Laporan Pekerjaan Harian IT</div>
  <div class="meta">
    <span><strong>Periode:</strong> <?= e($label) ?></span>
    <span><strong>Dicetak:</strong> <?= date('d F Y H:i') ?></span>
    <span><strong>Oleh:</strong> <?= e($user['name']) ?></span>
  </div>

  <div class="stats">
    <div class="stat"><div class="label">Total</div><div class="value"><?= (int)$stats['total'] ?></div></div>
    <div class="stat"><div class="label">Selesai</div><div class="value"><?= (int)($stats['done'] ?? 0) ?></div></div>
    <div class="stat"><div class="label">Dikerjakan</div><div class="value"><?= (int)($stats['in_progress'] ?? 0) ?></div></div>
    <div class="stat"><div class="label">Pending</div><div class="value"><?= (int)($stats['pending'] ?? 0) ?></div></div>
    <div class="stat"><div class="label">Staff/Proyek</div><div class="value"><?= (int)$stats['users'] ?>/<?= (int)$stats['projects'] ?></div></div>
  </div>

  <?php if (!$reports): ?>
    <p style="text-align:center;color:#64748b;font-size:13px;padding:24px;">Tidak ada laporan pada periode ini.</p>
  <?php else: foreach ($byDate as $date => $items): ?>
    <div class="day">
      <div class="day-head">
        <span><?= e(fmt_date($date, 'l, d F Y')) ?></span>
        <span><?= count($items) ?> laporan</span>
      </div>
      <?php foreach ($items as $r): ?>
      <div class="item">
        <div class="title"><?= e($r['title']) ?> <span class="badge b-<?= e($r['status']) ?>"><?= e(status_label($r['status'])) ?></span></div>
        <div class="meta-line">
          Staff: <?= e($r['user_name']) ?>
          <?php if ($r['project_name']): ?> &nbsp;•&nbsp; Proyek: <?= e($r['project_name']) ?><?php endif; ?>
        </div>
        <p><strong>Deskripsi:</strong> <?= e($r['description']) ?></p>
        <?php if ($r['solution']): ?>
          <p><strong>Solusi:</strong> <?= e($r['solution']) ?></p>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; endif; ?>

  <div class="foot">
    Dokumen ini di-generate secara otomatis oleh sistem <?= e(config('app_name')) ?>.
  </div>
</div>
</body>
</html>
