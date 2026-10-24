<?php
declare(strict_types=1);
require __DIR__.'/app/auth.php';require __DIR__.'/app/profile.php';auth_bootstrap();
function avatar_denied(int $status): never {http_response_code($status);header('Content-Type: text/plain; charset=UTF-8');exit('Picture unavailable.');}
$c=require __DIR__.'/config/config.php';
$p=new PDO('mysql:host='.$c['host'].';port='.$c['port'].';dbname='.$c['database'].';charset=utf8mb4',$c['username'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>5]);
auth_resume($p);auth_restore($p);
if(!isset($_SESSION['uid']))avatar_denied(401);
$q=$p->prepare('SELECT id,role,password_hash FROM users WHERE id=?');$q->execute([$_SESSION['uid']]);$viewer=$q->fetch();
if(!$viewer||!isset($_SESSION['password_version'])||!hash_equals($_SESSION['password_version'],hash('sha256',$viewer['password_hash']))){auth_logout($p);avatar_denied(401);}
$id=filter_var($_GET['id']??$viewer['id'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if($id===false)avatar_denied(400);
if($viewer['role']!=='admin'&&(int)$id!==(int)$viewer['id'])avatar_denied(403);
$q=$p->prepare('SELECT id,avatar_data,avatar_mime FROM users WHERE id=?');$q->execute([$id]);$owner=$q->fetch();if(!$owner)avatar_denied(404);
header("Content-Security-Policy: default-src 'none'; sandbox");header('X-Content-Type-Options: nosniff');session_write_close();
if($owner['avatar_data']!==null && in_array($owner['avatar_mime'],['image/jpeg','image/png','image/webp'],true)){
 header('Content-Type: '.$owner['avatar_mime']);header('Content-Disposition: inline; filename="profile-picture"');echo $owner['avatar_data'];
}else{header('Content-Type: image/svg+xml; charset=UTF-8');header('Content-Disposition: inline; filename="profile-picture.svg"');echo profile_default_svg((int)$owner['id']);}
