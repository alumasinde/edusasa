<?php

declare(strict_types=1);

namespace Modules\Teachers\Services;

use App\Core\AuditLog;
use App\Core\BaseService;
use App\Core\Database;
use App\Core\Mail;
use App\Core\Tenant;
use Modules\Auth\Services\AuthService;
use Modules\Teachers\Repositories\StaffRepository;

class TeacherService extends BaseService
{
    public function __construct(
        private readonly StaffRepository $staff,
        private readonly AuthService $authService,
    ) {}

    public function create(array $data): int
    {
        $employeeNo = trim((string) ($data['employee_no'] ?? ''));
        if (!empty($data['no_employee_no'])) {
            $employeeNo = null;
        } elseif ($employeeNo === '') {
            $employeeNo = $this->staff->nextEmployeeNumber();
        }

        $id = (int) $this->db()->transaction(function () use ($data, $employeeNo): int {
            $userId = null;
            if (!empty($data['create_login']) && !empty($data['email'])) {
                $userId = $this->createLinkedUser((string) $data['email']);
            }

            return $this->staff->create([
                'user_id' => $userId,
                'employee_no' => $employeeNo,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'gender' => $this->blankToNull($data['gender'] ?? null),
                'phone' => $this->blankToNull($data['phone'] ?? null),
                'email' => $this->blankToNull($data['email'] ?? null),
                'department_id' => $this->blankToNull($data['department_id'] ?? null),
                'designation' => $this->blankToNull($data['designation'] ?? null),
                'qualification' => $this->blankToNull($data['qualification'] ?? null),
                'hire_date' => $this->blankToNull($data['hire_date'] ?? null) ?? date('Y-m-d'),
                'status' => 'active',
            ]);
        });

        AuditLog::record('staff.created', 'staff', $id, null, $data);
        if (!empty($data['create_login']) && !empty($data['email'])) {
            $this->sendAccountSetupEmail((string) $data['email']);
        }
        return $id;
    }

    public function update(int $id, array $data): void
    {
        $before = $this->staff->findOrFail($id);
        $this->staff->update($id, [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'gender' => $this->blankToNull($data['gender'] ?? null),
            'phone' => $this->blankToNull($data['phone'] ?? null),
            'email' => $this->blankToNull($data['email'] ?? null),
            'department_id' => $this->blankToNull($data['department_id'] ?? null),
            'designation' => $this->blankToNull($data['designation'] ?? null),
            'qualification' => $this->blankToNull($data['qualification'] ?? null),
            'hire_date' => $this->blankToNull($data['hire_date'] ?? null),
        ]);
        AuditLog::record('staff.updated', 'staff', $id, $before, $data);
    }

    public function setStatus(int $id, string $status): void
    {
        $before = $this->staff->findOrFail($id);
        if ($before['status'] === $status) return;
        $this->db()->transaction(function () use ($id, $status) {
            $this->staff->update($id, ['status' => $status]);
            if ($status === 'inactive') {
                $this->db()->execute(
                    'UPDATE streams SET class_teacher_staff_id = NULL WHERE class_teacher_staff_id = :staff_id',
                    ['staff_id' => $id]
                );
            }
        });
        AuditLog::record('staff.status_changed', 'staff', $id, ['status' => $before['status']], ['status' => $status]);
    }

    public function delete(int $id): void
    {
        $before = $this->staff->findOrFail($id);
        $this->db()->transaction(function () use ($id) {
            $this->db()->execute('UPDATE streams SET class_teacher_staff_id = NULL WHERE class_teacher_staff_id = :staff_id', ['staff_id' => $id]);
            $this->staff->delete($id);
        });
        AuditLog::record('staff.deleted', 'staff', $id, $before, null);
    }

    private function createLinkedUser(string $email): int
    {
        $db = Database::getInstance();
        $schoolId = Tenant::id();
        $existing = $db->selectOne(
            'SELECT id FROM users WHERE school_id = :school_id AND email = :email AND deleted_at IS NULL',
            ['school_id' => $schoolId, 'email' => $email]
        );
        if ($existing !== null) return (int) $existing['id'];
        $userId = (int) $db->insert(
            'INSERT INTO users (school_id, email, status) VALUES (:school_id, :email, :status)',
            ['school_id' => $schoolId, 'email' => $email, 'status' => 'active']
        );
        $role = $db->selectOne("SELECT id FROM roles WHERE name = 'teacher'");
        if ($role !== null) {
            $db->execute('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)', ['user_id' => $userId, 'role_id' => $role['id']]);
        }
        return $userId;
    }

    private function sendAccountSetupEmail(string $email): void
    {
        $token = $this->authService->issuePasswordResetToken($email);
        if ($token === null) return;
        $tenant = Tenant::current();
        $resetUrl = 'https://' . ($tenant?->subdomain ?? '') . '/reset-password?token=' . $token;
        Mail::send($email, 'Set up your school account', '<p>An account has been created for you. Use the link below to set your password.</p><p><a href="' . e($resetUrl) . '">' . e($resetUrl) . '</a></p><p>This link expires in 60 minutes.</p>');
    }

    private function blankToNull(mixed $value): mixed
    {
        return ($value === '' || $value === null) ? null : $value;
    }
}
