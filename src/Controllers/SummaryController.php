<?php
namespace App\Controllers;

use App\Auth;
use App\Database;
use function App\{view, view_raw};

class SummaryController
{
    public function index(): void
    {
        $user = Auth::require();
        $pdo = Database::pdo();

        $period = $_GET['period'] ?? 'weekly';
        [$from, $to, $label] = $this->period($period);

        $userFilter = $_GET['user_id'] ?? '';
        $projectFilter = $_GET['project_id'] ?? '';

        $where = ["r.report_date BETWEEN ? AND ?"];
        $params = [$from, $to];

        [$scopeSql, $scopeParams] = Auth::reportScopeSql('r');
        $where[] = $scopeSql;
        $params = array_merge($params, $scopeParams);

        if (Auth::can('reports.viewAll') && $userFilter) {
            $where[] = "r.user_id = ?";
            $params[] = $userFilter;
        }
        if ($projectFilter) {
            $where[] = "r.project_id = ?";
            $params[] = $projectFilter;
        }

        $sql = "SELECT r.*, u.name AS user_name, p.name AS project_name
                FROM daily_reports r
                LEFT JOIN users u ON u.id = r.user_id
                LEFT JOIN projects p ON p.id = r.project_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY r.report_date DESC, u.name, r.created_at DESC";
        $s = $pdo->prepare($sql);
        $s->execute($params);
        $reports = $s->fetchAll();

        // Group by date
        $byDate = [];
        foreach ($reports as $r) {
            $byDate[$r['report_date']][] = $r;
        }

        // Stats
        $stats = [
            'total'       => count($reports),
            'done'        => 0,
            'in_progress' => 0,
            'pending'     => 0,
            'users'       => [],
            'projects'    => [],
        ];
        foreach ($reports as $r) {
            $stats[$r['status']] = ($stats[$r['status']] ?? 0) + 1;
            $stats['users'][$r['user_id']] = true;
            if ($r['project_id']) $stats['projects'][$r['project_id']] = true;
        }
        $stats['users'] = count($stats['users']);
        $stats['projects'] = count($stats['projects']);

        $users = Auth::can('reports.viewAll')
            ? $pdo->query("SELECT id, name FROM users WHERE active=1 ORDER BY name")->fetchAll()
            : [];
        $projects = $pdo->query("SELECT id, name FROM projects WHERE active=1 ORDER BY name")->fetchAll();

        $data = compact('user', 'period', 'from', 'to', 'label', 'byDate', 'stats', 'reports', 'users', 'projects', 'userFilter', 'projectFilter');

        if (($_GET['format'] ?? '') === 'print') {
            view_raw('summary/print', $data);
            return;
        }
        view('summary/index', $data);
    }

    private function period(string $period): array
    {
        $from = $_GET['from'] ?? null;
        $to   = $_GET['to']   ?? null;

        if ($period === 'custom' && $from && $to) {
            $label = 'Kustom: ' . date('d M Y', strtotime($from)) . ' s/d ' . date('d M Y', strtotime($to));
            return [$from, $to, $label];
        }
        if ($period === 'daily') {
            $d = $from ?: date('Y-m-d');
            return [$d, $d, 'Harian: ' . date('d M Y', strtotime($d))];
        }
        if ($period === 'monthly') {
            $base = $from ?: date('Y-m-01');
            $start = date('Y-m-01', strtotime($base));
            $end = date('Y-m-t', strtotime($base));
            return [$start, $end, 'Bulanan: ' . date('F Y', strtotime($start))];
        }
        // weekly (default)
        $base = $from ?: date('Y-m-d');
        $start = date('Y-m-d', strtotime('monday this week', strtotime($base)));
        $end = date('Y-m-d', strtotime('sunday this week', strtotime($base)));
        return [$start, $end, 'Mingguan: ' . date('d M', strtotime($start)) . ' - ' . date('d M Y', strtotime($end))];
    }
}
