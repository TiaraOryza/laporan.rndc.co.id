<?php
namespace App;

function config(string $key, $default = null)
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/../config/app.php';
    }
    return $cfg[$key] ?? $default;
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function uuid(): string
{
    return bin2hex(random_bytes(8));
}

function url(string $path = '/'): string
{
    if ($path === '' || $path[0] !== '/') $path = '/' . $path;
    return $path;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash_set(string $key, string $msg): void
{
    $_SESSION['_flash'][$key] = $msg;
}

function flash_get(string $key): ?string
{
    $msg = $_SESSION['_flash'][$key] ?? null;
    if ($msg !== null) unset($_SESSION['_flash'][$key]);
    return $msg;
}

function old(string $key, $default = '')
{
    return $_SESSION['_old'][$key] ?? $default;
}

function set_old(array $data): void
{
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $t = $_POST['_csrf'] ?? '';
        if (!hash_equals(csrf_token(), $t)) {
            http_response_code(419);
            die('CSRF token mismatch');
        }
    }
}

function view(string $path, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $__view_path = $path;
    require __DIR__ . '/../views/layout.php';
}

/**
 * Send an HTTP error status and render the matching error view, then exit.
 * Falls back to plain text if the view file does not exist.
 */
function abort(int $code, ?string $message = null): never
{
    http_response_code($code);
    $view = __DIR__ . '/../views/errors/' . $code . '.php';
    if (is_file($view)) {
        require $view;
    } else {
        header('Content-Type: text/plain; charset=UTF-8');
        echo $message ?? ('HTTP ' . $code);
    }
    exit;
}

function view_raw(string $path, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require __DIR__ . '/../views/' . $path . '.php';
}

/**
 * SQLite's datetime('now') returns UTC regardless of server TZ. When reading
 * those strings back, we need to parse as UTC and convert to the app timezone
 * (config('timezone'), default Asia/Jakarta) so the user sees WIB on screen.
 *
 * Pure date columns (e.g. report_date = 'Y-m-d') carry no timezone — we keep
 * them as-is without conversion.
 */
function fmt_date($date, string $format = 'd M Y'): string
{
    if (!$date) return '-';
    if (is_numeric($date)) {
        return date($format, (int)$date);
    }
    $s = (string)$date;
    // 'Y-m-d' calendar date — no timezone shift
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
        return date($format, strtotime($s));
    }
    // Datetime string from SQLite — parse as UTC then convert
    $appTz = config('timezone') ?: 'Asia/Jakarta';
    try {
        $dt = new \DateTimeImmutable($s, new \DateTimeZone('UTC'));
        return $dt->setTimezone(new \DateTimeZone($appTz))->format($format);
    } catch (\Exception $e) {
        return date($format, strtotime($s));
    }
}

function fmt_datetime($date, string $format = 'd M Y H:i'): string
{
    if (!$date) return '-';
    if (is_numeric($date)) {
        return date($format, (int)$date);
    }
    $appTz = config('timezone') ?: 'Asia/Jakarta';
    try {
        // DB timestamps from SQLite datetime('now') are UTC.
        $dt = new \DateTimeImmutable((string)$date, new \DateTimeZone('UTC'));
        return $dt->setTimezone(new \DateTimeZone($appTz))->format($format);
    } catch (\Exception $e) {
        return date($format, strtotime((string)$date));
    }
}

function role_label(string $role): string
{
    return match ($role) {
        'admin'    => 'Admin System',
        'director' => 'Direktur',
        'manager'  => 'Manager IT',
        'staff'    => 'Staff IT',
        'pic'      => 'PIC Project',
        'employee' => 'Karyawan',
        default    => ucfirst($role),
    };
}

function role_badge_class(string $role): string
{
    return match ($role) {
        'admin'    => 'bg-red-100 text-red-700',
        'director' => 'bg-indigo-100 text-indigo-700',
        'manager'  => 'bg-purple-100 text-purple-700',
        'staff'    => 'bg-blue-100 text-blue-700',
        'pic'      => 'bg-amber-100 text-amber-700',
        'employee' => 'bg-slate-100 text-slate-700',
        default    => 'bg-slate-100 text-slate-700',
    };
}

function status_label(string $status): string
{
    return match ($status) {
        'done'       => 'Selesai',
        'in_progress'=> 'Dikerjakan',
        'pending'    => 'Pending',
        default      => ucfirst($status),
    };
}

function status_badge_class(string $status): string
{
    return match ($status) {
        'done'       => 'bg-emerald-100 text-emerald-700',
        'in_progress'=> 'bg-sky-100 text-sky-700',
        'pending'    => 'bg-amber-100 text-amber-700',
        default      => 'bg-slate-100 text-slate-700',
    };
}

