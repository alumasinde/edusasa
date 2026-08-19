<?php

declare(strict_types=1);

namespace App\Core;

final class Mail
{
    public static function send(string $to, string $subject, string $html): bool
    {
        $to = trim($to);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('A valid recipient email address is required.');
        }

        $from = trim((string) Config::env('MAIL_FROM_ADDRESS', ''));
        $fromName = trim((string) Config::env('MAIL_FROM_NAME', Config::env('APP_NAME', 'EduSasa')));

        if ($from === '') {
            Logger::warning('Email not sent because MAIL_FROM_ADDRESS is not configured.', [
                'to' => $to,
                'subject' => $subject,
            ]);
            return false;
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . ($fromName !== '' ? sprintf('%s <%s>', $fromName, $from) : $from),
            'Reply-To: ' . $from,
        ];

        $sent = mail($to, $subject, $html, implode("\r\n", $headers));

        if (!$sent) {
            Logger::error('Email delivery failed.', ['to' => $to, 'subject' => $subject]);
        }

        return $sent;
    }
}
