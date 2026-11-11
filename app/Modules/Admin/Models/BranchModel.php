<?php

namespace App\Modules\Admin\Models;

use CodeIgniter\Model;

class BranchModel extends Model
{
    protected $table            = 'branches';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'public_id', 'tenant_id', 'name', 'branch_code',
        'address_line1', 'address_line2', 'city', 'state', 'pincode',
        'phone', 'email', 'status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'tenant_id'   => 'required|integer',
        'name'        => 'required|min_length[2]|max_length[150]',
        'branch_code' => 'required|alpha_dash|min_length[2]|max_length[30]',
        'email'       => 'permit_empty|valid_email',
        'phone'       => 'permit_empty|min_length[10]|max_length[15]',
        'status'      => 'in_list[active,inactive]',
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

    public function findByCode(int $tenantId, string $code): ?array
    {
        return $this->where('tenant_id', $tenantId)
                    ->where('branch_code', $code)
                    ->where('deleted_at IS NULL')
                    ->first();
    }

    public function listForTenant(int $tenantId, ?string $status = null): array
    {
        $b = $this->where('tenant_id', $tenantId)
                  ->where('deleted_at IS NULL');
        if ($status) $b->where('status', $status);
        return $b->orderBy('name', 'asc')->findAll();
    }

    /* -------- Stats -------- */

    public function studentCount(int $branchId): int
    {
        return (int) $this->db->table('students')
            ->where('branch_id', $branchId)
            ->where('deleted_at IS NULL')
            ->countAllResults();
    }

    public function batchCount(int $branchId): int
    {
        return (int) $this->db->table('batches')
            ->where('branch_id', $branchId)
            ->where('deleted_at IS NULL')
            ->countAllResults();
    }

    public function teacherCount(int $branchId): int
    {
        return (int) $this->db->table('teachers')
            ->where('branch_id', $branchId)
            ->where('deleted_at IS NULL')
            ->countAllResults();
    }

    public function userCount(int $branchId): int
    {
        return (int) $this->db->table('user_roles')
            ->where('branch_id', $branchId)
            ->countAllResults();
    }

    public function summary(int $branchId): array
    {
        return [
            'students' => $this->studentCount($branchId),
            'batches'  => $this->batchCount($branchId),
            'teachers' => $this->teacherCount($branchId),
            'users'    => $this->userCount($branchId),
        ];
    }

    protected function uuid4(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }
}