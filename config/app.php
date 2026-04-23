<?php
// Configuration loaded from .env if present, else defaults.
$envFile = dirname(__DIR__) . '/.env';
$env = [];
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v, " \t\"'");
    }
}

return [
    'app_name'       => $env['APP_NAME']       ?? 'Laporan IT RNDC',
    'app_env'        => $env['APP_ENV']        ?? 'production',
    'app_url'        => $env['APP_URL']        ?? 'http://laporan.rndc.co.id',
    'timezone'       => $env['APP_TIMEZONE']   ?? 'Asia/Jakarta',
    'db_path'        => $env['DB_PATH']        ?? __DIR__ . '/../storage/database.sqlite',
    'upload_dir'     => __DIR__ . '/../storage/uploads',
    'upload_url'     => '/storage/uploads',
    'max_upload_mb'  => (int)($env['MAX_UPLOAD_MB'] ?? 5),
    'session_name'   => 'laporan_rndc_sid',

    // WhatsApp notification settings (Fonnte-compatible by default)
    'wa_enabled'     => filter_var($env['WA_ENABLED'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
    'wa_api_url'     => $env['WA_API_URL']     ?? 'https://api.fonnte.com/send',
    'wa_api_token'   => $env['WA_API_TOKEN']   ?? '',
    'wa_target'      => $env['WA_TARGET']      ?? '', // group id or phone, comma-separated
];
