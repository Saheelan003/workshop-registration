<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/layout.php';
requireRole(['manager','staff']);
$status = $_GET['status'] ?? '';
$sql = 'SELECT r.*, w.code, w.title AS workshop_title, u.name AS registered_by_name, c.name AS cancelled_by_name FROM registrations r JOIN workshops w ON w.id=r.workshop_id JOIN users u ON u.id=r.registered_by LEFT JOIN users c ON c.id=r.cancelled_by WHERE 1=1';
$params=[];
if (in_array($status,['active','cancelled'],true)) { $sql .= ' AND r.status=?'; $params[]=$status; }
$sql .= ' ORDER BY r.registered_at DESC LIMIT 500';
$stmt=$pdo->prepare($sql); $stmt->execute($params); $rows=$stmt->fetchAll();
pageStart('Registration history');
?>
<section class="page-heading"><div><p class="eyebrow">RECORDS</p><h1>Registration history</h1><p class="muted">A record of registrations and cancellations. Up to 500 most recent records are shown.</p></div></section>
<section class="panel"><form class="filters compact" method="get"><label>Status<select name="status"><option value="">All records</option><option value="active" <?= $status==='active'?'selected':'' ?>>Active</option><option value="cancelled" <?= $status==='cancelled'?'selected':'' ?>>Cancelled</option></select></label><button class="button secondary" type="submit">Filter</button><a class="button quiet" href="history.php">Reset</a><label class="search-box push-right"><span class="sr-only">Search records</span><input type="search" placeholder="Search records on this page" data-table-filter="history-table" autocomplete="off"></label></form><div class="table-wrap"><table id="history-table" class="responsive-table"><thead><tr><th>Workshop</th><th>Attendee</th><th>Status</th><th>Registered by</th><th>Registered at</th><th>Cancelled by</th><th>Cancelled at</th></tr></thead><tbody><?php if (!$rows): ?><tr class="static-row"><td colspan="7" class="empty-cell"><strong>No registration records found.</strong></td></tr><?php endif; ?><?php foreach ($rows as $r): ?><tr><td data-label="Workshop"><div class="workshop-name"><?= e($r['workshop_title']) ?></div><div class="small muted"><?= e($r['code']) ?></div></td><td data-label="Attendee"><?= e($r['attendee_name']) ?><div class="small muted"><?= e($r['attendee_email']) ?></div></td><td data-label="Status"><span class="status <?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td><td data-label="Registered by"><?= e($r['registered_by_name']) ?></td><td data-label="Registered at"><?= e(date('M j, Y g:i A',strtotime($r['registered_at']))) ?></td><td data-label="Cancelled by"><?= e($r['cancelled_by_name'] ?? '—') ?></td><td data-label="Cancelled at"><?= $r['cancelled_at'] ? e(date('M j, Y g:i A',strtotime($r['cancelled_at']))) : '—' ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php pageEnd(); ?>
