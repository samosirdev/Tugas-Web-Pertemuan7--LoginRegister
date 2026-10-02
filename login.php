<?php
require 'config.php';
guest_only();

$errors = [];
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } else {
        $rawEmail = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $oldEmail = clean($rawEmail);

        if ($rawEmail === '' || $password === '') {
            $errors[] = 'Email dan password wajib diisi.';
        } elseif (!filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid.';
        } else {
            $user = find_user_by_email($rawEmail);
            // Pesan sengaja dibuat umum agar tidak membocorkan email mana yang terdaftar
            if (!$user || !password_verify($password, $user['password'])) {
                $errors[] = 'Email atau password salah.';
            } else {
                login_user($user);
                if (!empty($_POST['remember'])) {
                    remember_user($user['id']);          // bonus: Remember Me
                }
                flash('success', 'Selamat datang kembali, ' . $user['name'] . '!');
                redirect('dashboard.php');
            }
        }
    }
}

$pageTitle = 'Masuk';
$bodyClass = 'is-auth';
$brandTitle = 'Selamat datang kembali.';
$brandText  = 'Masuk untuk membuka dashboard dan mengelola profilmu.';
include 'partials/header.php';
?>
<main class="auth">
    <?php include 'partials/brand.php'; ?>
    <section class="panel">
        <div class="form-card">
            <h1>Masuk</h1>
            <p class="lead">Belum punya akun? <a href="register.php">Daftar gratis</a></p>

            <?php render_flash(); render_errors($errors); ?>

            <form method="post" novalidate>
                <?= csrf_field() ?>

                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= e($oldEmail) ?>" autocomplete="email" required autofocus>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="pw">
                        <input type="password" id="password" name="password" autocomplete="current-password" required>
                        <button type="button" class="pw-toggle" data-toggle="password">Lihat</button>
                    </div>
                </div>

                <label class="check">
                    <input type="checkbox" name="remember" value="1">
                    <span>Ingat saya selama <?= REMEMBER_DAYS ?> hari</span>
                </label>

                <button type="submit" class="btn">Masuk</button>
            </form>
        </div>
    </section>
</main>
<?php include 'partials/footer.php'; ?>
