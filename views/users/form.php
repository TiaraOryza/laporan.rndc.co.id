<?php
use function App\{e, csrf_field, role_label};
$t = $target ?? null;
$roles = ['admin', 'director', 'manager', 'staff', 'pic', 'employee'];
?>
<div class="mb-6">
  <a href="/users" class="text-sm text-slate-500 hover:text-slate-700">← Kembali ke Daftar</a>
  <h1 class="text-2xl font-bold mt-1"><?= $t ? 'Edit Pengguna' : 'Tambah Pengguna' ?></h1>
</div>

<form method="POST" action="<?= e($action) ?>" class="bg-white rounded-xl border border-slate-200 p-6 space-y-5 max-w-2xl">
  <?= csrf_field() ?>
  <div class="grid md:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
      <input type="text" name="name" required value="<?= e($t['name'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Email <span class="text-red-500">*</span></label>
      <input type="email" name="email" required value="<?= e($t['email'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Nomor WhatsApp</label>
      <input type="text" name="phone" value="<?= e($t['phone'] ?? '') ?>" placeholder="62812xxxxxxxx" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Role <span class="text-red-500">*</span></label>
      <select name="role" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <?php foreach ($roles as $r): ?>
          <option value="<?= $r ?>" <?= ($t['role'] ?? 'staff') === $r ? 'selected' : '' ?>><?= e(role_label($r)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm font-medium text-slate-700 mb-1">
        Password <?= $t ? '<span class="text-slate-400 text-xs">(kosongkan jika tidak ingin mengubah)</span>' : '<span class="text-red-500">*</span>' ?>
      </label>
      <input type="password" name="password" <?= $t ? '' : 'required' ?> minlength="6" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <?php if ($t): ?>
    <div class="md:col-span-2">
      <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="active" value="1" <?= $t['active'] ? 'checked' : '' ?> class="rounded">
        <span class="text-sm">Akun aktif</span>
      </label>
    </div>
    <?php endif; ?>
  </div>
  <div class="flex items-center justify-end gap-3 pt-2">
    <a href="/users" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm">Batal</a>
    <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium"><?= e($submit_label) ?></button>
  </div>
</form>
