<?php
namespace App;

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Autoload
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) require $file;
});

require __DIR__ . '/helpers.php';

$cfg = require __DIR__ . '/../config/app.php';
date_default_timezone_set($cfg['timezone']);

// Session
session_name($cfg['session_name']);
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Init DB
Database::pdo();
