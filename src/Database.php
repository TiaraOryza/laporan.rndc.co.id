<?php
namespace App;

use PDO;

class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo) return self::$pdo;

        $cfg = require __DIR__ . '/../config/app.php';
        $dbPath = $cfg['db_path'];
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON;');
        $pdo->exec('PRAGMA journal_mode = WAL;');

        self::$pdo = $pdo;
        self::migrate($pdo);
        return $pdo;
    }

    public static function migrate(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'staff',
            phone TEXT,
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            description TEXT,
            pic_id TEXT,
            active INTEGER NOT NULL DEFAULT 1,
            status TEXT NOT NULL DEFAULT 'active',
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (pic_id) REFERENCES users(id) ON DELETE SET NULL
        )");

        // One-time migration: add status column to existing databases and
        // seed it from the old active flag (active=0 → on_hold).
        $cols = $pdo->query("PRAGMA table_info(projects)")->fetchAll();
        if (!in_array('status', array_column($cols, 'name'), true)) {
            $pdo->exec("ALTER TABLE projects ADD COLUMN status TEXT NOT NULL DEFAULT 'active'");
            $pdo->exec("UPDATE projects SET status = 'on_hold' WHERE active = 0");
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS daily_reports (
            id TEXT PRIMARY KEY,
            user_id TEXT NOT NULL,
            project_id TEXT,
            report_date TEXT NOT NULL,
            title TEXT NOT NULL,
            description TEXT NOT NULL,
            solution TEXT,
            status TEXT NOT NULL DEFAULT 'done',
            photo_url TEXT,
            progress INTEGER NOT NULL DEFAULT 0,
            priority TEXT NOT NULL DEFAULT 'normal',
            duration TEXT,
            location TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
        )");

        // Migrate: add the extra context columns to existing DBs.
        $cols = array_column($pdo->query("PRAGMA table_info(daily_reports)")->fetchAll(), 'name');
        foreach ([
            'progress'      => "ALTER TABLE daily_reports ADD COLUMN progress INTEGER NOT NULL DEFAULT 0",
            'priority'      => "ALTER TABLE daily_reports ADD COLUMN priority TEXT NOT NULL DEFAULT 'normal'",
            'duration'      => "ALTER TABLE daily_reports ADD COLUMN duration TEXT",
            'location'      => "ALTER TABLE daily_reports ADD COLUMN location TEXT",
            'review_status' => "ALTER TABLE daily_reports ADD COLUMN review_status TEXT NOT NULL DEFAULT 'draft'",
            'category'      => "ALTER TABLE daily_reports ADD COLUMN category TEXT NOT NULL DEFAULT 'harian'",
        ] as $col => $sql) {
            if (!in_array($col, $cols, true)) $pdo->exec($sql);
        }

        // Review history (audit trail). Denormalized latest review_status lives on daily_reports.
        $pdo->exec("CREATE TABLE IF NOT EXISTS report_reviews (
            id TEXT PRIMARY KEY,
            report_id TEXT NOT NULL,
            reviewer_id TEXT,
            action TEXT NOT NULL,
            notes TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (report_id) REFERENCES daily_reports(id) ON DELETE CASCADE,
            FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_reviews_report ON report_reviews(report_id)");

        // Laporan Proyek (project-level reports) — ringkasan dibuat PIC terkait proyeknya.
        $pdo->exec("CREATE TABLE IF NOT EXISTS project_reports (
            id TEXT PRIMARY KEY,
            project_id TEXT NOT NULL,
            user_id TEXT NOT NULL,
            title TEXT NOT NULL,
            summary TEXT NOT NULL,
            period_from TEXT,
            period_to TEXT,
            status TEXT NOT NULL DEFAULT 'done',
            progress INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_project_reports_project ON project_reports(project_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_project_reports_user ON project_reports(user_id)");

        // Migrate: add status/progress columns to legacy project_reports tables.
        $prCols = array_column($pdo->query("PRAGMA table_info(project_reports)")->fetchAll(), 'name');
        foreach ([
            'status'   => "ALTER TABLE project_reports ADD COLUMN status TEXT NOT NULL DEFAULT 'done'",
            'progress' => "ALTER TABLE project_reports ADD COLUMN progress INTEGER NOT NULL DEFAULT 0",
        ] as $col => $sql) {
            if (!in_array($col, $prCols, true)) $pdo->exec($sql);
        }

        // One-time migration to simplified enum + retroactive 'created' entries.
        if (!$pdo->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='_migrations'")->fetchColumn()) {
            $pdo->exec("CREATE TABLE _migrations (key TEXT PRIMARY KEY, applied_at TEXT DEFAULT (datetime('now')))");
        }
        if (!$pdo->query("SELECT 1 FROM _migrations WHERE key='simplify_review_enum'")->fetchColumn()) {
            $pdo->exec("UPDATE report_reviews SET action='reviewing' WHERE action IN ('pic_reviewing','director_reviewing','pic_reviewed','director_reviewed')");
            $pdo->exec("UPDATE report_reviews SET action='approved' WHERE action='accepted'");
            $pdo->exec("UPDATE daily_reports SET review_status='reviewing' WHERE review_status IN ('pic_reviewing','director_reviewing','pic_reviewed','director_reviewed')");
            $pdo->exec("UPDATE daily_reports SET review_status='approved' WHERE review_status='accepted'");

            // Seed 'created' activity for reports created before this audit log existed.
            $pdo->exec("INSERT INTO report_reviews (id, report_id, reviewer_id, action, notes, created_at)
                SELECT lower(hex(randomblob(8))), r.id, r.user_id, 'created', NULL, r.created_at
                FROM daily_reports r
                WHERE NOT EXISTS (SELECT 1 FROM report_reviews rv WHERE rv.report_id = r.id AND rv.action = 'created')");

            $pdo->prepare("INSERT INTO _migrations (key) VALUES (?)")->execute(['simplify_review_enum']);
        }

        // Seed sensible defaults for old rows based on status:
        // done=100%, in_progress=50%, pending=10%. Only runs for rows with
        // progress=0 (the column default) so repeated calls are idempotent
        // for any row an admin later set back to 0 on purpose? No — keep it
        // one-shot by checking a migration marker.
        $marker = $pdo->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='_migrations'")->fetchColumn();
        if (!$marker) {
            $pdo->exec("CREATE TABLE _migrations (key TEXT PRIMARY KEY, applied_at TEXT DEFAULT (datetime('now')))");
        }
        $done = $pdo->query("SELECT 1 FROM _migrations WHERE key='seed_report_progress'")->fetchColumn();
        if (!$done) {
            $pdo->exec("UPDATE daily_reports SET progress = CASE status
                WHEN 'done' THEN 100
                WHEN 'in_progress' THEN 50
                WHEN 'pending' THEN 10
                ELSE 0 END
                WHERE progress = 0");
            $pdo->prepare("INSERT INTO _migrations (key) VALUES (?)")->execute(['seed_report_progress']);
        }

        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_reports_date ON daily_reports(report_date)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_reports_user ON daily_reports(user_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_reports_project ON daily_reports(project_id)");

        // Multi-photo support: one report → many photos. The legacy single
        // photo_url column stays for backward compat; on first migration we
        // copy any existing value into the new table.
        $pdo->exec("CREATE TABLE IF NOT EXISTS report_photos (
            id TEXT PRIMARY KEY,
            report_id TEXT NOT NULL,
            url TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (report_id) REFERENCES daily_reports(id) ON DELETE CASCADE
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_photos_report ON report_photos(report_id)");

        $legacy = $pdo->query(
            "SELECT r.id, r.photo_url FROM daily_reports r
             LEFT JOIN report_photos p ON p.report_id = r.id
             WHERE r.photo_url IS NOT NULL AND r.photo_url != '' AND p.id IS NULL"
        )->fetchAll();
        foreach ($legacy as $r) {
            $pdo->prepare("INSERT INTO report_photos (id, report_id, url) VALUES (?, ?, ?)")
                ->execute([bin2hex(random_bytes(8)), $r['id'], $r['photo_url']]);
        }

        // Seed initial admin user on first run
        $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        if ($count === 0) {
            $id = bin2hex(random_bytes(8));
            $stmt = $pdo->prepare("INSERT INTO users (id, name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $id,
                'Administrator',
                'admin@rndc.co.id',
                password_hash('admin123', PASSWORD_DEFAULT),
                'admin',
            ]);
        }
    }
}
