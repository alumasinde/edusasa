<?php declare(strict_types=1); $t=$teacher ?? []; ?>
<h1>Classes — <?= htmlspecialchars((string)$t['first_name'].' '.(string)$t['last_name']) ?></h1>
<ul><?php foreach (($classes ?? []) as $c): ?><li><?= htmlspecialchars((string)$c['name']) ?></li><?php endforeach; ?></ul>
<p>Class assignment is managed through the Academic class/teacher workflow. This page confirms current teacher assignments.</p>
