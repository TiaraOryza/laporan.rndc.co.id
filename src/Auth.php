<?php
namespace App;

class Auth
{
    public static function user(): ?array
    {
        if (empty($_SESSION['user_id'])) return null;
        static $cache = null;
        if ($cache && $cache['id'] === $_SESSION['user_id']) return $cache;

        $stmt = Database::pdo()->prepare("SELECT * FROM users WHERE id = ? AND active = 1");
        $stmt->execute([$_SESSION['user_id']]);
        $u = $stmt->fetch();
        if (!$u) {
            self::logout();
            return null;
        }
        $cache = $u;
        return $u;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function attempt(string $email, string $password): bool
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM users WHERE email = ? AND active = 1");
        $stmt->execute([strtolower(trim($email))]);
        $u = $stmt->fetch();
        if (!$u) return false;
        if (!password_verify($password, $u['password_hash'])) return false;
        session_regenerate_id(true);
        $_SESSION['user_id'] = $u['id'];
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function require(): array
    {
        $u = self::user();
        if (!$u) {
            flash_set('error', 'Silakan login terlebih dahulu.');
            redirect('/login');
        }
        return $u;
    }

    public static function requireRole(array $roles): array
    {
        $u = self::require();
        if (!in_array($u['role'], $roles, true)) {
            abort(403);
        }
        return $u;
    }

    /**
     * Standard authorization gate for controllers: ensure current user is
     * authenticated and has the given ability. Returns the user row on success,
     * halts with 403 otherwise. Controllers must prefer this over requireRole().
     */
    public static function requireCan(string $ability, ?array $target = null): array
    {
        $u = self::require();
        if (!self::can($ability, $target)) {
            abort(403);
        }
        return $u;
    }

    /**
     * Returns a SQL WHERE fragment + params that restricts a report query
     * to what the current user is allowed to see.
     *
     *   admin, manager → all reports             ('1=1', [])
     *   pic            → reports in projects where pic_id = user.id
     *   staff, employee, other → only reports they authored
     *   (no user)      → '1=0' (deny)
     *
     * @param string $alias SQL alias used for the daily_reports table.
     * @return array{0: string, 1: array} [whereFragment, params]
     */
    public static function reportScopeSql(string $alias = 'r'): array
    {
        $u = self::user();
        if (!$u) return ['1=0', []];

        if (self::can('reports.viewAll')) {
            return ['1=1', []];
        }
        if (self::can('reports.viewOwn.project')) {
            return ["$alias.project_id IN (SELECT id FROM projects WHERE pic_id = ?)", [$u['id']]];
        }
        return ["$alias.user_id = ?", [$u['id']]];
    }

    public static function can(string $ability, ?array $target = null): bool
    {
        $u = self::user();
        if (!$u) return false;
        $role = $u['role'];

        return match ($ability) {
            'users.manage'      => $role === 'admin',
            'projects.view'     => in_array($role, ['admin', 'director', 'manager', 'pic']),
            // PIC is "admin" only of their own projects. Creation and deletion stay
            // with admin/manager (delete: admin only) — so a PIC can't spawn or wipe
            // projects outside their assignment. Director sees everything but doesn't
            // create/edit projects (oversight role only).
            'projects.create'   => in_array($role, ['admin', 'manager', 'pic']),
            'projects.manage'   => in_array($role, ['admin', 'manager'])
                                    || ($role === 'pic' && $target && ($target['pic_id'] ?? null) === $u['id']),
            'projects.delete'   => $role === 'admin',
            'reports.create'    => in_array($role, ['admin', 'director', 'manager', 'staff', 'pic']),
            'reports.export'    => in_array($role, ['admin', 'director', 'manager', 'pic']),
            'reports.viewAll'         => in_array($role, ['admin', 'director', 'manager']),
            'reports.viewOwn.project' => $role === 'pic',
            // PIC acts as admin within their project: edit+delete any report whose
            // project.pic_id matches. $target must include 'project_pic_id'.
            // Director: oversight only — can review, not edit/delete others' reports.
            'reports.edit'      => $target && (
                                        $target['user_id'] === $u['id']
                                        || in_array($role, ['admin', 'manager'])
                                        || ($role === 'pic' && ($target['project_pic_id'] ?? null) === $u['id'])
                                    ),
            'reports.delete'    => $target && (
                                        $target['user_id'] === $u['id']
                                        || $role === 'admin'
                                        || ($role === 'pic' && ($target['project_pic_id'] ?? null) === $u['id'])
                                    ),
            // Director-level reviewers (admin/director/manager) can always review.
            // PIC can review only reports in their project.
            'reports.review'    => in_array($role, ['admin', 'director', 'manager'])
                                    || ($role === 'pic' && $target && ($target['project_pic_id'] ?? null) === $u['id']),

            // LAPORAN PROYEK (project-level reports)
            // Visibility: admin/director see all; PIC sees their own (scoped via `project_pic_id`)
            'projectReports.viewAll'       => in_array($role, ['admin', 'director']),
            'projectReports.viewOwn'       => $role === 'pic',
            // Only PIC can create — scoped to projects where pic_id = self (controller enforces).
            'projectReports.create'        => $role === 'pic',
            // Edit: author (who must be pic-of-project) or admin/director. Manager excluded.
            'projectReports.edit'          => $target && (
                                                $target['user_id'] === $u['id']
                                                || in_array($role, ['admin', 'director'])
                                              ),
            // Delete: admin only (consistent with project.delete).
            'projectReports.delete'        => $role === 'admin',
            default             => false,
        };
    }
}