const PROJECT_STATUSES = [
    'planning'  => 'Perencanaan',
    'active'    => 'Berjalan',
    'on_hold'   => 'Ditahan',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan',
];

function project_status_label(string $status): string
{
    return PROJECT_STATUSES[$status] ?? ucfirst($status);
}

function project_status_badge_class(string $status): string
{
    return match ($status) {
        'planning'  => 'bg-slate-100 text-slate-700',
        'active'    => 'bg-emerald-100 text-emerald-700',
        'on_hold'   => 'bg-amber-100 text-amber-800',
        'completed' => 'bg-blue-100 text-blue-700',
        'cancelled' => 'bg-red-100 text-red-700',
        default     => 'bg-slate-100 text-slate-700',
    };
}

/** Project lifecycle state → is it still operationally "in the pipeline"? */
function project_status_is_active(string $status): bool
{
    return !in_array($status, ['completed', 'cancelled'], true);
}

const REPORT_PRIORITIES = [
    'low'    => 'Rendah',
    'normal' => 'Normal',
    'high'   => 'Tinggi',
    'urgent' => 'Mendesak',
];

function priority_label(string $priority): string
{
    return REPORT_PRIORITIES[$priority] ?? ucfirst($priority);
}

function priority_badge_class(string $priority): string
{
    return match ($priority) {
        'low'    => 'bg-slate-100 text-slate-700',
        'normal' => 'bg-blue-100 text-blue-700',
        'high'   => 'bg-amber-100 text-amber-800',
        'urgent' => 'bg-red-100 text-red-700',
        default  => 'bg-slate-100 text-slate-700',
    };
}

function progress_bar_class(int $progress): string
{
    if ($progress >= 100) return 'bg-emerald-500';
    if ($progress >= 60)  return 'bg-blue-500';
    if ($progress >= 25)  return 'bg-amber-500';
    return 'bg-slate-400';
}

const REPORT_CATEGORIES = [
    'harian'   => 'Harian',
    'mingguan' => 'Mingguan',
    'bulanan'  => 'Bulanan',
];

function category_label(string $category): string
{
    return REPORT_CATEGORIES[$category] ?? ucfirst($category);
}

function category_badge_class(string $category): string
{
    return match ($category) {
        'harian'   => 'bg-sky-100 text-sky-700',
        'mingguan' => 'bg-violet-100 text-violet-700',
        'bulanan'  => 'bg-fuchsia-100 text-fuchsia-700',
        default    => 'bg-slate-100 text-slate-700',
    };
}

const REVIEW_STATUSES = [
    'draft'     => 'Baru Dibuat',
    'reviewing' => 'Sedang Direview',
    'approved'  => 'Disetujui',
    'revision'  => 'Revisi',
];

/** Activity types beyond review statuses (for the unified history log). */
const ACTIVITY_TYPES = [
    'created' => 'Membuat Laporan',
    'updated' => 'Melakukan Perubahan',
    // review actions reuse REVIEW_STATUSES keys above
];

function review_status_label(string $s): string
{
    return REVIEW_STATUSES[$s] ?? ACTIVITY_TYPES[$s] ?? ucfirst($s);
}

function review_status_badge_class(string $s): string
{
    return match ($s) {
        'draft'     => 'bg-slate-100 text-slate-700',
        'created'   => 'bg-slate-100 text-slate-700',
        'updated'   => 'bg-slate-100 text-slate-700',
        'reviewing' => 'bg-sky-100 text-sky-700',
        'approved'  => 'bg-emerald-100 text-emerald-700',
        'revision'  => 'bg-amber-100 text-amber-800',
        default     => 'bg-slate-100 text-slate-700',
    };
}

/**
 * Natural-Indonesian sentence for an activity log entry.
 * `action` is one of: created, updated, reviewing, approved, revision.
 */
function activity_sentence(string $action, string $name): string
{
    return match ($action) {
        'created'   => e($name) . ' membuat laporan',
        'updated'   => e($name) . ' melakukan perubahan',
        'reviewing' => e($name) . ' sedang mereview',
        'approved'  => e($name) . ' menyetujui laporan',
        'revision'  => e($name) . ' meminta revisi',
        default     => e($name) . ' melakukan: ' . e($action),
    };
}

/** Which review actions can a given user perform on a specific report? */
function review_actions_for_role(string $role, bool $isProjectPic): array
{
    $director    = in_array($role, ['admin', 'director', 'manager'], true);
    $reviewActs  = ['reviewing', 'approved', 'revision'];
    return ($director || $isProjectPic) ? $reviewActs : [];
}
