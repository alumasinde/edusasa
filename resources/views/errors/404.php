<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Not Found | EduSasa</title>
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f5f7fb;color:#172033;font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif}.card{width:min(560px,calc(100% - 32px));background:#fff;border:1px solid #e4e7ec;border-radius:20px;padding:40px 32px;box-shadow:0 16px 48px rgba(16,24,40,.08);text-align:center}.logo{font-weight:800;font-size:1.35rem;color:#0f766e;margin-bottom:28px}.code{font-size:4rem;line-height:1;font-weight:800;color:#0f766e;margin-bottom:16px}h1{margin:0 0 10px;font-size:1.55rem}.muted{color:#667085;line-height:1.65;margin:0 auto;max-width:430px}.actions{margin-top:26px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap}a{padding:11px 17px;border-radius:10px;background:#0f766e;color:#fff;text-decoration:none;font-weight:700}a.secondary{background:#eef2f6;color:#344054}
    </style>
</head>
<body>
<main class="card">
    <div class="logo">EduSasa</div>
    <div class="code">404</div>
    <h1>Page not found</h1>
    <p class="muted"><?= e($message ?? 'The page you requested could not be found.') ?></p>
    <div class="actions"><a href="/">Go to dashboard</a><a class="secondary" href="javascript:history.back()">Go back</a></div>
</main>
</body>
</html>
