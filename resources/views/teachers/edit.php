<?php declare(strict_types=1); $t=$teacher ?? []; ?>
<h1>Edit Teacher</h1>
<form method="post" action="/teachers/<?= (int)$t['id'] ?>">
<?= csrf_field() ?>
<label>Employee No. <input name="employee_no" value="<?= htmlspecialchars((string)($t['employee_no'] ?? '')) ?>"></label><br>
<label>First name <input name="first_name" required value="<?= htmlspecialchars((string)$t['first_name']) ?>"></label><br>
<label>Last name <input name="last_name" required value="<?= htmlspecialchars((string)$t['last_name']) ?>"></label><br>
<label>Email <input type="email" name="email" value="<?= htmlspecialchars((string)($t['email'] ?? '')) ?>"></label><br>
<label>Phone <input name="phone" value="<?= htmlspecialchars((string)($t['phone'] ?? '')) ?>"></label><br>
<label>Status <select name="status"><?php foreach(['active','inactive','suspended'] as $s): ?><option value="<?= $s ?>" <?= ($t['status']??'active')===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></label><br>
<button type="submit">Update Teacher</button>
</form>
