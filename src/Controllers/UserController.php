<?php
namespace App\Controllers;

use App\Auth;
use App\Database;
use function App\{view, redirect, flash_set, csrf_check, uuid};

class UserController
{
    public function index(): void
    {
        $user = Auth::requireCan('users.manage');
        $users = Database::pdo()->query("SELECT * FROM users ORDER BY active DESC, name")->fetchAll();
        view('users/index', compact('user', 'users'));
    }

    public function create(): void
    {
        $user = Auth::requireCan('users.manage');
        view('users/form', [
            'user' => $user,
            'target' => null,
            'action' => '/users',
            'submit_label' => 'Simpan Pengguna',
        ]);
    }

    public function store(): void
    {
        csrf_check();
        Auth::requireCan('users.manage');
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'staff';
        $phone = trim($_POST['phone'] ?? '');

        $errors = [];
        if (!$name) $errors[] = 'Nama wajib diisi.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
        if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
        if (!in_array($role, ['admin', 'director', 'manager', 'staff', 'pic', 'employee'], true)) $errors[] = 'Role tidak valid.';

        if ($errors) {
            flash_set('error', implode("\n", $errors));
            redirect('/users/create');
        }

        $pdo = Database::pdo();
        $exists = $pdo->prepare("SELECT 1 FROM users WHERE email = ?");
        $exists->execute([$email]);
        if ($exists->fetchColumn()) {
            flash_set('error', 'Email sudah terdaftar.');
            redirect('/users/create');
        }

        $pdo->prepare("INSERT INTO users (id, name, email, password_hash, role, phone, active) VALUES (?, ?, ?, ?, ?, ?, 1)")
            ->execute([uuid(), $name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $phone ?: null]);

        flash_set('success', 'Pengguna berhasil ditambahkan.');
        redirect('/users');
    }

    public function edit(string $id): void
    {
        $user = Auth::requireCan('users.manage');
        $pdo = Database::pdo();
        $s = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $s->execute([$id]);
        $target = $s->fetch();
        if (!$target) { http_response_code(404); die('Tidak ditemukan'); }
        view('users/form', [
            'user' => $user,
            'target' => $target,
            'action' => '/users/' . $id . '/update',
            'submit_label' => 'Perbarui Pengguna',
        ]);
    }

    public function update(string $id): void
    {
        csrf_check();
        $user = Auth::requireCan('users.manage');
        $pdo = Database::pdo();
        $s = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $s->execute([$id]);
        $target = $s->fetch();
        if (!$target) { http_response_code(404); die('Tidak ditemukan'); }

        // --- GUARDS (TIKET-1): prevent admin lockout ---
        $newRole = $_POST['role'] ?? $target['role'];

        // Block: admin cannot change their own role
        if ($id === $user['id'] && $newRole !== $target['role']) {
            flash_set('error', 'Tidak dapat mengubah role sendiri.');
            redirect('/users/' . $id . '/edit');
        }

        // Block: changing role away from 'admin' when no other active admin exists
        if ($newRole !== 'admin') {
            $c = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='admin' AND active=1 AND id != ?");
            $c->execute([$id]);
            if ((int)$c->fetchColumn() === 0) {
                flash_set('error', 'Harus ada minimal 1 admin aktif.');
                redirect('/users/' . $id . '/edit');
            }
        }
        // --- END GUARDS ---

        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $role = $newRole;
        $phone = trim($_POST['phone'] ?? '');
        $active = !empty($_POST['active']) ? 1 : 0;
        $password = $_POST['password'] ?? '';

        $errors = [];
        if (!$name) $errors[] = 'Nama wajib diisi.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
        if (!in_array($role, ['admin', 'director', 'manager', 'staff', 'pic', 'employee'], true)) $errors[] = 'Role tidak valid.';
        if ($password !== '' && strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
        if ($errors) {
            flash_set('error', implode("\n", $errors));
            redirect('/users/' . $id . '/edit');
        }

        // Prevent admin from disabling themselves
        if ($id === $user['id'] && !$active) {
            flash_set('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
            redirect('/users/' . $id . '/edit');
        }

        if ($password !== '') {
            $pdo->prepare("UPDATE users SET name=?, email=?, role=?, phone=?, active=?, password_hash=?, updated_at=datetime('now') WHERE id=?")
                ->execute([$name, $email, $role, $phone ?: null, $active, password_hash($password, PASSWORD_DEFAULT), $id]);
        } else {
            $pdo->prepare("UPDATE users SET name=?, email=?, role=?, phone=?, active=?, updated_at=datetime('now') WHERE id=?")
                ->execute([$name, $email, $role, $phone ?: null, $active, $id]);
        }

        flash_set('success', 'Pengguna berhasil diperbarui.');
        redirect('/users');
    }

    public function destroy(string $id): void
    {
        csrf_check();
        $user = Auth::requireCan('users.manage');
        if ($id === $user['id']) {
            flash_set('error', 'Anda tidak dapat menghapus akun sendiri.');
            redirect('/users');
        }
        Database::pdo()->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        flash_set('success', 'Pengguna berhasil dihapus.');
        redirect('/users');
    }

    public function profile(): void
    {
        $user = Auth::require();
        view('users/profile', ['user' => $user]);
    }

    public function updateProfile(): void
    {
        csrf_check();
        $user = Auth::require();
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $current = $_POST['current_password'] ?? '';

        $pdo = Database::pdo();
        if ($password !== '') {
            if (!password_verify($current, $user['password_hash'])) {
                flash_set('error', 'Password saat ini salah.');
                redirect('/profile');
            }
            if (strlen($password) < 6) {
                flash_set('error', 'Password baru minimal 6 karakter.');
                redirect('/profile');
            }
            $pdo->prepare("UPDATE users SET name=?, phone=?, password_hash=?, updated_at=datetime('now') WHERE id=?")
                ->execute([$name ?: $user['name'], $phone ?: null, password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        } else {
            $pdo->prepare("UPDATE users SET name=?, phone=?, updated_at=datetime('now') WHERE id=?")
                ->execute([$name ?: $user['name'], $phone ?: null, $user['id']]);
        }
        flash_set('success', 'Profil berhasil diperbarui.');
        redirect('/profile');
    }
}
