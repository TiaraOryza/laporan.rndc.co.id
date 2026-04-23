<?php
require __DIR__ . '/src/bootstrap.php';

use App\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ReportController;
use App\Controllers\ProjectController;
use App\Controllers\UserController;
use App\Controllers\SummaryController;
use App\Controllers\ProjectReportController;

$router = new Router();

// Auth
$router->get('/login',  [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout',[AuthController::class, 'logout']);

// Dashboard
$router->get('/',          [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);

// Reports
$router->get('/reports',              [ReportController::class, 'index']);
$router->get('/reports/create',       [ReportController::class, 'create']);
$router->post('/reports',             [ReportController::class, 'store']);
$router->get('/reports/{id}',         [ReportController::class, 'show']);
$router->get('/reports/{id}/edit',    [ReportController::class, 'edit']);
$router->post('/reports/{id}/update', [ReportController::class, 'update']);
$router->post('/reports/{id}/delete', [ReportController::class, 'destroy']);
$router->post('/reports/{id}/review', [ReportController::class, 'review']);
$router->get('/reports/{id}/pdf',     [ReportController::class, 'pdf']);

// Projects
$router->get('/projects',              [ProjectController::class, 'index']);
$router->get('/projects/create',       [ProjectController::class, 'create']);
$router->post('/projects',             [ProjectController::class, 'store']);
$router->get('/projects/{id}/edit',    [ProjectController::class, 'edit']);
$router->post('/projects/{id}/update', [ProjectController::class, 'update']);
$router->post('/projects/{id}/delete', [ProjectController::class, 'destroy']);

// Users
$router->get('/users',              [UserController::class, 'index']);
$router->get('/users/create',       [UserController::class, 'create']);
$router->post('/users',             [UserController::class, 'store']);
$router->get('/users/{id}/edit',    [UserController::class, 'edit']);
$router->post('/users/{id}/update', [UserController::class, 'update']);
$router->post('/users/{id}/delete', [UserController::class, 'destroy']);

// Profile
$router->get('/profile',  [UserController::class, 'profile']);
$router->post('/profile', [UserController::class, 'updateProfile']);

// Laporan Proyek (project-level reports)
$router->get('/project-reports',              [ProjectReportController::class, 'index']);
$router->get('/project-reports/create',       [ProjectReportController::class, 'create']);
$router->post('/project-reports',             [ProjectReportController::class, 'store']);
$router->get('/project-reports/{id}',         [ProjectReportController::class, 'show']);
$router->get('/project-reports/{id}/edit',    [ProjectReportController::class, 'edit']);
$router->post('/project-reports/{id}/update', [ProjectReportController::class, 'update']);
$router->post('/project-reports/{id}/delete', [ProjectReportController::class, 'destroy']);

// Summary / Rangkuman / PDF (print)
$router->get('/summary', [SummaryController::class, 'index']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
