<?php
/** @var string $token */
/** @var array|null $invitation */
/** @var array $errors */
use App\Core\Csrf;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Activate School Administrator · EduSasa</title>
<style>
body{font-family:system-ui,sans-serif;background:#f5f7fb;color:#172033;margin:0}.wrap{max-width:560px;margin:60px auto;padding:24px}.card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:28px;box-shadow:0 8px 30px rgba(16,24,40,.06)}h1{margin-top:0}.muted{color:#667085}.error{background:#fff1f0;color:#b42318;border:1px solid #fecdca;padding:12px;border-radius:8px;margin-bottom:16px}label{display:block;font-weight:600;margin:16px 0 6px}input{box-sizing:border-box;width:100%;padding:12px;border:1px solid #d0d5dd;border-radius:8px;font:inherit}button{width:100%;margin-top:22px;padding:13px;border:0;border-radius:8px;background:#172033;color:#fff;font-weight:700;font-size:15px}.hint{font-size:13px;color:#667085;margin-top:6px}
</style>
</head>
<body><main class="wrap"><section class="card">
<?php if ($invitation === null): ?>
<h1>Invitation unavailable</h1><p class="muted">This school administrator invitation is invalid, expired, or has already been used.</p>
<?php else: ?>
<h1>Activate your EduSasa account</h1>
<p class="muted">You have been invited to administer <strong><?= htmlspecialchars((string)$invitation['school_name']) ?></strong>.</p>
<?php foreach (($errors ?? []) as $error): ?><div class="error"><?= htmlspecialchars((string)$error) ?></div><?php endforeach; ?>
<form method="post" action="/school-admin/setup">
<input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
<input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
<label for="first_name">First name</label><input id="first_name" name="first_name" autocomplete="given-name" required>
<label for="last_name">Last name</label><input id="last_name" name="last_name" autocomplete="family-name" required>
<label>Email</label><input value="<?= htmlspecialchars((string)$invitation['email']) ?>" readonly>
<label for="password">Password</label><input id="password" type="password" name="password" autocomplete="new-password" minlength="12" required><div class="hint">At least 12 characters, including upper-case, lower-case and a number.</div>
<label for="password_confirmation">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="12" required>
<button type="submit">Activate account</button>
</form>
<?php endif; ?>
</section></main></body></html>
