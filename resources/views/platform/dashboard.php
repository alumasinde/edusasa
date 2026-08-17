<?php
/** @var array $stats */
/** @var array $plans */
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>EduSasa Platform</title><style>body{font-family:system-ui,sans-serif;margin:0;background:#f5f7fb;color:#172033}main{max-width:1200px;margin:auto;padding:32px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px}.card{background:#fff;border:1px solid #e6e9ef;border-radius:12px;padding:20px}.muted{color:#667085}.value{font-size:28px;font-weight:700;margin-top:8px}table{width:100%;border-collapse:collapse;background:#fff;margin-top:24px}th,td{text-align:left;padding:14px;border-bottom:1px solid #eee}</style></head>
<body><main><h1>EduSasa Platform</h1><p class="muted">Platform administration</p><section class="grid"><?php foreach (($stats ?? []) as $label=>$value): ?><div class="card"><div class="muted"><?= htmlspecialchars((string)$label) ?></div><div class="value"><?= htmlspecialchars((string)$value) ?></div></div><?php endforeach; ?></section><div class="card" style="margin-top:24px"><h2>Plans</h2><table><thead><tr><th>Name</th><th>Code</th><th>Status</th></tr></thead><tbody><?php foreach (($plans ?? []) as $plan): ?><tr><td><?= htmlspecialchars((string)$plan['name']) ?></td><td><?= htmlspecialchars((string)$plan['code']) ?></td><td><?= !empty($plan['is_active'])?'Active':'Inactive' ?></td></tr><?php endforeach; ?></tbody></table></div></main></body></html>
