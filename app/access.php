<?php
declare(strict_types=1);
function access_primary(array $user,int $primary): bool {
 return $user['role']==='admin'&&(int)$user['id']===$primary;
}
function access_action(array $user,int $primary,string $action): bool {
 if(!in_array($user['role'],['employee','admin'],true))return false;
 return match($action){
  'administrator','reset_password','delete_account'=>access_primary($user,$primary),
  'employee','allowance','review'=>$user['role']==='admin',
  'request','cancel','username'=>$user['role']==='employee',
  'work_details','password','profile_picture','remove_picture'=>true,
  default=>false,
 };
}
