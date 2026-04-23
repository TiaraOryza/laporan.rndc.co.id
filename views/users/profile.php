<?php
use function App\{e, csrf_field, role_label, role_badge_class};
?>
<div class="mb-6">
  <h1 class="text-2xl font-bold">Profil Saya</h1>
  <p class="text-sm text-slate-500">Perbarui informasi akun dan password Anda.</p>
</div>

<div class="grid md:grid-cols-3 gap-4 max-w-4xl">
  <div class="bg-white rounded-xl border border-slate-200 p-5 text-center">
    <div class="w-20 h-20 rounded-full bg-slate-800 text-white flex items-center justify-center text-3xl font-bold mx-auto mb-3">
      <?= e(strtoupper(mb_substr($user['name'], 0, 1))) ?>
    </div>
    <div class="font-semibold"><?= e($user['name']) ?></div>
    <div class="text-xs text-slate-500 mb-2"><?= e($user['email']) ?></div>
    <span class="inline-block text-xs px-2 py-1 rounded-full <?= role_badge_class($user['role']) ?>"><?= e(role_label($user['role'])) ?></span>
  </div>
  <form method="POST" action="/profile" class="md:col-span-2 bg-white rounded-xl border border-slate-200 p-6 space-y-4">
    <?= csrf_field() ?>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Nama</label>
      <input type="text" name="name" value="<?= e($user['name']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Nomor WhatsApp</label>
      <input type="text" name="phone" value="<?= e($user['phone'] ?? '') ?>" placeholder="62812xxxxxxxx" class="w-full rounded-lg border border-slate-300 px-3 py-2">
    </div>
    <div class="border-t border-slate-100 pt-4">
      <h3 class="text-sm font-semibold text-slate-700 mb-2">Ubah Password</h3>
      <div class="grid md:grid-cols-2 gap-3">
        <div>
          <label class="block text-xs text-slate-500 mb-1">Password Saat Ini</label>
          <input type="password" name="current_password" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <div>
          <label class="block text-xs text-slate-500 mb-1">Password Baru</label>
          <input type="password" name="password" minlength="6" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
      </div>
      <p class="mt-1 text-xs text-slate-500">Kosongkan bila tidak ingin mengubah password.</p>
    </div>
    <div class="flex items-center justify-end pt-2">
      <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium">Simpan Perubahan</button>
    </div>
  </form>
</div>
