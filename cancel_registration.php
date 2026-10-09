<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
requireRole(['manager','staff']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
verifyCsrf();
$id = filter_input(INPUT_POST, 'registration_id', FILTER_VALIDATE_INT);
$workshopId = filter_input(INPUT_POST, 'workshop_id', FILTER_VALIDATE_INT);
if (!$id || !$workshopId) { http_response_code(400); exit('Invalid registration.'); }
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT id, workshop_id, status FROM registrations WHERE id = ? FOR UPDATE');
    $stmt->execute([$id]); $registration = $stmt->fetch();
    if (!$registration || (int)$registration['workshop_id'] !== $workshopId) throw new RuntimeException('Registration not found.');
    if ($registration['status'] === 'active') {
        $update = $pdo->prepare("UPDATE registrations SET status='cancelled', cancelled_by=?, cancelled_at=NOW() WHERE id=? AND status='active'");
        $update->execute([(int)currentUser()['id'], $id]);
        audit($pdo, (int)currentUser()['id'], 'registration_cancelled', 'registration', $id, ['workshop_id'=>$workshopId]);
    }
    $pdo->commit(); flash('success', 'Registration cancelled. The history record was kept.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage()); flash('error', 'Could not cancel that registration.');
}
redirect('registrations.php?workshop_id=' . $workshopId);
