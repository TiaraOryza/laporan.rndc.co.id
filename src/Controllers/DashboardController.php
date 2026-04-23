<?php
namespace App\Controllers;

use App\Auth;
use App\Database;
use function App\view;

class DashboardController
{
    public function index(): void
    {
        $user = Auth::require();
        $pdo = Database::pdo();

        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $monthStart = date('Y-m-01');

        [$scopeSql, $scopeParams] = Auth::reportScopeSql('r');

        $q = function (string $since) use ($pdo, $scopeSql, $scopeParams): int {
            $sql = "SELECT COUNT(*) FROM daily_reports r WHERE r.report_date >= ? AND $scopeSql";
            $s = $pdo->prepare($sql);
            $s->execute(array_merge([$since], $scopeParams));
            return (int)$s->fetchColumn();
        };

        $stats = [
            'today'   => $q($today),
            'week'    => $q($weekStart),
            'month'   => $q($monthStart),
            'users'   => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE active=1")->fetchColumn(),
            'projects'=> (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE active=1")->fetchColumn(),
        ];

        // Recent reports
        $sql = "SELECT r.*, u.name AS user_name, p.name AS project_name
                FROM daily_reports r
                LEFT JOIN users u ON u.id = r.user_id
                LEFT JOIN projects p ON p.id = r.project_id
                WHERE $scopeSql
                ORDER BY r.report_date DESC, r.created_at DESC
                LIMIT 8";
        $s = $pdo->prepare($sql);
        $s->execute($scopeParams);
        $recent = $s->fetchAll();

        // Chart: last 7 days count per day
        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i day"));
            $s = $pdo->prepare("SELECT COUNT(*) FROM daily_reports r WHERE r.report_date = ? AND $scopeSql");
            $s->execute(array_merge([$d], $scopeParams));
            $chart[] = ['date' => $d, 'count' => (int)$s->fetchColumn()];
        }

        // Per project (last 30 days)
        $sql = "SELECT COALESCE(p.name, 'Tanpa Proyek') AS name, COUNT(*) AS total
                FROM daily_reports r
                LEFT JOIN projects p ON p.id = r.project_id
                WHERE r.report_date >= date('now','-30 day') AND $scopeSql
                GROUP BY p.id
                ORDER BY total DESC
                LIMIT 6";
        $s = $pdo->prepare($sql);
        $s->execute($scopeParams);
        $byProject = $s->fetchAll();

        view('dashboard/index', compact('user', 'stats', 'recent', 'chart', 'byProject'));
    }
}
