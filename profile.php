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
function profile_url(array $user): string {return 'avatar.php?id='.(int)$user['id'].'&v='.(int)($user['avatar_version']??0);}
function profile_default_svg(int $id): string {
 $palettes=[['#e3ebd9','#b77952','#273a2e','#436e50'],['#e6dfed','#e9b896','#302a39','#76688e'],['#dae7e9','#c89473','#3c2b26','#407f82'],['#f0e3d4','#f3c7a4','#4a3b2a','#ab8754']];
 $seed=hash('sha256','veridian-profile-'.$id);$bg=$palettes[hexdec(substr($seed,0,2))%4][0];$skin=$palettes[hexdec(substr($seed,2,2))%4][1];$hair=$palettes[hexdec(substr($seed,4,2))%4][2];$shirt=$palettes[hexdec(substr($seed,6,2))%4][3];$style=hexdec(substr($seed,8,2))%4;
 $hairShape=$style===1?'<path d="M58 100V74a42 42 0 0184 0v68H58z" fill="'.$hair.'"/>':'<path d="M64 85V69c0-39 72-47 76 1v18H64z" fill="'.$hair.'"/>';
 $glasses=$style===0||$style===3?'<g fill="none" stroke="'.$hair.'" stroke-width="3"><rect x="77" y="82" width="20" height="15" rx="6"/><rect x="103" y="82" width="20" height="15" rx="6"/><path d="M97 88h6"/></g>':'';
 return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><rect width="200" height="200" rx="100" fill="'.$bg.'"/><circle cx="157" cy="43" r="16" fill="#fff" opacity=".35"/>'.$hairShape.'<path d="M35 200v-23c0-34 28-51 65-51s65 17 65 51v23" fill="'.$shirt.'"/><path d="M88 115h24v27c-8 10-16 10-24 0z" fill="'.$skin.'"/><ellipse cx="100" cy="88" rx="34" ry="41" fill="'.$skin.'"/><path d="M66 77c-1-39 65-42 69 1-23-1-29-19-32-22-8 13-22 20-37 21" fill="'.$hair.'"/><g fill="'.$hair.'"><circle cx="87" cy="88" r="2.5"/><circle cx="113" cy="88" r="2.5"/></g><path d="M91 108q9 7 18 0" fill="none" stroke="#975e48" stroke-width="2.5" stroke-linecap="round"/>'.$glasses.'<path d="M80 137l20 18 20-18" fill="none" stroke="#fff" opacity=".55" stroke-width="4"/></svg>';
}
