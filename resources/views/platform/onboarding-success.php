<?php
/** @var int $schoolId */
/** @var string $adminEmail */
/** @var string $invitationToken */
$invitePath = '/school-admin/setup?token=' . rawurlencode($invitationToken);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>School Created · EduSasa Platform</title>
    <style>
        body{font-family:system-ui,sans-serif;margin:0;background:#f6f7f9;color:#17202a}
        main{max-width:760px;margin:50px auto;padding:0 20px}.card{background:#fff;border:1px solid #e3e7eb;border-radius:14px;padding:28px}
        code{display:block;padding:14px;background:#f1f3f5;border-radius:8px;word-break:break-all}.note{font-size:14px;color:#566}
        a{display:inline-block;margin-top:18px}
    </style>
</head>
<body><main><div class="card">
    <h1>School created</h1>
    <p>School #<?= htmlspecialchars((string)$schoolId, ENT_QUOTES, 'UTF-8') ?> is ready for administrator setup.</p>
    <p>Administrator: <strong><?= htmlspecialchars($adminEmail, ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p>One-time setup path:</p>
    <code><?= htmlspecialchars($invitePath, ENT_QUOTES, 'UTF-8') ?></code>
    <p class="note">The token is shown once. It is stored only as a SHA-256 hash and expires after 48 hours. Connect this path to the existing school-user authentication flow before sending it externally.</p>
    <a href="/platform/schools">Back to schools</a>
</div></main></body></html>
