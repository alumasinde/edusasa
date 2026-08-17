<?php

declare(strict_types=1);

namespace Modules\Teachers\Services;

use App\Core\AuditLog;
use App\Core\BaseService;
use App\Core\Database;
use App\Core\Tenant;
use Modules\Teachers\Repositories\StaffRepository;

final class TeacherImportService extends BaseService
{
    public function __construct(private readonly StaffRepository $staff) {}

    public function validate(array $rows): array
    {
        $errors = [];
        $preview = [];
        $seenEmails = [];
        $seenEmployeeNos = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $first = trim((string) ($row['first_name'] ?? ''));
            $last = trim((string) ($row['last_name'] ?? ''));
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            $employeeNo = trim((string) ($row['employee_no'] ?? ''));

            if ($first === '' || $last === '') {
                $errors[] = ['line' => $line, 'field' => 'name', 'message' => 'First name and last name are required.'];
            }
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = ['line' => $line, 'field' => 'email', 'message' => 'Invalid email address.'];
            }
            if ($email !== '' && isset($seenEmails[$email])) {
                $errors[] = ['line' => $line, 'field' => 'email', 'message' => 'Duplicate email in import.'];
            }
            if ($employeeNo !== '' && isset($seenEmployeeNos[$employeeNo])) {
                $errors[] = ['line' => $line, 'field' => 'employee_no', 'message' => 'Duplicate employee number in import.'];
            }
            if ($email !== '') $seenEmails[$email] = true;
            if ($employeeNo !== '') $seenEmployeeNos[$employeeNo] = true;

            $preview[] = [
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email !== '' ? $email : null,
                'employee_no' => $employeeNo !== '' ? $employeeNo : null,
                'phone' => trim((string) ($row['phone'] ?? '')) ?: null,
                'designation' => trim((string) ($row['designation'] ?? '')) ?: null,
                'department_id' => ($row['department_id'] ?? '') !== '' ? (int) $row['department_id'] : null,
            ];
        }

        return ['valid' => $errors === [], 'errors' => $errors, 'preview' => $preview];
    }

    public function import(array $rows): int
    {
        $validation = $this->validate($rows);
        if (!$validation['valid']) {
            throw new \InvalidArgumentException('Teacher import contains validation errors.');
        }

        $db = Database::getInstance();
        $schoolId = Tenant::id();
        $count = 0;
        $db->transaction(function () use ($validation, $schoolId, $db, &$count): void {
            foreach ($validation['preview'] as $row) {
                $existing = $row['employee_no'] !== null
                    ? $db->selectOne('SELECT id FROM staff WHERE school_id = :school_id AND employee_no = :employee_no AND deleted_at IS NULL', ['school_id' => $schoolId, 'employee_no' => $row['employee_no']])
                    : null;
                if ($existing !== null) continue;

                $this->staff->create([
                    'employee_no' => $row['employee_no'] ?? $this->staff->nextEmployeeNumber(),
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'email' => $row['email'],
                    'phone' => $row['phone'],
                    'designation' => $row['designation'],
                    'department_id' => $row['department_id'],
                    'hire_date' => date('Y-m-d'),
                    'status' => 'active',
                ]);
                $count++;
            }
        });

        AuditLog::record('staff.imported', 'staff', null, null, ['count' => $count]);
        return $count;
    }
}
