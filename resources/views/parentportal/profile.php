<?php $layout='layouts.app'; ?>
<div style="display:flex;justify-content:space-between;align-items:center"><div><h1>Parent Profile</h1><p class="muted">Update contact details used by the school.</p></div><a class="btn btn-outline" href="/parent-portal">Back to portal</a></div>
<div class="card" style="margin-top:var(--space-4)"><div class="card-body">
<?php if(!empty($errors)): ?><div class="alert alert-danger"><?php foreach($errors as $field=>$messages): foreach((array)$messages as $message): ?><div><?= e($message) ?></div><?php endforeach; endforeach; ?></div><?php endif; ?>
<p><strong>Name:</strong> <?= e(trim(($guardian['first_name']??'').' '.($guardian['middle_name']??'').' '.($guardian['last_name']??''))) ?></p><p><strong>Email:</strong> <?= e((string)($guardian['email']??'')) ?></p>
<form method="POST" action="/parent-portal/profile"><?= csrf() ?><div class="form-group"><label for="phone">Phone</label><input id="phone" name="phone" value="<?= e((string)($guardian['phone']??'')) ?>" maxlength="40"></div><div class="form-group"><label for="address">Address</label><textarea id="address" name="address" maxlength="500"><?= e((string)($guardian['address']??'')) ?></textarea></div><button class="btn btn-primary" type="submit">Save profile</button></form>
</div></div>
