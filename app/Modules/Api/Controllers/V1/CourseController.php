<?php

namespace App\Modules\Api\Controllers\V1;

use App\Controllers\BaseController;
use App\Modules\Api\Libraries\TenantContext;
use App\Modules\Api\Services\AcademicService;

class CourseController extends BaseController
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

        $result = $this->service->listCourses(
            TenantContext::getTenantId(),
            TenantContext::getBranchId(),
            [
                'search' => $this->request->getGet('search'),
                'status' => $this->request->getGet('status'),
            ],
            $page, $limit
        );

        return $this->response->setJSON([
            'status' => true, 'message' => 'Courses retrieved',
            'data'   => $result['items'], 'pagination' => $result['pagination'],
        ]);
    }

    public function create()
    {
        $body = $this->request->getJSON(true);
        if (empty($body['course_name']) || empty($body['course_code'])) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => false, 'message' => 'Validation failed',
                'errors' => ['course_name/course_code' => 'required'],
            ]);
        }
        try {
            $course = $this->service->createCourse(
                TenantContext::getTenantId(),
                TenantContext::getBranchId(),
                $body
            );
            return $this->response->setStatusCode(201)->setJSON([
                'status' => true, 'message' => 'Course created', 'data' => $course,
            ]);
        } catch (\RuntimeException $e) {
            return $this->response->setStatusCode(409)->setJSON(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    public function update($publicId)
    {
        $course = $this->service->updateCourse(TenantContext::getTenantId(), $publicId, $this->request->getJSON(true) ?? []);
        if (! $course) return $this->response->setStatusCode(404)->setJSON(['status' => false, 'message' => 'Course not found']);
        return $this->response->setJSON(['status' => true, 'message' => 'Course updated', 'data' => $course]);
    }

    public function delete($publicId)
    {
        try {
            $ok = $this->service->deleteCourse(TenantContext::getTenantId(), $publicId);
            if (! $ok) return $this->response->setStatusCode(404)->setJSON(['status' => false, 'message' => 'Course not found']);
            return $this->response->setJSON(['status' => true, 'message' => 'Course removed']);
        } catch (\RuntimeException $e) {
            return $this->response->setStatusCode(409)->setJSON(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    public function attachSubjects($publicId)
    {
        $body = $this->request->getJSON(true);
        $ids = $body['subject_public_ids'] ?? [];
        try {
            $linked = $this->service->attachSubjectsToCourse(TenantContext::getTenantId(), $publicId, $ids);
            return $this->response->setJSON(['status' => true, 'message' => 'Subjects linked', 'data' => $linked]);
        } catch (\RuntimeException $e) {
            return $this->response->setStatusCode(404)->setJSON(['status' => false, 'message' => $e->getMessage()]);
        }
    }
}