<?php
namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
require_once __DIR__ . '/../Helpers/admin_helper.php';

class AttendanceController extends BaseController
{
    public function index()
    {
        $db = db_connect();
        $date = $this->request->getGet('date') ?? date('Y-m-d');
        $sessions = $db->table('cd_attendance_sessions')
            ->select('cd_attendance_sessions.*, cd_batches.batch_name, cd_batches.batch_code,
                      (SELECT COUNT(*) FROM cd_attendance_records WHERE session_id = cd_attendance_sessions.id AND attendance_status = "present") AS present_count,
                      (SELECT COUNT(*) FROM cd_attendance_records WHERE session_id = cd_attendance_sessions.id AND attendance_status = "absent") AS absent_count')
            ->join('cd_batches', 'cd_batches.id = cd_attendance_sessions.batch_id')
            ->where('cd_attendance_sessions.tenant_id', tenant_id())
            ->where('cd_attendance_sessions.attendance_date', $date)
            ->get()->getResultArray();

        // $sessions = $db->table('cd_attendance_sessions s')
        // ->select('s.*, b.batch_name, b.batch_code,
        //             (SELECT COUNT(*) FROM cd_attendance_records WHERE session_id = s.id AND attendance_status = "present") AS present_count,
        //             (SELECT COUNT(*) FROM cd_attendance_records WHERE session_id = s.id AND attendance_status = "absent") AS absent_count')
        // ->join('cd_batches b', 'b.id = s.batch_id')
        // ->where('s.tenant_id', tenant_id())
        // ->where('s.attendance_date', $date)
        // ->get()->getResultArray();
    

            // SELECT `cd_s`.*, `cd_b`.`batch_name`, `cd_b`.`batch_code`, 
            // (SELECT COUNT(*) FROM cd_attendance_records WHERE session_id = s.id AND attendance_status = "present") AS present_count, 
            // (SELECT COUNT(*) FROM cd_attendance_records WHERE session_id = s.id AND attendance_status = "absent") AS absent_count
            // FROM `cd_attendance_sessions` `s`
            // JOIN `cd_batches` `b` ON `b`.`id` = `s`.`batch_id`
            // WHERE `cd_s`.`tenant_id` = :s.tenant_id:
            // AND `cd_s`.`attendance_date` = :s.attendance_date:    
            // echo $db->getLastQuery(); // Debug: Show the last executed query
            // die;

        $batches = $db->table('cd_batches')
            ->where('tenant_id', tenant_id())->where('status', 'running')
            ->get()->getResultArray();

        return view('App\Modules\Admin\Views\attendance\index', [
            'title' => 'Attendance', 'sessions' => $sessions, 'batches' => $batches, 'date' => $date,
        ]);
    }

    public function mark()
    {
        $batchId = (int) ($this->request->getGet('batch_id') ?? 0);
        $date    = $this->request->getGet('date') ?? date('Y-m-d');
        $db      = db_connect();

        $batch = $db->table('cd_batches')
            ->where(['id' => $batchId, 'tenant_id' => tenant_id()])
            ->get()->getRowArray();
        if (! $batch) return redirect()->to('/admin/attendance')->with('error', 'Batch not found.');

        // Existing session?
        $session = $db->table('cd_attendance_sessions')
            ->where(['batch_id' => $batchId, 'attendance_date' => $date])
            ->get()->getRowArray();

        $students = $db->table('cd_batch_students bs')
            ->select('s.id, s.public_id, s.student_code, s.name')
            ->join('cd_students s', 's.id = bs.student_id')
            ->where(['bs.batch_id' => $batchId, 'bs.status' => 'active'])
            ->get()->getResultArray();

        $existing = [];
        if ($session) {
            $rows = $db->table('cd_attendance_records')
                ->where('session_id', $session['id'])->get()->getResultArray();
            foreach ($rows as $r) $existing[$r['student_id']] = $r['attendance_status'];
        }

        return view('App\Modules\Admin\Views\attendance\mark', [
            'title' => 'Mark Attendance', 'batch' => $batch, 'students' => $students,
            'date' => $date, 'existing' => $existing,
        ]);
    }

    public function save()
    {
        $batchId = (int) $this->request->getPost('batch_id');
        $date    = $this->request->getPost('attendance_date');
        $statuses = $this->request->getPost('status') ?? []; // [student_id => status]
        $db = db_connect();

        $db->transStart();

        $session = $db->table('cd_attendance_sessions')
            ->where(['batch_id' => $batchId, 'attendance_date' => $date])
            ->get()->getRowArray();

        if (! $session) {
            $db->table('cd_attendance_sessions')->insert([
                'public_id'       => uuid4(),
                'tenant_id'       => tenant_id(),
                'branch_id'       => branch_id() ?? 0,
                'batch_id'        => $batchId,
                'attendance_date' => $date,
                'status'          => 'submitted',
                'submitted_by'    => session()->get('user_id'),
                'submitted_at'    => date('Y-m-d H:i:s'),
            ]);
            $sessionId = $db->insertID();
        } else {
            $sessionId = $session['id'];
            $db->table('cd_attendance_sessions')->where('id', $sessionId)
                ->update(['submitted_at' => date('Y-m-d H:i:s')]);
        }

        foreach ($statuses as $studentId => $status) {
            if (! in_array($status, ['present','absent','late','half_day','on_leave'])) continue;
            $exists = $db->table('cd_attendance_records')
                ->where(['session_id' => $sessionId, 'student_id' => $studentId])->get()->getRow();
            if ($exists) {
                $db->table('cd_attendance_records')->where('id', $exists->id)
                    ->update(['attendance_status' => $status]);
            } else {
                $db->table('cd_attendance_records')->insert([
                    'tenant_id'         => tenant_id(),
                    'session_id'        => $sessionId,
                    'student_id'        => $studentId,
                    'attendance_status' => $status,
                ]);
            }
        }

        $db->transComplete();
        return redirect()->to('/admin/attendance?date='.$date)->with('success', 'Attendance saved.');
    }
}