<?php
declare(strict_types=1);
function profile_upload(array $upload): array {
 if(($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new RuntimeException('Choose a picture up to 500 KB.');
 $path=$upload['tmp_name']??'';
 if(!is_string($path)||!is_uploaded_file($path))throw new RuntimeException('Invalid upload.');
 $bytes=filesize($path);
 if($bytes===false||$bytes<1||$bytes>500*1024)throw new RuntimeException('Picture must be under 500 KB.');
 $info=@getimagesize($path);$mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);
 $types=['image/jpeg'=>IMAGETYPE_JPEG,'image/png'=>IMAGETYPE_PNG,'image/webp'=>IMAGETYPE_WEBP];
 if(!$info||!isset($types[$mime])||$info[2]!==$types[$mime]||($info['mime']??'')!==$mime)throw new RuntimeException('Use a JPG, PNG, or WebP picture.');
 if($info[0]>4096||$info[1]>4096||$info[0]<1||$info[1]<1)throw new RuntimeException('Use a picture up to 4096 × 4096 pixels.');
 $data=file_get_contents($path);if($data===false)throw new RuntimeException('Unable to read the picture.');
 return [$data,$mime];
}
function profile_url(array $user): string {return 'avatar.php?id='.(int)$user['id'].'&v='.(int)($user['avatar_version']??0).'&default=neutral-1';}
function profile_default_svg(int $id): string {
 // Every account starts with the same neutral silhouette; only an owner upload replaces it.
 return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><rect width="200" height="200" rx="100" fill="#e5eee3"/><circle cx="100" cy="75" r="32" fill="#52765c"/><path d="M40 171c0-35 27-57 60-57s60 22 60 57v9H40z" fill="#52765c"/></svg>';
}
