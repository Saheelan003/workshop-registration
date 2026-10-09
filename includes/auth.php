<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_start();
}

function requireLogin(): void
{
    if (empty($_SESSION['user']['id'])) {
        header('Location: login.php');
        exit;
    }
}

function requireRole(array $roles): void
{
    requireLogin();
    if (!in_array($_SESSION['user']['role'] ?? '', $roles, true)) {
        http_response_code(403);
        exit('Access denied. Your account does not have permission to perform this action.');
    }
}

function currentUser(): array
{
    return $_SESSION['user'] ?? [];
}
