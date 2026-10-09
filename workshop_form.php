<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/layout.php';
requireRole(['manager']);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
$workshop = ['code'=>'','title'=>'','instructor'=>'','start_at'=>'','capacity'=>12,'status'=>'scheduled'];
if ($id) { $stmt = $pdo->prepare('SELECT * FROM workshops WHERE id = ?'); $stmt->execute([$id]); $found = $stmt->fetch(); if (!$found) { http_response_code(404); exit('Workshop not found.'); } $workshop = $found; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $code = strtoupper(trim($_POST['code'] ?? '')); $title = trim($_POST['title'] ?? ''); $instructor = trim($_POST['instructor'] ?? '');
    $startAt = trim($_POST['start_at'] ?? ''); $capacity = filter_var($_POST['capacity'] ?? null, FILTER_VALIDATE_INT); $status = $_POST['status'] ?? '';
    $workshop = compact('code','title','instructor','capacity','status'); $workshop['start_at'] = $startAt;
    if ($code === '' || $title === '' || $instructor === '' || !$capacity || $capacity < 1 || !in_array($status, ['scheduled','completed','cancelled'], true) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $startAt)) {
        $error = 'Please complete every field. Capacity must be at least 1.';
    } else {
        try {
            $startAt = str_replace('T', ' ', $startAt) . ':00';
            if ($id) {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM registrations WHERE workshop_id = ? AND status = \'active\''); $stmt->execute([$id]);
                if ((int)$stmt->fetchColumn() > $capacity) throw new RuntimeException('Capacity cannot be lower than the number of active registrations.');
                $stmt = $pdo->prepare('UPDATE workshops SET code=?, title=?, instructor=?, start_at=?, capacity=?, status=? WHERE id=?');
                $stmt->execute([$code,$title,$instructor,$startAt,$capacity,$status,$id]);
                audit($pdo, (int)currentUser()['id'], 'workshop_updated', 'workshop', $id, ['code'=>$code]);
                flash('success', 'Workshop updated.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO workshops (code,title,instructor,start_at,capacity,status) VALUES (?,?,?,?,?,?)');
                $stmt->execute([$code,$title,$instructor,$startAt,$capacity,$status]); $newId = (int)$pdo->lastInsertId();
                audit($pdo, (int)currentUser()['id'], 'workshop_created', 'workshop', $newId, ['code'=>$code]);
                flash('success', 'Workshop created.');
            }
            redirect('index.php');
        } catch (RuntimeException $e) { $error = $e->getMessage(); }
        catch (PDOException $e) { $error = $e->getCode() === '23000' ? 'That workshop code is already in use.' : 'Could not save the workshop.'; error_log($e->getMessage()); }
    }
}
$dt = !empty($workshop['start_at']) ? date('Y-m-d\TH:i', strtotime($workshop['start_at'])) : '';
pageStart($id ? 'Edit workshop' : 'Add workshop');
?>
<section class="page-heading"><div><p class="eyebrow">WORKSHOP SETUP</p><h1><?= $id ? 'Edit workshop' : 'Add a workshop' ?></h1><p class="muted">Keep the schedule and seat limit up to date.</p></div><a class="button quiet" href="index.php">← Back to workshops</a></section>
<section class="panel form-panel"><?php if ($error): ?><div class="notice error" role="alert"><span><?= e($error) ?></span></div><?php endif; ?><form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><label>Workshop code<input name="code" autofocus maxlength="30" required value="<?= e($workshop['code']) ?>" placeholder="e.g. POT-101"><span class="field-help">Must be unique. Shown to staff in lists.</span></label><label>Workshop title<input name="title" maxlength="150" required value="<?= e($workshop['title']) ?>" placeholder="e.g. Introduction to pottery"></label><label>Instructor<input name="instructor" maxlength="100" required value="<?= e($workshop['instructor']) ?>"></label><label>Date and time<input type="datetime-local" name="start_at" required value="<?= e($dt) ?>"></label><label>Seat capacity<input type="number" name="capacity" min="1" max="10000" required value="<?= e($workshop['capacity']) ?>"><span class="field-help">Cannot be lower than current active registrations.</span></label><label>Status<select name="status"><?php foreach (['scheduled','completed','cancelled'] as $s): ?><option value="<?= e($s) ?>" <?= $workshop['status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?></select></label><div class="form-actions"><button class="button primary" type="submit"><?= $id ? 'Save changes' : 'Create workshop' ?></button><a class="button secondary" href="index.php">Cancel</a></div></form></section>
<?php pageEnd(); ?>
