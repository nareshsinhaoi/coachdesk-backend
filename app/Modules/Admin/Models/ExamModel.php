<?php

namespace App\Modules\Admin\Models;

use CodeIgniter\Model;

/**
 * Manages exams, exam_subjects, exam_results, exam_result_details.
 */
class ExamModel extends Model
{
    protected $table         = 'exams';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes= true;
    protected $allowedFields = [
        'public_id', 'tenant_id', 'branch_id', 'batch_id',
        'exam_name', 'exam_date', 'status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'tenant_id' => 'required|integer',
        'batch_id'  => 'required|integer',
        'exam_name' => 'required|min_length[2]|max_length[150]',
        'exam_date' => 'required|valid_date[Y-m-d]',
        'status'    => 'in_list[scheduled,ongoing,completed,published]',
    ];

    protected $allowCallbacks = true;
    protected $beforeInsert   = ['generatePublicId'];
    protected function generatePublicId(array $data): array
    {
        if (empty($data['data']['public_id'])) {
            $data['data']['public_id'] = $this->uuid4();
        }
        return $data;
    }

    /* -------- Finders -------- */

    public function findByPublicId(int $tenantId, string $publicId): ?array
    {
        return $this->where('tenant_id', $tenantId)
                    ->where('public_id', $publicId)
                    ->where('deleted_at IS NULL')
                    ->first();
    }

