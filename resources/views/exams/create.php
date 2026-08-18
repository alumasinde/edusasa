<?php $old=$old??[];$errors=$errors??[];$esc=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); ?>
<div class="page-header"><div><h1>Create Examination</h1><p>Set up the assessment period, target classes and scheduling window.</p></div><a href="/exams" class="btn">Back</a></div>
<?php if($errors): ?><div class="alert alert-danger"><?php foreach($errors as $error): ?><div><?= $esc($error) ?></div><?php endforeach; ?></div><?php endif; ?>
<form method="post" action="/exams" class="card"><input type="hidden" name="csrf_token" value="<?= $esc(csrf_token()) ?>">
<div class="form-grid">
<label>Name *<input name="name" required value="<?= $esc($old['name']??'') ?>" placeholder="End Term Examination"></label>
<label>Code *<input name="code" required value="<?= $esc($old['code']??'') ?>" placeholder="ETE-2026-T1"></label>
<label>Exam type *<input name="exam_type" required value="<?= $esc($old['exam_type']??'') ?>" placeholder="End Term"></label>
<label>Academic year *<select name="academic_year_id" required><option value="">Select</option><?php foreach($years as $year): ?><option value="<?= $year['id'] ?>" <?= (string)($old['academic_year_id']??'')===(string)$year['id']?'selected':'' ?>><?= $esc($year['name']) ?></option><?php endforeach; ?></select></label>
<label>Term *<select name="term_id" required><option value="">Select</option><?php foreach($terms as $term): ?><option value="<?= $term['id'] ?>" <?= (string)($old['term_id']??'')===(string)$term['id']?'selected':'' ?>><?= $esc($term['name']) ?></option><?php endforeach; ?></select></label>
<label>Starts *<input type="date" name="starts_on" required value="<?= $esc($old['starts_on']??'') ?>"></label>
<label>Ends *<input type="date" name="ends_on" required value="<?= $esc($old['ends_on']??'') ?>"></label>
</div>
<label>Instructions<textarea name="instructions" rows="4" placeholder="Optional instructions for teachers and administrators."><?= $esc($old['instructions']??'') ?></textarea></label>
<fieldset><legend>Classes *</legend><div class="checkbox-grid"><?php foreach($classes as $class): ?><label><input type="checkbox" name="class_ids[]" value="<?= $class['id'] ?>" <?= in_array((string)$class['id'],array_map('strval',(array)($old['class_ids']??[])),true)?'checked':'' ?>> <?= $esc($class['name']) ?><?= $class['code']?' ('.$esc($class['code']).')':'' ?></label><?php endforeach; ?></div></fieldset>
<button class="btn btn-primary" type="submit">Create Examination</button>
</form>
