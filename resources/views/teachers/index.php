<?php declare(strict_types=1); ?>
<h1>Teachers</h1>
<p><a href="/teachers/create">Add Teacher</a></p>
<table><thead><tr><th>Name</th><th>Employee No.</th><th>Email</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach (($teachers ?? []) as $teacher): ?>
<tr><td><?= htmlspecialchars((string)$teacher['first_name'].' '.(string)$teacher['last_name']) ?></td><td><?= htmlspecialchars((string)($teacher['employee_no'] ?? '')) ?></td><td><?= htmlspecialchars((string)($teacher['email'] ?? '')) ?></td><td><?= htmlspecialchars((string)$teacher['status']) ?></td><td><a href="/teachers/<?= (int)$teacher['id'] ?>">View</a> <a href="/teachers/<?= (int)$teacher['id'] ?>/edit">Edit</a></td></tr>
<?php endforeach; ?>
</tbody></table>