    public function listForTenant(int $tenantId, ?int $branchId = null, array $f = [], int $perPage = 20): array
    {
        $b = $this->db->table('exams e')
            ->select('e.*, b.batch_name,
                      (SELECT COUNT(*) FROM exam_results WHERE exam_id = e.id) AS result_count')
            ->join('batches b', 'b.id = e.batch_id')
            ->where('e.tenant_id', $tenantId)
            ->where('e.deleted_at IS NULL');

        if ($branchId) $b->where('e.branch_id', $branchId);
        if (! empty($f['status'])) $b->where('e.status', $f['status']);
        if (! empty($f['search'])) $b->like('e.exam_name', $f['search']);

        $b->orderBy('e.exam_date', 'desc');
        $total = (clone $b)->countAllResults(false);
        $rows  = $b->limit($perPage, max(0, ((int) ($f['page'] ?? 1)) - 1) * $perPage)->get()->getResultArray();

        return ['items' => $rows, 'total' => $total, 'page' => (int) ($f['page'] ?? 1), 'totalPages' => (int) ceil($total / $perPage)];
    }

    public function upcoming(int $tenantId, int $limit = 5): array
    {
        return $this->db->table('exams e')
            ->select('e.public_id, e.exam_name, e.exam_date, b.batch_name')
            ->join('batches b', 'b.id = e.batch_id')
            ->where('e.tenant_id', $tenantId)
            ->where('e.exam_date >=', date('Y-m-d'))
            ->where('e.deleted_at IS NULL')
            ->orderBy('e.exam_date', 'asc')
            ->limit($limit)
            ->get()->getResultArray();
    }

    /* -------- Subjects -------- */

    public function getSubjects(int $examId): array
    {
        return $this->db->table('exam_subjects es')
            ->select('es.*, s.subject_name, s.subject_code')
            ->join('subjects s', 's.id = es.subject_id')
            ->where('es.exam_id', $examId)
            ->get()->getResultArray();
    }

    public function addSubject(int $examId, int $subjectId, int $maxMarks = 100, int $passMarks = 33): bool
    {
        $exists = $this->db->table('exam_subjects')
            ->where(['exam_id' => $examId, 'subject_id' => $subjectId])
            ->countAllResults();
        if ($exists) return false;
        $this->db->table('exam_subjects')->insert([
            'exam_id'       => $examId,
            'subject_id'    => $subjectId,
            'maximum_marks' => $maxMarks,
            'passing_marks' => $passMarks,
        ]);
        return true;
    }

    /* -------- Marks Entry -------- */

    /**
     * Save obtained marks for one student across all subjects of the exam.
     * $marks = [ subject_id => obtained ]
     */
    public function saveStudentMarks(int $tenantId, int $examId, int $studentId, array $marks): array
    {
        $this->db->transStart();

        $result = $this->db->table('exam_results')
            ->where(['exam_id' => $examId, 'student_id' => $studentId])
            ->get()->getRow();

        if (! $result) {
            $this->db->table('exam_results')->insert([
                'public_id'  => $this->uuid4(),
                'tenant_id'  => $tenantId,
                'exam_id'    => $examId,
                'student_id' => $studentId,
                'total_marks'=> 0,
                'percentage' => 0,
                'status'     => 'draft',
            ]);
            $resultId = (int) $this->db->insertID();
        } else {
            $resultId = (int) $result->id;
        }

        foreach ($marks as $subjectId => $obtained) {
            $existing = $this->db->table('exam_result_details')
                ->where(['result_id' => $resultId, 'subject_id' => (int) $subjectId])
                ->get()->getRow();
            if ($existing) {
                $this->db->table('exam_result_details')->where('id', $existing->id)
                    ->update(['obtained_marks' => (float) $obtained]);
            } else {
                $this->db->table('exam_result_details')->insert([
                    'result_id'      => $resultId,
                    'subject_id'     => (int) $subjectId,
                    'obtained_marks' => (float) $obtained,
                ]);
            }
        }

        $this->recalculateResult($resultId);

        $this->db->transComplete();
        return ['ok' => $this->db->transStatus(), 'result_id' => $resultId];
    }

    /**
     * Recalculate total, percentage, grade for a result.
     * Fetches max marks from exam_subjects.
     */
    public function recalculateResult(int $resultId): void
    {
        $result = $this->db->table('exam_results')->where('id', $resultId)->get()->getRow();
        if (! $result) return;

        $rows = $this->db->table('exam_result_details erd')
            ->select('erd.obtained_marks, es.maximum_marks')
            ->join('exam_subjects es', 'es.exam_id = ' . (int) $result->exam_id . ' AND es.subject_id = erd.subject_id')
            ->where('erd.result_id', $resultId)
            ->get()->getResultArray();

        $totalObtained = 0.0;
        $totalMax = 0.0;
        foreach ($rows as $r) {
            $totalObtained += (float) $r['obtained_marks'];
            $totalMax += (float) $r['maximum_marks'];
        }
        $percentage = $totalMax > 0 ? round(($totalObtained / $totalMax) * 100, 2) : 0;
        $grade = $this->gradeFor($percentage);

        $this->db->table('exam_results')->where('id', $resultId)->update([
            'total_marks' => $totalObtained,
            'percentage'  => $percentage,
            'grade'       => $grade,
        ]);
    }

    protected function gradeFor(float $pct): string
    {
        if ($pct >= 90) return 'A+';
        if ($pct >= 80) return 'A';
        if ($pct >= 70) return 'B';
        if ($pct >= 60) return 'C';
        if ($pct >= 50) return 'D';
        if ($pct >= 33) return 'E';
        return 'F';
    }

    /* -------- Publish + Rankings -------- */

    public function publishResults(int $examId): bool
    {
        // Compute ranks for non-absent students ordered by total_marks
        $rows = $this->db->table('exam_results')
            ->where('exam_id', $examId)
            ->where('status', 'draft')
            ->orderBy('total_marks', 'desc')
            ->get()->getResultArray();

        $rank = 1;
        $this->db->transStart();
        foreach ($rows as $r) {
            $this->db->table('exam_results')->where('id', $r['id'])->update([
                'rank_no' => $rank++,
                'status'  => 'published',
            ]);
        }
        $this->db->table('exams')->where('id', $examId)->update(['status' => 'published']);
        $this->db->transComplete();
        return $this->db->transStatus();
    }

    public function getResults(int $examId): array
    {
        return $this->db->table('exam_results er')
            ->select('er.*, s.student_code, s.name')
            ->join('students s', 's.id = er.student_id')
            ->where('er.exam_id', $examId)
            ->orderBy('er.rank_no', 'asc')
            ->get()->getResultArray();
    }

    public function studentResults(int $tenantId, int $studentId): array
    {
        return $this->db->table('exam_results er')
            ->select('er.*, e.exam_name, e.exam_date')
            ->join('exams e', 'e.id = er.exam_id')
            ->where('er.tenant_id', $tenantId)
            ->where('er.student_id', $studentId)
            ->orderBy('e.exam_date', 'desc')
            ->get()->getResultArray();
    }

    protected function uuid4(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }
}