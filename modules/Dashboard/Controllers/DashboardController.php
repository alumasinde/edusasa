<?php

declare(strict_types=1);

namespace Modules\Dashboard\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Tenant;

final class DashboardController
{
    public function __construct(private readonly Database $db) {}

    public function index(Request $request): Response
    {
        $schoolId = Tenant::id();
        $stats = [
            'students' => $this->count("SELECT COUNT(*) FROM students WHERE school_id=:school_id AND deleted_at IS NULL AND status='active'", $schoolId),
            'teachers' => $this->count("SELECT COUNT(*) FROM teachers WHERE school_id=:school_id AND deleted_at IS NULL AND status='active'", $schoolId),
            'classes' => $this->count("SELECT COUNT(*) FROM classes WHERE school_id=:school_id AND deleted_at IS NULL", $schoolId),
            'attendance_today' => $this->count("SELECT COUNT(*) FROM attendance WHERE school_id=:school_id AND attendance_date=CURRENT_DATE AND deleted_at IS NULL", $schoolId),
            'outstanding_fees' => $this->sum("SELECT COALESCE(SUM(balance),0) FROM fee_invoices WHERE school_id=:school_id AND deleted_at IS NULL AND status<>'draft'", $schoolId),
            'published_exams' => $this->count("SELECT COUNT(*) FROM examinations WHERE school_id=:school_id AND status='published'", $schoolId),
            'published_timetables' => $this->count("SELECT COUNT(*) FROM timetables WHERE school_id=:school_id AND status='published'", $schoolId),
            'unread_communications' => $this->unreadCommunications($schoolId),
        ];

        $attendance = $this->db->selectOne(
            "SELECT
                SUM(status='present') AS present,
                SUM(status='absent') AS absent,
                SUM(status='late') AS late,
                SUM(status='excused') AS excused,
                COUNT(*) AS total
             FROM attendance
             WHERE school_id=:school_id AND attendance_date=CURRENT_DATE AND deleted_at IS NULL",
            ['school_id' => $schoolId]
        ) ?? [];

        $recentAnnouncements = $this->db->select(
            "SELECT id,title,type,published_at
             FROM communications
             WHERE school_id=:school_id AND status='published'
             ORDER BY published_at DESC,id DESC LIMIT 8",
            ['school_id' => $schoolId]
        );

        return Response::view('dashboard.index', [
            'stats' => $stats,
            'attendance' => $attendance,
            'recentAnnouncements' => $recentAnnouncements,
        ]);
    }

    private function count(string $sql, int $schoolId): int
    {
        $row = $this->db->selectOne($sql, ['school_id' => $schoolId]);
        return (int) ($row['COUNT(*)'] ?? 0);
    }

    private function sum(string $sql, int $schoolId): float
    {
        $row = $this->db->selectOne($sql, ['school_id' => $schoolId]);
        return (float) ($row['COALESCE(SUM(balance),0)'] ?? 0);
    }

    private function unreadCommunications(int $schoolId): int
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) return 0;
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS total
             FROM communication_recipients r
             INNER JOIN communications c ON c.id=r.communication_id
             WHERE r.user_id=:user_id AND c.school_id=:school_id
             AND c.status='published' AND r.read_at IS NULL",
            ['user_id' => $userId, 'school_id' => $schoolId]
        );
        return (int) ($row['total'] ?? 0);
    }
}
