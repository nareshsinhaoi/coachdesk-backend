<?php

namespace App\Modules\Api\Services;

class StudentService
{
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function list(int $tenantId, ?int $branchId, array $filters, int $page, int $limit): array
    {
        $b = $this->db->table('cd_students s')
            ->select('s.id, s.public_id, s.student_code, s.name, s.email, s.phone, s.gender, s.status, s.admission_date, b.branch_code, b.name AS branch_name')
            ->join('cd_branches b', 'b.id = s.branch_id', 'left')
            ->where('s.tenant_id', $tenantId)
            ->where('s.deleted_at IS NULL');

        if ($branchId) $b->where('s.branch_id', $branchId);

        if (! empty($filters['status'])) $b->where('s.status', $filters['status']);
        if (! empty($filters['branch_id'])) $b->where('s.branch_id', (int) $filters['branch_id']);
        if (! empty($filters['batch_id'])) {
            $b->join('cd_batch_students bs', 'bs.student_id = s.id')
              ->where('bs.batch_id', (int) $filters['batch_id'])
              ->where('bs.status', 'active');
        }
        if (! empty($filters['search'])) {
            $q = $filters['search'];
            $b->groupStart()
                ->like('s.name', $q)
                ->orLike('s.student_code', $q)
                ->orLike('s.phone', $q)
                ->orLike('s.email', $q)
              ->groupEnd();
        }

        // Count (clone, without limit)
        $countRow = clone $b;
        $total = $countRow->countAllResults(false);

        // Sorting
        $allowedSort = ['name', 'student_code', 'admission_date', 'created_at'];
        $sort = in_array($filters['sort'] ?? '', $allowedSort, true) ? $filters['sort'] : 'created_at';
        $order = strtolower($filters['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $b->orderBy("s.$sort", $order);

        $rows = $b->limit($limit, ($page - 1) * $limit)->get()->getResultArray();

        return [
            'items' => $rows,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int) $total,
                'totalPages' => (int) ceil($total / $limit),
            ],
        ];
    }

    public function find(int $tenantId, string $publicId): ?array
    {
        return $this->db->table('cd_students s')
            ->select('s.*, b.branch_code, b.name AS branch_name')
            ->join('cd_branches b', 'b.id = s.branch_id', 'left')
            ->where('s.tenant_id', $tenantId)
            ->where('s.public_id', $publicId)
            ->where('s.deleted_at IS NULL')
            ->get()->getRowArray();
    }

    public function create(int $tenantId, int $branchId, array $data): array
    {
        // Generate student_code: CD-{BRANCH}-{YEAR}-{SEQ}
        $branch = $this->db->table('cd_branches')->where(['id' => $branchId, 'tenant_id' => $tenantId])->get()->getRow();
        //echo $this->db->getLastQuery(); die;
        
        if (! $branch) {
            throw new \RuntimeException('Invalid branch for this tenant');
        }
        $year = date('Y');
        $prefix = "BS{$branchId}-{$branch->branch_code}-{$year}-";
        $last = $this->db->table('cd_students')
            ->like('student_code', $prefix, 'after')
            ->where('tenant_id', $tenantId)
            ->orderBy('id', 'DESC')
            ->get()->getRow();
        $seq = $last ? ((int) substr($last->student_code, -5)) + 1 : 1;
        $studentCode = $prefix . str_pad($seq, 5, '0', STR_PAD_LEFT);

        $this->db->transStart();

        $this->db->table('cd_students')->insert([
            'public_id'      => $this->uuid(),
            'tenant_id'      => $tenantId,
            'branch_id'      => $branchId,
            'student_code'   => $studentCode,
            'name'           => $data['name'],
            'dob'            => $data['dob'] ?? null,
            'gender'         => $data['gender'] ?? null,
            'phone'          => $data['phone'] ?? null,
            'email'          => $data['email'] ?? null,
            'address_line1'  => $data['address_line1'] ?? null,
            'city'           => $data['city'] ?? null,
            'state'          => $data['state'] ?? null,
            'pincode'        => $data['pincode'] ?? null,
            'admission_date' => $data['admission_date'] ?? date('Y-m-d'),
            'status'         => $data['status'] ?? 'active',
        ]);
        $studentId = $this->db->insertID();

        // Guardian
        if (! empty($data['guardian']['name'])) {
            $this->db->table('cd_student_guardians')->insert([
                'public_id'    => $this->uuid(),
                'tenant_id'    => $tenantId,
                'student_id'   => $studentId,
                'name'         => $data['guardian']['name'],
                'relationship' => $data['guardian']['relationship'] ?? 'father',
                'phone'        => $data['guardian']['phone'] ?? null,
                'email'        => $data['guardian']['email'] ?? null,
                'is_primary'   => 1,
            ]);
        }

        // Batch enrollment
        if (! empty($data['batch_id'])) {
            $batch = $this->db->table('cd_batches')
                ->where(['id' => (int) $data['batch_id'], 'tenant_id' => $tenantId])
                ->get()->getRow();
            if (! $batch) throw new \RuntimeException('Invalid batch');

            $this->db->table('cd_batch_students')->insert([
                'tenant_id'  => $tenantId,
                'batch_id'   => (int) $data['batch_id'],
                'student_id' => $studentId,
                'start_date' => date('Y-m-d'),
                'status'     => 'active',
            ]);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new \RuntimeException('Failed to create student');
        }

        return $this->find($tenantId, $this->db->table('cd_students')->where('id', $studentId)->get()->getRow()->public_id);
    }

    public function update(int $tenantId, string $publicId, array $data): ?array
    {
        $existing = $this->find($tenantId, $publicId);
        if (! $existing) return null;

        $update = array_intersect_key($data, array_flip([
            'name', 'dob', 'gender', 'phone', 'email', 'address_line1',
            'city', 'state', 'pincode', 'status', 'admission_date',
        ]));
        if (empty($update)) return $existing;

        $this->db->table('cd_students')
            ->where(['tenant_id' => $tenantId, 'public_id' => $publicId])
            ->update($update);

        return $this->find($tenantId, $publicId);
    }

    public function delete(int $tenantId, string $publicId): bool
    {
        $existing = $this->find($tenantId, $publicId);
        if (! $existing) return false;

        $this->db->table('cd_students')
            ->where(['tenant_id' => $tenantId, 'public_id' => $publicId])
            ->update(['deleted_at' => date('Y-m-d H:i:s'), 'status' => 'cancelled']);

        return true;
    }

    protected function uuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}