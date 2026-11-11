<?php

namespace App\Modules\Admin\Models;

use CodeIgniter\Model;

class StudentModel extends Model
{
    protected $table            = 'students';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'public_id', 'tenant_id', 'branch_id', 'student_code', 'name',
        'dob', 'gender', 'phone', 'email',
        'address_line1', 'address_line2', 'city', 'state', 'pincode',
        'photo_path', 'admission_date', 'status',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'tenant_id'      => 'required|integer',
        'branch_id'      => 'required|integer',
        'name'           => 'required|min_length[2]|max_length[150]',
        'gender'         => 'permit_empty|in_list[male,female,other]',
        'email'          => 'permit_empty|valid_email',
        'phone'          => 'permit_empty|min_length[10]|max_length[15]',
        'status'         => 'in_list[active,inactive,completed,cancelled]',
        'admission_date' => 'permit_empty|valid_date[Y-m-d]',
    ];

    protected $allowCallbacks = true;
    protected $beforeInsert   = ['generatePublicId', 'generateStudentCode'];
    protected $beforeUpdate   = [];

    protected function generatePublicId(array $data): array
    {
        if (empty($data['data']['public_id'])) {
            $data['data']['public_id'] = $this->uuid4();
        }
        return $data;
    }

    /**
     * Generate student_code: CD-{branch_code}-{YEAR}-{SEQ5}
     * Called only on insert when code not supplied.
     */
    protected function generateStudentCode(array $data): array
    {
        if (! empty($data['data']['student_code'])) return $data;

        $branchId = (int) ($data['data']['branch_id'] ?? 0);
        $tenantId = (int) ($data['data']['tenant_id'] ?? 0);
        $branch = $this->db->table('branches')
            ->where(['id' => $branchId, 'tenant_id' => $tenantId])
            ->get()->getRow();
        $branchCode = $branch->branch_code ?? 'XX';
        $year = date('Y');
        $prefix = "CD-{$branchCode}-{$year}-";

        $last = $this->db->table('students')
            ->like('student_code', $prefix, 'after')
            ->where('tenant_id', $tenantId)
            ->orderBy('id', 'desc')->get()->getRow();
        $seq = $last ? ((int) substr($last->student_code, -5)) + 1 : 1;

        $data['data']['student_code'] = $prefix . str_pad($seq, 5, '0', STR_PAD_LEFT);
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
                    ->where('student_code', $code)
                    ->first();
    }

    /**
     * Full-text-ish search + filtering + pagination
     */
    public function search(int $tenantId, ?int $branchId, array $f = [], int $perPage = 20): array
    {
        $b = $this->builder()
            ->select('students.*, branches.branch_code, branches.name AS branch_name')
            ->join('branches', 'branches.id = students.branch_id', 'left')
            ->where('students.tenant_id', $tenantId)
            ->where('students.deleted_at IS NULL');

        if ($branchId) $b->where('students.branch_id', $branchId);
        if (! empty($f['status']))    $b->where('students.status', $f['status']);
        if (! empty($f['branch_id'])) $b->where('students.branch_id', (int) $f['branch_id']);

        if (! empty($f['batch_id'])) {
            $b->join('batch_students bs', 'bs.student_id = students.id')
              ->where('bs.batch_id', (int) $f['batch_id'])
              ->where('bs.status', 'active');
        }

        if (! empty($f['search'])) {
            $q = $f['search'];
            $b->groupStart()
                ->like('students.name', $q)
                ->orLike('students.student_code', $q)
                ->orLike('students.phone', $q)
                ->orLike('students.email', $q)
              ->groupEnd();
        }

        $sort  = in_array($f['sort'] ?? '', ['name','student_code','admission_date','created_at'], true) ? $f['sort'] : 'created_at';
        $order = strtolower($f['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $b->orderBy("students.$sort", $order);

        $total = (clone $b)->countAllResults(false);
        $rows  = $b->limit($perPage, max(0, ((int) ($f['page'] ?? 1)) - 1) * $perPage)
                   ->get()->getResultArray();

        return [
            'items'      => $rows,
            'total'      => $total,
            'perPage'    => $perPage,
            'page'       => (int) ($f['page'] ?? 1),
            'totalPages' => (int) ceil($total / $perPage),
        ];
    }

    /* -------- Relationships -------- */

    public function getGuardian(int $studentId): ?array
    {
        return $this->db->table('student_guardians')
            ->where('student_id', $studentId)
            ->where('is_primary', 1)
            ->get()->getRowArray();
    }

    public function getBatches(int $studentId): array
    {
        return $this->db->table('batch_students bs')
            ->select('b.id, b.public_id, b.batch_name, b.batch_code, bs.start_date, bs.status')
            ->join('batches b', 'b.id = bs.batch_id')
            ->where('bs.student_id', $studentId)
            ->get()->getResultArray();
    }

    public function getFeeAccount(int $studentId): ?array
    {
        return $this->db->table('student_fee_accounts')
            ->where('student_id', $studentId)
            ->where('status', 'active')
            ->get()->getRowArray();
    }

    /* -------- Batch enrollment -------- */

    public function enrollInBatch(int $tenantId, int $studentId, int $batchId): bool
    {
        $exists = $this->db->table('batch_students')
            ->where(['batch_id' => $batchId, 'student_id' => $studentId, 'status' => 'active'])
            ->countAllResults();
        if ($exists) return false;

        $this->db->table('batch_students')->insert([
            'tenant_id'  => $tenantId,
            'batch_id'   => $batchId,
            'student_id' => $studentId,
            'start_date' => date('Y-m-d'),
            'status'     => 'active',
        ]);
        return true;
    }

    /* -------- Counts for dashboard -------- */

    public function countByStatus(int $tenantId, ?int $branchId = null): array
    {
        $b = $this->builder()
            ->select('status, COUNT(*) AS c')
            ->where('tenant_id', $tenantId)
            ->where('deleted_at IS NULL');
        if ($branchId) $b->where('branch_id', $branchId);
        $b->groupBy('status');
        $rows = $b->get()->getResultArray();

        $out = ['active' => 0, 'inactive' => 0, 'completed' => 0, 'cancelled' => 0, 'total' => 0];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['c'];
            $out['total'] += (int) $r['c'];
        }
        return $out;
    }

    protected function uuid4(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }
}