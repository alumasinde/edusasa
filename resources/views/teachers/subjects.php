<?php declare(strict_types=1); $t=$teacher ?? []; ?>
<h1>Subjects — <?= htmlspecialchars((string)$t['first_name'].' '.(string)$t['last_name']) ?></h1>
<p>Current assignments:</p>
<ul><?php foreach (($subjects ?? []) as $s): ?><li><?= htmlspecialchars((string)$s['name']) ?></li><?php endforeach; ?></ul>
<p>Subject assignment is managed through the Academic class/subject workflow. This page confirms current teacher assignments.</p>
