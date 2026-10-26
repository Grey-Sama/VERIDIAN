<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Manila');
require __DIR__.'/app/auth.php';
require __DIR__.'/app/profile.php';
require __DIR__.'/app/leave.php';
require __DIR__.'/app/personnel.php';
require __DIR__.'/app/access.php';
auth_bootstrap();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

$config = require __DIR__.'/config/config.php';
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function go(string $path='index.php'): never { header('Location: '.$path); exit; }
function csrf(): string { return '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">'; }
function db(array $c, bool $select=true): PDO {
 return new PDO('mysql:host='.$c['host'].';port='.$c['port'].($select?';dbname='.$c['database']:'').';charset=utf8mb4',$c['username'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>5]);
}
function validDate(string $value): bool { $d=DateTimeImmutable::createFromFormat('!Y-m-d',$value);return $d && $d->format('Y-m-d')===$value; }
function field(string $name): string { $value=$_POST[$name]??''; if(!is_string($value))return '';return in_array($name,['password','confirm_password','current_password','new_password'],true)?$value:trim($value); }
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$error=''; $notice=$_SESSION['notice']??''; unset($_SESSION['notice']);
$pdo=null;$installed=false;$connectionError=false;
try { $pdo=db($config); $installed=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn()>0; } catch(PDOException $ex){ $connectionError=!in_array((int)($ex->errorInfo[1]??0),[1049,1146],true);if($connectionError)error_log($ex->getMessage()); }
$isPost=$_SERVER['REQUEST_METHOD']==='POST';
if($installed){
 auth_resume($pdo);
 try{auth_restore($pdo);}catch(PDOException $authError){error_log($authError->getMessage());if(isset($_SESSION['remembered']))auth_logout($pdo);}
}
if(!$isPost && in_array($_GET['auth']??'',['login','register','admin'],true) && empty($_SESSION['remembered']) && isset($_SESSION['uid']))auth_logout($pdo);
if($isPost && !hash_equals($_SESSION['csrf'],field('csrf'))) { http_response_code(403); exit('Session expired. Refresh and try again.'); }
$user=null;
if($installed && isset($_SESSION['uid'])) {
 $q=$pdo->prepare('SELECT id,name,username,email,role,password_hash,avatar_version,password_must_change,job_title,department FROM users WHERE id=?');$q->execute([$_SESSION['uid']]);$record=$q->fetch();
 if(!$record || !isset($_SESSION['password_version']) || !hash_equals($_SESSION['password_version'],hash('sha256',$record['password_hash']))){auth_logout($pdo);}
 else{$user=$record;unset($user['password_hash']);}
}
$primaryAdmin=$installed?(int)$pdo->query("SELECT MIN(id) FROM users WHERE role='admin'")->fetchColumn():0;
if($isPost){
 try {
  if($connectionError && field('action') !== 'setup' && field('action') !== 'logout') throw new RuntimeException('Workspace unavailable. Try again shortly.');
  $action=field('action');
  if(!in_array($action,['setup','login','register','logout'],true)){
   if(!$user){http_response_code(401);throw new RuntimeException('Please sign in.');}
   if(!access_action($user,$primaryAdmin,$action)){http_response_code(403);throw new RuntimeException('Access denied.');}
  }
  if($user && !empty($user['password_must_change']) && !in_array($action,['password','logout'],true))throw new RuntimeException('Change your temporary password first.');
  if($action==='setup' && !$installed){
   $remote=$_SERVER['REMOTE_ADDR']??'';
   $local=in_array($remote,['127.0.0.1','::1'],true);
   if(!$local && ($config['setup_key']==='' || !hash_equals($config['setup_key'],field('setup_key')))) throw new RuntimeException('Invalid setup key.');
   $name=field('name');$username=field('username');$email=field('email');$password=field('password');
   if($name==='' || mb_strlen($name,'UTF-8')>100)throw new RuntimeException('Enter a name of up to 100 characters.');
   if(!security_username_ok($username))throw new RuntimeException('Use 3–30 letters, numbers, dots, hyphens, or underscores.');
   if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190)throw new RuntimeException('Enter a valid email, e.g. name@veridian.ph.');
   if(!security_password_ok($password))throw new RuntimeException('Passwords must be 8–16 characters.');
   if(!preg_match('/^[a-zA-Z0-9_]+$/D',$config['database'])) throw new RuntimeException('Use letters, digits, and underscores for the database name.');
   try { $pdo=db($config); }
   catch(PDOException $connection) {
    if((int)($connection->errorInfo[1]??0)!==1049) throw $connection;
    $server=db($config,false);
    $server->exec('CREATE DATABASE `'.$config['database'].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo=db($config);
   }
   $lock=$pdo->query("SELECT GET_LOCK('veridian_first_admin',10)")->fetchColumn();
   if((int)$lock!==1) throw new RuntimeException('Setup is busy. Try again.');
   try {
    $schema=file_get_contents(__DIR__.'/database/schema.sql');
    foreach(explode(';',$schema) as $sql) { $sql=trim(ltrim($sql,"\xEF\xBB\xBF"));if($sql!=='')$pdo->exec($sql); }
    if((int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn()>0) throw new RuntimeException('Setup has already been completed. Reload to sign in.');
    $q=$pdo->prepare("INSERT INTO users(name,username,email,password_hash,role) VALUES(?,?,?,?,'admin')");$q->execute([$name,$username,strtolower($email),password_hash($password,PASSWORD_DEFAULT)]);
   } finally { $pdo->query("SELECT RELEASE_LOCK('veridian_first_admin')"); }
   $_SESSION['notice']='Veridian is ready. Sign in with your administrator account.';go();
  }
  elseif($action==='register' && $installed && !$user){
   security_login_attempt($pdo,':employee-registration');
   $name=field('name');$username=field('username');$email=strtolower(field('email'));$password=field('password');
   if($name===''||mb_strlen($name,'UTF-8')>100)throw new RuntimeException('Enter a name of up to 100 characters.');
   if(!security_username_ok($username))throw new RuntimeException('Use 3–30 letters, numbers, dots, hyphens, or underscores.');
   if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190)throw new RuntimeException('Enter a valid email address.');
   if(!security_password_ok($password))throw new RuntimeException('Passwords must be 8–16 characters.');
   if(!hash_equals($password,field('confirm_password')))throw new RuntimeException('Passwords do not match.');
   [$jobTitle,$department]=personnel_validate(false);
   $q=$pdo->prepare("INSERT INTO users(name,username,email,password_hash,role,job_title,department) VALUES(?,?,?,?,'employee',?,?)");$q->execute([$name,$username,$email,password_hash($password,PASSWORD_DEFAULT),$jobTitle,$department]);
   $_SESSION['notice']='Account created. Sign in with your username.';go('index.php?auth=login');
  }
  elseif($action==='login' && $installed){
   $username=field('username');
   $bucket=security_login_attempt($pdo,strtolower(substr($username,0,30)));
   $q=$pdo->prepare('SELECT * FROM users WHERE username=?');$q->execute([substr($username,0,30)]);$account=$q->fetch();
   // Run a password check even for unknown users to reduce account-enumeration timing.
   $fallback='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
   $valid=password_verify(field('password'),$account['password_hash']??$fallback);
   if(!$account || !$valid || !security_username_ok($username)){throw new RuntimeException('Username or password is incorrect.');}
   $pdo->prepare('DELETE FROM login_throttles WHERE bucket_hash=?')->execute([$bucket]);
      if(field('remember_me')==='1'){auth_remember($pdo,$account);}else{auth_forget($pdo);}
   session_regenerate_id(true);$_SESSION['uid']=$account['id'];$_SESSION['password_version']=hash('sha256',$account['password_hash']);$_SESSION['attempts']=[];$_SESSION['csrf']=bin2hex(random_bytes(32));go();
  }
  elseif($action==='logout'){ auth_logout($pdo);go('index.php?auth=login'); }
  elseif(!$user) {http_response_code(401);throw new RuntimeException('Please sign in.');}
  elseif($action==='employee' && $user['role']==='admin'){
   $name=field('name');$username=field('username');$email=strtolower(field('email'));$password=field('password');
   if($name==='' || mb_strlen($name,'UTF-8')>100)throw new RuntimeException('Enter a name of up to 100 characters.');
   if(!security_username_ok($username))throw new RuntimeException('Use 3–30 letters, numbers, dots, hyphens, or underscores.');
   if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190)throw new RuntimeException('Enter a valid email, e.g. name@veridian.ph.');
   if(!security_password_ok($password))throw new RuntimeException('Passwords must be 8–16 characters.');
   [$jobTitle,$department]=personnel_validate(false);
   $q=$pdo->prepare("INSERT INTO users(name,username,email,password_hash,role,job_title,department) VALUES(?,?,?,?,'employee',?,?)");$q->execute([$name,$username,$email,password_hash($password,PASSWORD_DEFAULT),$jobTitle,$department]);$_SESSION['notice']='Employee account created.';go('index.php?view=accounts');
  }
  elseif($action==='delete_account' && access_primary($user,$primaryAdmin)){
   $target=security_request_id(field('user_id'));
   if($target===(int)$user['id'] || $target===$primaryAdmin)throw new RuntimeException('AdminA cannot be deleted.');
   $pdo->beginTransaction();
   $q=$pdo->prepare('SELECT id,username,role FROM users WHERE id=? FOR UPDATE');$q->execute([$target]);$account=$q->fetch();
   if(!$account)throw new RuntimeException('Account not found.');
   if(!hash_equals($account['username'],field('confirm_username')))throw new RuntimeException('Enter the exact username to confirm deletion.');
   // Keep decisions on other employees\' requests; remove only the deleted reviewer reference.
   $pdo->prepare('UPDATE leave_requests SET reviewed_by=NULL WHERE reviewed_by=?')->execute([$target]);
   $pdo->prepare('DELETE FROM leave_requests WHERE user_id=?')->execute([$target]);
   $pdo->prepare('DELETE FROM leave_allowances WHERE user_id=?')->execute([$target]);
   $pdo->prepare('DELETE FROM remember_tokens WHERE user_id=?')->execute([$target]);
   $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$target]);$pdo->commit();
   unset($_SESSION['reset_credential']);$_SESSION['notice']='Account deleted.';go('index.php?view=team');
  }
  elseif($action==='reset_password' && $user['role']==='admin' && (int)$user['id']===$primaryAdmin){
   $target=security_request_id(field('user_id'));$pdo->beginTransaction();$q=$pdo->prepare("SELECT id,username FROM users WHERE id=? AND role='employee' FOR UPDATE");$q->execute([$target]);$employee=$q->fetch();if(!$employee)throw new RuntimeException('Employee not found.');
   $temporary=bin2hex(random_bytes(6));$q=$pdo->prepare('UPDATE users SET password_hash=?,password_must_change=1 WHERE id=?');$q->execute([password_hash($temporary,PASSWORD_DEFAULT),$target]);$pdo->prepare('DELETE FROM remember_tokens WHERE user_id=?')->execute([$target]);$pdo->commit();
   $_SESSION['reset_credential']=['username'=>$employee['username'],'password'=>$temporary];go('index.php?view=team');
  }
  elseif($action==='allowance' && $user['role']==='admin'){
   $uid=security_request_id(field('user_id'));$year=filter_var(field('year'),FILTER_VALIDATE_INT,['options'=>['min_range'=>2020,'max_range'=>2100]]);if(!$year)throw new RuntimeException('Choose a valid year.');
   $values=[];foreach(['annual','sick','emergency'] as $key){$v=filter_var(field($key),FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>260]]);if($v===false)throw new RuntimeException('Allowances must be 0–260 days.');$values[]=$v;}
   $pdo->beginTransaction();$q=$pdo->prepare("SELECT id FROM users WHERE id=? AND role='employee' FOR UPDATE");$q->execute([$uid]);if(!$q->fetch())throw new RuntimeException('Employee not found.');
   $balance=leave_balance($pdo,$uid,$year);foreach(array_slice(array_values($balance),0,3) as $i=>$b)if($values[$i]<$b['approved']+$b['pending'])throw new RuntimeException('Allowance cannot be below committed days.');
   $q=$pdo->prepare('INSERT INTO leave_allowances(user_id,year,annual,sick,emergency) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE annual=VALUES(annual),sick=VALUES(sick),emergency=VALUES(emergency)');$q->execute([$uid,$year,...$values]);$pdo->commit();$_SESSION['notice']='Allowance updated.';go('index.php?view=balances&year='.$year);
  }
  elseif($action==='administrator' && access_primary($user,$primaryAdmin)){
   $name=field('name');$username=field('username');$email=strtolower(field('email'));$password=field('password');
   if($name===''||mb_strlen($name)>100||!security_username_ok($username)||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190||!security_password_ok($password))throw new RuntimeException('Check the account details. Passwords must be 8–16 characters.');
   [$jobTitle,$department]=personnel_validate(true);
   $q=$pdo->prepare("INSERT INTO users(name,username,email,password_hash,role,job_title,department) VALUES(?,?,?,?,'admin',?,?)");$q->execute([$name,$username,$email,password_hash($password,PASSWORD_DEFAULT),$jobTitle,$department]);$_SESSION['notice']='Administrator account created.';go('index.php?view=accounts');
  }
  elseif($action==='request' && $user['role']==='employee'){
   $start=field('start_date');$requestedDays=filter_var(field('leave_days'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>260]]);if($requestedDays===false)throw new RuntimeException('Enter 1–260 whole leave days.');$end=leave_end($start,$requestedDays);$type=field('leave_type');$reason=field('reason');if($type==='Fast' && $requestedDays!==1)throw new RuntimeException('Fast Leave covers one half-day per use.');
   if(!in_array($type,['Annual','Sick','Emergency','Personal','Fast'],true)||!validDate($start)||!validDate($end)||$end<$start||$start<date('Y-m-d')||(new DateTimeImmutable($start))->diff(new DateTimeImmutable($end))->days>=365||days($start,$end)===0||$reason===''||strlen($reason)>2000) throw new RuntimeException('Check your dates, leave type, and reason.');
   $pdo->beginTransaction();
   $q=$pdo->prepare('SELECT id FROM users WHERE id=? FOR UPDATE');$q->execute([$user['id']]);
   $q=$pdo->prepare("SELECT id FROM leave_requests WHERE user_id=? AND status IN ('Pending','Approved') AND start_date<=? AND end_date>=?");$q->execute([$user['id'],$end,$start]);
   if($q->fetch()) throw new RuntimeException('These dates overlap an existing request.');
   leave_check($pdo,(int)$user['id'],$type,$start,$end);
   $q=$pdo->prepare('INSERT INTO leave_requests(user_id,leave_type,start_date,end_date,reason) VALUES(?,?,?,?,?)');$q->execute([$user['id'],$type,$start,$end,$reason]);$pdo->commit();$_SESSION['notice']='Your leave request has been submitted.';go('index.php?view=requests');
  }
  elseif($action==='cancel' && $user['role']==='employee'){
   $q=$pdo->prepare("UPDATE leave_requests SET status='Cancelled' WHERE id=? AND user_id=? AND status='Pending'");$q->execute([security_request_id(field('id')),$user['id']]);if(!$q->rowCount())throw new RuntimeException('You can cancel only your pending requests.');$_SESSION['notice']='Request cancelled.';go('index.php?view=requests');
  }
  elseif($action==='review' && $user['role']==='admin'){
   $status=field('status');$remarks=field('remarks');
   if(!in_array($status,['Approved','Rejected'],true)||strlen($remarks)>1000)throw new RuntimeException('Choose a decision. Keep remarks under 1,000 characters.');
   $id=security_request_id(field('id'));$q=$pdo->prepare('SELECT user_id FROM leave_requests WHERE id=?');$q->execute([$id]);$owner=$q->fetchColumn();if(!$owner)throw new RuntimeException('Request not found.');
   $pdo->beginTransaction();$q=$pdo->prepare('SELECT id FROM users WHERE id=? FOR UPDATE');$q->execute([$owner]);$q=$pdo->prepare('SELECT * FROM leave_requests WHERE id=? FOR UPDATE');$q->execute([$id]);$review=$q->fetch();if($review['status']!=='Pending')throw new RuntimeException('This request is no longer pending.');
   if($status==='Approved')leave_check($pdo,(int)$owner,$review['leave_type'],$review['start_date'],$review['end_date'],true);
   $q=$pdo->prepare("UPDATE leave_requests SET status=?,remarks=?,reviewed_by=?,reviewed_at=NOW() WHERE id=? AND status='Pending'");$q->execute([$status,$remarks,$user['id'],security_request_id(field('id'))]);if(!$q->rowCount())throw new RuntimeException('This request is no longer pending.');$pdo->commit();$_SESSION['notice']='Leave request '.strtolower($status).'.';go('index.php?view=requests');
  }
  elseif($action==='username' && $user['role']==='employee'){
   $username=field('new_username');
   if(!security_username_ok($username))throw new RuntimeException('Use 3–30 letters, numbers, dots, hyphens, or underscores.');
   $q=$pdo->prepare('SELECT password_hash FROM users WHERE id=?');$q->execute([$user['id']]);
   if(!password_verify(field('current_password'),$q->fetchColumn()))throw new RuntimeException('Current password is incorrect.');
   $q=$pdo->prepare('SELECT id FROM users WHERE username=? AND id<>?');$q->execute([$username,$user['id']]);
   if($q->fetch())throw new RuntimeException('Username is already in use.');
   if($username===$user['username'])throw new RuntimeException('Choose a different username.');
   $pdo->prepare('UPDATE users SET username=? WHERE id=?')->execute([$username,$user['id']]);
   $_SESSION['notice']='Username updated. Use it next time you sign in.';go('index.php?view=settings');
  }
  elseif($action==='work_details'){
   [$jobTitle,$department]=personnel_validate($user['role']==='admin');
   $pdo->prepare('UPDATE users SET job_title=?,department=? WHERE id=?')->execute([$jobTitle,$department,$user['id']]);$_SESSION['notice']='Work details updated.';go('index.php?view=settings');
  }
  elseif($action==='profile_picture'){
   $upload=$_FILES['picture']??[];if(!is_array($upload))throw new RuntimeException('Choose a picture.');
   [$data,$mime]=profile_upload($upload);
   $q=$pdo->prepare('UPDATE users SET avatar_data=?,avatar_mime=?,avatar_version=avatar_version+1 WHERE id=?');$q->execute([$data,$mime,$user['id']]);
   $_SESSION['notice']='Profile picture updated.';go('index.php?view=settings');
  }
  elseif($action==='remove_picture'){
   $q=$pdo->prepare('UPDATE users SET avatar_data=NULL,avatar_mime=NULL,avatar_version=avatar_version+1 WHERE id=?');$q->execute([$user['id']]);
   $_SESSION['notice']='Default picture restored.';go('index.php?view=settings');
  }
  elseif($action==='password'){
   $q=$pdo->prepare('SELECT password_hash FROM users WHERE id=?');$q->execute([$user['id']]);$new=field('new_password');
   $currentHash=$q->fetchColumn();if(!empty($user['password_must_change']) && password_verify($new,$currentHash))throw new RuntimeException('Choose a different password.');
   if(!password_verify(field('current_password'),$currentHash)||!security_password_ok($new))throw new RuntimeException('Check your current password. New passwords must be 8–16 characters.');
   $newHash=password_hash($new,PASSWORD_DEFAULT);$q=$pdo->prepare('UPDATE users SET password_hash=?,password_must_change=0 WHERE id=?');$q->execute([$newHash,$user['id']]);$_SESSION['password_version']=hash('sha256',$newHash);$pdo->prepare('DELETE FROM remember_tokens WHERE user_id=?')->execute([$user['id']]);auth_forget($pdo);session_regenerate_id(true);$_SESSION['notice']='Password updated.';go('index.php?view=settings');
  }
  else {http_response_code(403);throw new RuntimeException('Access denied.');}
 } catch(Throwable $ex) { if($pdo && $pdo->inTransaction())$pdo->rollBack();if($ex instanceof PDOException){error_log($ex->getMessage());$error=$ex->getCode()==='23000'?'Username or email is already in use.':'Unable to save. Try again shortly.';}else{$error=$ex->getMessage();} }
}
$view=$_GET['view']??'overview';if(!is_string($view)||!in_array($view,['overview','requests','approved','balances','calendar','new','team','accounts','settings'],true))$view='overview';
if($user && !empty($user['password_must_change']))$view='settings';
$admin=$user && $user['role']==='admin';
$viewLabel=['overview'=>'Overview','requests'=>$admin?'Leave Approvals':'My Requests','approved'=>'Approved Leaves','balances'=>'Leave Balances','calendar'=>'Leave Calendar','new'=>'Request Leave','team'=>'Employees','accounts'=>'Create Accounts','settings'=>'Account Settings'][$view];
if($user && ((!$admin && in_array($view,['team','accounts'],true)) || ($admin && $view==='new')))http_response_code(403);
$requests=[];$counts=['Pending'=>0,'Approved'=>0,'Rejected'=>0,'Cancelled'=>0];$employees=[];
if($user){
 $q=$pdo->prepare('SELECT r.*,u.name,u.email,a.name AS reviewer FROM leave_requests r JOIN users u ON r.user_id=u.id LEFT JOIN users a ON r.reviewed_by=a.id'.($admin?'':' WHERE r.user_id=?').' ORDER BY r.created_at DESC,r.id DESC');$q->execute($admin?[]:[$user['id']]);$requests=$q->fetchAll();foreach($requests as $r)$counts[$r['status']]++;
 if($admin)$employees=$pdo->query("SELECT id,name,username,email,created_at,avatar_version,job_title,department FROM users WHERE role='employee' ORDER BY name")->fetchAll();
}
$register=$installed && ($_GET['auth']??'')==='register';
if($user)$_SESSION['document']=bin2hex(random_bytes(24));
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Veridian · <?= $user?e($viewLabel):'Welcome' ?></title><link rel="stylesheet" href="assets/css/app.css?v=company-portal"><link rel="stylesheet" href="assets/css/interactions.css?v=green-motion"><link rel="stylesheet" href="assets/css/portal.css?v=username-settings-1"><script src="assets/js/interactions.js?v=green-motion" defer></script><script src="assets/js/session-navigation.js?v=3" defer></script><script src="assets/js/leave-planner.js?v=3" defer></script><script src="assets/js/account-deletion.js?v=1" defer></script><script src="assets/js/account-recovery.js?v=1" defer></script><script src="assets/js/personnel.js?v=1" defer></script></head><body<?php if($user): ?> data-session-document="<?= e($_SESSION['document']) ?>" data-session-csrf="<?= e($_SESSION['csrf']) ?>" data-session-mode="<?= empty($_SESSION['remembered'])?'ordinary':'remembered' ?>"<?php endif ?>>
<?php if(!$user): ?>
<main class="auth-layout"><section class="auth-story"><a href="landing.html" class="brand">✳ veridian.</a><div><p class="eyebrow">PEOPLE FIRST. PAPERWORK SECOND.</p><h1>Good work.<br><em>Well-earned<br>time off.</em></h1><p>A little less admin. A little more room for life.<br>Veridian’s employee leave portal.</p></div><small>Clear requests. Thoughtful decisions. Better workdays.</small></section><section class="auth-panel"><div class="auth-form"><p class="eyebrow">A BETTER BALANCE STARTS HERE</p><h2><?= $installed?($register?'Create Employee Account':'Welcome back.'):'Let’s set up Veridian.' ?></h2><p class="muted"><?= $installed?($register?'Register to request and track your leave.':'Sign in to Veridian’s employee portal.'):'Create the first administrator account to get started.' ?></p>
<?php if($error):?><div class="alert error" role="alert"><?= e($error) ?></div><?php endif ?><?php if($notice):?><div class="alert" role="status"><?= e($notice) ?></div><?php endif ?>
<?php if($connectionError):?><div class="alert error" role="alert">Workspace unavailable. Try again shortly.</div><?php endif ?><?php if(!$connectionError):?><?php if(!$installed):?><div class="info">Create your administrator account to begin.</div><?php endif ?>
<?php if($register):include __DIR__.'/views/register.php';else:?>
<form method="post">
 <?= csrf() ?><input type="hidden" name="action" value="<?= $installed?'login':'setup' ?>">
 <?php if(!$installed):?>
 <label>Full Name<input name="name" maxlength="100" autocomplete="name" aria-describedby="setup-name-hint" required></label><small class="field-hint" id="setup-name-hint">Enter your full name.</small>
 <?php endif ?>
 <label>Username<input name="username" type="text" <?= $installed?'':'minlength="3" pattern="[A-Za-z0-9_.\-]{3,30}"' ?> maxlength="30" autocomplete="username" autocapitalize="none" spellcheck="false" aria-describedby="username-hint" required></label>
 <small class="field-hint" id="username-hint"><?= $installed?'Use your username.':'3–30 letters, numbers, dots, hyphens, or underscores.' ?></small>
 <?php if(!$installed):?>
 <label>Email Address<input name="email" type="email" maxlength="190" autocomplete="email" aria-describedby="setup-email-hint" required></label><small class="field-hint" id="setup-email-hint">Include @ and a domain, e.g. name@veridian.ph.</small>
 <?php endif ?>
 <label>Password<input name="password" type="password" <?= $installed?'maxlength="72"':'minlength="8" maxlength="16"' ?> autocomplete="<?= $installed?'current-password':'new-password' ?>" aria-describedby="password-hint" required></label>
 <small class="field-hint" id="password-hint"><?= $installed?'Enter your account password.':'8–16 characters. Special characters are optional.' ?></small>
 <?php if(!$installed && !in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true)):?><label>Setup Key<input name="setup_key" type="password" aria-describedby="setup-key-hint" required></label><small class="field-hint" id="setup-key-hint">Use the configured setup key.</small><?php endif ?>
 <?php if($installed):?><label class="remember-option"><input type="checkbox" name="remember_me" value="1"><span>Remember Me <small>Stay signed in for 30 days.</small></span></label><p class="session-help">Without Remember Me, returning home signs you out.</p><?php endif ?>
 <button class="button full"><?= $installed?'Sign In →':'Create Administrator Account →' ?></button></form><p class="muted footnote"><?= $installed?'Use your username to sign in.':'Use a strong, unique password.' ?></p><?php if($installed):?><p class="signup-link">New employee? <a class="text-link" href="index.php?auth=register">Create Employee Account</a></p><?php endif ?><?php endif ?><?php endif ?><a class="text-link" href="landing.html">← Explore Veridian</a></div></section></main>
