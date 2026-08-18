<?php

declare(strict_types=1);

namespace Modules\Teachers\Services;

use App\Core\Database;
use App\Core\Tenant;
use RuntimeException;

final class TeacherPhase10Service
{
    public function __construct(private readonly Database $db) {}

    public function list(): array
    {
        return $this->db->fetchAll('SELECT t.* FROM teachers t WHERE t.school_id = ? ORDER BY t.last_name, t.first_name', [Tenant::id()]);
    }

    public function find(int $id): array
    {
        $row = $this->db->fetch('SELECT t.* FROM teachers t WHERE t.id = ? AND t.school_id = ?', [$id, Tenant::id()]);
        if (!$row) throw new RuntimeException('Teacher not found.');
        return $row;
    }

    public function create(array $data): int
    {
        $first = trim((string)($data['first_name'] ?? ''));
        $last = trim((string)($data['last_name'] ?? ''));
        if ($first === '' || $last === '') throw new RuntimeException('First name and last name are required.');
        $this->db->execute('INSERT INTO teachers (school_id, employee_no, first_name, last_name, email, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?)', [Tenant::id(), trim((string)($data['employee_no'] ?? '')) ?: null, $first, $last, trim((string)($data['email'] ?? '')) ?: null, trim((string)($data['phone'] ?? '')) ?: null, $this->status((string)($data['status'] ?? 'active'))]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $this->find($id);
        $first = trim((string)($data['first_name'] ?? ''));
        $last = trim((string)($data['last_name'] ?? ''));
        if ($first === '' || $last === '') throw new RuntimeException('First name and last name are required.');
        $this->db->execute('UPDATE teachers SET employee_no=?, first_name=?, last_name=?, email=?, phone=?, status=? WHERE id=? AND school_id=?', [trim((string)($data['employee_no'] ?? '')) ?: null, $first, $last, trim((string)($data['email'] ?? '')) ?: null, trim((string)($data['phone'] ?? '')) ?: null, $this->status((string)($data['status'] ?? 'active')), $id, Tenant::id()]);
    }

    public function changeStatus(int $id, string $status): void
    {
        $this->find($id);
        $this->db->execute('UPDATE teachers SET status=? WHERE id=? AND school_id=?', [$this->status($status), $id, Tenant::id()]);
    }

    public function subjects(int $id): array
    {
        $this->find($id);
        return $this->db->fetchAll('SELECT s.* FROM subjects s JOIN teacher_subjects ts ON ts.subject_id=s.id WHERE ts.teacher_id=? AND s.school_id=? ORDER BY s.name', [$id, Tenant::id()]);
    }

    public function classes(int $id): array
    {
        $this->find($id);
        return $this->db->fetchAll('SELECT c.* FROM school_classes c JOIN teacher_classes tc ON tc.class_id=c.id WHERE tc.teacher_id=? AND c.school_id=? ORDER BY c.name', [$id, Tenant::id()]);
    }

    public function assignSubjects(int $id, array $subjectIds): void
    {
        $this->find($id);
        $ids = $this->ownedIds('subjects', $subjectIds);
        $this->db->transaction(function () use ($id, $ids): void {
            $this->db->execute('DELETE FROM teacher_subjects WHERE teacher_id=?', [$id]);
            foreach ($ids as $subjectId) $this->db->execute('INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)', [$id, $subjectId]);
        });
    }

    public function assignClasses(int $id, array $classIds): void
    {
        $this->find($id);
        $ids = $this->ownedIds('school_classes', $classIds);
        $this->db->transaction(function () use ($id, $ids): void {
            $this->db->execute('DELETE FROM teacher_classes WHERE teacher_id=?', [$id]);
            foreach ($ids as $classId) $this->db->execute('INSERT INTO teacher_classes (teacher_id, class_id) VALUES (?, ?)', [$id, $classId]);
        });
    }

    private function ownedIds(string $table, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $v): bool => $v > 0)));
        if (!$ids) return [];
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->db->fetchAll("SELECT id FROM {$table} WHERE school_id=? AND id IN ({$ph})", array_merge([Tenant::id()], $ids));
        return array_map(static fn(array $r): int => (int)$r['id'], $rows);
    }

    private function status(string $value): string
    {
        return in_array($value, ['active','inactive','suspended'], true) ? $value : 'active';
    }
}
