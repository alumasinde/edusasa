<?php
declare(strict_types=1);

namespace Modules\Exams\Services;

use App\Core\Database;
use App\Core\Tenant;
use RuntimeException;

final class ExaminationAnalyticsService
{
    public function __construct(private readonly Database $db) {}

    private function exam(int $id): array
    {
        $exam = $this->db->selectOne(
            'SELECT id,name,school_id,status,academic_year_id,term_id
             FROM examinations
             WHERE id=:id AND school_id=:school_id',
            ['id' => $id, 'school_id' => Tenant::id()]
        );

        if (!$exam) {
            throw new RuntimeException('Examination not found.');
        }

        if (!in_array($exam['status'], ['closed', 'published'], true)) {
            throw new RuntimeException('Analytics are available after the examination is closed.');
        }

        return $exam;
    }

    public function dashboard(int $examId): array
    {
        $exam = $this->exam($examId);
        $params = ['school_id' => Tenant::id(), 'exam_id' => $examId];

        $summary = $this->db->selectOne(
            'SELECT COUNT(*) students,
                    ROUND(AVG(percentage),2) average,
                    ROUND(MAX(percentage),2) highest,
                    ROUND(MIN(percentage),2) lowest,
                    SUM(CASE WHEN percentage >= 50 THEN 1 ELSE 0 END) passed,
                    SUM(CASE WHEN percentage < 50 THEN 1 ELSE 0 END) failed
             FROM examination_results
             WHERE school_id=:school_id
               AND examination_id=:exam_id
               AND status=\'published\'',
            $params
        );

        $subjects = $this->db->select(
            'SELECT s.id subject_id, s.name subject,
                    COUNT(m.id) students,
                    ROUND(AVG(m.percentage),2) average,
                    ROUND(MAX(m.percentage),2) highest,
                    ROUND(MIN(m.percentage),2) lowest,
                    SUM(CASE WHEN m.percentage >= 50 THEN 1 ELSE 0 END) passed
             FROM examination_report_card_subjects m
             JOIN examination_report_cards c
               ON c.id=m.report_card_id
              AND c.school_id=:school_id
              AND c.examination_id=:exam_id
              AND c.status=\'published\'
             JOIN subjects s ON s.id=m.subject_id
             GROUP BY s.id,s.name
             ORDER BY average DESC',
            $params
        );

        $grades = $this->db->select(
            'SELECT grade, COUNT(*) students
             FROM examination_results
             WHERE school_id=:school_id
               AND examination_id=:exam_id
               AND status=\'published\'
             GROUP BY grade ORDER BY grade',
            $params
        );

        $top = $this->db->select(
            'SELECT r.student_id,s.admission_no,s.first_name,s.middle_name,s.last_name,
                    r.percentage,r.grade,r.points
             FROM examination_results r
             JOIN students s ON s.id=r.student_id AND s.school_id=r.school_id
             WHERE r.school_id=:school_id
               AND r.examination_id=:exam_id
               AND r.status=\'published\'
             ORDER BY r.percentage DESC,r.student_id LIMIT 10',
            $params
        );

        $support = $this->db->select(
            'SELECT r.student_id,s.admission_no,s.first_name,s.middle_name,s.last_name,
                    r.percentage,r.grade,r.remark
             FROM examination_results r
             JOIN students s ON s.id=r.student_id AND s.school_id=r.school_id
             WHERE r.school_id=:school_id
               AND r.examination_id=:exam_id
               AND r.status=\'published\'
             ORDER BY r.percentage ASC,r.student_id LIMIT 10',
            $params
        );

        return compact('exam', 'summary', 'subjects', 'grades', 'top', 'support');
    }

    public function csv(int $examId): string
    {
        $this->exam($examId);

        $rows = $this->db->select(
            'SELECT s.admission_no,
                    CONCAT_WS(\' \',s.first_name,s.middle_name,s.last_name) student,
                    r.total_marks,r.maximum_marks,r.percentage,r.grade,r.points,r.remark
             FROM examination_results r
             JOIN students s ON s.id=r.student_id AND s.school_id=r.school_id
             WHERE r.school_id=:school_id
               AND r.examination_id=:exam_id
               AND r.status=\'published\'
             ORDER BY r.percentage DESC',
            ['school_id' => Tenant::id(), 'exam_id' => $examId]
        );

        $out = fopen('php://temp', 'r+');
        fputcsv($out, [
            'Admission No','Student','Total Marks','Maximum Marks',
            'Percentage','Grade','Points','Remark'
        ]);

        foreach ($rows as $row) {
            fputcsv($out, $row);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $csv;
    }
}
