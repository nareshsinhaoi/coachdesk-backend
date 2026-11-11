<?php
namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Api\Services\StudentService;
require_once __DIR__ . '/../Helpers/admin_helper.php';

class StudentController extends BaseController
{
    protected StudentService $service;

    public function __construct() { $this->service = new StudentService(); }

    public function index()
    {
        // die("StudentController::index() called");
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit   = 20;
        $filters = [
            'search' => $this->request->getGet('search'),
            'status' => $this->request->getGet('status'),
            'batch_id' => $this->request->getGet('batch_id'),
        ];
        $result = $this->service->list(tenant_id(), branch_id(), $filters, $page, $limit);

        return view('App\Modules\Admin\Views\students\index', [
            'title' => 'Students',
            'students' => $result['items'],
            'pagination' => $result['pagination'],
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        $branches = db_connect()->table('cd_branches')
            ->where('tenant_id', tenant_id())->where('deleted_at IS NULL')
            ->get()->getResultArray();
        $batches = db_connect()->table('cd_batches')
            ->where('tenant_id', tenant_id())->where('deleted_at IS NULL')
            ->get()->getResultArray();
        return view('App\Modules\Admin\Views\students\create', [
            'title' => 'Admit Student', 'branches' => $branches, 'batches' => $batches,
        ]);
    }

    public function store()
    {
        $rules = [
            'name'      => 'required|min_length[2]',
            'branch_id' => 'required|integer',
            'phone'     => 'permit_empty|min_length[10]|max_length[25]',
            'email'     => 'permit_empty|valid_email',
            'gender'    => 'permit_empty|in_list[male,female,other]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        try {
            $data = $this->request->getPost([
                'name','dob','gender','phone','email','address_line1','city',
                'state','pincode','branch_id', 'batch_id','admission_date',
            ]);
            $data['guardian'] = [
                'name' => $this->request->getPost('guardian_name'),
                'relationship' => $this->request->getPost('guardian_relationship') ?? 'father',
                'phone' => $this->request->getPost('guardian_phone'),
                'email' => $this->request->getPost('guardian_email'),
            ];
            #echo"<br>Data: <pre>"; print_r($data); echo "</pre>"; die;

            $student = $this->service->create(tenant_id(), (int) $data['branch_id'], $data);

            return redirect()->to('/admin/students')->with('success', 'Student admitted: ' . $student['student_code']);
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit($publicId)
    {
        $student = $this->service->find(tenant_id(), $publicId);
        if (! $student) return redirect()->to('/admin/students')->with('error', 'Student not found.');
        $branches = db_connect()->table('cd_branches')->where('tenant_id', tenant_id())->get()->getResultArray();
        return view('App\Modules\Admin\Views\students\edit', [
            'title' => 'Edit Student', 'student' => $student, 'branches' => $branches,
        ]);
    }

    public function update($publicId)
    {
        $data = $this->request->getPost([
            'name','dob','gender','phone','email','address_line1','city','state','pincode','status',
        ]);
        $this->service->update(tenant_id(), $publicId, $data);
        return redirect()->to('/admin/students')->with('success', 'Student updated.');
    }

    public function delete($publicId)
    {
        $this->service->delete(tenant_id(), $publicId);
        return redirect()->to('/admin/students')->with('success', 'Student removed.');
    }
}