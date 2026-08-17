<?php
/** @var array $plans */
/** @var array $old */
/** @var array $errors */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Onboard School · EduSasa Platform</title>
    <style>
        body{font-family:system-ui,sans-serif;margin:0;background:#f6f7f9;color:#17202a}
        main{max-width:820px;margin:40px auto;padding:0 20px}
        form{background:#fff;border:1px solid #e3e7eb;border-radius:14px;padding:24px}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        label{display:block;font-weight:600;font-size:14px}
        input,select{width:100%;box-sizing:border-box;margin-top:7px;padding:11px;border:1px solid #ccd3da;border-radius:8px}
        .full{grid-column:1/-1}.errors{padding:12px;border-radius:8px;background:#fff0f0;margin-bottom:16px}
        button{margin-top:20px;padding:12px 18px;border:0;border-radius:8px;cursor:pointer}
        @media(max-width:650px){.grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<main>
    <h1>Onboard a school</h1>
    <p>Create the school tenant, assign its initial plan and prepare the first administrator invitation.</p>
    <?php if (!empty($errors)): ?><div class="errors"><?php foreach ($errors as $error): ?><div><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div><?php endforeach; ?></div><?php endif; ?>
    <form method="post" action="/platform/schools">
        <div class="grid">
            <label>School name <input name="name" required value="<?= htmlspecialchars((string)($old['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>School code <input name="code" value="<?= htmlspecialchars((string)($old['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Slug <input name="slug" value="<?= htmlspecialchars((string)($old['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>School email <input type="email" name="email" value="<?= htmlspecialchars((string)($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Phone <input name="phone" value="<?= htmlspecialchars((string)($old['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Domain <input name="domain" value="<?= htmlspecialchars((string)($old['domain'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Timezone <input name="timezone" value="<?= htmlspecialchars((string)($old['timezone'] ?? 'Africa/Nairobi'), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Administrator email <input type="email" name="admin_email" required value="<?= htmlspecialchars((string)($old['admin_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Plan
                <select name="plan" required>
                    <option value="">Select a plan</option>
                    <?php foreach ($plans as $plan): ?>
                        <?php $code=(string)($plan['code'] ?? ''); ?>
                        <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>" <?= (($old['plan'] ?? '') === $code) ? 'selected' : '' ?>><?= htmlspecialchars((string)($plan['name'] ?? $code), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <button type="submit">Create school & prepare invitation</button>
    </form>
</main>
</body>
</html>
