<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Service Temporarily Unavailable | EduSasa</title>
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f5f7fb;color:#172033;font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif}.card{width:min(600px,calc(100% - 32px));background:#fff;border:1px solid #e4e7ec;border-radius:20px;padding:40px 32px;box-shadow:0 16px 48px rgba(16,24,40,.08);text-align:center}.logo{font-weight:800;font-size:1.35rem;color:#0f766e;margin-bottom:28px}.icon{width:64px;height:64px;margin:0 auto 18px;border-radius:50%;display:grid;place-items:center;background:#fff8eb;color:#b54708;font-size:25px;font-weight:800}h1{margin:0 0 10px;font-size:1.55rem}.muted{color:#667085;line-height:1.65;margin:0 auto;max-width:470px}.ref{margin-top:18px;color:#98a2b3;font-size:.85rem}.ref code{color:#475467;background:#f2f4f7;padding:4px 7px;border-radius:6px}.actions{margin-top:26px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap}a{padding:11px 17px;border-radius:10px;background:#0f766e;color:#fff;text-decoration:none;font-weight:700}a.secondary{background:#eef2f6;color:#344054}
    </style>
</head>
<body>
<main class="card">
    <div class="logo">EduSasa</div>
    <div class="icon">⏳</div>
    <h1>Service temporarily unavailable</h1>
    <p class="muted"><?= e($message ?? 'EduSasa is temporarily unable to complete that request. Please try again shortly.') ?></p>
    <?php if (!empty($reference)): ?><p class="ref">Reference <code><?= e((string)$reference) ?></code></p><?php endif; ?>
    <div class="actions"><a href="javascript:location.reload()">Try again</a><a class="secondary" href="/">Return home</a></div>
</main>
</body>
</html>
