<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/layout.php';
requireRole(['manager','staff']);
$workshopId = filter_input(INPUT_GET, 'workshop_id', FILTER_VALIDATE_INT);
$stmt = $pdo->prepare('SELECT * FROM workshops WHERE id = ?'); $stmt->execute([$workshopId]); $workshop = $stmt->fetch();
if (!$workshop) { http_response_code(404); exit('Workshop not found.'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name = trim($_POST['attendee_name'] ?? ''); $email = trim($_POST['attendee_email'] ?? '');
    if ($name === '' || mb_strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $error = 'Enter a name and a valid email address.';
    } else {
        $pdo->beginTransaction();
        try {
            // Lock the workshop row so concurrent registrations for this workshop queue up.
            $lock = $pdo->prepare('SELECT id, capacity, status FROM workshops WHERE id = ? FOR UPDATE');
            $lock->execute([$workshopId]); $lockedWorkshop = $lock->fetch();
            if (!$lockedWorkshop) throw new RuntimeException('Workshop not found.');
            if ($lockedWorkshop['status'] !== 'scheduled') throw new RuntimeException('This workshop is not open for registration.');
            $count = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE workshop_id = ? AND status = 'active'");
            $count->execute([$workshopId]);
            if ((int)$count->fetchColumn() >= (int)$lockedWorkshop['capacity']) throw new RuntimeException('This workshop is full. No registration was added.');
            $insert = $pdo->prepare("INSERT INTO registrations (workshop_id, attendee_name, attendee_email, status, registered_by) VALUES (?, ?, ?, 'active', ?)");
            $insert->execute([$workshopId, $name, $email, (int)currentUser()['id']]);
            $registrationId = (int)$pdo->lastInsertId();
            audit($pdo, (int)currentUser()['id'], 'registration_created', 'registration', $registrationId, ['workshop_id'=>$workshopId,'attendee_email'=>$email]);
            $pdo->commit(); flash('success', 'Attendee registered successfully.'); redirect('registrations.php?workshop_id=' . $workshopId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($e instanceof RuntimeException) $error = $e->getMessage(); else { error_log($e->getMessage()); $error = 'Could not complete registration. Please try again.'; }
        }
    }
}
$stmt = $pdo->prepare("SELECT r.*, u.name AS registered_by_name, c.name AS cancelled_by_name FROM registrations r JOIN users u ON u.id = r.registered_by LEFT JOIN users c ON c.id = r.cancelled_by WHERE r.workshop_id = ? ORDER BY r.registered_at DESC"); $stmt->execute([$workshopId]); $registrations = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE workshop_id = ? AND status = 'active'"); $stmt->execute([$workshopId]); $activeCount = (int)$stmt->fetchColumn();
pageStart('Attendees');
?>
<section class="page-heading"><div><p class="eyebrow">ATTENDEE MANAGEMENT · <?= e($workshop['code']) ?></p><h1><?= e($workshop['title']) ?></h1><p class="muted"><?= e(date('M j, Y · g:i A', strtotime($workshop['start_at']))) ?> · <?= $activeCount ?> of <?= (int)$workshop['capacity'] ?> seats booked</p></div><a class="button quiet" href="index.php">← Back to workshops</a></section>
<?php $cap=(int)$workshop['capacity']; $left=max(0,$cap-$activeCount); $pct=$cap?min(100,$activeCount/$cap*100):0; $barClass=$left===0?'full':($pct>=80?'low':''); ?><section class="capacity-banner"><div><strong><?= $left === 0 ? 'Workshop is full' : $left . ' seat' . ($left === 1 ? '' : 's') . ' left' ?></strong><span class="muted"><?= $activeCount ?> of <?= $cap ?> booked · <span class="status <?= e($workshop['status']) ?>"><?= e(ucfirst($workshop['status'])) ?></span></span></div><div class="seat-bar wide <?= $barClass ?>" role="img" aria-label="<?= $activeCount ?> of <?= $cap ?> seats booked"><span style="width:<?= round($pct) ?>%"></span></div></section>
<div class="content-grid"><section class="panel sticky-panel"><div class="panel-heading"><div><h2>Register attendee</h2><p class="muted">Attendees do not need their own accounts.</p></div></div><?php if ($error): ?><div class="notice error" role="alert"><span><?= e($error) ?></span></div><?php endif; ?><form method="post" class="stack-form"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><label>Attendee name<input name="attendee_name" autocomplete="off" maxlength="100" required value="<?= e($_POST['attendee_name'] ?? '') ?>"></label><label>Email address<input type="email" name="attendee_email" autocomplete="off" maxlength="150" required value="<?= e($_POST['attendee_email'] ?? '') ?>"></label><button class="button primary" type="submit" <?= ($workshop['status'] !== 'scheduled' || $activeCount >= (int)$workshop['capacity']) ? 'disabled' : '' ?>>Register attendee</button><?php if ($workshop['status'] !== 'scheduled'): ?><p class="small muted">Registration is closed because this workshop is <?= e($workshop['status']) ?>.</p><?php elseif ($activeCount >= (int)$workshop['capacity']): ?><p class="small muted">This workshop is full.</p><?php endif; ?></form></section>
<section class="panel"><div class="panel-heading"><div><h2>Registration records</h2><p class="muted">Cancelled records remain in the history.</p></div><label class="search-box"><span class="sr-only">Search attendees</span><input type="search" placeholder="Search attendees" data-table-filter="attendees-table" autocomplete="off"></label></div><div class="table-wrap"><table id="attendees-table" class="responsive-table"><thead><tr><th>Attendee</th><th>Status</th><th>Registered by / when</th><th>Cancellation</th><th>Action</th></tr></thead><tbody><?php if (!$registrations): ?><tr class="static-row"><td colspan="5" class="empty-cell"><strong>No registrations yet.</strong><br><span class="muted">Use the form to add the first attendee.</span></td></tr><?php endif; ?><?php foreach ($registrations as $r): ?><tr><td data-label="Attendee"><div class="workshop-name"><?= e($r['attendee_name']) ?></div><div class="small muted"><?= e($r['attendee_email']) ?></div></td><td data-label="Status"><span class="status <?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td><td data-label="Registered by"><?= e($r['registered_by_name']) ?><div class="small muted"><?= e(date('M j, Y g:i A', strtotime($r['registered_at']))) ?></div></td><td data-label="Cancellation"><?php if ($r['cancelled_at']): ?><?= e($r['cancelled_by_name']) ?><div class="small muted"><?= e(date('M j, Y g:i A', strtotime($r['cancelled_at']))) ?></div><?php else: ?><span class="muted">—</span><?php endif; ?></td><td data-label="Action"><?php if ($r['status'] === 'active'): ?><form method="post" action="cancel_registration.php" data-confirm="Cancel this registration? The record will be kept in history."><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="registration_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="workshop_id" value="<?= (int)$workshopId ?>"><button class="button danger small-button" type="submit">Cancel</button></form><?php else: ?><span class="muted">—</span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section></div>
<?php pageEnd(); ?>
