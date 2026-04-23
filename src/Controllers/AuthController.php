<?php
namespace App\Controllers;

use App\Auth;
use function App\{view, redirect, flash_set, flash_get, csrf_check, set_old, old, clear_old};

class AuthController
{
    private const MAX_ATTEMPTS    = 5;
    private const LOCKOUT_SECONDS = 300; // 5 minutes

    public function showLogin(): void
    {
        if (Auth::check()) redirect('/');
        view('auth/login', [
            'error' => flash_get('error'),
            'success' => flash_get('success'),
        ]);
    }

    public function login(): void
    {
        csrf_check();

        // Gate 1: under active lockout?
        $remaining = $this->lockoutRemaining();
        if ($remaining > 0) {
            $mins = (int)ceil($remaining / 60);
            flash_set('error', "Terlalu banyak percobaan. Coba lagi dalam {$mins} menit.");
            redirect('/login');
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        set_old(['email' => $email]);

        if (!$email || !$password) {
            flash_set('error', 'Email dan password wajib diisi.');
            redirect('/login');
        }

        if (!Auth::attempt($email, $password)) {
            $this->registerFailure();
            $remainingTries = self::MAX_ATTEMPTS - ($_SESSION['login_attempts'] ?? 0);
            if ($remainingTries <= 0) {
                $mins = (int)ceil(self::LOCKOUT_SECONDS / 60);
                flash_set('error', "Terlalu banyak percobaan. Akun terkunci selama {$mins} menit.");
            } else {
                flash_set('error', "Email atau password salah. Sisa percobaan: {$remainingTries}.");
            }
            redirect('/login');
        }

        // Success — reset counters
        $this->resetAttempts();
        clear_old();
        redirect('/');
    }

    public function logout(): void
    {
        csrf_check();
        Auth::logout();
        redirect('/login');
    }

    /**
     * @return int Seconds remaining on active lockout, 0 if not locked out.
     */
    private function lockoutRemaining(): int
    {
        $until = $_SESSION['login_lockout_until'] ?? 0;
        if ($until > time()) {
            return $until - time();
        }
        // Lockout expired: clear so the user gets a fresh attempt budget
        if ($until !== 0) {
            unset($_SESSION['login_lockout_until'], $_SESSION['login_attempts']);
        }
        return 0;
    }

    private function registerFailure(): void
    {
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] >= self::MAX_ATTEMPTS) {
            $_SESSION['login_lockout_until'] = time() + self::LOCKOUT_SECONDS;
        }
    }

    private function resetAttempts(): void
    {
        unset($_SESSION['login_attempts'], $_SESSION['login_lockout_until']);
    }
}
