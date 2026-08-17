<?php

declare(strict_types=1);

namespace Modules\Teachers\Repositories;

use App\Core\BaseRepository;
use App\Core\Tenant;

class StaffRepository extends BaseRepository
{
    protected function table(): string { return 'staff'; }

    public function active(): array
    {
        return $this->where(['status' => 'active'], 'first_name ASC, last_name ASC');
    }

    public function findByUserId(int $userId): ?array
    {
        return $this->whereFirst(['user_id' => $userId]);
    }

    public function search(array $filters, int $page = 1, int $perPage = 25): array
    {
        [$where, $params] = $this->buildFilterClause($filters);
        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT s.*, d.name AS department_name FROM staff s LEFT JOIN departments d ON d.id = s.department_id {$where} ORDER BY s.first_name ASC, s.last_name ASC LIMIT {$perPage} OFFSET {$offset}";
        return $this->db->select($sql, $params);
    }

    public function countFiltered(array $filters): int
    {
        [$where, $params] = $this->buildFilterClause($filters);
        $row = $this->db->selectOne("SELECT COUNT(*) AS total FROM staff s {$where}", $params);
        return (int) ($row['total'] ?? 0);
    }

    private function buildFilterClause(array $filters): array
    {
        $conditions = ['s.school_id = :school_id', 's.deleted_at IS NULL'];
        $params = ['school_id' => Tenant::id()];
        if (!empty($filters['q'])) {
            $conditions[] = '(s.first_name LIKE :q1 OR s.last_name LIKE :q2 OR s.employee_no LIKE :q3)';
            $like = '%' . $filters['q'] . '%';
            $params['q1'] = $like; $params['q2'] = $like; $params['q3'] = $like;
        }
        if (!empty($filters['department_id'])) {
            $conditions[] = 's.department_id = :department_id';
            $params['department_id'] = (int) $filters['department_id'];
        }
        if (!empty($filters['status'])) {
            $conditions[] = 's.status = :status';
            $params['status'] = $filters['status'];
        }
        return ['WHERE ' . implode(' AND ', $conditions), $params];
    }

    public function findWithDepartment(int $id): ?array
    {
        return $this->db->selectOne('SELECT s.*, d.name AS department_name FROM staff s LEFT JOIN departments d ON d.id = s.department_id WHERE s.id = :id AND s.school_id = :school_id AND s.deleted_at IS NULL', ['id' => $id, 'school_id' => Tenant::id()]);
    }

    public function nextEmployeeNumber(): string
    {
        $tenant = Tenant::current();
        $prefix = $tenant?->employeeNoPrefix ?? 'EMP';
        $padding = $tenant?->employeeNoPadding ?? 4;
        $row = $this->db->selectOne('SELECT employee_no FROM staff WHERE school_id = :school_id AND employee_no LIKE :prefix ORDER BY id DESC LIMIT 1', ['school_id' => Tenant::id(), 'prefix' => $prefix . '%']);
        $last = 0;
        if ($row !== null && preg_match('/(\d+)$/', (string) $row['employee_no'], $m)) $last = (int) $m[1];
        return $prefix . str_pad((string) ($last + 1), $padding, '0', STR_PAD_LEFT);
    }

    public function classTeacherOf(int $staffId): array
    {
        return $this->db->select('SELECT st.id, st.name AS stream_name, c.name AS class_name FROM streams st INNER JOIN classes c ON c.id = st.class_id WHERE st.class_teacher_staff_id = :staff_id AND st.deleted_at IS NULL', ['staff_id' => $staffId]);
    }
}