<?php else: ?>
<div class="app-layout"><aside class="app-sidebar"><a class="brand" href="index.php">✳ veridian.</a><p class="portal-identity">EMPLOYEE PORTAL</p><p class="nav-label"><?= $admin?'ADMIN WORKSPACE':'EMPLOYEE WORKSPACE' ?></p><button class="menu-toggle" type="button" data-menu-target="workspace-navigation" aria-controls="workspace-navigation" aria-expanded="false" aria-label="Open Navigation"><span class="menu-symbol" aria-hidden="true"></span>Menu</button><nav id="workspace-navigation" aria-label="Workspace navigation"><?php foreach(['overview'=>'Overview','requests'=>$admin?'Leave Approvals':'My Requests','approved'=>'Approved Leaves','balances'=>'Leave Balances','calendar'=>'Leave Calendar',...($admin?['team'=>'Employees','accounts'=>'Create Accounts']:['new'=>'Request Leave']),'settings'=>'Account Settings'] as $key=>$label):?><a class="<?= $view===$key?'active':'' ?>" href="index.php?view=<?= $key ?>"><?= e($label) ?><span>↗</span></a><?php endforeach ?></nav><div class="sidebar-bottom"><div class="user-card"><img class="avatar profile-avatar" src="<?= e(profile_url($user)) ?>" alt="<?= e($user['name']) ?> profile picture" width="36" height="36"><div><b><?= e($user['name']) ?></b><small><?= $admin?'Administrator':'Employee' ?></small></div></div><form method="post"><?= csrf() ?><input type="hidden" name="action" value="logout"><button class="signout">Sign Out ↗</button></form></div></aside>
<main class="app-main"><header class="app-top"><span>Veridian Portal / <?= e($viewLabel) ?></span><span><?= e(date('D, M j, Y')) ?></span></header><?php if($notice):?><div class="alert" role="status"><?= e($notice) ?></div><?php endif ?><?php if($error):?><div class="alert error" role="alert"><?= e($error) ?></div><?php endif ?>
<?php if($view==='overview'):?>
<section class="welcome"><div><p class="eyebrow">A LITTLE MORE ROOM FOR LIFE</p><h1>Hello, <?= e(explode(' ',$user['name'])[0]) ?>.</h1><p><?= $admin?'Review and manage employee leave.':'Your leave requests at a glance.' ?></p></div><a class="button" href="index.php?view=<?= $admin?'requests':'new' ?>"><?= $admin?'Review Requests':'＋ Request Leave' ?> ↗</a></section><?php include __DIR__.'/views/balance-summary.php'; ?>
<div class="stat-grid"><?php foreach(['Pending'=>'Awaiting Review','Approved'=>'Approved Requests','Rejected'=>'Rejected Requests','Cancelled'=>'Cancelled Requests'] as $status=>$label):?><article class="stat"><span><?= e($label) ?></span><strong><?= $counts[$status] ?></strong><div class="stat-line <?= strtolower($status) ?>"></div></article><?php endforeach ?></div>
<section class="panel"><div class="panel-heading"><div><p class="eyebrow">KEEP EVERYONE IN SYNC</p><h2>Recent Activity</h2></div><a class="text-link" href="index.php?view=requests">View All Requests ↗</a></div><?php $shown=array_slice($requests,0,5); include __DIR__.'/views/table.php'; ?></section><section class="gentle-banner"><span>✳</span><div><h3>Good planning makes space for people.</h3><p>Weekdays count. Pending requests reserve your balance.</p></div></section>
<?php elseif($view==='new' && !$admin):?>
<div class="page-heading"><p class="eyebrow">MAKE SPACE FOR YOUR NEXT BREAK</p><h1>Request Time Off</h1><p class="muted">Choose your dates and add a reason.</p></div><?php include __DIR__.'/views/balance-summary.php'; ?><section class="panel form-panel"><form method="post" data-leave-planner><?= csrf() ?><input type="hidden" name="action" value="request"><label>Leave Type<select name="leave_type" aria-label="Leave Type" required><option value="">Choose Leave Type</option><?php foreach(['Annual','Sick','Emergency','Personal','Fast'] as $type):?><option value="<?= e($type) ?>" <?= field('leave_type')===$type?'selected':'' ?>><?= e(leave_label($type)) ?></option><?php endforeach ?></select></label><label><span data-duration-label>Number of Leave Days</span><input type="number" name="leave_days" min="1" max="260" step="1" value="<?= e(field('leave_days')) ?>" aria-describedby="leave-days-hint" required></label><small class="field-hint" id="leave-days-hint">Whole weekdays. Subject to your available balance.</small><div class="form-grid"><label>Start Date<input type="date" name="start_date" min="<?= date('Y-m-d') ?>" value="<?= e(field('start_date')) ?>" aria-describedby="planner-message" required></label><label>Calculated End Date<input type="date" name="end_date" readonly aria-describedby="planner-message"></label></div><p class="info" id="planner-message" data-planner-message role="status" aria-live="polite">Choose days and a start date.</p><noscript><p class="info">The end date will be calculated when you submit.</p></noscript><label>Reason<textarea name="reason" rows="5" maxlength="2000" placeholder="Brief reason. Omit sensitive details." required><?= e(field('reason')) ?></textarea></label><p class="info">Monday–Friday only. Holidays count. No overlaps.</p><button class="button">Submit Request ↗</button></form></section>
<?php elseif($view==='calendar'): include __DIR__.'/views/calendar.php'; ?>
<?php elseif($view==='approved'): include __DIR__.'/views/approved.php'; ?>
<?php elseif($view==='balances'): include __DIR__.'/views/balances.php'; ?>
<?php elseif($view==='requests'):?>
<div class="page-heading"><p class="eyebrow">CLARITY AT EVERY STEP</p><h1><?= $admin?'Leave Approvals':'My Request History' ?></h1><p class="muted"><?= $admin?'Approve or reject pending requests.':'View requests, decisions, and remarks.' ?></p></div><div class="filter-links"><a href="index.php?view=requests&amp;status=All">All (<?= count($requests) ?>)</a><?php foreach($counts as $status=>$count):?><a href="index.php?view=requests&amp;status=<?= $status ?>"><?= e($status) ?> (<?= $count ?>)</a><?php endforeach ?></div><section class="panel"><?php $filter=query_text('status',$admin?'Pending':'');$shown=array_values(array_filter($requests,fn($r)=>!is_string($filter)||!isset($counts[$filter])||$r['status']===$filter));include __DIR__.'/views/table.php'; ?></section>
<?php elseif($view==='team' && $admin):?>
<div class="page-heading"><p class="eyebrow">YOUR PEOPLE, CONNECTED</p><h1>Employees</h1><p class="muted">Share passwords privately. Employees should change them after signing in.</p></div><div class="page-actions"><a class="button" href="index.php?view=accounts">Create Account ↗</a></div><?php include __DIR__.'/views/employees.php'; ?>
<?php elseif($view==='accounts' && $admin): include __DIR__.'/views/create-accounts.php'; ?>
<?php elseif($view==='settings'):?>
<?php if(!empty($user['password_must_change'])):?><div class="alert" role="status">Change your temporary password to continue.</div><?php endif ?>
<div class="page-heading"><p class="eyebrow">YOUR WORKSPACE, YOUR ACCOUNT</p><h1>Account Settings</h1><p class="muted"><?= e($user['email']) ?> · <?= $admin?'Administrator':'Employee' ?></p></div><?php if(empty($user['password_must_change'])):?><?php if(!$admin):?><section class="panel form-panel"><h2>Change Username</h2><p class="muted">Current username: <strong><?= e($user['username']) ?></strong></p><form method="post"><?= csrf() ?><input type="hidden" name="action" value="username"><label>New Username<input name="new_username" minlength="3" maxlength="30" pattern="[A-Za-z0-9_.\-]{3,30}" autocomplete="username" autocapitalize="none" spellcheck="false" value="<?= e(field('action')==='username'?field('new_username'):'') ?>" aria-describedby="change-username-hint" required></label><small class="field-hint" id="change-username-hint">3–30 letters, numbers, dots, hyphens, or underscores.</small><label>Current Password<input type="password" name="current_password" autocomplete="current-password" required></label><button class="button">Update Username</button></form></section><?php endif ?><section class="panel form-panel"><h2>Work Details</h2><p class="muted">Your job role and department. Account access stays unchanged.</p><form method="post"><?= csrf() ?><input type="hidden" name="action" value="work_details"><?php $personnelKind=$admin;$personnelValues=$user;include __DIR__.'/views/personnel-fields.php'; ?><button class="button">Save Work Details</button></form></section><section class="panel form-panel profile-panel"><div class="profile-settings-top"><img class="profile-picture-large" src="<?= e(profile_url($user)) ?>" alt="Your profile picture" width="96" height="96"><div><h2>Profile Picture</h2><p class="muted">Choose a picture for your account.</p></div></div><form method="post" enctype="multipart/form-data"><?= csrf() ?><input type="hidden" name="action" value="profile_picture"><label>Choose Picture<input type="file" name="picture" accept="image/jpeg,image/png,image/webp" aria-describedby="picture-hint" required></label><small class="field-hint" id="picture-hint">JPG, PNG, or WebP. Up to 500 KB.</small><button class="button">Save Picture ↗</button></form><form method="post" class="restore-picture-form"><?= csrf() ?><input type="hidden" name="action" value="remove_picture"><button class="cancel">Use Default Picture</button></form></section><?php endif ?><section class="panel form-panel"><h2>Change Password</h2><form method="post"><?= csrf() ?><input type="hidden" name="action" value="password"><label>Current Password<input type="password" name="current_password" autocomplete="current-password" required></label><label>New Password<input type="password" name="new_password" autocomplete="new-password" minlength="8" maxlength="16" aria-describedby="new-password-hint" required></label><small class="field-hint" id="new-password-hint">8–16 characters. Special characters are optional.</small><button class="button">Update Password ↗</button></form></section>
<?php else: ?><div class="panel"><h2>Access denied.</h2><a href="index.php">Return to overview</a></div><?php endif ?>
<footer class="app-footer"><span>© <?= date('Y') ?> Veridian Employee Portal · Leave Management</span>
<span>Made for better workdays. <b>●</b></span></footer></main></div>
<?php endif ?></body></html>
