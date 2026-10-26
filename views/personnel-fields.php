<?php
if(!isset($installed)||!$installed){http_response_code(404);exit;}
$personnelValues=$personnelValues??[];$personnelKind=$personnelKind??false;
foreach(['job_title'=>['Job Role',personnel_titles($personnelKind),80],'department'=>['Department / Office',personnel_departments(),100]] as $key=>[$label,$options,$limit]):
 $value=array_key_exists($key,$_POST)?field($key):($personnelValues[$key]??'');$other=array_key_exists($key.'_other',$_POST)?field($key.'_other'):'';
 if($value!==''&&!in_array($value,$options,true)){$other=$value;$value='Other';}
?>
<label><?= e($label) ?><select name="<?= $key ?>" aria-label="<?= e($label) ?>" required><option value="">Choose <?= e($label) ?></option><?php foreach($options as $option):?><option value="<?= e($option) ?>" <?= $value===$option?'selected':'' ?>><?= e($option) ?></option><?php endforeach ?></select></label>
<label class="personnel-other">Other <?= e($label) ?> <small>(if selected)</small><input name="<?= $key ?>_other" maxlength="<?= $limit ?>" value="<?= e($other) ?>" placeholder="Specify if you selected Other"></label>
<?php endforeach;unset($personnelValues,$personnelKind); ?>
