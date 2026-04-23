<?php
use App\Auth;
use function App\{e, csrf_field, fmt_date, project_status_label, project_status_badge_class};
?>
<div class="mb-6 flex items-center justify-between flex-wrap gap-3">
  <div>
    <h1 class="text-2xl font-bold">Proyek</h1>
    <p class="text-sm text-slate-500">Daftar proyek dan PIC yang bertanggung jawab.</p>
  </div>
  <?php if (Auth::can('projects.create')): ?>
  <a href="/projects/create" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-sm text-sm font-medium">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
    Tambah Proyek
  </a>
  <?php endif; ?>
</div>

<?php if (!$projects): ?>
  <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500">
    Belum ada proyek.
  </div>
<?php else: ?>
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-slate-600 text-left">
        <tr>
          <th class="px-4 py-3 font-medium">Nama Proyek</th>
          <th class="px-4 py-3 font-medium">PIC</th>
          <th class="px-4 py-3 font-medium">Jumlah Laporan</th>
          <th class="px-4 py-3 font-medium">Status</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($projects as $p): ?>
        <tr class="hover:bg-slate-50">
          <td class="px-4 py-3">
            <div class="font-medium text-slate-900"><?= e($p['name']) ?></div>
            <?php if ($p['description']): ?>
              <div class="text-slate-500 line-clamp-1"><?= e(mb_substr($p['description'], 0, 120)) ?></div>
            <?php endif; ?>
          </td>
          <td class="px-4 py-3 text-slate-600"><?= e($p['pic_name'] ?? '-') ?></td>
          <td class="px-4 py-3 text-slate-600"><?= (int)$p['report_count'] ?></td>
          <td class="px-4 py-3">
            <span class="text-xs px-2 py-1 rounded-full <?= project_status_badge_class($p['status'] ?? 'active') ?>">
              <?= e(project_status_label($p['status'] ?? 'active')) ?>
            </span>
          </td>
          <td class="px-4 py-3 text-right">
            <?php if (Auth::can('projects.manage', $p)): ?>
              <a href="/projects/<?= e($p['id']) ?>/edit" class="text-blue-600 hover:text-blue-800 text-sm">Edit</a>
            <?php endif; ?>
            <?php if (Auth::can('projects.delete')): ?>
              <form method="POST" action="/projects/<?= e($p['id']) ?>/delete" class="inline ml-2" onsubmit="return confirm('Hapus proyek ini? Semua laporan yang terkait akan kehilangan referensi proyek.');">
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
<?php endif; ?>
