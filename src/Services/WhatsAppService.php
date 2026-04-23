<?php
namespace App\Services;

use function App\config;

class WhatsAppService
{
    public function sendReportNotification(array $report): void
    {
        if (!config('wa_enabled')) return;
        $url = config('wa_api_url');
        $token = config('wa_api_token');
        $target = config('wa_target');
        if (!$url || !$token || !$target) return;

        $message = $this->formatReport($report);

        // Send non-blocking (short timeout; errors silent)
        $targets = array_map('trim', explode(',', $target));
        foreach ($targets as $to) {
            if ($to === '') continue;
            $this->post($url, $token, $to, $message);
        }
    }

    private function formatReport(array $r): string
    {
        $lines = [];
        $lines[] = "*📋 Laporan Harian IT*";
        $lines[] = "";
        $lines[] = "👤 Staff: " . ($r['user_name'] ?? '-');
        $lines[] = "📅 Tanggal: " . date('d M Y', strtotime($r['report_date']));
        $lines[] = "📌 Proyek: " . ($r['project_name'] ?? '-');
        $lines[] = "🏷️ Status: " . strtoupper($r['status'] ?? 'done');
        $lines[] = "";
        $lines[] = "📝 *" . ($r['title'] ?? '') . "*";
        $lines[] = "";
        $lines[] = "*Deskripsi:*";
        $lines[] = $this->trim($r['description'] ?? '-', 500);
        if (!empty($r['solution'])) {
            $lines[] = "";
            $lines[] = "*Solusi:*";
            $lines[] = $this->trim($r['solution'], 500);
        }
        return implode("\n", $lines);
    }

    private function trim(string $s, int $max): string
    {
        if (mb_strlen($s) <= $max) return $s;
        return mb_substr($s, 0, $max - 3) . '...';
    }

    private function post(string $url, string $token, string $target, string $message): void
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_HTTPHEADER     => [
                'Authorization: ' . $token,
            ],
            CURLOPT_POSTFIELDS     => [
                'target'  => $target,
                'message' => $message,
            ],
        ]);
        @curl_exec($ch);
        curl_close($ch);
    }
}
