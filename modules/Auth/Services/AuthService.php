<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use App\Core\Database;
use RuntimeException;

final class AuthService
{
    public function __construct(private readonly Database $db) {}

    /**
     * Issues a one-time password reset token for an active school user.
     * Only the SHA-256 hash is persisted; the raw token is returned once.
     */
    public function issuePasswordResetToken(string $email): ?string
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $schoolId = $this->schoolId();
        $user = $this->db->selectOne(
            'SELECT id FROM users
             WHERE school_id=:school_id AND email=:email
               AND status=\'active\' AND deleted_at IS NULL
             LIMIT 1',
            ['school_id' => $schoolId, 'email' => $email],
        );

        if ($user === null) {
            return null;
        }

        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $hash = hash('sha256', $token);

        $this->db->transaction(function (Database $db) use ($schoolId, $user, $hash): void {
            $db->execute(
                'UPDATE password_reset_tokens
                 SET used_at=NOW()
                 WHERE school_id=:school_id AND user_id=:user_id AND used_at IS NULL',
                ['school_id' => $schoolId, 'user_id' => (int) $user['id']],
            );
            $db->insert(
                'INSERT INTO password_reset_tokens
                    (school_id,user_id,token_hash,expires_at)
                 VALUES
                    (:school_id,:user_id,:token_hash,DATE_ADD(NOW(), INTERVAL 60 MINUTE))',
                [
                    'school_id' => $schoolId,
                    'user_id' => (int) $user['id'],
                    'token_hash' => $hash,
                ],
            );
        });

        return $token;
    }

    private function schoolId(): int
    {
        $schoolId = \App\Core\Tenant::id();
        if ($schoolId < 1) {
            throw new RuntimeException('A school tenant is required.');
        }
        return $schoolId;
    }
}
