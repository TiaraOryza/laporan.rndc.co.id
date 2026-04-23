<?php
namespace App\Controllers;

use App\Auth;
use App\Database;
use function App\{view, view_raw, redirect, flash_set, csrf_check, uuid, abort, e};

class ProjectReportController
{
    public function index(): void
    {
        $user = Auth::require();
        if (!Auth::can('projectReports.viewAll') && !Auth::can('projectReports.viewOwn')) {
            abort(403);
        }
        $pdo = Database::pdo();

        $filter = [
            'from'       => $_GET['from']       ?? date('Y-m-01'),
            'to'         => $_GET['to']         ?? date('Y-m-d'),
            'project_id' => $_GET['project_id'] ?? '',
            'user_id'    => $_GET['user_id']    ?? '',
            'status'     => $_GET['status']     ?? '',
            'q'          => trim($_GET['q']     ?? ''),
        ];

        $where = ["date(pr.created_at) BETWEEN ? AND ?"];
        $params = [$filter['from'], $filter['to']];

        // Scope: PIC hanya lihat laporan proyek yang dia pegang.
        if (!Auth::can('projectReports.viewAll')) {
            $where[] = "p.pic_id = ?";
            $params[] = $user['id'];
        } else {
            if ($filter['user_id']) {
                $where[] = "pr.user_id = ?";
                $params[] = $filter['user_id'];
            }
        }
        if ($filter['project_id']) {
            $where[] = "pr.project_id = ?";
            $params[] = $filter['project_id'];
        }
        if ($filter['status']) {
            $where[] = "pr.status = ?";
            $params[] = $filter['status'];
        }
        if ($filter['q']) {
            $where[] = "(pr.title LIKE ? OR pr.summary LIKE ?)";
            $like = '%' . $filter['q'] . '%';
            $params[] = $like; $params[] = $like;
        }

        $sql = "SELECT pr.*, p.name AS project_name, u.name AS author_name, u.role AS author_role
                FROM project_reports pr
                LEFT JOIN projects p ON p.id = pr.project_id
                LEFT JOIN users u ON u.id = pr.user_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY pr.created_at DESC
                LIMIT 200";
        $s = $pdo->prepare($sql);
        $s->execute($params);
        $reports = $s->fetchAll();

        // Projects dropdown untuk filter
        $projects = Auth::can('projectReports.viewAll')
            ? $pdo->query("SELECT id, name FROM projects ORDER BY name")->fetchAll()
            : (function () use ($pdo, $user) {
                $s = $pdo->prepare("SELECT id, name FROM projects WHERE pic_id = ? ORDER BY name");
                $s->execute([$user['id']]);
                return $s->fetchAll();
            })();

        // PIC/author dropdown — hanya utk admin/direktur
        $authors = Auth::can('projectReports.viewAll')
            ? $pdo->query("SELECT id, name FROM users WHERE role IN ('pic','admin','director','manager') AND active=1 ORDER BY name")->fetchAll()
            : [];

        view('project_reports/index', compact('user', 'reports', 'projects', 'authors', 'filter'));
    }

    public function create(): void
    {
        $user = Auth::requireCan('projectReports.create');
        $pdo = Database::pdo();
        // Proyek yang user ini pegang sebagai PIC — satu-satunya yang boleh dipilih.
        $s = $pdo->prepare("SELECT id, name FROM projects WHERE pic_id = ? ORDER BY name");
        $s->execute([$user['id']]);
        $projects = $s->fetchAll();
        if (!$projects) {
            flash_set('error', 'Anda belum menjadi PIC dari proyek manapun. Hubungi admin untuk penunjukan.');
            redirect('/project-reports');
        }

        view('project_reports/form', [
            'user' => $user,
            'report' => null,
            'projects' => $projects,
            'action' => '/project-reports',
            'submit_label' => 'Simpan Laporan Proyek',
        ]);
    }

