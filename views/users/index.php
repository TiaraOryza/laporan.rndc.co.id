<?php
use function App\{e, csrf_field, role_label, role_badge_class};
?>
<div class="mb-6 flex items-center justify-between flex-wrap gap-3">
  <div>
    <h1 class="text-2xl font-bold">Pengguna</h1>
    <p class="text-sm text-slate-500">Kelola akun, role, dan akses pengguna.</p>
  </div>
  <a href="/users/create" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-sm text-sm font-medium">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
    Tambah Pengguna
  </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-slate-600 text-left">
        <tr>
          <th class="px-4 py-3 font-medium">Nama</th>
          <th class="px-4 py-3 font-medium">Email</th>
          <th class="px-4 py-3 font-medium">Telepon</th>
          <th class="px-4 py-3 font-medium">Role</th>
          <th class="px-4 py-3 font-medium">Status</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($users as $u): ?>
        <tr class="hover:bg-slate-50">
          <td class="px-4 py-3 font-medium"><?= e($u['name']) ?></td>
          <td class="px-4 py-3 text-slate-600"><?= e($u['email']) ?></td>
          <td class="px-4 py-3 text-slate-600"><?= e($u['phone'] ?? '-') ?></td>
          <td class="px-4 py-3"><span class="text-xs px-2 py-1 rounded-full <?= role_badge_class($u['role']) ?>"><?= e(role_label($u['role'])) ?></span></td>
          <td class="px-4 py-3">
            <?php if ($u['active']): ?>
              <span class="text-xs px-2 py-1 rounded-full bg-emerald-100 text-emerald-700">Aktif</span>
            <?php else: ?>
              <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600">Nonaktif</span>
            <?php endif; ?>
          </td>
          <td class="px-4 py-3 text-right">
            <a href="/users/<?= e($u['id']) ?>/edit" class="text-blue-600 hover:text-blue-800 text-sm">Edit</a>
            <?php if ($u['id'] !== $user['id']): ?>
              <form method="POST" action="/users/<?= e($u['id']) ?>/delete" class="inline ml-2" onsubmit="return confirm('Hapus pengguna ini? Laporan milik pengguna akan ikut terhapus.');">
                <?= csrf_field() ?>
                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Hapus</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
