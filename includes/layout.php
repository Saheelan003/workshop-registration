<?php
require_once __DIR__ . '/helpers.php';
function navLink(string $href, string $label): string
{
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $same = $current === $href || ($href === 'index.php' && in_array($current, ['registrations.php'], true)) || ($href === 'workshop_form.php' && false);
    return '<a href="' . e($href) . '"' . ($same ? ' class="active" aria-current="page"' : '') . '>' . e($label) . '</a>';
}
function pageStart(string $title): void
{
    $user = currentUser();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> · Workshop Registration</title>
        <link rel="stylesheet" href="assets/css/style.css">
        <script src="assets/js/app.js" defer></script>
    </head>
    <body>
    <a class="skip-link" href="#main">Skip to content</a>
    <header class="topbar">
        <a class="brand" href="index.php"><span class="brand-mark">W</span><span>Workshop <small>Registration Service</small></span></a>
        <?php if ($user): ?>
        <nav class="nav-links" aria-label="Main navigation">
            <?php if ($user['role'] === 'admin'): ?>
                <?= navLink('users.php', 'User management') ?>
            <?php else: ?>
                <?= navLink('index.php', 'Workshops') ?>
                <?= navLink('history.php', 'Registration history') ?>
                <?php if ($user['role'] === 'manager'): ?><?= navLink('workshop_form.php', 'Add workshop') ?><?php endif; ?>
            <?php endif; ?>
            <span class="user-chip"><?= e($user['name']) ?> · <?= e(ucfirst($user['role'])) ?></span>
            <form method="post" action="logout.php" class="inline-form"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><button class="link-button" type="submit">Log out</button></form>
        </nav>
        <?php endif; ?>
    </header>
    <main class="container" id="main">
    <?php showFlash(); ?>
    <?php
}
function pageEnd(): void
{
    echo '</main><footer class="footer">Workshop Registration Service <span>·</span> Local development</footer></body></html>';
}
