<?php
declare(strict_types=1);
require __DIR__.'/auth.php';auth_bootstrap();
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);header('Allow: POST');exit;}
$csrf=$_POST['csrf']??'';$document=$_POST['document']??'';
if(!is_string($csrf)||!is_string($document)||!hash_equals($_SESSION['csrf']??'', $csrf)){http_response_code(403);exit;}
// Ignore late unload messages from a page replaced by a newer workspace page.
if(isset($_SESSION['uid']) && empty($_SESSION['remembered']) && isset($_SESSION['document']) && hash_equals($_SESSION['document'],$document))$_SESSION['closed_at']=time();
http_response_code(204);
