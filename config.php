<?php
/**
 * config.php — dipanggil paling atas di SETIAP halaman.
 * Isi: session, helper JSON, sanitasi, flash message, CSRF, auth & "Remember Me".
 */
session_start();
date_default_timezone_set('Asia/Jakarta');

define('DATA_DIR', __DIR__ . '/data');
define('DATA_FILE', DATA_DIR . '/users.json');
define('REMEMBER_DAYS', 30);
define('REMEMBER_COOKIE', 'remember_me');

// Buat folder & file JSON otomatis kalau belum ada
if (!is_dir(DATA_DIR)) { mkdir(DATA_DIR, 0775, true); }
if (!file_exists(DATA_FILE)) { file_put_contents(DATA_FILE, '[]', LOCK_EX); }

/* ---------- Sanitasi ---------- */
// Dipakai saat input diterima (syarat #9)
function clean(string $v): string {
    return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
}
// Dipakai saat menampilkan data ke HTML (double_encode=false supaya tidak dobel &amp;amp;)
function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8', false);
}

/* ---------- Penyimpanan JSON (syarat #4) ---------- */
function get_users(): array {
    $json = file_get_contents(DATA_FILE);
    $data = json_decode($json ?: '[]', true);
    return is_array($data) ? $data : [];
}
function save_users(array $users): bool {
    $json = json_encode(array_values($users), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents(DATA_FILE, $json, LOCK_EX) !== false;
}
function find_user_by_email(string $email): ?array {
    foreach (get_users() as $u) {
        if (strcasecmp($u['email'], $email) === 0) return $u;
    }
    return null;
}
function find_user_by_id(string $id): ?array {
    foreach (get_users() as $u) {
        if ($u['id'] === $id) return $u;
    }
    return null;
}

/* ---------- Redirect & pesan flash (syarat #10) ---------- */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}
function flash(string $type, string $msg): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}
function render_flash(): void {
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    foreach ($items as $f) {
        echo '<div class="alert alert-' . e($f['type']) . '" role="alert">' . e($f['msg']) . '</div>';
    }
}
function render_errors(array $errors): void {
    if (!$errors) return;
    echo '<div class="alert alert-error" role="alert"><strong>Periksa kembali isian kamu:</strong><ul>';
    foreach ($errors as $err) echo '<li>' . e($err) . '</li>';
    echo '</ul></div>';
}

/* ---------- CSRF ---------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
function csrf_valid(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

/* ---------- Auth & session (syarat #6, #7) ---------- */
function current_user(): ?array {
    return !empty($_SESSION['user_id']) ? find_user_by_id($_SESSION['user_id']) : null;
}
function require_login(): array {
    $user = current_user();
    if (!$user) {
        flash('error', 'Silakan login dulu untuk membuka halaman itu.');
        redirect('login.php');
    }
    return $user;
}
function guest_only(): void {
    if (current_user()) redirect('dashboard.php');
}
function login_user(array $user): void {
    session_regenerate_id(true);           // cegah session fixation
    $_SESSION['user_id'] = $user['id'];
    $users = get_users();
    foreach ($users as &$u) {
        if ($u['id'] === $user['id']) $u['last_login'] = date('c');
    }
    unset($u);
    save_users($users);
}

/* ---------- Bonus: Remember Me dengan cookie ---------- */
function set_remember_cookie(string $value, int $expires): void {
    setcookie(REMEMBER_COOKIE, $value, [
        'expires'  => $expires,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
function remember_user(string $userId): void {
    $token   = bin2hex(random_bytes(32));
    $expires = time() + REMEMBER_DAYS * 86400;
    $users   = get_users();
    foreach ($users as &$u) {
        if ($u['id'] === $userId) {
            $u['remember_hash']    = hash('sha256', $token); // yang disimpan hanya hash-nya
            $u['remember_expires'] = $expires;
        }
    }
    unset($u);
    save_users($users);
    set_remember_cookie($userId . ':' . $token, $expires);
}
function forget_user(string $userId): void {
    $users = get_users();
    foreach ($users as &$u) {
        if ($u['id'] === $userId) { unset($u['remember_hash'], $u['remember_expires']); }
    }
    unset($u);
    save_users($users);
    set_remember_cookie('', time() - 3600);
}
// Auto-login dari cookie jika session belum ada
if (empty($_SESSION['user_id']) && !empty($_COOKIE[REMEMBER_COOKIE])) {
    $parts = explode(':', $_COOKIE[REMEMBER_COOKIE], 2);
    if (count($parts) === 2) {
        $u = find_user_by_id($parts[0]);
        if ($u && !empty($u['remember_hash'])
            && ($u['remember_expires'] ?? 0) > time()
            && hash_equals($u['remember_hash'], hash('sha256', $parts[1]))) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $u['id'];
        } else {
            set_remember_cookie('', time() - 3600);
        }
    }
}
