<?php
declare(strict_types=1);
require __DIR__.'/app/auth.php';
auth_bootstrap();
header('X-Content-Type-Options: nosniff');header('X-Frame-Options: DENY');
$remembered=false;
if(isset($_COOKIE['veridian_remember'])){
 try{
  $c=require __DIR__.'/config/config.php';
  $p=new PDO('mysql:host='.$c['host'].';port='.$c['port'].';dbname='.$c['database'].';charset=utf8mb4',$c['username'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>5]);
  $remembered=auth_restore($p);
 }catch(PDOException $error){error_log('Landing authentication check failed: '.$error->getMessage());auth_logout(null);}
}
if(!$remembered && isset($_SESSION['uid']))auth_logout(null);
readfile(__DIR__.'/views/landing.html');
