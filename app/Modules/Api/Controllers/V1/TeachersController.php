<?php

namespace App\Modules\Api\Controllers\V1;

use App\Controllers\BaseController;
use App\Modules\Api\Libraries\TenantContext;

class TeachersController extends BaseController
{
    public function index()
    {
        $tenantId = TenantContext::getTenantId();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $page  = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit = min(100, max(1, (int) ($this->request->getGet('perPage') ?? $this->request->getGet('limit') ?? 20)));
        $branchId = TenantContext::getBranchId();

        $builder = db_connect()->table('cd_teachers')
            ->select('cd_teachers.id, cd_teachers.public_id, cd_teachers.employee_code, cd_teachers.name, cd_teachers.qualification, cd_teachers.experience, cd_teachers.phone, cd_teachers.email, cd_teachers.joining_date, cd_teachers.status, cd_teachers.branch_id, cd_branches.name AS branch_name, cd_teachers.created_at')
            ->join('cd_branches', 'cd_branches.id = cd_teachers.branch_id', 'left')
            ->where('cd_teachers.tenant_id', $tenantId)
            ->where('cd_teachers.deleted_at IS NULL');

        if ($branchId !== null) {
            $builder->where('cd_teachers.branch_id', $branchId);
        } elseif ($this->request->getGet('branch_id')) {
            $builder->where('cd_teachers.branch_id', (int) $this->request->getGet('branch_id'));
        }

        $status = $this->request->getGet('status');
        if ($status !== null && $status !== '') {
            if (! in_array($status, ['active', 'inactive'], true)) {
                return $this->response->setStatusCode(422)
                    ->setJSON(['status' => false, 'message' => 'status must be active or inactive']);
            }
            $builder->where('cd_teachers.status', $status);
        }

        $search = trim((string) $this->request->getGet('search'));
        if ($search !== '') {
            $builder->groupStart()
                ->like('cd_teachers.name', $search)
                ->orLike('cd_teachers.employee_code', $search)
                ->orLike('cd_teachers.email', $search)
                ->orLike('cd_teachers.phone', $search)
                ->groupEnd();
        }

        $countBuilder = clone $builder;
        $total = (int) $countBuilder->countAllResults();
        $teachers = $builder
            ->orderBy('cd_teachers.id', 'DESC')
            ->limit($limit, ($page - 1) * $limit)
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Teachers retrieved',
            'data' => $teachers,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    public function show(string $publicId)
    {
        $tenantId = TenantContext::getTenantId();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $query = db_connect()->table('cd_teachers')
            ->select('cd_teachers.*, cd_branches.name AS branch_name')
            ->join('cd_branches', 'cd_branches.id = cd_teachers.branch_id', 'left')
            ->where('cd_teachers.tenant_id', $tenantId)
            ->where('cd_teachers.public_id', $publicId)
            ->where('cd_teachers.deleted_at IS NULL');

        $branchId = TenantContext::getBranchId();
        if ($branchId !== null) {
            $query->where('cd_teachers.branch_id', $branchId);
        }

        $teacher = $query->get()->getRowArray();
        if (! $teacher) {
            return $this->response->setStatusCode(404)
                ->setJSON(['status' => false, 'message' => 'Teacher not found']);
        }

        return $this->response->setJSON([
            'status' => true,
            'data' => $teacher,
        ]);
    }

    public function create()
    {
        $tenantId = TenantContext::getTenantId();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $body = $this->request->getJSON(true);
        if (! is_array($body)) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'A JSON request body is required']);
        }

        $errors = $this->validateTeacher($body, true);
        if ($errors !== []) {
            return $this->response->setStatusCode(422)
                ->setJSON(['status' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        }

        $branchId = TenantContext::getBranchId();
        if ($branchId === null && ! empty($body['branch_id'])) {
            $branchId = (int) $body['branch_id'];
        }
        if ($branchId !== null && ! $this->branchBelongsToTenant($tenantId, $branchId)) {
            return $this->response->setStatusCode(422)
                ->setJSON(['status' => false, 'message' => 'Invalid branch for this tenant']);
        }

        $data = $this->teacherFields($body);
        $data['public_id'] = $this->uuid();
        $data['tenant_id'] = $tenantId;
        $data['branch_id'] = $branchId;
        $data['status'] = $body['status'] ?? 'active';

        $db = db_connect();
        if (! $db->table('cd_teachers')->insert($data)) {
            log_message('error', 'Failed to create teacher for tenant ID ' . $tenantId);
            return $this->response->setStatusCode(500)
                ->setJSON(['status' => false, 'message' => 'Unable to create teacher']);
        }

        $teacher = $db->table('cd_teachers')
            ->where('id', $db->insertID())
            ->where('tenant_id', $tenantId)
            ->get()
            ->getRowArray();

        return $this->response->setStatusCode(201)->setJSON([
            'status' => true,
            'message' => 'Teacher created',
            'data' => $teacher,
        ]);
    }

    public function update(string $publicId)
    {
        $tenantId = TenantContext::getTenantId();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $body = $this->request->getJSON(true);
        if (! is_array($body)) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'A JSON request body is required']);
        }

        $errors = $this->validateTeacher($body, false);
        if ($errors !== []) {
            return $this->response->setStatusCode(422)
                ->setJSON(['status' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        }

        $db = db_connect();
        $query = $db->table('cd_teachers')
            ->where('tenant_id', $tenantId)
            ->where('public_id', $publicId)
            ->where('deleted_at IS NULL');
        $branchId = TenantContext::getBranchId();
        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }
        $existing = $query->get()->getRowArray();
        if (! $existing) {
            return $this->response->setStatusCode(404)
                ->setJSON(['status' => false, 'message' => 'Teacher not found']);
        }

        if (array_key_exists('branch_id', $body)) {
            $newBranchId = $branchId ?? ($body['branch_id'] !== null ? (int) $body['branch_id'] : null);
            if ($branchId !== null && $newBranchId !== (int) $existing['branch_id']) {
                return $this->response->setStatusCode(422)
                    ->setJSON(['status' => false, 'message' => 'Cannot move a teacher outside your branch']);
            }
            if ($newBranchId !== null && ! $this->branchBelongsToTenant($tenantId, $newBranchId)) {
                return $this->response->setStatusCode(422)
                    ->setJSON(['status' => false, 'message' => 'Invalid branch for this tenant']);
            }
            $body['branch_id'] = $newBranchId;
        }

        $update = $this->teacherFields($body);
        if (array_key_exists('status', $body)) {
            $update['status'] = $body['status'];
        }
        if (array_key_exists('branch_id', $body)) {
            $update['branch_id'] = $body['branch_id'];
        }

        if ($update !== []) {
            $db->table('cd_teachers')
                ->where('tenant_id', $tenantId)
                ->where('public_id', $publicId)
                ->update($update);
        }

        $teacher = $db->table('cd_teachers')
            ->where('tenant_id', $tenantId)
            ->where('public_id', $publicId)
            ->where('deleted_at IS NULL')
            ->get()
            ->getRowArray();

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Teacher updated',
            'data' => $teacher,
        ]);
    }

    public function delete(string $publicId)
    {
        $tenantId = TenantContext::getTenantId();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $query = db_connect()->table('cd_teachers')
            ->where('tenant_id', $tenantId)
            ->where('public_id', $publicId)
            ->where('deleted_at IS NULL');
        $branchId = TenantContext::getBranchId();
        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }
        if ($query->countAllResults() === 0) {
            return $this->response->setStatusCode(404)
                ->setJSON(['status' => false, 'message' => 'Teacher not found']);
        }

        db_connect()->table('cd_teachers')
            ->where('tenant_id', $tenantId)
            ->where('public_id', $publicId)
            ->update(['deleted_at' => date('Y-m-d H:i:s'), 'status' => 'inactive']);

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Teacher removed',
        ]);
    }

    private function validateTeacher(array $body, bool $requireName): array
    {
        $errors = [];

        if ($requireName && empty($body['name'])) {
            $errors['name'] = 'Name is required';
        } elseif (isset($body['name']) && (strlen(trim((string) $body['name'])) < 2 || strlen((string) $body['name']) > 150)) {
            $errors['name'] = 'Name must be between 2 and 150 characters';
        }

        if (array_key_exists('email', $body) && $body['email'] !== null && $body['email'] !== ''
            && ! filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email';
        }

        if (array_key_exists('status', $body) && ! in_array($body['status'], ['active', 'inactive'], true)) {
            $errors['status'] = 'Status must be active or inactive';
        }

        return $errors;
    }

    private function teacherFields(array $body): array
    {
        return array_intersect_key($body, array_flip([
            'name',
            'qualification',
            'experience',
            'phone',
            'email',
            'joining_date',
        ]));
    }

    private function branchBelongsToTenant(int $tenantId, int $branchId): bool
    {
        return db_connect()->table('cd_branches')
            ->where('tenant_id', $tenantId)
            ->where('id', $branchId)
            ->where('deleted_at IS NULL')
            ->countAllResults() > 0;
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}
