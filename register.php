<?php
require 'config.php';
guest_only();

$errors = [];
$old = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } else {
        $rawName  = trim($_POST['name'] ?? '');
        $rawEmail = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';

        $old['name']  = clean($rawName);   // sanitasi input (syarat #9)
        $old['email'] = clean($rawEmail);

        // Validasi nama
        if ($rawName === '') {
            $errors[] = 'Nama wajib diisi.';
        } elseif (mb_strlen($rawName) < 3 || mb_strlen($rawName) > 50) {
            $errors[] = 'Nama harus 3–50 karakter.';
        }

        // Validasi email dengan filter_var (syarat #2)
        if ($rawEmail === '') {
            $errors[] = 'Email wajib diisi.';
        } elseif (!filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid. Contoh: nama@email.com';
        } elseif (find_user_by_email($rawEmail)) {           // cek duplikasi (syarat #5)
            $errors[] = 'Email ini sudah terdaftar. Silakan login atau pakai email lain.';
        }

        // Validasi password
        if ($password === '') {
            $errors[] = 'Password wajib diisi.';
        } elseif (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            $errors[] = 'Password minimal 8 karakter dan mengandung huruf serta angka.';
        } elseif ($password !== $confirm) {
            $errors[] = 'Konfirmasi password tidak sama.';
        }

        if (!$errors) {
            $users   = get_users();
            $users[] = [
                'id'         => bin2hex(random_bytes(8)),
                'name'       => $old['name'],
                'email'      => strtolower($rawEmail),
                'password'   => password_hash($password, PASSWORD_DEFAULT), // syarat #3
                'created_at' => date('c'),
                'last_login' => null,
            ];
            if (save_users($users)) {
                flash('success', 'Registrasi berhasil! Silakan login dengan akun barumu.');
                redirect('login.php');
            }
            $errors[] = 'Gagal menyimpan data. Pastikan folder data/ bisa ditulis.';
        }
    }
}

$pageTitle = 'Daftar';
$bodyClass = 'is-auth';
$brandTitle = 'Buat akun dalam satu menit.';
$brandText  = 'Isi nama, email, dan password. Passwordmu disimpan dalam bentuk hash, bukan teks asli.';
include 'partials/header.php';
?>
<main class="auth">
    <?php include 'partials/brand.php'; ?>
    <section class="panel">
        <div class="form-card">
            <h1>Daftar</h1>
            <p class="lead">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>

            <?php render_flash(); render_errors($errors); ?>

            <form method="post" novalidate>
                <?= csrf_field() ?>

                <div class="field">
                    <label for="name">Nama lengkap</label>
                    <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" autocomplete="name" required>
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" autocomplete="email" required>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="pw">
                        <input type="password" id="password" name="password" autocomplete="new-password" required>
                        <button type="button" class="pw-toggle" data-toggle="password">Lihat</button>
                    </div>
                    <span class="hint">Minimal 8 karakter, kombinasi huruf dan angka.</span>
                </div>

                <div class="field">
                    <label for="confirm">Ulangi password</label>
                    <div class="pw">
                        <input type="password" id="confirm" name="confirm" autocomplete="new-password" required>
                        <button type="button" class="pw-toggle" data-toggle="confirm">Lihat</button>
                    </div>
                </div>

                <button type="submit" class="btn">Buat akun</button>
            </form>
        </div>
    </section>
</main>
<?php include 'partials/footer.php'; ?>
