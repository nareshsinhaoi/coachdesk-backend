<?php

namespace App\Modules\Admin\Models;

use CodeIgniter\Model;

class TeacherModel extends Model
{
    protected $table            = 'teachers';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'public_id', 'tenant_id', 'branch_id', 'user_id',
        'employee_code', 'name', 'qualification', 'experience',
        'phone', 'email', 'joining_date', 'status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'tenant_id' => 'required|integer',
        'name'      => 'required|min_length[2]|max_length[150]',
        'email'     => 'permit_empty|valid_email',
        'phone'     => 'permit_empty|min_length[10]|max_length[15]',
        'status'    => 'in_list[active,inactive]',
    ];

    protected $allowCallbacks = true;
    protected $beforeInsert   = ['generatePublicId', 'generateEmployeeCode'];
    protected $beforeUpdate   = [];

    protected function generatePublicId(array $data): array
    {
        if (empty($data['data']['public_id'])) {
            $data['data']['public_id'] = $this->uuid4();
        }
        return $data;
    }

    protected function generateEmployeeCode(array $data): array
    {
        if (! empty($data['data']['employee_code'])) return $data;
        $tenantId = (int) ($data['data']['tenant_id'] ?? 0);
        $last = $this->db->table('teachers')
            ->like('employee_code', 'EMP-', 'after')
            ->where('tenant_id', $tenantId)
            ->orderBy('id', 'desc')->get()->getRow();
        $seq = $last ? ((int) substr($last->employee_code, -3)) + 1 : 1;
        $data['data']['employee_code'] = 'EMP-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
        return $data;
    }

    public function findByPublicId(int $tenantId, string $publicId): ?array
    {
        return $this->where('tenant_id', $tenantId)
                    ->where('public_id', $publicId)
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
        if (! empty($f['search'])) $b->groupStart()->like('name', $f['search'])->orLike('employee_code', $f['search'])->groupEnd();

        $b->orderBy('id', 'desc');
        $total = (clone $b)->countAllResults(false);
        $rows  = $b->limit($perPage, max(0, ((int) ($f['page'] ?? 1)) - 1) * $perPage)->get()->getResultArray();

        return ['items' => $rows, 'total' => $total, 'page' => (int) ($f['page'] ?? 1), 'totalPages' => (int) ceil($total / $perPage)];
    }

    /* -------- Batches taught -------- */

    public function getBatches(int $teacherId): array
    {
        return $this->db->table('batch_subjects bs')
            ->select('b.id, b.public_id, b.batch_name, b.batch_code, s.subject_name, s.subject_code')
            ->join('batches b', 'b.id = bs.batch_id')
            ->join('subjects s', 's.id = bs.subject_id', 'left')
            ->where('bs.teacher_id', $teacherId)
            ->get()->getResultArray();
    }

    public function getTimetable(int $teacherId): array
    {
        return $this->db->table('timetable')
            ->where('teacher_id', $teacherId)
            ->where('status', 'active')
            ->orderBy('day_of_week', 'asc')
            ->orderBy('start_time', 'asc')
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