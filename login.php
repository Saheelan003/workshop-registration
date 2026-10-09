<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
if (!empty($_SESSION['user'])) redirect('index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']];
        audit($pdo, (int)$user['id'], 'login', 'user', (int)$user['id']);
        redirect('index.php');
    }
    $error = 'The email or password was not correct.';
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sign in · Workshop Registration</title><link rel="stylesheet" href="assets/css/style.css"><script src="assets/js/app.js" defer></script></head><body class="login-page"><main class="login-shell"><section class="login-intro"><div class="brand-mark large">W</div><p class="eyebrow">COMMUNITY TRAINING CENTRE</p><h1>Workshops, without the spreadsheet chaos.</h1><p>Keep workshop schedules, attendee registrations and available seats in one place.</p><div class="intro-note">A simple workspace for the people who make your workshops happen.</div></section><section class="login-card"><p class="eyebrow">STAFF ACCESS</p><h2>Welcome back</h2><p class="muted">Sign in with the account created by your administrator.</p><?php if ($error): ?><div class="notice error" role="alert"><span><?= e($error) ?></span></div><?php endif; ?><form method="post" class="stack-form"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><label>Email address<input type="email" name="email" autocomplete="username" autofocus required value="<?= e($_POST['email'] ?? '') ?>"></label><label>Password<span class="password-field"><input type="password" name="password" autocomplete="current-password" required><button type="button" class="toggle-password" data-toggle-password aria-label="Show password" aria-pressed="false">Show</button></span></label><button class="button primary full" type="submit">Sign in <span>→</span></button></form><p class="small muted">Accounts are created by an Admin. Public sign-up is disabled.</p></section></main></body></html>
