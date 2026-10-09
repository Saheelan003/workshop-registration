<?php
require_once __DIR__ . '/auth.php';

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Your form session expired. Go back, refresh the page, and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function showFlash(): void
{
    if (!empty($_SESSION['flash'])) {
        $item = $_SESSION['flash'];

        echo '<div class="notice ' . e($item['type']) . '" role="' . ($item['type'] === 'error' ? 'alert' : 'status') . '">'
            . '<span>' . e($item['message']) . '</span>'
            . '<button type="button" class="notice-close" aria-label="Dismiss message" data-dismiss>&times;</button>'
            . '</div>';

        unset($_SESSION['flash']);
    }
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function audit(
    PDO $pdo,
    int $userId,
    string $action,
    string $entityType,
    ?int $entityId = null,
    array $details = []
): void {
    try {
        $detailsJson = json_encode(
            $details,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($detailsJson === false) {
            $detailsJson = '{}';
        }

        $sql = 'INSERT INTO audit_logs
                (user_id, action, entity_type, entity_id, details)
                VALUES (?, ?, ?, ?, ?)';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $detailsJson
        ]);
    } catch (PDOException $exception) {
        error_log('Audit logging failed: ' . $exception->getMessage());
    }
}
