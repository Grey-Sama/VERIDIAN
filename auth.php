<?php
declare(strict_types=1);
require_once __DIR__.'/security.php';
security_bootstrap();
function auth_cookie_path(): string {
 $dir=str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/index.php'));
 return rtrim($dir,'/').'/';
}
function auth_secure(): bool { return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off'; }
function auth_bootstrap(): void {
 if(session_status()!==PHP_SESSION_ACTIVE){
  ini_set('session.use_strict_mode','1');
  ini_set('session.use_only_cookies','1');
  ini_set('session.use_trans_sid','0');
  session_name('veridian_session');
  session_set_cookie_params(['lifetime'=>0,'path'=>auth_cookie_path(),'httponly'=>true,'secure'=>auth_secure(),'samesite'=>'Lax']);
  session_start();
 }
 header('Cache-Control: no-store, no-cache, must-revalidate, private');
 header('Pragma: no-cache');header('Expires: 0');
 $_SESSION['csrf']??=bin2hex(random_bytes(32));
}
function auth_cookie(string $value,int $expires): void {
 setcookie('veridian_remember',$value,['expires'=>$expires,'path'=>auth_cookie_path(),'secure'=>auth_secure(),'httponly'=>true,'samesite'=>'Lax']);
 if($value==='')unset($_COOKIE['veridian_remember']);else $_COOKIE['veridian_remember']=$value;
}
function auth_forget(?PDO $pdo): void {
 $token=$_COOKIE['veridian_remember']??'';
 if($pdo && is_string($token) && preg_match('/^[a-f0-9]{64}$/D',$token)){
  try{$q=$pdo->prepare('DELETE FROM remember_tokens WHERE token_hash=?');$q->execute([hash('sha256',$token)]);}catch(PDOException $e){error_log('Remember-token revocation failed: '.$e->getMessage());}
 }
 auth_cookie('',time()-3600);
 unset($_SESSION['remembered'],$_SESSION['remember_expires']);
}
function auth_logout(?PDO $pdo): void {
 auth_forget($pdo);$_SESSION=[];session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));
}
function auth_restore(PDO $pdo): bool {
 $raw=$_COOKIE['veridian_remember']??'';
 if(!is_string($raw)||!preg_match('/^[a-f0-9]{64}$/D',$raw)){
  if(isset($_SESSION['remembered']))auth_logout($pdo);
  return false;
 }
 $q=$pdo->prepare('SELECT t.user_id,t.password_version,t.expires_at,u.password_hash FROM remember_tokens t JOIN users u ON u.id=t.user_id WHERE t.token_hash=?');
 $q->execute([hash('sha256',$raw)]);$token=$q->fetch(PDO::FETCH_ASSOC);
 if(!$token || (int)$token['expires_at']<=time() || !hash_equals($token['password_version'],hash('sha256',$token['password_hash']))){
  // A stale device cookie must not reset a valid ordinary session or its CSRF token.
  if(!empty($_SESSION['remembered']))auth_logout($pdo);else auth_forget($pdo);
  return false;
 }
 if(!isset($_SESSION['uid']) || (int)$_SESSION['uid']!==(int)$token['user_id']){session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));}
 $_SESSION['uid']=(int)$token['user_id'];$_SESSION['password_version']=hash('sha256',$token['password_hash']);$_SESSION['remembered']=true;$_SESSION['remember_expires']=(int)$token['expires_at'];return true;
}
function auth_remember(PDO $pdo,array $account): void {
 auth_forget($pdo);
 $raw=bin2hex(random_bytes(32));$expires=time()+30*24*60*60;
 $q=$pdo->prepare('INSERT INTO remember_tokens(token_hash,user_id,password_version,expires_at) VALUES(?,?,?,?)');
 $q->execute([hash('sha256',$raw),$account['id'],hash('sha256',$account['password_hash']),$expires]);
 auth_cookie($raw,$expires);$_SESSION['remembered']=true;$_SESSION['remember_expires']=$expires;
 $pdo->prepare('DELETE FROM remember_tokens WHERE expires_at<=?')->execute([time()]);
}
function auth_resume(?PDO $pdo): void {
 if(empty($_SESSION['remembered']) && isset($_SESSION['closed_at']) && time()-(int)$_SESSION['closed_at']>=5)auth_logout($pdo);
 unset($_SESSION['closed_at']);
}
