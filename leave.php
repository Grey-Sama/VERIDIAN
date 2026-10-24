<?php
declare(strict_types=1);
// Internal policy helpers. Personal leave shares the Annual pool.
function leave_end(string $start,int $count): string {
 if(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$start))throw new RuntimeException('Choose a valid start date.');
 $date=DateTimeImmutable::createFromFormat('!Y-m-d',$start);
 if(!$date||$date->format('Y-m-d')!==$start||(int)$date->format('N')>5||$count<1||$count>260)throw new RuntimeException('Choose a weekday and 1–260 leave days.');
 for($left=$count-1;$left>0;){$date=$date->modify('+1 day');if((int)$date->format('N')<6)$left--;}
 return $date->format('Y-m-d');
}
function days(string $a,string $b): int {
 $count=0;for($d=new DateTimeImmutable($a),$end=new DateTimeImmutable($b);$d<=$end;$d=$d->modify('+1 day'))if((int)$d->format('N')<6)$count++;
 return $count;
}
function leave_pool(string $type): string { return $type==='Personal'?'Annual':$type; }
function leave_label(string $type): string { return $type==='Fast'?'Fast Leave':$type; }
function leave_duration(array $r): float { return $r['leave_type']==='Fast'?0.5:(float)days($r['start_date'],$r['end_date']); }
function leave_parts(string $a,string $b): array {
 $parts=[];for($y=(int)substr($a,0,4);$y<=(int)substr($b,0,4);$y++)$parts[$y]=days(max($a,"$y-01-01"),min($b,"$y-12-31"));return $parts;
}
function leave_balance(PDO $pdo,int $uid,int $year): array {
 $q=$pdo->prepare('SELECT annual,sick,emergency FROM leave_allowances WHERE user_id=? AND year=?');$q->execute([$uid,$year]);$row=$q->fetch()?:['annual'=>15,'sick'=>15,'emergency'=>7];
 $out=[];foreach(['Annual','Sick','Emergency'] as $type)$out[$type]=['allowance'=>(int)$row[strtolower($type)],'approved'=>0,'pending'=>0];
 $out['Fast']=['allowance'=>3,'approved'=>0,'pending'=>0];
 $q=$pdo->prepare("SELECT * FROM leave_requests WHERE user_id=? AND status IN ('Pending','Approved') AND start_date<=? AND end_date>=?");$q->execute([$uid,"$year-12-31","$year-01-01"]);
 foreach($q as $r){$pool=leave_pool($r['leave_type']);$out[$pool][strtolower($r['status'])]+=days(max($r['start_date'],"$year-01-01"),min($r['end_date'],"$year-12-31"));}
 foreach($out as &$b)$b['available']=$b['allowance']-$b['approved']-$b['pending'];unset($b);return $out;
}
// Caller holds the employee row lock, serializing requests and allowance edits.
function leave_check(PDO $pdo,int $uid,string $type,string $a,string $b,bool $existing=false): void {
 foreach(leave_parts($a,$b) as $year=>$needed){$balance=leave_balance($pdo,$uid,$year)[leave_pool($type)];if($balance['available']<($existing?0:$needed))throw new RuntimeException("Insufficient ".leave_label(leave_pool($type))." balance for $year.");}
}
function leave_timing(array $r,?string $today=null): string {
 $today??=date('Y-m-d');if($r['status']!=='Approved')return $r['status']==='Pending'?'Awaiting approval':$r['status'];
 if($r['end_date']<$today)return 'Completed';
 if($r['start_date']===$today)return 'Starts today';
 if($r['start_date']<$today)return 'On leave · '.days($today,$r['end_date']).' weekdays left';
 return 'Starts in '.(new DateTimeImmutable($today))->diff(new DateTimeImmutable($r['start_date']))->days.' days';
}
function query_text(string $key,string $default=''): string { return isset($_GET[$key])&&is_string($_GET[$key])?substr($_GET[$key],0,100):$default; }
function sort_link(string $key,string $label): string {
 $params=['view'=>query_text('view','requests'),'sort'=>$key,'dir'=>query_text('sort')===$key&&query_text('dir')!=='desc'?'desc':'asc'];
 foreach(['status','q','year'] as $p)if(query_text($p)!=='')$params[$p]=query_text($p);
 return '<a href="index.php?'.e(http_build_query($params)).'">'.e($label).(query_text('sort')===$key?(query_text('dir')==='desc'?' ↓':' ↑'):' ↕').'</a>';
}
