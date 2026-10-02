<?php
require 'config.php';
$user = require_login();

$errors = [];
$old = ['name' => $user['name'], 'email' => $user['email']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } else {
        $rawName  = trim($_POST['name'] ?? '');
        $rawEmail = trim($_POST['email'] ?? '');
        $current  = $_POST['current_password'] ?? '';
        $newPass  = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';

        $old['name']  = clean($rawName);
        $old['email'] = clean($rawEmail);

        if ($rawName === '' || mb_strlen($rawName) < 3 || mb_strlen($rawName) > 50) {
            $errors[] = 'Nama harus diisi 3–50 karakter.';
        }
        if (!filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid.';
        } else {
            $other = find_user_by_email($rawEmail);
            if ($other && $other['id'] !== $user['id']) {
                $errors[] = 'Email itu sudah dipakai akun lain.';
            }
        }

        // Ganti password hanya jika kolom password baru diisi
        $changePassword = $newPass !== '';
        if ($changePassword) {
            if (!password_verify($current, $user['password'])) {
                $errors[] = 'Password saat ini salah.';
            }
            if (strlen($newPass) < 8 || !preg_match('/[A-Za-z]/', $newPass) || !preg_match('/\d/', $newPass)) {
                $errors[] = 'Password baru minimal 8 karakter dan mengandung huruf serta angka.';
            } elseif ($newPass !== $confirm) {
                $errors[] = 'Konfirmasi password baru tidak sama.';
            }
        }

        if (!$errors) {
            $users = get_users();
            foreach ($users as &$u) {
                if ($u['id'] === $user['id']) {
                    $u['name']  = $old['name'];
                    $u['email'] = strtolower($rawEmail);
                    if ($changePassword) $u['password'] = password_hash($newPass, PASSWORD_DEFAULT);
                }
            }
            unset($u);
            save_users($users);
            flash('success', 'Profil berhasil diperbarui.');
            redirect('dashboard.php');
        }
    }
}

$pageTitle = 'Edit profil';
$bodyClass = 'is-app';
include 'partials/header.php';
?>
<header class="topbar">
    <a class="wordmark" href="dashboard.php">Gerbang<span>.</span></a>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php" class="nav-out">Keluar</a>
    </nav>
</header>

<main class="app narrow">
    <h1>Edit profil</h1>
    <?php render_errors($errors); ?>

    <form method="post" class="card-form" novalidate>
        <?= csrf_field() ?>

        <div class="field">
            <label for="name">Nama lengkap</label>
            <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" required>
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>
        </div>

        <fieldset>
            <legend>Ganti password <small>(kosongkan jika tidak ingin mengubah)</small></legend>
            <div class="field">
                <label for="current_password">Password saat ini</label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password">
            </div>
            <div class="field">
                <label for="new_password">Password baru</label>
                <input type="password" id="new_password" name="new_password" autocomplete="new-password">
            </div>
            <div class="field">
                <label for="confirm">Ulangi password baru</label>
                <input type="password" id="confirm" name="confirm" autocomplete="new-password">
            </div>
        </fieldset>

        <button type="submit" class="btn">Simpan perubahan</button>
    </form>
</main>
<?php include 'partials/footer.php'; ?>
