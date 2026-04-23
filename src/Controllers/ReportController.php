<?php
namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Services\WhatsAppService;
use function App\{view, view_raw, redirect, flash_set, flash_get, csrf_check, uuid, config, e, abort};

class ReportController
{
    public function index(): void
    {
        $user = Auth::require();
        $pdo = Database::pdo();

        $filter = [
            'from'       => $_GET['from']       ?? date('Y-m-01'),
            'to'         => $_GET['to']         ?? date('Y-m-d'),
            'user_id'    => $_GET['user_id']    ?? '',
            'project_id' => $_GET['project_id'] ?? '',
            'q'          => trim($_GET['q']     ?? ''),
            'status'     => $_GET['status']     ?? '',
            'category'   => $_GET['category']   ?? '',
        ];

        $where = ["r.report_date BETWEEN ? AND ?"];
        $params = [$filter['from'], $filter['to']];

        [$scopeSql, $scopeParams] = Auth::reportScopeSql('r');
        $where[] = $scopeSql;
        $params = array_merge($params, $scopeParams);

        // user_id filter: only meaningful for viewAll roles (admin, manager).
        // For other roles, scope already restricts rows, and the filter UI is hidden.
        if (Auth::can('reports.viewAll') && $filter['user_id']) {
            $where[] = "r.user_id = ?";
            $params[] = $filter['user_id'];
        }
        if ($filter['project_id']) {
            $where[] = "r.project_id = ?";
            $params[] = $filter['project_id'];
        }
        if ($filter['status']) {
            $where[] = "r.status = ?";
            $params[] = $filter['status'];
        }
        if ($filter['category']) {
            $where[] = "r.category = ?";
            $params[] = $filter['category'];
        }
        if ($filter['q']) {
            $where[] = "(r.title LIKE ? OR r.description LIKE ? OR r.solution LIKE ?)";
            $like = '%' . $filter['q'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT r.*, u.name AS user_name, p.name AS project_name, pic.name AS pic_name
                FROM daily_reports r
                LEFT JOIN users u ON u.id = r.user_id
                LEFT JOIN projects p ON p.id = r.project_id
                LEFT JOIN users pic ON pic.id = p.pic_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY r.report_date DESC, r.created_at DESC
                LIMIT 200";
        $s = $pdo->prepare($sql);
        $s->execute($params);
        $reports = $s->fetchAll();

        $users = Auth::can('reports.viewAll')
            ? $pdo->query("SELECT id, name FROM users WHERE active=1 ORDER BY name")->fetchAll()
            : [];
        $projects = $pdo->query("SELECT id, name FROM projects WHERE active=1 ORDER BY name")->fetchAll();

        view('reports/index', compact('user', 'reports', 'filter', 'users', 'projects'));
    }

    public function create(): void
    {
        $user = Auth::require();
        if (!Auth::can('reports.create')) {
            abort(403);
        }
        $pdo = Database::pdo();
        $projects = $pdo->query("SELECT id, name FROM projects WHERE active=1 ORDER BY name")->fetchAll();
        view('reports/form', [
            'user' => $user,
            'projects' => $projects,
            'report' => null,
            'action' => '/reports',
            'submit_label' => 'Simpan Laporan',
        ]);
    }

    public function store(): void
    {
        csrf_check();
        $user = Auth::require();
        if (!Auth::can('reports.create')) {
            abort(403);
        }

        $data = $this->validate($_POST);
        if (!empty($data['_errors'])) {
            flash_set('error', implode("\n", $data['_errors']));
            redirect('/reports/create');
        }

        [$photoUrls, $photoErrors] = $this->handlePhotos();

        $id = uuid();
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("INSERT INTO daily_reports
            (id, user_id, project_id, report_date, title, description, solution, status, photo_url,
             progress, priority, duration, location, category)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $id,
            $user['id'],
            $data['project_id'] ?: null,
            $data['report_date'],
            $data['title'],
            $data['description'],
            $data['solution'],
            $data['status'],
            null, // legacy photo_url column — superseded by report_photos
            $data['progress'],
            $data['priority'],
            $data['duration'],
            $data['location'],
            $data['category'],
        ]);

        $this->insertPhotos($pdo, $id, $photoUrls);

        // Log 'created' activity
        $pdo->prepare("INSERT INTO report_reviews (id, report_id, reviewer_id, action) VALUES (?,?,?,?)")
            ->execute([uuid(), $id, $user['id'], 'created']);

        // WhatsApp notification (non-blocking, errors ignored)
        $projectName = '-';
        if ($data['project_id']) {
            $q = $pdo->prepare("SELECT name FROM projects WHERE id = ?");
            $q->execute([$data['project_id']]);
            $projectName = $q->fetchColumn() ?: '-';
        }
        (new WhatsAppService())->sendReportNotification([
            'user_name'    => $user['name'],
            'report_date'  => $data['report_date'],
            'title'        => $data['title'],
            'description'  => $data['description'],
            'solution'     => $data['solution'],
            'project_name' => $projectName,
            'status'       => $data['status'],
        ]);

        $msg = 'Laporan berhasil disimpan.';
        if ($photoErrors) {
            flash_set('error', "Laporan tersimpan, tapi beberapa foto gagal diunggah:\n" . implode("\n", $photoErrors));
        } else {
            flash_set('success', $msg);
        }
        redirect('/reports/' . $id);
    }

    public function show(string $id): void
    {
        $user = Auth::require();
        $pdo = Database::pdo();
        $s = $pdo->prepare("SELECT r.*, u.name AS user_name, u.email AS user_email,
                                   p.name AS project_name, p.pic_id AS project_pic_id
            FROM daily_reports r
            LEFT JOIN users u ON u.id = r.user_id
            LEFT JOIN projects p ON p.id = r.project_id
            WHERE r.id = ?");
        $s->execute([$id]);
        $report = $s->fetch();
        if (!$report) {
            http_response_code(404);
            view_raw('errors/404', ['path' => '/reports/' . $id]);
            return;
        }

        $canSee = Auth::can('reports.viewAll')
            || $report['user_id'] === $user['id']
            || (Auth::can('reports.viewOwn.project') && $report['project_pic_id'] === $user['id']);
        if (!$canSee) {
            abort(403);
        }
        $photos = $this->fetchPhotos($pdo, $id);
        $reviews = $this->fetchReviews($pdo, $id);
        view('reports/show', [
            'user'    => $user,
            'report'  => $report,
            'photos'  => $photos,
            'reviews' => $reviews,
        ]);
    }

    public function pdf(string $id): void
    {
        $user = Auth::require();
        $pdo = Database::pdo();
        $s = $pdo->prepare("SELECT r.*, u.name AS user_name, u.email AS user_email, u.role AS user_role, u.phone AS user_phone,
                                   p.name AS project_name, p.pic_id AS project_pic_id, p.description AS project_description,
                                   pic.name AS pic_name, pic.role AS pic_role
            FROM daily_reports r
            LEFT JOIN users u ON u.id = r.user_id
            LEFT JOIN projects p ON p.id = r.project_id
            LEFT JOIN users pic ON pic.id = p.pic_id
            WHERE r.id = ?");
        $s->execute([$id]);
        $report = $s->fetch();
        if (!$report) { http_response_code(404); view_raw('errors/404', ['path' => '/reports/' . $id]); return; }

        $canSee = Auth::can('reports.viewAll')
            || $report['user_id'] === $user['id']
            || (Auth::can('reports.viewOwn.project') && $report['project_pic_id'] === $user['id']);
        if (!$canSee) abort(403);

        $photos = $this->fetchPhotos($pdo, $id);
        $reviews = $this->fetchReviews($pdo, $id);
        view_raw('reports/pdf', [
            'user'    => $user,
            'report'  => $report,
            'photos'  => $photos,
            'reviews' => $reviews,
        ]);
    }

    public function review(string $id): void
    {
        csrf_check();
        $user = Auth::require();
        $pdo = Database::pdo();

        $s = $pdo->prepare("SELECT r.*, p.pic_id AS project_pic_id
            FROM daily_reports r
            LEFT JOIN projects p ON p.id = r.project_id
            WHERE r.id = ?");
        $s->execute([$id]);
        $report = $s->fetch();
        if (!$report) { http_response_code(404); die('Tidak ditemukan'); }
        if (!Auth::can('reports.review', $report)) abort(403);

        $action = $_POST['action'] ?? '';
        if (!in_array($action, ['reviewing', 'approved', 'revision'], true)) {
            flash_set('error', 'Aksi review tidak valid.');
            redirect('/reports/' . $id);
        }

        // Role-scoped: PIC can't set director actions, director can't falsely mark pic-level
        $isProjectPic = ($user['role'] === 'pic' && $report['project_pic_id'] === $user['id']);
        $allowed = \App\review_actions_for_role($user['role'], $isProjectPic);
        if (!in_array($action, $allowed, true)) {
            flash_set('error', 'Anda tidak berwenang melakukan aksi review ini.');
            redirect('/reports/' . $id);
        }

        $notes = trim($_POST['notes'] ?? '') ?: null;

        $pdo->prepare("INSERT INTO report_reviews (id, report_id, reviewer_id, action, notes) VALUES (?,?,?,?,?)")
            ->execute([uuid(), $id, $user['id'], $action, $notes]);
        $pdo->prepare("UPDATE daily_reports SET review_status = ?, updated_at = datetime('now') WHERE id = ?")
            ->execute([$action, $id]);

        flash_set('success', 'Review tersimpan: ' . \App\review_status_label($action));
        redirect('/reports/' . $id);
    }

    /**
     * Compare old report row vs submitted $data to produce a human list of changes.
     * Photos are tracked separately via $addedCount / $removedCount since they live
     * in their own table.
     */
    private function describeChanges(\PDO $pdo, array $old, array $data, int $addedPhotos, int $removedPhotos): array
    {
        $changes = [];

        // Free-text fields: just note that they were modified (no diff dump).
        $textFields = [
            'title'       => 'judul',
            'description' => 'deskripsi',
            'solution'    => 'solusi',
            'duration'    => 'durasi',
            'location'    => 'lokasi',
        ];
        foreach ($textFields as $col => $label) {
            if ((string)($old[$col] ?? '') !== (string)($data[$col] ?? '')) {
                $changes[] = "Mengubah $label";
            }
        }

        // report_date
        if (($old['report_date'] ?? '') !== ($data['report_date'] ?? '')) {
            $changes[] = sprintf(
                'Mengubah tanggal: %s → %s',
                \App\fmt_date($old['report_date']),
                \App\fmt_date($data['report_date'])
            );
        }

        // status (enum)
        if (($old['status'] ?? '') !== ($data['status'] ?? '')) {
            $changes[] = sprintf(
                'Mengubah status: %s → %s',
                \App\status_label($old['status']),
                \App\status_label($data['status'])
            );
        }

        // progress
        if ((int)($old['progress'] ?? 0) !== (int)($data['progress'] ?? 0)) {
            $changes[] = sprintf(
                'Mengubah progress: %d%% → %d%%',
                (int)$old['progress'],
                (int)$data['progress']
            );
        }

        // priority (enum)
        if (($old['priority'] ?? 'normal') !== ($data['priority'] ?? 'normal')) {
            $changes[] = sprintf(
                'Mengubah prioritas: %s → %s',
                \App\priority_label($old['priority']),
                \App\priority_label($data['priority'])
            );
        }

        // project — pretty-print names
        $oldPid = $old['project_id'] ?: null;
        $newPid = $data['project_id'] ?: null;
        if ($oldPid !== $newPid) {
            $oldName = $old['project_name'] ?? 'Tanpa Proyek';
            if (!$oldPid) $oldName = 'Tanpa Proyek';
            if ($newPid) {
                $ps = $pdo->prepare("SELECT name FROM projects WHERE id = ?");
                $ps->execute([$newPid]);
                $newName = $ps->fetchColumn() ?: 'proyek tidak dikenal';
            } else {
                $newName = 'Tanpa Proyek';
            }
            $changes[] = "Mengubah proyek: $oldName → $newName";
        }

        // Photos
        if ($addedPhotos > 0)   $changes[] = "Menambah $addedPhotos foto";
        if ($removedPhotos > 0) $changes[] = "Menghapus $removedPhotos foto";

        return $changes;
    }

    private function fetchReviews(\PDO $pdo, string $reportId): array
    {
        $s = $pdo->prepare("SELECT rv.*, u.name AS reviewer_name, u.role AS reviewer_role
            FROM report_reviews rv
            LEFT JOIN users u ON u.id = rv.reviewer_id
            WHERE rv.report_id = ?
            ORDER BY rv.created_at ASC");
        $s->execute([$reportId]);
        return $s->fetchAll();
    }

    public function edit(string $id): void
    {
        $user = Auth::require();
        $pdo = Database::pdo();
        $s = $pdo->prepare("SELECT r.*, p.pic_id AS project_pic_id
            FROM daily_reports r
            LEFT JOIN projects p ON p.id = r.project_id
            WHERE r.id = ?");
        $s->execute([$id]);
        $report = $s->fetch();
        if (!$report) { http_response_code(404); die('Tidak ditemukan'); }
        if (!Auth::can('reports.edit', $report)) { abort(403); }

        $projects = $pdo->query("SELECT id, name FROM projects WHERE active=1 ORDER BY name")->fetchAll();
        $photos = $this->fetchPhotos($pdo, $id);
        view('reports/form', [
            'user'     => $user,
            'projects' => $projects,
            'report'   => $report,
            'photos'   => $photos,
            'action'   => '/reports/' . $id . '/update',
            'submit_label' => 'Perbarui Laporan',
        ]);
    }

    public function update(string $id): void
    {
        csrf_check();
        $user = Auth::require();
        $pdo = Database::pdo();
        $s = $pdo->prepare("SELECT r.*, p.pic_id AS project_pic_id, p.name AS project_name
            FROM daily_reports r
            LEFT JOIN projects p ON p.id = r.project_id
            WHERE r.id = ?");
        $s->execute([$id]);
        $report = $s->fetch();
        if (!$report) { http_response_code(404); die('Tidak ditemukan'); }
        if (!Auth::can('reports.edit', $report)) { abort(403); }

        $data = $this->validate($_POST);
        if (!empty($data['_errors'])) {
            flash_set('error', implode("\n", $data['_errors']));
            redirect('/reports/' . $id . '/edit');
        }

        // Remove selected existing photos
        $removeIds = array_filter((array)($_POST['remove_photos'] ?? []), 'is_string');
        $removedCount = 0;
        if ($removeIds) {
            $in = implode(',', array_fill(0, count($removeIds), '?'));
            $s = $pdo->prepare("SELECT id, url FROM report_photos WHERE report_id = ? AND id IN ($in)");
            $s->execute(array_merge([$id], array_values($removeIds)));
            foreach ($s->fetchAll() as $p) {
                $this->deletePhotoFile($p['url']);
                $pdo->prepare("DELETE FROM report_photos WHERE id = ?")->execute([$p['id']]);
                $removedCount++;
            }
        }

        // Append newly uploaded photos
        [$photoUrls, $photoErrors] = $this->handlePhotos();
        $this->insertPhotos($pdo, $id, $photoUrls);
        $addedCount = count($photoUrls);

        $stmt = $pdo->prepare("UPDATE daily_reports SET
                project_id = ?, report_date = ?, title = ?, description = ?,
                solution = ?, status = ?, progress = ?, priority = ?,
                duration = ?, location = ?, category = ?, updated_at = datetime('now')
            WHERE id = ?");
        $stmt->execute([
            $data['project_id'] ?: null,
            $data['report_date'],
            $data['title'],
            $data['description'],
            $data['solution'],
            $data['status'],
            $data['progress'],
            $data['priority'],
            $data['duration'],
            $data['location'],
            $data['category'],
            $id,
        ]);

        // Diff old vs new to produce human-readable change list.
        $changes = $this->describeChanges($pdo, $report, $data, $addedCount, $removedCount);
        if ($changes) {
            $pdo->prepare("INSERT INTO report_reviews (id, report_id, reviewer_id, action, notes) VALUES (?,?,?,?,?)")
                ->execute([uuid(), $id, $user['id'], 'updated', implode("\n", array_map(fn($c) => '• ' . $c, $changes))]);
        }

        if ($photoErrors) {
            flash_set('error', "Laporan diperbarui, tapi beberapa foto gagal diunggah:\n" . implode("\n", $photoErrors));
        } else {
            flash_set('success', 'Laporan berhasil diperbarui.');
        }
        redirect('/reports/' . $id);
    }

    public function destroy(string $id): void
    {
        csrf_check();
        $user = Auth::require();
        $pdo = Database::pdo();
        $s = $pdo->prepare("SELECT r.*, p.pic_id AS project_pic_id
            FROM daily_reports r
            LEFT JOIN projects p ON p.id = r.project_id
            WHERE r.id = ?");
        $s->execute([$id]);
        $report = $s->fetch();
        if (!$report) { http_response_code(404); die('Tidak ditemukan'); }
        if (!Auth::can('reports.delete', $report)) { abort(403); }

        // Remove photo files from disk. FK CASCADE handles the report_photos rows.
        foreach ($this->fetchPhotos($pdo, $id) as $p) {
            $this->deletePhotoFile($p['url']);
        }
        $this->deletePhotoFile($report['photo_url']); // legacy column, in case data missed migration
        $pdo->prepare("DELETE FROM daily_reports WHERE id = ?")->execute([$id]);

        flash_set('success', 'Laporan berhasil dihapus.');
        redirect('/reports');
    }

    private function validate(array $in): array
    {
        $errors = [];
        $title = trim($in['title'] ?? '');
        $description = trim($in['description'] ?? '');
        $solution = trim($in['solution'] ?? '');
        $date = $in['report_date'] ?? date('Y-m-d');
        $project = $in['project_id'] ?? '';
        $status = $in['status'] ?? 'done';

        $progress = (int)($in['progress'] ?? 0);
        if ($progress < 0)  $progress = 0;
        if ($progress > 100) $progress = 100;

        $priority = $in['priority'] ?? 'normal';
        if (!isset(\App\REPORT_PRIORITIES[$priority])) $priority = 'normal';

        $category = $in['category'] ?? 'harian';
        if (!isset(\App\REPORT_CATEGORIES[$category])) $category = 'harian';

        $duration = trim($in['duration'] ?? '');
        $location = trim($in['location'] ?? '');

        if (!$title) $errors[] = 'Judul laporan wajib diisi.';
        if (!$description) $errors[] = 'Deskripsi pekerjaan wajib diisi.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $errors[] = 'Tanggal tidak valid.';
        if (!in_array($status, ['done', 'in_progress', 'pending'], true)) $status = 'done';

        return [
            'title'       => $title,
            'description' => $description,
            'solution'    => $solution,
            'report_date' => $date,
            'project_id'  => $project,
            'status'      => $status,
            'progress'    => $progress,
            'priority'    => $priority,
            'category'    => $category,
            'duration'    => $duration ?: null,
            'location'    => $location ?: null,
            '_errors'     => $errors,
        ];
    }

    /**
     * Handle field <input type="file" name="photos[]" multiple>.
     * Returns [urls[], errors[]]: URLs that successfully moved, plus per-file
     * error descriptions for the flash message.
     */
    private function handlePhotos(): array
    {
        $urls = [];
        $errors = [];
        if (empty($_FILES['photos']['name'])) return [$urls, $errors];

        $files = $_FILES['photos'];
        $count = is_array($files['name']) ? count($files['name']) : 0;

        $maxBytes = (int)config('max_upload_mb', 5) * 1024 * 1024;
        $allowed = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF  => 'gif',
        ];

        for ($i = 0; $i < $count; $i++) {
            $origName = $files['name'][$i];
            if ($origName === '') continue; // empty slot (browser sometimes sends)

            $err = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            if ($err !== UPLOAD_ERR_OK) {
                $errors[] = "$origName: upload gagal (code $err)";
                continue;
            }
            if ($files['size'][$i] > $maxBytes) {
                $errors[] = "$origName: ukuran melebihi " . (int)config('max_upload_mb', 5) . ' MB';
                continue;
            }
            $info = @getimagesize($files['tmp_name'][$i]);
            $type = $info[2] ?? 0;
            if (!isset($allowed[$type])) {
                $errors[] = "$origName: format tidak didukung (pakai JPG/PNG/WebP/GIF)";
                continue;
            }

            $dir = config('upload_dir');
            $subdir = date('Y/m');
            $full = $dir . '/' . $subdir;
            if (!is_dir($full)) @mkdir($full, 0775, true);

            $name = uuid() . '.' . $allowed[$type];
            $dest = $full . '/' . $name;
            if (!move_uploaded_file($files['tmp_name'][$i], $dest)) {
                $errors[] = "$origName: gagal menyimpan file ke disk";
                continue;
            }

            $urls[] = config('upload_url') . '/' . $subdir . '/' . $name;
        }

        return [$urls, $errors];
    }

    private function insertPhotos(\PDO $pdo, string $reportId, array $urls): void
    {
        if (!$urls) return;
        $stmt = $pdo->prepare("INSERT INTO report_photos (id, report_id, url) VALUES (?, ?, ?)");
        foreach ($urls as $url) {
            $stmt->execute([uuid(), $reportId, $url]);
        }
    }

    private function fetchPhotos(\PDO $pdo, string $reportId): array
    {
        $s = $pdo->prepare("SELECT id, url, created_at FROM report_photos WHERE report_id = ? ORDER BY created_at ASC");
        $s->execute([$reportId]);
        return $s->fetchAll();
    }

    private function deletePhotoFile(?string $url): void
    {
        if (!$url) return;
        $prefix = config('upload_url');
        if (!str_starts_with($url, $prefix)) return;
        $rel = ltrim(substr($url, strlen($prefix)), '/');
        $path = config('upload_dir') . '/' . $rel;
        if (is_file($path)) @unlink($path);
    }
}
