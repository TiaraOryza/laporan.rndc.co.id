<?php
namespace App\Controllers;

use App\Auth;
use App\Database;
use function App\{view, redirect, flash_set, csrf_check, uuid, abort, project_status_is_active};
use const App\PROJECT_STATUSES;

class ProjectController
{
    public function index(): void
    {
        $user = Auth::requireCan('projects.view');
        $pdo = Database::pdo();
        $projects = $pdo->query("
            SELECT p.*, u.name AS pic_name,
                   (SELECT COUNT(*) FROM daily_reports r WHERE r.project_id = p.id) AS report_count
            FROM projects p
            LEFT JOIN users u ON u.id = p.pic_id
            ORDER BY p.active DESC, p.name
        ")->fetchAll();
        view('projects/index', compact('user', 'projects'));
    }

    public function create(): void
    {
        $user = Auth::requireCan('projects.create');
        $pics = Database::pdo()->query(
            "SELECT id, name, role FROM users
             WHERE active=1 AND role IN ('pic', 'manager', 'admin')
             ORDER BY name"
        )->fetchAll();
        view('projects/form', [
            'user' => $user,
            'project' => null,
            'pics' => $pics,
            'action' => '/projects',
            'submit_label' => 'Simpan Proyek',
        ]);
    }

    public function store(): void
    {
        csrf_check();
        $user = Auth::requireCan('projects.create');
        $name = trim($_POST['name'] ?? '');
        if (!$name) {
            flash_set('error', 'Nama proyek wajib diisi.');
            redirect('/projects/create');
        }

        $status = $_POST['status'] ?? 'active';
        if (!isset(PROJECT_STATUSES[$status])) $status = 'active';

        // PIC can create, but only with themselves as PIC (no assigning others).
        if ($user['role'] === 'pic') {
            $picId = $user['id'];
        } else {
            $picId = ($_POST['pic_id'] ?? '') ?: null;
        }

        $pdo = Database::pdo();
        $pdo->prepare("INSERT INTO projects (id, name, description, pic_id, status, active) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([
                uuid(),
                $name,
                trim($_POST['description'] ?? '') ?: null,
                $picId,
                $status,
                project_status_is_active($status) ? 1 : 0,
            ]);
        flash_set('success', 'Proyek berhasil ditambahkan.');
        redirect('/projects');
    }

    public function edit(string $id): void
    {
        $user = Auth::require();
        $pdo = Database::pdo();
        $s = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $s->execute([$id]);
        $project = $s->fetch();
        if (!$project) { http_response_code(404); die('Tidak ditemukan'); }
        // Per-project gate: PIC of this project is allowed; others follow role rules.
        if (!Auth::can('projects.manage', $project)) abort(403);

        // Include the current PIC in the list even if their role no longer matches,
        // so editing legacy projects doesn't silently drop the assignment.
        $s = $pdo->prepare(
            "SELECT id, name, role FROM users
             WHERE active=1 AND (role IN ('pic', 'manager', 'admin') OR id = ?)
             ORDER BY name"
        );
        $s->execute([$project['pic_id'] ?? '']);
        $pics = $s->fetchAll();
        view('projects/form', [
            'user' => $user,
            'project' => $project,
            'pics' => $pics,
            'action' => '/projects/' . $id . '/update',
            'submit_label' => 'Perbarui Proyek',
        ]);
    }

    public function update(string $id): void
    {
        csrf_check();
        Auth::require();
        $pdo = Database::pdo();
        $s = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $s->execute([$id]);
        $project = $s->fetch();
        if (!$project) { http_response_code(404); die('Tidak ditemukan'); }
        if (!Auth::can('projects.manage', $project)) abort(403);

        $name = trim($_POST['name'] ?? '');
        if (!$name) {
            flash_set('error', 'Nama proyek wajib diisi.');
            redirect('/projects/' . $id . '/edit');
        }

        $status = $_POST['status'] ?? $project['status'] ?? 'active';
        if (!isset(PROJECT_STATUSES[$status])) $status = 'active';

        // PIC can only keep or transfer PIC rights to another valid PIC/manager/admin.
        // If the posted pic_id is different from current and user is pic, they'd be
        // transferring the project. Allow it (they lose access after) — matches current behavior.
        $picId = ($_POST['pic_id'] ?? '') ?: null;

        $pdo->prepare("UPDATE projects SET
                name = ?, description = ?, pic_id = ?, status = ?, active = ?, updated_at = datetime('now')
            WHERE id = ?")
            ->execute([
                $name,
                trim($_POST['description'] ?? '') ?: null,
                $picId,
                $status,
                project_status_is_active($status) ? 1 : 0,
                $id,
            ]);
        flash_set('success', 'Proyek berhasil diperbarui.');
        redirect('/projects');
    }

    public function destroy(string $id): void
    {
        csrf_check();
        Auth::requireCan('projects.delete');
        Database::pdo()->prepare("DELETE FROM projects WHERE id = ?")->execute([$id]);
        flash_set('success', 'Proyek berhasil dihapus.');
        redirect('/projects');
    }
}
