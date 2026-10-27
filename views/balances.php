<?php
if(!isset($user)||!$user){http_response_code(404);exit;}
$year=(int)query_text('year',(string)date('Y'));if($year<2020||$year>2100)$year=(int)date('Y');
?>
<div class="page-heading"><p class="eyebrow">PLAN WITH CONFIDENCE</p><h1>Leave Balances</h1><p class="muted">Available = allowance − approved − pending.</p></div>
<form method="get" class="table-tools"><input type="hidden" name="view" value="balances"><label>Year<input type="number" name="year" min="2020" max="2100" value="<?= $year ?>" required></label><button class="button">View Year</button></form>
<section class="panel policy-note"><h2>Leave Policy</h2><p>Full-Time and Part-Time: 15 Annual · 15 Sick · 7 Emergency days per calendar year. New Employee: 3 Sick days; Annual, Personal, and Emergency leave are locked. Fast Leave: 3 uses for every category (0.5 day each).</p><p class="muted">Full-Time · Part-Time · New Employee. Monday–Friday only; holidays count. No carry-over or monthly accrual.</p><details><summary>Other Leave Types</summary><p>For maternity, paternity, solo-parent, and other special leave, contact your administrator.</p></details></section>
<?php if($admin):
$balanceRows=[];$term=query_text('q');
foreach($employees as $person){
 if($term!==''&&stripos($person['name'].' '.$person['username'],$term)===false)continue;
 $person['balance']=leave_balance($pdo,(int)$person['id'],$year);
 $person['category']=leave_category($pdo,(int)$person['id'],$year)?:'Not Set';
 $balanceRows[]=$person;
}
$sort=query_text('sort','name');if(!in_array($sort,['name','category','Annual','Sick','Emergency','Fast'],true))$sort='name';
$direction=query_text('dir')==='desc'?-1:1;
usort($balanceRows,function($a,$b)use($sort,$direction){$av=isset($a['balance'][$sort])?$a['balance'][$sort]['available']:$a[$sort];$bv=isset($b['balance'][$sort])?$b['balance'][$sort]['available']:$b[$sort];return $direction*(($av<=>$bv)?:($a['id']<=>$b['id']));});
?>
<section class="panel"><h2>Employee Balances · <?= $year ?></h2><p class="muted">Select an employee to view usage and change their category.</p>
<form method="get" class="table-tools"><input type="hidden" name="view" value="balances"><input type="hidden" name="year" value="<?= $year ?>"><label>Search Employees<input name="q" value="<?= e($term) ?>" maxlength="100" placeholder="Name or username"></label><button class="button">Search</button><a class="text-link" href="index.php?view=balances&amp;year=<?= $year ?>">Reset</a></form>
<div class="table-scroll" role="region" aria-label="Employee leave balances" tabindex="0"><table class="data-table"><thead><tr><th><?= sort_link('name','Employee') ?></th><th><?= sort_link('category','Employment Category') ?></th><?php foreach(['Annual','Sick','Emergency','Fast'] as $type):?><th><?= sort_link($type,$type==='Fast'?'Fast Uses Left':$type.' Left') ?></th><?php endforeach ?></tr></thead><tbody>
<?php foreach($balanceRows as $person):?><tr><td><a class="balance-account-link" href="index.php?view=balances&amp;year=<?= $year ?>&amp;employee=<?= (int)$person['id'] ?>" data-balance-open="balance-<?= (int)$person['id'] ?>"><strong><?= e($person['name']) ?></strong><small><?= e($person['username']) ?></small></a></td><td><?= e($person['category']) ?></td><?php foreach($person['balance'] as $b):?><td><strong class="<?= $b['available']<0?'balance-warning':'' ?>"><?= $b['available'] ?></strong></td><?php endforeach ?></tr><?php endforeach ?>
<?php if(!$balanceRows):?><tr><td colspan="6">No matching employees.</td></tr><?php endif ?></tbody></table></div></section>
<?php else:$balanceRows=[$user];endif ?>
<?php foreach($balanceRows as $person):$balance=$person['balance']??leave_balance($pdo,(int)$person['id'],$year);$dialogId='balance-'.(int)$person['id']; ?>
<?php if($admin):?><dialog class="balance-dialog" id="<?= $dialogId ?>" aria-labelledby="<?= $dialogId ?>-title" <?= query_text('employee')===(string)$person['id']?'open':'' ?>><a class="balance-close" href="index.php?view=balances&amp;year=<?= $year ?>" data-balance-close aria-label="Close employee balances">×</a><?php endif ?>
<section class="<?= $admin?'balance-dialog-content':'panel balance-panel' ?>"><h2 id="<?= $dialogId ?>-title"><?= e($person['name']) ?> <small>· <?= $year ?></small></h2><p class="muted">Employment Category: <strong><?= e(leave_category($pdo,(int)$person['id'],$year)?:'Not Set') ?></strong></p>
<?php if($admin):?><form method="post" class="table-tools employment-category-form"><?= csrf() ?><input type="hidden" name="action" value="employment_type"><input type="hidden" name="user_id" value="<?= (int)$person['id'] ?>"><label>Current Employment Category<select name="employment_type" required><option value="" disabled <?= empty($person['employment_type'])?'selected':'' ?>>Choose Category</option><?php foreach(employment_types() as $category):?><option value="<?= e($category) ?>" <?= $person['employment_type']===$category?'selected':'' ?>><?= e($category) ?></option><?php endforeach ?></select></label><button class="button">Save Category</button></form><p class="muted">Category sets the credits. Existing usage still counts. Past years stay unchanged.</p><?php endif ?>
<div class="table-scroll" role="region" aria-label="<?= e($person['name']) ?> leave balances" tabindex="0"><table class="data-table"><thead><tr><th>Leave Type</th><th>Allowance</th><th>Approved</th><th>Pending</th><th>Available</th></tr></thead><tbody><?php foreach($balance as $type=>$b):?><tr><th scope="row"><?= e(leave_label($type).($type==='Fast'?' (uses)':' (days)')) ?></th><td><?= $b['allowance'] ?></td><td><?= $b['approved'] ?></td><td><?= $b['pending'] ?></td><td><strong class="<?= $b['available']<0?'balance-warning':'' ?>"><?= $b['available'] ?></strong></td></tr><?php endforeach ?></tbody></table></div></section>
<?php if($admin):?></dialog><?php endif ?>
<?php endforeach ?>
