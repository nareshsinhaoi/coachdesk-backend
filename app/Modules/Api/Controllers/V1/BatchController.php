<?php

namespace App\Modules\Api\Controllers\V1;

use App\Controllers\BaseController;
use App\Modules\Api\Libraries\TenantContext;
use App\Modules\Api\Services\AcademicService;

class BatchController extends BaseController
{
    protected AcademicService $service;

    public function __construct()
    {
        $this->service = new AcademicService();
    }

    public function index()
    {
        $page  = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit = min(100, max(1, (int) ($this->request->getGet('limit') ?? 20)));

        $result = $this->service->listBatches(
            TenantContext::getTenantId(),
            TenantContext::getBranchId(),
            [
                'search'    => $this->request->getGet('search'),
                'status'    => $this->request->getGet('status'),
                'course_id' => $this->request->getGet('course_id'),
            ],
            $page, $limit
        );

        return $this->response->setJSON([
            'status' => true, 'data' => $result['items'], 'pagination' => $result['pagination'],
        ]);
    }

    public function create()
    {
        $body = $this->request->getJSON(true);
        if (empty($body['batch_name']) || empty($body['batch_code']) || empty($body['course_id'])) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => false, 'message' => 'batch_name, batch_code, course_id required',
            ]);
        }
        try {
            $batch = $this->service->createBatch(
                TenantContext::getTenantId(),
                TenantContext::getBranchId() ?? (int) ($body['branch_id'] ?? 0),
                $body
            );
            return $this->response->setStatusCode(201)->setJSON([
                'status' => true, 'message' => 'Batch created', 'data' => $batch,
            ]);
        } catch (\RuntimeException $e) {
            return $this->response->setStatusCode(409)->setJSON(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    public function enroll($batchPublicId)
    {
        $body = $this->request->getJSON(true);
        if (empty($body['student_public_id'])) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => false, 'message' => 'student_public_id required',
            ]);
        }
        try {
            $r = $this->service->enrollStudent(
                TenantContext::getTenantId(),
                $batchPublicId,
                $body['student_public_id']
            );
            return $this->response->setJSON(['status' => true, 'message' => 'Student enrolled', 'data' => $r]);
        } catch (\RuntimeException $e) {
            return $this->response->setStatusCode(409)->setJSON(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    public function students($batchPublicId)
    {
        $rows = $this->service->listBatchStudents(TenantContext::getTenantId(), $batchPublicId);
        return $this->response->setJSON(['status' => true, 'data' => $rows]);
    }
}