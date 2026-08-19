<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Tenant;

$school = $school ?? Tenant::current();
$schoolName = $school?->name ?? 'Your School';
$errors = is_array($errors ?? null) ? $errors : [];
$old = is_array($old ?? null) ? $old : [];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Sign in | <?= e($schoolName) ?> | EduSasa</title>
    <style>
        :root {
            --brand: #0f766e;
            --brand-dark: #115e59;
            --brand-soft: #e7f6f3;
            --ink: #102a2a;
            --muted: #667085;
            --line: #e4e7ec;
            --surface: #ffffff;
            --page: #f5f8f8;
            --danger: #b42318;
            --danger-bg: #fff3f2;
        }
        * { box-sizing: border-box; }
        html, body { min-height: 100%; }
        body {
            margin: 0;
            background: var(--page);
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(420px, .95fr);
        }
        .brand-panel {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px clamp(28px, 6vw, 88px);
            background: linear-gradient(145deg, #0b4f4a 0%, #0f766e 55%, #159a8e 100%);
            color: #fff;
        }
        .brand-panel::before,
        .brand-panel::after {
            content: "";
            position: absolute;
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 50%;
            pointer-events: none;
        }
        .brand-panel::before { width: 520px; height: 520px; right: -220px; top: -160px; }
        .brand-panel::after { width: 360px; height: 360px; left: -190px; bottom: -160px; }
        .brand { position: relative; z-index: 1; display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 1.35rem; letter-spacing: -.02em; }
        .brand-mark {
            width: 42px; height: 42px; border-radius: 12px;
            display: grid; place-items: center;
            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.2);
            box-shadow: 0 8px 24px rgba(0,0,0,.12);
        }
        .brand-copy { position: relative; z-index: 1; max-width: 560px; margin: auto 0; padding: 72px 0; }
        .eyebrow { display: inline-flex; align-items: center; gap: 8px; padding: 7px 11px; border-radius: 999px; background: rgba(255,255,255,.12); font-size: .78rem; font-weight: 700; letter-spacing: .03em; }
        .dot { width: 7px; height: 7px; border-radius: 50%; background: #9ff3df; }
        .brand-copy h1 { margin: 20px 0 14px; font-size: clamp(2.2rem, 4vw, 4rem); line-height: 1.04; letter-spacing: -.045em; }
        .brand-copy p { margin: 0; max-width: 500px; color: rgba(255,255,255,.78); font-size: 1.02rem; line-height: 1.75; }
        .feature-list { margin: 34px 0 0; padding: 0; list-style: none; display: grid; gap: 12px; }
        .feature-list li { display: flex; gap: 10px; align-items: center; color: rgba(255,255,255,.9); font-size: .92rem; }
        .check { width: 22px; height: 22px; border-radius: 7px; display: grid; place-items: center; background: rgba(255,255,255,.14); font-size: .75rem; }
        .copyright { position: relative; z-index: 1; color: rgba(255,255,255,.58); font-size: .78rem; }

        .login-panel { display: grid; place-items: center; padding: 32px; }
        .card { width: min(100%, 460px); }
        .mobile-brand { display: none; }
        .school-badge {
            width: 58px; height: 58px; margin-bottom: 20px; border-radius: 16px;
            display: grid; place-items: center;
            background: var(--brand-soft); color: var(--brand-dark);
            font-weight: 800; font-size: 1.1rem;
        }
        .card h2 { margin: 0 0 8px; font-size: 1.85rem; letter-spacing: -.035em; }
        .intro { margin: 0 0 28px; color: var(--muted); line-height: 1.6; font-size: .94rem; }
        .alert { margin: 0 0 20px; padding: 13px 14px; border: 1px solid #fecdca; border-radius: 12px; background: var(--danger-bg); color: var(--danger); font-size: .88rem; line-height: 1.5; }
        .alert strong { display: block; margin-bottom: 3px; }
        .field { margin-bottom: 18px; }
        label { display: block; margin-bottom: 8px; font-size: .84rem; font-weight: 700; color: #344054; }
        input {
            width: 100%; height: 50px; padding: 0 14px;
            border: 1px solid #d0d5dd; border-radius: 11px;
            background: #fff; color: var(--ink); font: inherit; outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        input::placeholder { color: #98a2b3; }
        input:focus { border-color: var(--brand); box-shadow: 0 0 0 4px rgba(15,118,110,.12); }
        .password-wrap { position: relative; }
        .password-wrap input { padding-right: 72px; }
        .toggle { position: absolute; right: 8px; top: 7px; height: 36px; padding: 0 10px; border: 0; background: transparent; color: var(--muted); font-weight: 700; font-size: .78rem; cursor: pointer; }
        .row { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 2px 0 24px; }
        .remember { display: flex; align-items: center; gap: 8px; color: var(--muted); font-size: .82rem; }
        .remember input { width: 16px; height: 16px; accent-color: var(--brand); }
        .help { color: var(--brand-dark); font-size: .82rem; font-weight: 700; text-decoration: none; }
        .button {
            width: 100%; height: 50px; border: 0; border-radius: 11px;
            background: var(--brand); color: #fff; font: inherit; font-weight: 800;
            cursor: pointer; box-shadow: 0 8px 18px rgba(15,118,110,.18);
            transition: transform .15s, background .15s, box-shadow .15s;
        }
        .button:hover { background: var(--brand-dark); box-shadow: 0 10px 22px rgba(15,118,110,.22); }
        .button:active { transform: translateY(1px); }
        .security { margin-top: 22px; padding-top: 20px; border-top: 1px solid var(--line); display: flex; gap: 10px; color: #98a2b3; font-size: .76rem; line-height: 1.5; }
        .security-icon { flex: 0 0 auto; color: var(--brand); }
        .footer { margin-top: 30px; text-align: center; color: #98a2b3; font-size: .74rem; }
        @media (max-width: 850px) {
            .shell { display: block; }
            .brand-panel { display: none; }
            .login-panel { min-height: 100vh; padding: 24px 18px; align-items: start; }
            .mobile-brand { display: flex; align-items: center; gap: 10px; margin: 8px 0 48px; font-weight: 800; color: var(--brand-dark); }
            .mobile-brand .brand-mark { background: var(--brand-soft); border-color: #d4eeea; }
            .card { width: min(100%, 460px); }
        }
        @media (max-width: 420px) {
            .login-panel { padding: 20px 16px; }
            .mobile-brand { margin-bottom: 38px; }
            .card h2 { font-size: 1.65rem; }
            .row { align-items: flex-start; flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="shell">
    <section class="brand-panel" aria-label="EduSasa">
        <div class="brand">
            <span class="brand-mark" aria-hidden="true">ES</span>
            <span>EduSasa</span>
        </div>
        <div class="brand-copy">
            <span class="eyebrow"><span class="dot"></span> School management platform</span>
            <h1>Everything your school needs, in one place.</h1>
            <p>Manage academics, students, teachers, attendance, finance, examinations and school communication from one secure system.</p>
            <ul class="feature-list">
                <li><span class="check">✓</span> Secure access for every school role</li>
                <li><span class="check">✓</span> Centralised school operations</li>
                <li><span class="check">✓</span> Built for modern Kenyan schools</li>
            </ul>
        </div>
        <div class="copyright">© <?= date('Y') ?> EduSasa. All rights reserved.</div>
    </section>

    <main class="login-panel">
        <div class="card">
            <div class="mobile-brand"><span class="brand-mark">ES</span><span>EduSasa</span></div>
            <div class="school-badge" aria-hidden="true"><?= e(strtoupper(substr($schoolName, 0, 2))) ?></div>
            <h2>Welcome back</h2>
            <p class="intro">Sign in to <strong><?= e($schoolName) ?></strong> to continue to your school dashboard.</p>

            <?php if ($errors !== []): ?>
                <div class="alert" role="alert">
                    <strong>Unable to sign you in</strong>
                    <?= e((string) $errors[0]) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/login" autocomplete="on">
                <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">

                <div class="field">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" value="<?= e((string) ($old['email'] ?? '')) ?>" placeholder="you@school.ac.ke" autocomplete="username" required autofocus>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-wrap">
                        <input id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>
                        <button class="toggle" type="button" onclick="togglePassword()" aria-label="Show password">Show</button>
                    </div>
                </div>

                <div class="row">
                    <label class="remember"><input type="checkbox" name="remember" value="1"> Remember me</label>
                    <a class="help" href="mailto:<?= e($school?->email ?? '') ?>">Need help signing in?</a>
                </div>

                <button class="button" type="submit">Sign in to <?= e($schoolName) ?></button>
            </form>

            <div class="security">
                <span class="security-icon">🔒</span>
                <span>Your connection is protected. Never share your password or verification codes with anyone.</span>
            </div>
            <div class="footer">Powered by EduSasa</div>
        </div>
    </main>
</div>
<script>
function togglePassword() {
    const input = document.getElementById('password');
    const button = document.querySelector('.toggle');
    const visible = input.type === 'text';
    input.type = visible ? 'password' : 'text';
    button.textContent = visible ? 'Show' : 'Hide';
    button.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
}
</script>
</body>
</html>
