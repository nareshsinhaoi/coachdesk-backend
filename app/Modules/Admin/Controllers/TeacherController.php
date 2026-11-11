<?php
namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
require_once __DIR__ . '/../Helpers/admin_helper.php';

class TeacherController extends BaseController
{
    public function index()
    {
        $teachers = db_connect()->table('cd_teachers')
            ->where('tenant_id', tenant_id())->where('deleted_at IS NULL')
            ->orderBy('id', 'desc')->get()->getResultArray();
        return view('App\Modules\Admin\Views\teachers\index', ['title' => 'Teachers', 'teachers' => $teachers]);
    }

    public function create()
    {
        return view('App\Modules\Admin\Views\teachers\form', ['title' => 'New Teacher', 'teacher' => null]);
    }

    public function store()
    {
        $db = db_connect();
        $db->table('cd_teachers')->insert([
            'public_id'    => uuid4(),
            'tenant_id'    => tenant_id(),
            'branch_id'    => branch_id(),
            'name'         => $this->request->getPost('name'),
            'qualification'=> $this->request->getPost('qualification'),
            'experience'   => $this->request->getPost('experience'),
            'phone'        => $this->request->getPost('phone'),
            'email'        => $this->request->getPost('email'),
            'joining_date' => $this->request->getPost('joining_date'),
            'status'       => 'active',
        ]);
        return redirect()->to('/admin/teachers')->with('success', 'Teacher added.');
    }
}