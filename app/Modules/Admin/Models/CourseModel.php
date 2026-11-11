<?php

namespace App\Modules\Admin\Models;

use CodeIgniter\Model;

class CourseModel extends Model
{
    protected $table            = 'courses';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'public_id', 'tenant_id', 'branch_id',
        'course_name', 'course_code', 'duration', 'fees',
        'description', 'status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'tenant_id'   => 'required|integer',
        'course_name' => 'required|min_length[2]|max_length[150]',
        'course_code' => 'required|alpha_dash|min_length[2]|max_length[30]',
        'fees'        => 'permit_empty|decimal',
        'status'      => 'in_list[active,inactive]',
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

    /* -------- Scoped finders -------- */

    public function findByPublicId(int $tenantId, string $publicId): ?array
    {
        return $this->where('tenant_id', $tenantId)
                    ->where('public_id', $publicId)
                    ->where('deleted_at IS NULL')
                    ->first();
    }

    public function findByCode(int $tenantId, string $code): ?array
    {
        return $this->where('tenant_id', $tenantId)
                    ->where('course_code', $code)
                    ->where('deleted_at IS NULL')
                    ->first();
    }

    public function listForTenant(int $tenantId, ?int $branchId = null, array $f = [], int $perPage = 20): array
    {
        $b = $this->builder()
            ->where('tenant_id', $tenantId)
            ->where('deleted_at IS NULL');
        if ($branchId) $b->where('branch_id', $branchId);
        if (! empty($f['status'])) $b->where('status', $f['status']);
        if (! empty($f['search'])) {
            $b->groupStart()
                ->like('course_name', $f['search'])
                ->orLike('course_code', $f['search'])
              ->groupEnd();
        }
        $b->orderBy('id', 'desc');

        $total = (clone $b)->countAllResults(false);
        $rows  = $b->limit($perPage, max(0, ((int) ($f['page'] ?? 1)) - 1) * $perPage)->get()->getResultArray();

        return ['items' => $rows, 'total' => $total, 'page' => (int) ($f['page'] ?? 1), 'totalPages' => (int) ceil($total / $perPage)];
    }

    /* -------- Subjects of course -------- */

    public function getSubjects(int $courseId): array
    {
        return $this->db->table('course_subjects cs')
            ->select('s.id, s.public_id, s.subject_name, s.subject_code')
            ->join('subjects s', 's.id = cs.subject_id')
            ->where('cs.course_id', $courseId)
            ->get()->getResultArray();
    }

    public function attachSubject(int $courseId, int $subjectId): bool
    {
        $exists = $this->db->table('course_subjects')
            ->where(['course_id' => $courseId, 'subject_id' => $subjectId])
            ->countAllResults();
        if ($exists) return false;
        $this->db->table('course_subjects')->insert([
            'course_id'  => $courseId,
            'subject_id' => $subjectId,
        ]);
        return true;
    }

    public function detachSubject(int $courseId, int $subjectId): bool
    {
        return (bool) $this->db->table('course_subjects')
            ->where(['course_id' => $courseId, 'subject_id' => $subjectId])
            ->delete();
    }

    /* -------- Stats -------- */

    public function batchCount(int $courseId): int
    {
        return (int) $this->db->table('batches')
            ->where('course_id', $courseId)
            ->where('deleted_at IS NULL')
            ->countAllResults();
    }

    public function activeBatchCount(int $courseId): int
    {
        return (int) $this->db->table('batches')
            ->where('course_id', $courseId)
            ->where('deleted_at IS NULL')
            ->whereIn('status', ['upcoming', 'running'])
            ->countAllResults();
    }

    protected function uuid4(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }
}