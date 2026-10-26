<?php
declare(strict_types=1);
function personnel_titles(bool $administrator=false): array {
 return $administrator?['Administrator','IT Head','Maintenance Head','Head of Registrar','Department Head','Dean','Principal','HR Head','Finance Head','Library Head','Security Head','Student Affairs Head','Other']:['Instructor','Teacher','Professor','Lecturer','Laboratory Instructor','Registrar Staff','IT Staff','Accounting Staff','Cashier','HR Staff','Administrative Assistant','Librarian','Library Assistant','Guidance Counselor','School Nurse','Laboratory Assistant','Security Guard','Maintenance Staff','Janitorial Staff','Housekeeping Staff','Electrician','Plumber','Carpenter','Gardener','Canteen Staff','Driver','Other'];
}
function personnel_departments(): array {
 return ['IT','INDUS','CBA','Education','Arts and Sciences','Engineering','Hospitality Management','Criminology','Basic Education','Registrar','IT Services','Accounting and Finance','Human Resources','Administration','Library','Guidance and Counseling','Clinic','Security','Maintenance','Facilities','Transportation','Other'];
}
function personnel_validate(bool $administrator=false): array {
 $values=[];
 foreach(['job_title'=>[personnel_titles($administrator),80,'job role'],'department'=>[personnel_departments(),100,'department or office']] as $key=>[$options,$limit,$label]){
  $choice=field($key);if(!in_array($choice,$options,true))throw new RuntimeException('Choose your '.$label.'.');
  $value=$choice==='Other'?field($key.'_other'):$choice;
  if($value===''||mb_strlen($value,'UTF-8')>$limit||preg_match('/[\x00-\x1f\x7f]/',$value))throw new RuntimeException('Enter a valid '.$label.' (up to '.$limit.' characters).');
  $values[]=$value;
 }
 return $values;
}
