<?php
require 'config.php';

if (!empty($_SESSION['user_id'])) {
    forget_user($_SESSION['user_id']);   // hapus token Remember Me
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();                        // syarat #8

// Mulai session baru hanya untuk membawa pesan flash
session_start();
flash('success', 'Kamu sudah keluar. Sampai jumpa lagi!');
redirect('login.php');
