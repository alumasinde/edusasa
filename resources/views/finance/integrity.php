<?php
/** @var array $summary */
/** @var array $issues */ ?>
<div class="page-header"><div><h1>Finance Integrity</h1><p>Automated checks for financial inconsistencies.</p></div></div>
<div class="stats-grid"><div class="card"><small>Total issues</small><strong><?=number_format((int)$summary['total'])?></strong></div><div class="card"><small>Status</small><strong><?= $summary['healthy'] ? 'Healthy' : 'Needs attention' ?></strong></div></div>
<div class="grid-2">
<?php foreach($issues as $type=>$rows): ?><section class="card"><h2><?=htmlspecialchars(ucwords(str_replace('_',' ',$type)))?> <span>(<?=count($rows)?>)</span></h2><?php if(!$rows): ?><p>No issues detected.</p><?php else: ?><div class="table-wrap"><table><thead><tr><?php foreach(array_keys($rows[0]) as $column): ?><th><?=htmlspecialchars(ucwords(str_replace('_',' ',$column)))?></th><?php endforeach; ?></tr></thead><tbody><?php foreach($rows as $row): ?><tr><?php foreach($row as $value): ?><td><?=htmlspecialchars((string)$value)?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section><?php endforeach; ?>
</div>
