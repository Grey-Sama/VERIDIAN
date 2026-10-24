<?php
declare(strict_types=1);
function security_bootstrap(): void {
 ini_set('display_errors','0');
 ini_set('display_startup_errors','0');
 set_exception_handler(function(Throwable $error): void {
  error_log((string)$error);
  http_response_code(503);
  header('Content-Type: text/html; charset=UTF-8');
  header('Cache-Control: no-store');
  echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Veridian</title><h1>Temporarily unavailable</h1><p>Please try again shortly.</p></html>';
 });
 header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
 header('X-Content-Type-Options: nosniff');
 header('X-Frame-Options: DENY');
 header('Referrer-Policy: same-origin');
 header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}
function security_password_ok(string $password): bool {
 $length=mb_strlen($password,'UTF-8');return $length>=8 && $length<=16;
}
function security_login_attempt(PDO $pdo,string $username): string {
 // Key limits by IP and account/IP, so a fresh session cannot reset attempts.
 // Do not trust client-controlled forwarding headers.
 $ip=$_SERVER['REMOTE_ADDR']??'unknown';
 $accountKey=hash('sha256','account|'.$ip.'|'.strtolower($username));
 $buckets=[hash('sha256','ip|'.$ip)=>100,$accountKey=>5];
 $now=time();$cutoff=$now-300;
 $pdo->beginTransaction();
 try {
  foreach($buckets as $key=>$limit){
   $q=$pdo->prepare('INSERT INTO login_throttles(bucket_hash,attempts,window_start) VALUES(?,1,?) ON DUPLICATE KEY UPDATE attempts=IF(window_start<=?,1,attempts+1),window_start=IF(window_start<=?,VALUES(window_start),window_start)');
   $q->execute([$key,$now,$cutoff,$cutoff]);
   $q=$pdo->prepare('SELECT attempts FROM login_throttles WHERE bucket_hash=? FOR UPDATE');$q->execute([$key]);
   if((int)$q->fetchColumn()>$limit){$pdo->commit();http_response_code(429);header('Retry-After: 300');throw new RuntimeException('Too many attempts. Try again in 5 minutes.');}
  }
  $pdo->commit();
 } catch(Throwable $error){if($pdo->inTransaction())$pdo->rollBack();throw $error;}
 $pdo->prepare('DELETE FROM login_throttles WHERE window_start<?')->execute([$now-86400]);
 return $accountKey;
}
function security_request_id(string $value): int {
 $id=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
 if($id===false){http_response_code(400);throw new RuntimeException('Invalid request.');}
 return $id;
}

function security_username_ok(string $username): bool { return preg_match('/^[A-Za-z0-9_.-]{3,30}$/D',$username)===1; }
