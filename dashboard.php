<?php
require 'config.php';
$user = require_login();   // belum login → otomatis redirect ke login.php (syarat #7)

$pageTitle = 'Dashboard';
$bodyClass = 'is-app';
include 'partials/header.php';

$joined    = date('d M Y', strtotime($user['created_at']));
$lastLogin = $user['last_login'] ? date('d M Y, H:i', strtotime($user['last_login'])) : 'Baru pertama kali';
$initial   = mb_strtoupper(mb_substr(html_entity_decode($user['name']), 0, 1));
?>
<header class="topbar">
    <a class="wordmark" href="dashboard.php">Gerbang<span>.</span></a>
    <nav>
        <a href="edit_profile.php">Edit profil</a>
        <a href="logout.php" class="nav-out">Keluar</a>
    </nav>
</header>

<main class="app">
    <?php render_flash(); ?>

    <section class="hero">
        <div class="avatar" aria-hidden="true"><?= e($initial) ?></div>
        <div>
            <h1>Halo, <?= e($user['name']) ?></h1>
            <p>Kamu berhasil masuk. Halaman ini hanya bisa dibuka setelah login.</p>
        </div>
    </section>

    <section class="info">
        <article>
            <h3>Email</h3>
            <p><?= e($user['email']) ?></p>
        </article>
        <article>
            <h3>Bergabung sejak</h3>
            <p><?= e($joined) ?></p>
        </article>
        <article>
            <h3>Login terakhir</h3>
            <p><?= e($lastLogin) ?></p>
        </article>
        <article>
            <h3>Ingat saya</h3>
            <p><?= !empty($user['remember_hash']) ? 'Aktif di perangkat ini' : 'Tidak aktif' ?></p>
        </article>
    </section>

    <a class="btn btn-inline" href="edit_profile.php">Edit profil</a>
</main>
<?php include 'partials/footer.php'; ?>
