<?php
namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Api\Services\AcademicService;
require_once __DIR__ . '/../Helpers/admin_helper.php';

class BatchController extends BaseController
{
    protected AcademicService $service;
    public function __construct() { $this->service = new AcademicService(); }

    public function index()
    {
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $result = $this->service->listBatches(tenant_id(), branch_id(), [
            'search' => $this->request->getGet('search'),
            'status' => $this->request->getGet('status'),
        ], $page, 20);
        return view('App\Modules\Admin\Views\batches\index', [
            'title' => 'Batches', 'batches' => $result['items'], 'pagination' => $result['pagination'],
        ]);
    }

    public function create()
    {
        $courses = db_connect()->table('cd_courses')
            ->where('tenant_id', tenant_id())->where('deleted_at IS NULL')->where('status', 'active')->orderBy('course_name', 'ASC')
            ->get()->getResultArray();
        return view('App\Modules\Admin\Views\batches\form', ['title' => 'New Batch', 'batch' => null, 'courses' => $courses]);
    }

    public function store()
    {
        try {
            $data = $this->request->getPost([
                'course_id','batch_name','batch_code','capacity','start_date','end_date','timing_start','timing_end','status',
            ]);
            $this->service->createBatch(tenant_id(), branch_id() ?? 0, $data);
            return redirect()->to('/admin/batches')->with('success', 'Batch created.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function students($publicId)
    {
        $rows = $this->service->listBatchStudents(tenant_id(), $publicId);
        $batch = db_connect()->table('cd_batches')
            ->where(['tenant_id' => tenant_id(), 'public_id' => $publicId])
            ->get()->getRowArray();
        return view('App\Modules\Admin\Views\batches\students', [
            'title' => 'Batch Students', 'students' => $rows, 'batch' => $batch,
        ]);
    }
}