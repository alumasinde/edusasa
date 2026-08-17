<?php

declare(strict_types=1);

namespace Modules\Platform\Services;

use App\Core\Database;
use App\Core\Session;
use InvalidArgumentException;
use RuntimeException;

final class SchoolAdminActivationService
{
    public function __construct(private readonly Database $db) {}

    public function invitation(string $token): ?array
    {
        $hash = hash('sha256', trim($token));
        return $this->db->selectOne(
            'SELECT i.id,i.school_id,i.email,i.expires_at,i.accepted_at,s.name AS school_name,s.status AS school_status
             FROM school_admin_invitations i INNER JOIN schools s ON s.id=i.school_id
             WHERE i.token_hash=:token_hash LIMIT 1',
            ['token_hash' => $hash]
        );
    }

    public function activate(string $token, string $firstName, string $lastName, string $password, string $confirmation): array
    {
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        $token = trim($token);
        if ($firstName === '' || $lastName === '') throw new InvalidArgumentException('First name and last name are required.');
        if ($password !== $confirmation) throw new InvalidArgumentException('Passwords do not match.');
        $this->validatePassword($password);
        if ($token === '') throw new InvalidArgumentException('Invitation token is required.');

        return $this->db->transaction(function (Database $db) use ($token, $firstName, $lastName, $password): array {
            $invitation = $db->selectOne(
                'SELECT i.id,i.school_id,i.email,i.expires_at,i.accepted_at,s.name AS school_name,s.status AS school_status
                 FROM school_admin_invitations i INNER JOIN schools s ON s.id=i.school_id
                 WHERE i.token_hash=:token_hash LIMIT 1 FOR UPDATE',
                ['token_hash' => hash('sha256', $token)]
            );
            if ($invitation === null) throw new InvalidArgumentException('This invitation is invalid or has expired.');
            if ($invitation['accepted_at'] !== null) throw new InvalidArgumentException('This invitation has already been used.');
            if (strtotime((string) $invitation['expires_at']) <= time()) throw new InvalidArgumentException('This invitation has expired.');
            if ($invitation['school_status'] === 'archived') throw new InvalidArgumentException('This school is archived.');

            $columns = $this->columns('users');
            $passwordColumn = in_array('password_hash', $columns, true) ? 'password_hash' : (in_array('password', $columns, true) ? 'password' : null);
            foreach (['email','school_id'] as $required) {
                if (!in_array($required, $columns, true)) throw new RuntimeException("Users table is missing required column: {$required}");
            }
            if ($passwordColumn === null) throw new RuntimeException('Users table has no supported password column.');

            $emailWhere = 'email=:email AND school_id=:school_id';
            if (in_array('deleted_at', $columns, true)) $emailWhere .= ' AND deleted_at IS NULL';
            $existing = $db->selectOne("SELECT id FROM users WHERE {$emailWhere} LIMIT 1", [
                'email' => $invitation['email'], 'school_id' => $invitation['school_id'],
            ]);
            if ($existing !== null) throw new InvalidArgumentException('An account already exists for this administrator.');

            $data = [
                'email' => $invitation['email'],
                'school_id' => (int) $invitation['school_id'],
                $passwordColumn => password_hash($password, PASSWORD_DEFAULT),
            ];
            if (in_array('first_name', $columns, true)) $data['first_name'] = $firstName;
            if (in_array('last_name', $columns, true)) $data['last_name'] = $lastName;
            if (in_array('name', $columns, true)) $data['name'] = trim($firstName . ' ' . $lastName);
            if (in_array('status', $columns, true)) $data['status'] = 'active';
            if (in_array('email_verified_at', $columns, true)) $data['email_verified_at'] = date('Y-m-d H:i:s');
            if (in_array('created_at', $columns, true)) $data['created_at'] = date('Y-m-d H:i:s');
            if (in_array('updated_at', $columns, true)) $data['updated_at'] = date('Y-m-d H:i:s');

            $userId = (int) $db->insert(
                'INSERT INTO users (' . implode(',', array_map([$this, 'identifier'], array_keys($data))) . ') VALUES (' . implode(',', array_map(static fn(string $key): string => ':' . $key, array_keys($data))) . ')',
                $data
            );

            $roleId = $this->schoolAdminRole($db);
            $userRoleColumns = $this->columns('user_roles');
            if (!in_array('user_id', $userRoleColumns, true) || !in_array('role_id', $userRoleColumns, true)) {
                throw new RuntimeException('User-role mapping table is missing required columns.');
            }
            $db->insert('INSERT INTO user_roles(user_id,role_id) VALUES(:user_id,:role_id)', ['user_id'=>$userId,'role_id'=>$roleId]);

            $db->execute('UPDATE school_admin_invitations SET accepted_at=NOW() WHERE id=:id', ['id'=>$invitation['id']]);
            $db->execute("UPDATE schools SET status='active' WHERE id=:id AND status='pending'", ['id'=>$invitation['school_id']]);
            $db->insert('INSERT INTO platform_audit_logs(school_id,action,resource_type,resource_id,metadata_json) VALUES(:school_id,:action,:type,:resource_id,:metadata)', [
                'school_id'=>$invitation['school_id'], 'action'=>'school_admin.activated', 'type'=>'user', 'resource_id'=>$userId,
                'metadata'=>json_encode(['email'=>$invitation['email']], JSON_THROW_ON_ERROR),
            ]);

            Session::regenerate();
            Session::set('user_id', $userId);
            Session::set('school_id', (int) $invitation['school_id']);
            return ['user_id'=>$userId,'school_id'=>(int)$invitation['school_id'],'school_name'=>$invitation['school_name']];
        });
    }