    public function store(): void
    {
        csrf_check();
        $user = Auth::requireCan('projectReports.create');
        $data = $this->validate($_POST);
        if (!empty($data['_errors'])) {
            flash_set('error', implode("\n", $data['_errors']));
            redirect('/project-reports/create');
        }

        // Pastikan project_id yang disubmit memang di-handle PIC ini (anti-tamper).
        $pdo = Database::pdo();
        $check = $pdo->prepare("SELECT 1 FROM projects WHERE id = ? AND pic_id = ?");
        $check->execute([$data['project_id'], $user['id']]);
        if (!$check->fetchColumn()) {
            flash_set('error', 'Anda bukan PIC dari proyek yang dipilih.');
            redirect('/project-reports/create');
        }

        $id = uuid();
        $pdo->prepare("INSERT INTO project_reports
            (id, project_id, user_id, title, summary, period_from, period_to, status, progress)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([
                $id, $data['project_id'], $user['id'],
                $data['title'], $data['summary'],
                $data['period_from'], $data['period_to'],
                $data['status'], $data['progress'],
            ]);

        flash_set('success', 'Laporan proyek berhasil disimpan.');
        redirect('/project-reports/' . $id);
    }

    public function show(string $id): void
    {
        $user = Auth::require();
        $pdo = Database::pdo();
        $s = $pdo->prepare("SELECT pr.*, p.name AS project_name, p.pic_id AS project_pic_id,
                                   u.name AS author_name, u.role AS author_role
            FROM project_reports pr
            LEFT JOIN projects p ON p.id = pr.project_id
            LEFT JOIN users u ON u.id = pr.user_id
            WHERE pr.id = ?");
        $s->execute([$id]);
        $report = $s->fetch();
        if (!$report) { http_response_code(404); view_raw('errors/404', ['path' => '/project-reports/' . $id]); return; }

        $canSee = Auth::can('projectReports.viewAll')
            || ($user['role'] === 'pic' && $report['project_pic_id'] === $user['id']);
        if (!$canSee) abort(403);

        view('project_reports/show', compact('user', 'report'));
    }

    public function edit(string $id): void
    {
        $user = Auth::require();
        $pdo = Database::pdo();
        $s = $pdo->prepare("SELECT pr.*, p.pic_id AS project_pic_id
            FROM project_reports pr
            LEFT JOIN projects p ON p.id = pr.project_id
            WHERE pr.id = ?");
        $s->execute([$id]);
        $report = $s->fetch();
        if (!$report) { http_response_code(404); die('Tidak ditemukan'); }
        if (!Auth::can('projectReports.edit', $report)) abort(403);

        // PIC hanya boleh pindahkan ke proyek yang dia pegang.
        if ($user['role'] === 'pic') {
            $q = $pdo->prepare("SELECT id, name FROM projects WHERE pic_id = ? ORDER BY name");
            $q->execute([$user['id']]);
        } else {
            $q = $pdo->query("SELECT id, name FROM projects ORDER BY name");
        }
        $projects = $q->fetchAll();

        view('project_reports/form', [
            'user' => $user,
            'report' => $report,
            'projects' => $projects,
            'action' => '/project-reports/' . $id . '/update',
            'submit_label' => 'Perbarui Laporan',
        ]);
    }

    public function update(string $id): void
    {
        csrf_check();
        $user = Auth::require();
        $pdo = Database::pdo();
        $s = $pdo->prepare("SELECT pr.*, p.pic_id AS project_pic_id
            FROM project_reports pr
            LEFT JOIN projects p ON p.id = pr.project_id
            WHERE pr.id = ?");
        $s->execute([$id]);
        $report = $s->fetch();
        if (!$report) { http_response_code(404); die('Tidak ditemukan'); }
        if (!Auth::can('projectReports.edit', $report)) abort(403);

        $data = $this->validate($_POST);
        if (!empty($data['_errors'])) {
            flash_set('error', implode("\n", $data['_errors']));
            redirect('/project-reports/' . $id . '/edit');
        }

        // PIC hanya boleh assign ke proyek yang dia pegang.
        if ($user['role'] === 'pic') {
            $check = $pdo->prepare("SELECT 1 FROM projects WHERE id = ? AND pic_id = ?");
            $check->execute([$data['project_id'], $user['id']]);
            if (!$check->fetchColumn()) {
                flash_set('error', 'Proyek yang dipilih bukan tanggung jawab Anda.');
                redirect('/project-reports/' . $id . '/edit');
            }
        }

        $pdo->prepare("UPDATE project_reports SET
                project_id = ?, title = ?, summary = ?,
                period_from = ?, period_to = ?, status = ?, progress = ?,
                updated_at = datetime('now')
            WHERE id = ?")->execute([
                $data['project_id'], $data['title'], $data['summary'],
                $data['period_from'], $data['period_to'],
                $data['status'], $data['progress'], $id,
            ]);

        flash_set('success', 'Laporan proyek berhasil diperbarui.');
        redirect('/project-reports/' . $id);
    }

    public function destroy(string $id): void
    {
        csrf_check();
        Auth::requireCan('projectReports.delete');
        Database::pdo()->prepare("DELETE FROM project_reports WHERE id = ?")->execute([$id]);
        flash_set('success', 'Laporan proyek berhasil dihapus.');
        redirect('/project-reports');
    }

    private function validate(array $in): array
    {
        $errors = [];
        $title = trim($in['title'] ?? '');
        $summary = trim($in['summary'] ?? '');
        $project = $in['project_id'] ?? '';
        $from = $in['period_from'] ?? '';
        $to   = $in['period_to'] ?? '';
        $status = $in['status'] ?? 'done';
        $progress = (int)($in['progress'] ?? 0);

        if (!$title)   $errors[] = 'Judul laporan wajib diisi.';
        if (!$summary) $errors[] = 'Isi ringkasan laporan wajib diisi.';
        if (!$project) $errors[] = 'Proyek wajib dipilih.';
        if ($from && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $errors[] = 'Tanggal awal tidak valid.';
        if ($to && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))     $errors[] = 'Tanggal akhir tidak valid.';
        if ($from && $to && $from > $to) $errors[] = 'Tanggal awal tidak boleh lebih besar dari tanggal akhir.';

        if (!in_array($status, ['done', 'in_progress', 'pending'], true)) $status = 'done';
        if ($progress < 0)   $progress = 0;
        if ($progress > 100) $progress = 100;

        return [
            'title' => $title,
            'summary' => $summary,
            'project_id' => $project,
            'period_from' => $from ?: null,
            'period_to'   => $to ?: null,
            'status'      => $status,
            'progress'    => $progress,
            '_errors' => $errors,
        ];
    }
}
