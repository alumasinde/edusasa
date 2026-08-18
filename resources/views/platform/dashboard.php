<?php
/** @var array $stats */
/** @var array $plans */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Platform Dashboard | EduSasa</title>
</head>
<body>
<header class="topbar">
    <a class="brand" href="/platform">EduSasa</a>
    <button class="mobile-menu btn-small" type="button" data-mobile-menu aria-label="Toggle navigation">Menu</button>
    <nav class="nav" data-nav>
        <a href="/platform">Dashboard</a>
        <a href="/platform/schools">Schools</a>
        <a href="/platform/subscriptions">Subscriptions</a>
        <a href="/platform/plans/1">Plans</a>
        <a href="/platform/entitlements">Entitlements</a>
        <a href="/platform/access">Access</a>
    </nav>
</header>

<aside class="sidebar">
    <div class="muted" style="margin-bottom:10px">Platform administration</div>
    <a href="/platform">Dashboard</a>
    <a href="/platform/schools">Schools</a>
    <a href="/platform/schools/new">Add school</a>
    <a href="/platform/subscriptions">Subscriptions</a>
    <a href="/platform/entitlements">Entitlements</a>
    <a href="/platform/access">Access control</a>
</aside>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1>Platform Dashboard</h1>
            <p class="muted">Manage schools, plans, subscriptions and platform access.</p>
        </div>
        <div class="actions">
            <a class="btn" href="/platform/schools/new">Add school</a>
        </div>
    </div>

    <section class="grid grid-4" aria-label="Platform statistics">
        <?php foreach (($stats ?? []) as $label => $value): ?>
            <article class="stat-card">
                <div class="muted"><?= htmlspecialchars((string) $label) ?></div>
                <div class="value"><?= htmlspecialchars((string) $value) ?></div>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="panel" style="margin-top:24px">
        <div class="page-header" style="margin-top:0">
            <div>
                <h2>Plans</h2>
                <p class="muted">Available EduSasa subscription plans.</p>
            </div>
            <a class="btn btn-secondary" href="/platform/entitlements">Manage features</a>
        </div>
        <?php if (empty($plans)): ?>
            <div class="empty-state">No plans are available yet.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr><th>Name</th><th>Code</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($plans as $plan): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars((string) $plan['name']) ?></strong></td>
                            <td><code><?= htmlspecialchars((string) $plan['code']) ?></code></td>
                            <td>
                                <span class="badge"><?= !empty($plan['is_active']) ? 'Active' : 'Inactive' ?></span>
                            </td>
                            <td><a href="/platform/plans/<?= htmlspecialchars((string) ($plan['id'] ?? '')) ?>">Manage</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
