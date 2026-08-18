<?php declare(strict_types=1); $t=$teacher ?? []; ?>
<h1><?= htmlspecialchars((string)$t['first_name'].' '.(string)$t['last_name']) ?></h1>
<p>Employee No: <?= htmlspecialchars((string)($t['employee_no'] ?? '')) ?></p>
<p>Email: <?= htmlspecialchars((string)($t['email'] ?? '')) ?></p>
<p>Phone: <?= htmlspecialchars((string)($t['phone'] ?? '')) ?></p>
<p>Status: <?= htmlspecialchars((string)$t['status']) ?></p>
<p><a href="/teachers/<?= (int)$t['id'] ?>/edit">Edit</a> | <a href="/teachers/<?= (int)$t['id'] ?>/subjects">Subjects</a> | <a href="/teachers/<?= (int)$t['id'] ?>/classes">Classes</a></p>
<form method="post" action="/teachers/<?= (int)$t['id'] ?>/status">
<?= csrf_field() ?><select name="status"><option value="active">Active</option><option value="inactive">Inactive</option><option value="suspended">Suspended</option></select><button type="submit">Change status</button>
</form>
