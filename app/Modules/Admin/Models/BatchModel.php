<?php

namespace App\Modules\Admin\Models;

use CodeIgniter\Model;

class BatchModel extends Model
{
    protected $table            = 'batches';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'public_id', 'tenant_id', 'branch_id', 'course_id',
        'academic_session_id', 'batch_name', 'batch_code', 'capacity',
        'start_date', 'end_date', 'timing_start', 'timing_end', 'status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'tenant_id'  => 'required|integer',
        'branch_id'  => 'required|integer',
        'course_id'  => 'required|integer',
        'batch_name' => 'required|min_length[2]|max_length[120]',
        'batch_code' => 'required|alpha_dash|min_length[2]|max_length[30]',
        'capacity'   => 'permit_empty|integer|greater_than[0]',
        'status'     => 'in_list[upcoming,running,completed,cancelled]',
    ];

    protected $allowCallbacks = true;
    protected $beforeInsert   = ['generatePublicId'];
    protected $beforeUpdate   = [];

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

    public function listForTenant(int $tenantId, ?int $branchId, array $f = [], int $perPage = 20): array
    {
        $b = $this->db->table('batches b')
            ->select('b.*, c.course_name, c.course_code')
            ->join('courses c', 'c.id = b.course_id', 'left')
            ->where('b.tenant_id', $tenantId)
            ->where('b.deleted_at IS NULL');

        if ($branchId) $b->where('b.branch_id', $branchId);
        if (! empty($f['status']))    $b->where('b.status', $f['status']);
        if (! empty($f['course_id'])) $b->where('b.course_id', (int) $f['course_id']);
        if (! empty($f['search']))    $b->like('b.batch_name', $f['search']);

        $b->orderBy('b.id', 'desc');

        $total = (clone $b)->countAllResults(false);
        $rows  = $b->limit($perPage, max(0, ((int) ($f['page'] ?? 1)) - 1) * $perPage)->get()->getResultArray();

        return ['items' => $rows, 'total' => $total, 'page' => (int) ($f['page'] ?? 1), 'totalPages' => (int) ceil($total / $perPage)];
    }

    public function runningBatches(int $tenantId, ?int $branchId = null): array
    {
        $b = $this->where('tenant_id', $tenantId)
                  ->where('status', 'running')
                  ->where('deleted_at IS NULL');
        if ($branchId) $b->where('branch_id', $branchId);
        return $b->orderBy('batch_name', 'asc')->findAll();
    }

    /* -------- Students in batch -------- */

    public function getStudents(int $batchId): array
    {
        return $this->db->table('batch_students bs')
            ->select('s.id, s.public_id, s.student_code, s.name, s.phone, s.status, bs.start_date, bs.status AS enrollment_status')
            ->join('students s', 's.id = bs.student_id')
            ->where('bs.batch_id', $batchId)
            ->where('bs.status', 'active')
            ->get()->getResultArray();
    }

    public function studentCount(int $batchId): int
    {
        return (int) $this->db->table('batch_students')
            ->where(['batch_id' => $batchId, 'status' => 'active'])
            ->countAllResults();
    }

    public function hasCapacity(int $batchId): bool
    {
        $batch = $this->find($batchId);
        if (! $batch) return false;
        return $this->studentCount($batchId) < (int) $batch['capacity'];
    }

    /**
     * Enroll a student; returns ['ok' => bool, 'reason' => string]
     */
    public function enroll(int $tenantId, int $batchId, int $studentId): array
    {
        $batch = $this->where(['tenant_id' => $tenantId, 'id' => $batchId])->first();
        if (! $batch) return ['ok' => false, 'reason' => 'Batch not found'];

        if ($this->studentCount($batchId) >= (int) $batch['capacity']) {
            return ['ok' => false, 'reason' => 'Batch is full'];
        }
        $exists = $this->db->table('batch_students')
            ->where(['batch_id' => $batchId, 'student_id' => $studentId, 'status' => 'active'])
            ->countAllResults();
        if ($exists) return ['ok' => false, 'reason' => 'Student already enrolled'];

        $this->db->table('batch_students')->insert([
            'tenant_id'  => $tenantId,
            'batch_id'   => $batchId,
            'student_id' => $studentId,
            'start_date' => date('Y-m-d'),
            'status'     => 'active',
        ]);
        return ['ok' => true, 'reason' => 'Enrolled'];
    }

    public function transferStudent(int $tenantId, int $fromBatchId, int $toBatchId, int $studentId): array
    {
        $from = $this->db->table('batch_students')
            ->where(['batch_id' => $fromBatchId, 'student_id' => $studentId, 'status' => 'active'])
            ->get()->getRow();
        if (! $from) return ['ok' => false, 'reason' => 'Student not in source batch'];

        $this->db->transStart();
        $this->db->table('batch_students')->where('id', $from->id)->update([
            'status'   => 'transferred',
            'end_date' => date('Y-m-d'),
        ]);
        $result = $this->enroll($tenantId, $toBatchId, $studentId);
        $this->db->transComplete();

        return $result;
    }

    protected function uuid4(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }
}