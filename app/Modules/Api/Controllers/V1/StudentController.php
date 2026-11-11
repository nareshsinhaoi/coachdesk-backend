<?php

namespace App\Modules\Api\Controllers\V1;

use App\Controllers\BaseController;
use App\Modules\Api\Libraries\TenantContext;
use App\Modules\Api\Services\StudentService;

class StudentController extends BaseController
{
    protected StudentService $service;

    public function __construct()
    {
        $this->service = new StudentService();
    }

    public function index()
    {
        $tenantId = TenantContext::getTenantId();
        $branchId = TenantContext::getBranchId();

        $filters = [
            'search'    => $this->request->getGet('search'),
            'status'    => $this->request->getGet('status'),
            'branch_id' => $this->request->getGet('branch_id'),
            'batch_id'  => $this->request->getGet('batch_id'),
            'sort'      => $this->request->getGet('sort'),
            'order'     => $this->request->getGet('order'),
        ];
        $page  = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit = min(100, max(1, (int) ($this->request->getGet('limit') ?? 20)));

        $result = $this->service->list($tenantId, $branchId, $filters, $page, $limit);

        return $this->response->setJSON([
            'status'     => true,
            'message'    => 'Students retrieved',
            'data'       => $result['items'],
            'pagination' => $result['pagination'],
        ]);
    }

    public function show($publicId)
    {
        $student = $this->service->find(TenantContext::getTenantId(), $publicId);
        if (! $student) {
            return $this->response->setStatusCode(404)->setJSON(['status' => false, 'message' => 'Student not found']);
        }
        return $this->response->setJSON(['status' => true, 'data' => $student]);
    }

    public function create()
    {
        $body = $this->request->getJSON(true);

        $errors = [];
        if (empty($body['name']))       $errors['name'] = 'Name is required';
        if (empty($body['branch_id']))  $errors['branch_id'] = 'Branch is required';
        if (! empty($body['email']) && ! filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email';
        }
        if ($errors) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => false, 'message' => 'Validation failed', 'errors' => $errors,
            ]);
        }

        try {
            $student = $this->service->create(
                TenantContext::getTenantId(),
                (int) $body['branch_id'],
                $body
            );
            return $this->response->setStatusCode(201)->setJSON([
                'status' => true, 'message' => 'Student admitted', 'data' => $student,
            ]);
        } catch (\RuntimeException $e) {
            return $this->response->setStatusCode(409)->setJSON([
                'status' => false, 'message' => $e->getMessage(),
            ]);
        }
    }

    public function update($publicId)
    {
        $body = $this->request->getJSON(true);
        $student = $this->service->update(TenantContext::getTenantId(), $publicId, $body ?? []);
        if (! $student) {
            return $this->response->setStatusCode(404)->setJSON(['status' => false, 'message' => 'Student not found']);
        }
        return $this->response->setJSON(['status' => true, 'message' => 'Student updated', 'data' => $student]);
    }

    public function delete($publicId)
    {
        $ok = $this->service->delete(TenantContext::getTenantId(), $publicId);
        if (! $ok) {
            return $this->response->setStatusCode(404)->setJSON(['status' => false, 'message' => 'Student not found']);
        }
        return $this->response->setJSON(['status' => true, 'message' => 'Student removed']);
    }
}