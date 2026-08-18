<?php $school=$school??[];$settings=$settings??[];$errors=$errors??[]; ?>
<div class="page-header"><h1>School Settings</h1><p>Manage school identity and operational defaults.</p></div>
<?php if($errors): ?><div class="alert alert-danger"><?php foreach($errors as $group=>$messages): ?><?php foreach((array)$messages as $message): ?><div><?=htmlspecialchars((string)$message,ENT_QUOTES,'UTF-8')?></div><?php endforeach; ?><?php endforeach; ?></div><?php endif; ?>
<form method="post" action="/settings">
<input type="hidden" name="_token" value="<?=htmlspecialchars((string)($csrf_token??''),ENT_QUOTES,'UTF-8')?>">
<label>School name <input name="name" required maxlength="190" value="<?=htmlspecialchars((string)($school['name']??''),ENT_QUOTES,'UTF-8')?>"></label>
<label>Email <input type="email" name="email" maxlength="190" value="<?=htmlspecialchars((string)($school['email']??''),ENT_QUOTES,'UTF-8')?>"></label>
<label>Phone <input name="phone" maxlength="40" value="<?=htmlspecialchars((string)($school['phone']??''),ENT_QUOTES,'UTF-8')?>"></label>
<label>Address <input name="address" maxlength="255" value="<?=htmlspecialchars((string)($school['address']??''),ENT_QUOTES,'UTF-8')?>"></label>
<label>Timezone <input name="timezone" required maxlength="80" value="<?=htmlspecialchars((string)($school['timezone']??' Africa/Nairobi'),ENT_QUOTES,'UTF-8')?>"></label>
<label>Currency <input name="default_currency" maxlength="3" value="<?=htmlspecialchars((string)($settings['default_currency']??'KES'),ENT_QUOTES,'UTF-8')?>"></label>
<label>Date format <input name="date_format" maxlength="30" value="<?=htmlspecialchars((string)($settings['date_format']??'Y-m-d'),ENT_QUOTES,'UTF-8')?>"></label>
<label>Attendance cutoff <input type="time" name="attendance_cutoff_time" value="<?=htmlspecialchars((string)($settings['attendance_cutoff_time']??'09:00'),ENT_QUOTES,'UTF-8')?>"></label>
<label>Academic year label <input name="academic_year_label" maxlength="100" value="<?=htmlspecialchars((string)($settings['academic_year_label']??''),ENT_QUOTES,'UTF-8')?>"></label>
<label>Current term label <input name="term_label" maxlength="100" value="<?=htmlspecialchars((string)($settings['term_label']??''),ENT_QUOTES,'UTF-8')?>"></label>
<label><input type="checkbox" name="notifications_enabled" value="1" <?=!empty($settings['notifications_enabled'])?'checked':''?>> Notifications enabled</label>
<button type="submit">Save settings</button>
</form>
<p><a href="/settings/audit">View audit log</a></p>
