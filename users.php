<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/layout.php';
requireRole(['admin']);
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $password=$_POST['password']??''; $role=$_POST['role']??'';
    if ($name==='' || mb_strlen($name)>100 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>150 || strlen($password)<10 || !in_array($role,['manager','staff'],true)) {
        $error='Enter a name, valid email, password with at least 10 characters, and a permitted role.';
    } else {
        try {
            $stmt=$pdo->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)');
            $stmt->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role]);
            $id=(int)$pdo->lastInsertId(); audit($pdo,(int)currentUser()['id'],'user_created','user',$id,['role'=>$role]);
            flash('success','Account created successfully. Share the temporary password with the user securely.'); redirect('users.php');
        } catch(PDOException $e) { $error=$e->getCode()==='23000'?'That email address is already in use.':'Could not create the account.'; error_log($e->getMessage()); }
    }
}
$users=$pdo->query('SELECT id,name,email,role,created_at FROM users ORDER BY created_at DESC')->fetchAll();
pageStart('User management');
?>
<section class="page-heading"><div><p class="eyebrow">ADMINISTRATION</p><h1>User management</h1><p class="muted">Create and manage access for your workshop team.</p></div></section>
<div class="content-grid"><section class="panel"><div class="panel-heading"><div><h2>Create an account</h2><p class="muted">Only Admin can create accounts. Public sign-up is disabled.</p></div></div><?php if($error): ?><div class="notice error" role="alert"><span><?= e($error) ?></span></div><?php endif; ?><form method="post" class="stack-form"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><label>Full name<input name="name" maxlength="100" required value="<?= e($_POST['name']??'') ?>"></label><label>Email address<input type="email" name="email" maxlength="150" required value="<?= e($_POST['email']??'') ?>"></label><label>Temporary password<span class="password-field"><input type="password" name="password" minlength="10" required autocomplete="new-password"><button type="button" class="toggle-password" data-toggle-password aria-label="Show password" aria-pressed="false">Show</button></span><span class="field-help">Use at least 10 characters.</span></label><label>Role<select name="role" required><option value="manager">Manager</option><option value="staff">Staff</option></select></label><button class="button primary" type="submit">Create account</button></form></section>
<section class="panel"><div class="panel-heading"><div><h2>Team accounts</h2><p class="muted"><?= count($users) ?> account(s)</p></div></div><div class="table-wrap"><table class="responsive-table"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Created</th></tr></thead><tbody><?php foreach($users as $u): ?><tr><td class="workshop-name" data-label="Name"><?= e($u['name']) ?></td><td data-label="Email"><?= e($u['email']) ?></td><td data-label="Role"><span class="role-pill <?= e($u['role']) ?>"><?= e(ucfirst($u['role'])) ?></span></td><td data-label="Created"><?= e(date('M j, Y',strtotime($u['created_at']))) ?></td></tr><?php endforeach; ?></tbody></table></div></section></div>
<?php pageEnd(); ?>
