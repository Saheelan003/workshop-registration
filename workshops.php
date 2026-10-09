<?php
// Compatibility URL: the workshop catalogue and filters live on index.php.
require_once __DIR__ . '/includes/auth.php';
requireLogin();
header('Location: index.php');
exit;
