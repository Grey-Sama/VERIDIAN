<?php
declare(strict_types=1);
function access_primary(array $user,int $primary): bool {
 return $user['role']==='admin'&&(int)$user['id']===$primary;
}
function access_action(array $user,int $primary,string $action): bool {
 if(!in_array($user['role'],['employee','admin'],true))return false;
 switch($action){
  case 'administrator':
  case 'reset_password':
  case 'delete_account':
   return access_primary($user,$primary);
  case 'employee':
  case 'employment_type':
  case 'review':
   return $user['role']==='admin';
  case 'request':
  case 'cancel':
  case 'username':
   return $user['role']==='employee';
  case 'work_details':
  case 'password':
  case 'profile_picture':
  case 'remove_picture':
   return true;
  default:
   return false;
 }
}
