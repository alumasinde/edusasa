<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Tenant;

$school = $school ?? Tenant::current();
$schoolName = $school?->name ?? 'Your School';
$errors = is_array($errors ?? null) ? $errors : [];
$old = is_array($old ?? null) ? $old : [];
$initials = '';
foreach (preg_split('/\s+/', trim($schoolName)) ?: [] as $word) {
    if ($word !== '') {
        $initials .= strtoupper(substr($word, 0, 1));
    }
}
$initials = substr($initials ?: 'ES', 0, 2);
$helpEmail = trim((string) ($school?->email ?? ''));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b6b63">
    <meta name="color-scheme" content="light">
    <title>Sign in | <?= e($schoolName) ?> | EduSasa</title>
    <style>
        :root {
            --brand: #0b6b63;
            --brand-dark: #07554f;
            --brand-soft: #e9f7f5;
            --brand-border: #cdebe7;
            --ink: #172b2a;
            --muted: #667575;
            --line: #e5eaea;
            --page: #f4f7f7;
            --surface: #ffffff;
            --danger: #b42318;
            --danger-bg: #fff5f4;
            --shadow: 0 18px 50px rgba(21, 52, 50, .08);
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { min-height: 100%; background: var(--page); }
        body {
            min-height: 100vh;
            margin: 0;
            background: var(--page);
            color: var(--ink);
            font-family: Inter, ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        button, input { font: inherit; }
        a { color: inherit; }

        .page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 18px;
            background: rgba(255,255,255,.96);
            border-bottom: 1px solid var(--line);
        }
        .topbar-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--ink);
            font-weight: 800;
            text-decoration: none;
            letter-spacing: -.02em;
        }
        .logo {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            flex: 0 0 36px;
            border-radius: 10px;
            background: var(--brand);
            color: #fff;
            font-size: .72rem;
            font-weight: 900;
            letter-spacing: .02em;
        }
        .topbar-label {
            color: var(--muted);
            font-size: .78rem;
            font-weight: 600;
        }

        .content {
            width: 100%;
            max-width: 560px;
            margin: 0 auto;
            padding: 30px 16px 28px;
            flex: 1;
        }

        .school-header {
            text-align: center;
            margin-bottom: 22px;
        }
        .school-logo {
            width: 68px;
            height: 68px;
            margin: 0 auto 14px;
            display: grid;
            place-items: center;
            border-radius: 18px;
            background: linear-gradient(145deg, var(--brand), #138d82);
            color: #fff;
            box-shadow: 0 10px 24px rgba(11,107,99,.18);
            font-size: 1.15rem;
            font-weight: 900;
            letter-spacing: .02em;
        }
        .school-header h1 {
            margin: 0;
            color: var(--ink);
            font-size: 1.55rem;
            line-height: 1.2;
            letter-spacing: -.035em;
        }
        .school-header p {
            margin: 7px 0 0;
            color: var(--muted);
            font-size: .88rem;
            line-height: 1.5;
        }

        .card {
            width: 100%;
            padding: 22px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: var(--shadow);
        }
        .card-heading { margin-bottom: 20px; }
        .card-heading h2 {
            margin: 0;
            font-size: 1.25rem;
            letter-spacing: -.025em;
        }
        .card-heading p {
            margin: 5px 0 0;
            color: var(--muted);
            font-size: .84rem;
            line-height: 1.5;
        }

        .alert {
            display: flex;
            gap: 10px;
            margin: 0 0 18px;
            padding: 12px 13px;
            border: 1px solid #f6c8c4;
            border-radius: 11px;
            background: var(--danger-bg);
            color: var(--danger);
            font-size: .82rem;
            line-height: 1.45;
        }
        .alert-icon { flex: 0 0 auto; font-weight: 900; }
        .alert strong { display: block; margin-bottom: 2px; }

        .field { margin-bottom: 17px; }
        .field label {
            display: block;
            margin: 0 0 7px;
            color: #344646;
            font-size: .82rem;
            font-weight: 750;
        }
        .input-wrap { position: relative; }
        .input-wrap input {
            width: 100%;
            height: 50px;
            padding: 0 14px;
            border: 1px solid #ccd6d5;
            border-radius: 11px;
            outline: none;
            background: #fff;
            color: var(--ink);
            font-size: .92rem;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .input-wrap input:hover { border-color: #aebdbc; }
        .input-wrap input:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 4px rgba(11,107,99,.11);
        }
        .input-wrap input::placeholder { color: #9aa9a8; }
        .password input { padding-right: 68px; }
        .toggle-password {
            position: absolute;
            top: 5px;
            right: 5px;
            height: 40px;
            min-width: 56px;
            padding: 0 9px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: var(--brand-dark);
            cursor: pointer;
            font-size: .76rem;
            font-weight: 800;
        }
        .toggle-password:hover { background: var(--brand-soft); }

        .options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 2px 0 20px;
        }
        .remember {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: .8rem;
            cursor: pointer;
        }
        .remember input {
            width: 16px;
            height: 16px;
            margin: 0;
            accent-color: var(--brand);
        }
        .help {
            color: var(--brand-dark);
            font-size: .8rem;
            font-weight: 750;
            text-decoration: none;
        }
        .help:hover { text-decoration: underline; }

        .submit {
            width: 100%;
            min-height: 50px;
            padding: 0 18px;
            border: 0;
            border-radius: 11px;
            background: var(--brand);
            color: #fff;
            cursor: pointer;
            font-size: .9rem;
            font-weight: 800;
            box-shadow: 0 8px 18px rgba(11,107,99,.18);
            transition: background .15s ease, transform .15s ease, box-shadow .15s ease;
        }
        .submit:hover { background: var(--brand-dark); box-shadow: 0 10px 22px rgba(11,107,99,.22); }
        .submit:active { transform: translateY(1px); }
        .submit:focus-visible { outline: 3px solid rgba(11,107,99,.22); outline-offset: 2px; }

        .security {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid var(--line);
            color: #81908f;
            font-size: .73rem;
            line-height: 1.5;
        }
        .security-icon { color: var(--brand); font-size: .82rem; }

        .footer {
            padding: 18px 16px 24px;
            text-align: center;
            color: #8b9998;
            font-size: .72rem;
        }
        .footer strong { color: #6e7d7b; }

        .desktop-showcase { display: none; }

        @media (min-width: 900px) {
            .page {
                display: grid;
                grid-template-columns: minmax(0, 1.05fr) minmax(460px, .95fr);
            }
            .topbar { display: none; }
            .desktop-showcase {
                position: relative;
                display: flex;
                min-height: 100vh;
                overflow: hidden;
                padding: 54px clamp(40px, 6vw, 90px);
                flex-direction: column;
                justify-content: space-between;
                background: linear-gradient(145deg, #064d48 0%, #0b6b63 58%, #149185 100%);
                color: #fff;
            }
            .desktop-showcase::before,
            .desktop-showcase::after {
                content: "";
                position: absolute;
                border: 1px solid rgba(255,255,255,.13);
                border-radius: 50%;
                pointer-events: none;
            }
            .desktop-showcase::before { width: 620px; height: 620px; right: -280px; top: -220px; }
            .desktop-showcase::after { width: 430px; height: 430px; left: -250px; bottom: -220px; }
            .showcase-brand { position: relative; z-index: 1; display: flex; align-items: center; gap: 11px; font-weight: 850; font-size: 1.15rem; }
            .showcase-brand .logo { background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.2); }
            .showcase-copy { position: relative; z-index: 1; max-width: 610px; margin: auto 0; padding: 80px 0; }
            .eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 7px 11px;
                border-radius: 999px;
                background: rgba(255,255,255,.12);
                color: rgba(255,255,255,.9);
                font-size: .76rem;
                font-weight: 750;
            }
            .dot { width: 7px; height: 7px; border-radius: 50%; background: #a9f2df; }
            .showcase-copy h2 { margin: 20px 0 14px; max-width: 600px; font-size: clamp(2.5rem, 4vw, 4.3rem); line-height: 1.03; letter-spacing: -.05em; }
            .showcase-copy p { max-width: 560px; margin: 0; color: rgba(255,255,255,.78); font-size: 1rem; line-height: 1.75; }
            .features { display: grid; gap: 11px; margin: 32px 0 0; padding: 0; list-style: none; }
            .features li { display: flex; align-items: center; gap: 10px; color: rgba(255,255,255,.9); font-size: .88rem; }
            .feature-check { width: 22px; height: 22px; display: grid; place-items: center; border-radius: 7px; background: rgba(255,255,255,.13); }
            .desktop-copy { position: relative; z-index: 1; color: rgba(255,255,255,.55); font-size: .72rem; }

            .auth-area {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
                padding: 48px clamp(34px, 5vw, 76px);
                background: var(--page);
            }
            .topbar { display: none; }
            .content { max-width: 500px; padding: 0; margin: 0 auto; }
            .school-header { text-align: left; margin-bottom: 24px; }
            .school-header-inner { display: flex; align-items: center; gap: 14px; }
            .school-logo { width: 58px; height: 58px; flex-basis: 58px; margin: 0; border-radius: 15px; }
            .school-header h1 { font-size: 1.5rem; }
            .school-header p { margin-top: 4px; }
            .card { padding: 28px; }
            .footer { padding: 22px 0 0; }
        }

        @media (max-width: 380px) {
            .content { padding-left: 12px; padding-right: 12px; }
            .card { padding: 18px 16px; border-radius: 15px; }
            .school-header h1 { font-size: 1.4rem; }
            .options { align-items: flex-start; flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="page">
    <section class="desktop-showcase" aria-label="EduSasa school management platform">
        <div class="showcase-brand">
            <span class="logo">ES</span>
            <span>EduSasa</span>
        </div>
        <div class="showcase-copy">
            <span class="eyebrow"><span class="dot"></span> School management platform</span>
            <h2>Everything your school needs, in one place.</h2>
            <p>Manage academics, students, teachers, attendance, finance, examinations and school communication from one secure system.</p>
            <ul class="features">
                <li><span class="feature-check">✓</span> Secure access for every school role</li>
                <li><span class="feature-check">✓</span> Centralised school operations</li>
                <li><span class="feature-check">✓</span> Built for modern Kenyan schools</li>
            </ul>
        </div>
        <div class="desktop-copy">© <?= date('Y') ?> EduSasa. All rights reserved.</div>
    </section>

    <section class="auth-area">
        <header class="topbar">
            <a class="topbar-brand" href="/" aria-label="EduSasa home">
                <span class="logo">ES</span>
                <span>EduSasa</span>
            </a>
            <span class="topbar-label">Secure school access</span>
        </header>

        <main class="content">
            <div class="school-header">
                <div class="school-header-inner">
                    <div class="school-logo" aria-hidden="true"><?= e($initials) ?></div>
                    <div>
                        <h1><?= e($schoolName) ?></h1>
                        <p>School management portal</p>
                    </div>
                </div>
            </div>

            <section class="card" aria-labelledby="login-title">
                <div class="card-heading">
                    <h2 id="login-title">Welcome back</h2>
                    <p>Sign in to continue to your school dashboard.</p>
                </div>

                <?php if ($errors !== []): ?>
                    <div class="alert" role="alert">
                        <span class="alert-icon">!</span>
                        <div>
                            <strong>Unable to sign you in</strong>
                            <?= e((string) $errors[0]) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post" action="/login" autocomplete="on">
                    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">

                    <div class="field">
                        <label for="email">Email address</label>
                        <div class="input-wrap">
                            <input id="email" name="email" type="email" value="<?= e((string) ($old['email'] ?? '')) ?>" placeholder="you@school.ac.ke" autocomplete="username" required autofocus>
                        </div>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <div class="input-wrap password">
                            <input id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>
                            <button class="toggle-password" type="button" onclick="togglePassword()" aria-label="Show password">Show</button>
                        </div>
                    </div>

                    <div class="options">
                        <label class="remember">
                            <input type="checkbox" name="remember" value="1">
                            <span>Remember me</span>
                        </label>
                        <?php if ($helpEmail !== ''): ?>
                            <a class="help" href="mailto:<?= e($helpEmail) ?>">Need help signing in?</a>
                        <?php endif; ?>
                    </div>

                    <button class="submit" type="submit">Sign in to <?= e($schoolName) ?></button>
                </form>

                <div class="security">
                    <span class="security-icon">🔒</span>
                    <span>Your connection is protected. Never share your password or verification codes with anyone.</span>
                </div>
            </section>
        </main>

        <footer class="footer">Powered by <strong>EduSasa</strong></footer>
    </section>
</div>
<script>
function togglePassword() {
    const input = document.getElementById('password');
    const button = document.querySelector('.toggle-password');
    const visible = input.type === 'text';
    input.type = visible ? 'password' : 'text';
    button.textContent = visible ? 'Show' : 'Hide';
    button.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
}
</script>
</body>
</html>