    private function schoolAdminRole(Database $db): int
    {
        $columns = $this->columns('roles');
        if (!in_array('name', $columns, true)) throw new RuntimeException('Roles table is missing the name column.');
        $conditions = ['name IN (\'School Admin\',\'School Administrator\',\'school_admin\')'];
        $params = [];
        if (in_array('code', $columns, true)) $conditions[] = 'code=:role_code';
        if (in_array('slug', $columns, true)) $conditions[] = 'slug=:role_slug';
        if (in_array('code', $columns, true)) $params['role_code']='school_admin';
        if (in_array('slug', $columns, true)) $params['role_slug']='school-admin';
        $role = $db->selectOne('SELECT id FROM roles WHERE ' . implode(' OR ', $conditions) . ' ORDER BY id LIMIT 1', $params);
        if ($role !== null) return (int) $role['id'];

        $data = ['name'=>'School Admin'];
        if (in_array('code', $columns, true)) $data['code']='school_admin';
        if (in_array('slug', $columns, true)) $data['slug']='school-admin';
        if (in_array('description', $columns, true)) $data['description']='Full administration access within a school tenant.';
        if (in_array('is_active', $columns, true)) $data['is_active']=1;
        if (in_array('status', $columns, true)) $data['status']='active';
        return (int) $db->insert(
            'INSERT INTO roles (' . implode(',', array_map([$this, 'identifier'], array_keys($data))) . ') VALUES (' . implode(',', array_map(static fn(string $key): string => ':' . $key, array_keys($data))) . ')',
            $data
        );
    }

    private function columns(string $table): array
    {
        $rows = $this->db->select('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table', ['table'=>$table]);
        return array_map(static fn(array $row): string => (string) $row['COLUMN_NAME'], $rows);
    }

    private function identifier(string $value): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value)) throw new RuntimeException('Invalid database identifier.');
        return '`' . $value . '`';
    }

    private function validatePassword(string $password): void
    {
        if (strlen($password) < 12 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
            throw new InvalidArgumentException('Password must be at least 12 characters and include upper-case, lower-case and a number.');
        }
    }
}
