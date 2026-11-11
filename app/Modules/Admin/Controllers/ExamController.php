<?php
namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
require_once __DIR__ . '/../Helpers/admin_helper.php';


class ExamController extends BaseController
{
    public function index()
    {
        $db = db_connect();
        // $exams = $db->table('cd_exams e')
        //     ->select('e.*, b.batch_name, (SELECT COUNT(*) FROM cd_exam_results WHERE exam_id = e.id) AS result_count')
        //     ->join('cd_batches b', 'b.id = e.batch_id')
        //     ->where('e.tenant_id', tenant_id())
        //     ->where('e.deleted_at IS NULL')
        //     ->orderBy('e.exam_date', 'desc')
        //     ->get()->getResultArray();
        $exams = $db->table('cd_exams')
            ->select('cd_exams.*, cd_batches.batch_name,
                      (SELECT COUNT(*) FROM cd_exam_results
                       WHERE cd_exam_results.exam_id = cd_exams.id) AS result_count')
            ->join('cd_batches', 'cd_batches.id = cd_exams.batch_id')
            ->where('cd_exams.tenant_id', tenant_id())
            ->where('cd_exams.deleted_at IS NULL')
            ->orderBy('cd_exams.exam_date', 'desc')
            ->get()->getResultArray();

        $batches = $db->table('cd_batches')
            ->where('tenant_id', tenant_id())->where('status', 'running')
            ->get()->getResultArray();

        return view('App\Modules\Admin\Views\exams\index', [
            'title' => 'Exams', 'exams' => $exams, 'batches' => $batches,
        ]);
    }

    public function create()
    {
        $db = db_connect();
        $data = $this->request->getPost(['batch_id','exam_name','exam_date']);
        if (empty($data['exam_name']) || empty($data['batch_id'])) {
            return redirect()->back()->with('error', 'Batch and exam name required.');
        }
        $db->table('cd_exams')->insert([
            'public_id'  => uuid4(),
            'tenant_id'  => tenant_id(),
            'branch_id'  => branch_id() ?? 0,
            'batch_id'   => (int) $data['batch_id'],
            'exam_name'  => $data['exam_name'],
            'exam_date'  => $data['exam_date'] ?? date('Y-m-d'),
            'status'     => 'scheduled',
        ]);
        return redirect()->to('/admin/exams')->with('success', 'Exam scheduled.');
    }

    public function marks($examPublicId)
    {
        $db = db_connect();
        $exam = $db->table('cd_exams')
            ->where(['public_id' => $examPublicId, 'tenant_id' => tenant_id()])
            ->get()->getRowArray();
        if (! $exam) return redirect()->to('/admin/exams')->with('error', 'Exam not found.');

        $students = $db->table('cd_batch_students bs')
            ->select('s.id, s.student_code, s.name')
            ->join('cd_students s', 's.id = bs.student_id')
            ->where(['bs.batch_id' => $exam['batch_id'], 'bs.status' => 'active'])
            ->get()->getResultArray();

        return view('App\Modules\Admin\Views\exams\marks', [
            'title' => 'Enter Marks', 'exam' => $exam, 'students' => $students,
        ]);
    }
}