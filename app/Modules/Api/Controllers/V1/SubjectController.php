<?php

namespace App\Modules\Api\Controllers\V1;

use App\Controllers\BaseController;
use App\Modules\Api\Libraries\TenantContext;
use App\Modules\Api\Services\AcademicService;

class SubjectController extends BaseController
{
    protected AcademicService $service;

    public function __construct()
    {
        $this->service = new AcademicService();
    }

    public function index()
    {
        $page  = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit = min(100, max(1, (int) ($this->request->getGet('limit') ?? 50)));

        $result = $this->service->listSubjects(
            TenantContext::getTenantId(),
            ['search' => $this->request->getGet('search')],
            $page, $limit
        );

        return $this->response->setJSON([
            'status' => true, 'data' => $result['items'], 'pagination' => $result['pagination'],
        ]);
    }

    public function create()
    {
        $body = $this->request->getJSON(true);
        if (empty($body['subject_name'])) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => false, 'message' => 'subject_name required',
            ]);
        }
        $subject = $this->service->createSubject(TenantContext::getTenantId(), $body);
        return $this->response->setStatusCode(201)->setJSON([
            'status' => true, 'message' => 'Subject created', 'data' => $subject,
        ]);
    }
}