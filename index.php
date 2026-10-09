<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/layout.php';
requireLogin();
$user = currentUser();
if ($user['role'] === 'admin') redirect('users.php');

$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$status = $_GET['status'] ?? '';
$available = $_GET['available'] ?? '';
$sql = "SELECT w.*, COALESCE(r.active_count, 0) AS active_count
        FROM workshops w
        LEFT JOIN (SELECT workshop_id, COUNT(*) AS active_count FROM registrations WHERE status = 'active' GROUP BY workshop_id) r ON r.workshop_id = w.id
        WHERE 1=1";
$params = [];
if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $sql .= ' AND DATE(w.start_at) >= ?'; $params[] = $from; }
if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) { $sql .= ' AND DATE(w.start_at) <= ?'; $params[] = $to; }
if (in_array($status, ['scheduled', 'completed', 'cancelled'], true)) { $sql .= ' AND w.status = ?'; $params[] = $status; }
$sql .= ' ORDER BY w.start_at ASC';
$stmt = $pdo->prepare($sql); $stmt->execute($params); $workshops = $stmt->fetchAll();
if ($available === 'yes') $workshops = array_values(array_filter($workshops, fn($w) => $w['status'] === 'scheduled' && (int)$w['capacity'] > (int)$w['active_count']));
pageStart('Workshops');
?>
<section class="page-heading"><div><p class="eyebrow">WORKSPACE</p><h1>Workshops</h1><p class="muted">Find a session, check the seats, and manage attendee registrations.</p></div><?php if ($user['role'] === 'manager'): ?><a class="button primary" href="workshop_form.php">＋ Add workshop</a><?php endif; ?></section>
<section class="stats-grid"><article class="stat-card"><span class="stat-icon blue">▦</span><div><p class="stat-label">Total workshops</p><strong><?= count($workshops) ?></strong></div></article><article class="stat-card"><span class="stat-icon green">✓</span><div><p class="stat-label">Scheduled</p><strong><?= count(array_filter($workshops, fn($w) => $w['status'] === 'scheduled')) ?></strong></div></article><article class="stat-card"><span class="stat-icon amber">◷</span><div><p class="stat-label">Seats available</p><strong><?= array_sum(array_map(fn($w) => $w['status'] === 'scheduled' ? max(0, (int)$w['capacity'] - (int)$w['active_count']) : 0, $workshops)) ?></strong></div></article></section>
<section class="panel"><div class="panel-heading"><div><h2>Workshop schedule</h2><p class="muted">Filter the list to find the right session.</p></div><label class="search-box"><span class="sr-only">Search workshops</span><input type="search" placeholder="Search title, code or instructor" data-table-filter="workshops-table" autocomplete="off"></label></div><form class="filters" method="get"><label>From<input type="date" name="from" value="<?= e($from) ?>"></label><label>To<input type="date" name="to" value="<?= e($to) ?>"></label><label>Status<select name="status"><option value="">All statuses</option><?php foreach (['scheduled','completed','cancelled'] as $s): ?><option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?></select></label><label class="check-filter"><input type="checkbox" name="available" value="yes" <?= $available === 'yes' ? 'checked' : '' ?>> Seats available only</label><button class="button secondary" type="submit">Apply filters</button><a class="button quiet" href="index.php">Reset</a></form>
<div class="table-wrap"><table id="workshops-table" class="responsive-table"><thead><tr><th>Workshop</th><th>Instructor</th><th>Date &amp; time</th><th>Seats</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if (!$workshops): ?><tr class="static-row"><td colspan="6" class="empty-cell"><strong>No workshops match those filters.</strong><br><span class="muted">Try widening the date range or <a href="index.php">reset the filters</a>.</span></td></tr><?php endif; ?>
<?php foreach ($workshops as $w): $left = max(0, (int)$w['capacity'] - (int)$w['active_count']); $pct = (int)$w['capacity'] ? min(100, ((int)$w['active_count'] / (int)$w['capacity']) * 100) : 0; $barClass = $left === 0 ? 'full' : ($pct >= 80 ? 'low' : ''); ?><tr><td data-label="Workshop"><div class="workshop-name"><?= e($w['title']) ?></div><div class="small muted"><?= e($w['code']) ?></div></td><td data-label="Instructor"><?= e($w['instructor']) ?></td><td data-label="Date &amp; time"><?= e(date('M j, Y · g:i A', strtotime($w['start_at']))) ?></td><td data-label="Seats"><div class="seat-count"><?= (int)$w['active_count'] ?> <span class="muted">/ <?= (int)$w['capacity'] ?></span></div><div class="seat-bar <?= $barClass ?>" role="img" aria-label="<?= (int)$w['active_count'] ?> of <?= (int)$w['capacity'] ?> seats booked"><span style="width:<?= round($pct) ?>%"></span></div><small class="muted"><?= $left === 0 ? 'Full' : $left . ' available' ?></small></td><td data-label="Status"><span class="status <?= e($w['status']) ?>"><?= e(ucfirst($w['status'])) ?></span></td><td data-label="Actions"><div class="actions"><a class="text-link" href="registrations.php?workshop_id=<?= (int)$w['id'] ?>">Attendees</a><?php if ($user['role'] === 'manager'): ?><a class="text-link" href="workshop_form.php?id=<?= (int)$w['id'] ?>">Edit</a><?php endif; ?></div></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<?php pageEnd(); ?>
