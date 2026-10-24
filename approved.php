<?php if(!isset($user,$requests)||!$user){http_response_code(404);exit;} ?>
<div class="page-heading"><p class="eyebrow">TIME OFF, CONFIRMED</p><h1>Approved Leaves</h1><p class="muted">Upcoming, active, and completed leaves. Countdown uses calendar days.</p></div>
<section class="panel"><?php $shown=array_values(array_filter($requests,fn($r)=>$r['status']==='Approved'));include __DIR__.'/table.php'; ?></section>
