<?php $rows=$rows??[]; ?>
<div class="page-header"><h1>Audit Log</h1><p>Recent administrative activity for this school.</p></div>
<table><thead><tr><th>Time</th><th>Action</th><th>Resource</th><th>ID</th><th>IP</th><th>Details</th></tr></thead><tbody>
<?php foreach($rows as $row): ?><tr><td><?=htmlspecialchars((string)$row['created_at'],ENT_QUOTES,'UTF-8')?></td><td><?=htmlspecialchars((string)$row['action'],ENT_QUOTES,'UTF-8')?></td><td><?=htmlspecialchars((string)$row['resource_type'],ENT_QUOTES,'UTF-8')?></td><td><?=htmlspecialchars((string)$row['resource_id'],ENT_QUOTES,'UTF-8')?></td><td><?=htmlspecialchars((string)$row['ip_address'],ENT_QUOTES,'UTF-8')?></td><td><code><?=htmlspecialchars((string)$row['metadata_json'],ENT_QUOTES,'UTF-8')?></code></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="6">No audit activity found.</td></tr><?php endif; ?></tbody></table>
<p><a href="/settings">Back to settings</a></p>
