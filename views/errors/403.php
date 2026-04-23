<?php use function App\e; ?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>403 — Akses Ditolak</title>
<link rel="icon" type="image/png" href="/public_assets/favicon.png">
<link rel="stylesheet" href="/public_assets/app.css?v=1">
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
  <div class="max-w-md text-center">
    <div class="text-7xl font-bold text-slate-300">403</div>
    <h1 class="text-xl font-semibold mt-3">Akses Ditolak</h1>
    <p class="text-slate-500 text-sm mt-1">
      <?= e($message ?? 'Anda tidak memiliki hak akses untuk halaman ini.') ?>
    </p>
    <a href="/" class="inline-block mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm">Kembali ke Dashboard</a>
  </div>
</body>
</html>
