<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>School Not Found | EduSasa</title>
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f5f7fb;color:#172033;font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif}.card{width:min(560px,calc(100% - 32px));background:#fff;border:1px solid #e4e7ec;border-radius:18px;padding:36px;box-shadow:0 12px 40px rgba(16,24,40,.08);text-align:center}.logo{font-weight:800;font-size:1.25rem;color:#0f766e;margin-bottom:28px}.icon{width:64px;height:64px;margin:0 auto 20px;border-radius:50%;display:grid;place-items:center;background:#eef2f6;color:#475467;font-size:28px}.muted{color:#667085;line-height:1.6}.host{display:inline-block;margin-top:8px;padding:6px 10px;border-radius:8px;background:#f8fafc;color:#475467;font-family:ui-monospace,monospace;font-size:.9rem}a{display:inline-block;margin-top:22px;padding:10px 16px;border-radius:10px;background:#0f766e;color:#fff;text-decoration:none;font-weight:700}
    </style>
</head>
<body>
<main class="card">
    <div class="logo">EduSasa</div>
    <div class="icon">?</div>
    <h1>School not found</h1>
    <p class="muted">We could not identify a registered school for this address. Check the school URL and try again.</p>
    <?php if (!empty($host)): ?><div class="host"><?= htmlspecialchars((string)$host) ?></div><?php endif; ?>
    <div><a href="/">Return home</a></div>
</main>
</body>
</html>
