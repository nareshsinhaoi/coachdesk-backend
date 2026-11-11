<?php
namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Api\Services\AcademicService;
require_once __DIR__ . '/../Helpers/admin_helper.php';

class CourseController extends BaseController
{
    protected AcademicService $service;
    public function __construct() { $this->service = new AcademicService(); }

    public function index()
    {
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $result = $this->service->listCourses(tenant_id(), branch_id(), [
            'search' => $this->request->getGet('search'),
            'status' => $this->request->getGet('status'),
        ], $page, 20);
        return view('App\Modules\Admin\Views\courses\index', [
            'title' => 'Courses', 'courses' => $result['items'], 'pagination' => $result['pagination'],
        ]);
    }

    public function create()
    {
        return view('App\Modules\Admin\Views\courses\form', ['title' => 'New Course', 'course' => null]);
    }

    public function store()
    {
        try {
            $data = $this->request->getPost(['course_name','course_code','duration','fees','description','status']);
            $this->service->createCourse(tenant_id(), branch_id(), $data);
            return redirect()->to('/admin/courses')->with('success', 'Course created.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit($publicId)
    {
        $course = db_connect()->table('cd_courses')
            ->where(['tenant_id' => tenant_id(), 'public_id' => $publicId, 'deleted_at' => null])
            ->get()->getRowArray();
        if (! $course) return redirect()->to('/admin/courses')->with('error', 'Course not found.');
        return view('App\Modules\Admin\Views\courses\form', ['title' => 'Edit Course', 'course' => $course]);
    }

    public function update($publicId)
    {
        $this->service->updateCourse(tenant_id(), $publicId, $this->request->getPost([
            'course_name','duration','fees','description','status',
        ]));
        return redirect()->to('/admin/courses')->with('success', 'Course updated.');
    }
}