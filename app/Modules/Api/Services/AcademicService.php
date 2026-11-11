<?php

namespace App\Modules\Api\Services;

class AcademicService
{
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    /* ---------------- COURSES ---------------- */

    public function listCourses(int $tenantId, ?int $branchId, array $f, int $page, int $limit): array
    {
        $b = $this->db->table('cd_courses')
            ->where('tenant_id', $tenantId)
            ->where('deleted_at IS NULL');
        if ($branchId) $b->where('branch_id', $branchId);
        if (! empty($f['status'])) $b->where('status', $f['status']);
        if (! empty($f['search'])) $b->groupStart()->like('course_name', $f['search'])->orLike('course_code', $f['search'])->groupEnd();

        $total = (clone $b)->countAllResults(false);
        $rows  = $b->orderBy('id', 'desc')->limit($limit, ($page - 1) * $limit)->get()->getResultArray();

        return ['items' => $rows, 'pagination' => $this->pag($page, $limit, $total)];
    }

    public function createCourse(int $tenantId, ?int $branchId, array $data): array
    {
        $code = trim($data['course_code'] ?? '');
        if ($code === '') throw new \RuntimeException('course_code required');

        $exists = $this->db->table('cd_courses')
            ->where(['tenant_id' => $tenantId, 'course_code' => $code])
            ->countAllResults();
        if ($exists) throw new \RuntimeException('Course code already exists');

        $row = [
            'public_id'   => $this->uuid(),
            'tenant_id'   => $tenantId,
            'branch_id'   => $branchId,
            'course_name' => $data['course_name'],
            'course_code' => $code,
            'duration'    => $data['duration'] ?? null,
            'fees'        => $data['fees'] ?? 0,
            'description' => $data['description'] ?? null,
            'status'      => $data['status'] ?? 'active',
        ];
        $this->db->table('cd_courses')->insert($row);
        $row['id'] = $this->db->insertID();
        return $row;
    }

    public function updateCourse(int $tenantId, string $publicId, array $data): ?array
    {
        $row = $this->db->table('cd_courses')
            ->where(['tenant_id' => $tenantId, 'public_id' => $publicId, 'deleted_at' => null])
            ->get()->getRowArray();
        if (! $row) return null;

        $allowed = ['course_name', 'duration', 'fees', 'description', 'status'];
        $update = array_intersect_key($data, array_flip($allowed));
        if ($update) {
            $this->db->table('cd_courses')->where('id', $row['id'])->update($update);
        }
        return $this->db->table('cd_courses')->where('id', $row['id'])->get()->getRowArray();
    }

    public function deleteCourse(int $tenantId, string $publicId): bool
    {
        $row = $this->db->table('cd_courses')
            ->where(['tenant_id' => $tenantId, 'public_id' => $publicId, 'deleted_at' => null])
            ->get()->getRow();
        if (! $row) return false;

        // Prevent delete if active batches exist
        $active = $this->db->table('cd_batches')
            ->where('course_id', $row->id)
            ->where('deleted_at IS NULL')
            ->whereIn('status', ['upcoming', 'running'])
            ->countAllResults();
        if ($active > 0) {
            throw new \RuntimeException('Cannot delete course with active batches');
        }

        $this->db->table('cd_courses')->where('id', $row->id)
            ->update(['deleted_at' => date('Y-m-d H:i:s'), 'status' => 'inactive']);
        return true;
    }

    /* ---------------- SUBJECTS ---------------- */

    public function listSubjects(int $tenantId, array $f, int $page, int $limit): array
    {
        $b = $this->db->table('cd_subjects')
            ->where('tenant_id', $tenantId)
            ->where('deleted_at IS NULL');
        if (! empty($f['search'])) $b->like('subject_name', $f['search']);

        $total = (clone $b)->countAllResults(false);
        $rows = $b->orderBy('subject_name', 'asc')->limit($limit, ($page - 1) * $limit)->get()->getResultArray();

        return ['items' => $rows, 'pagination' => $this->pag($page, $limit, $total)];
    }

    public function createSubject(int $tenantId, array $data): array
    {
        $row = [
            'public_id'    => $this->uuid(),
            'tenant_id'    => $tenantId,
            'subject_name' => $data['subject_name'],
            'subject_code' => $data['subject_code'] ?? null,
            'description'  => $data['description'] ?? null,
            'status'       => $data['status'] ?? 'active',
        ];
        $this->db->table('cd_subjects')->insert($row);
        $row['id'] = $this->db->insertID();
        return $row;
    }

    public function attachSubjectsToCourse(int $tenantId, string $coursePublicId, array $subjectPublicIds): array
    {
        $course = $this->db->table('cd_courses')
            ->where(['tenant_id' => $tenantId, 'public_id' => $coursePublicId])
            ->get()->getRow();
        if (! $course) throw new \RuntimeException('Course not found');

        $subjects = $this->db->table('cd_subjects')
            ->where('tenant_id', $tenantId)
            ->whereIn('public_id', $subjectPublicIds)
            ->get()->getResultArray();

        $linked = [];
        foreach ($subjects as $s) {
            $exists = $this->db->table('cd_course_subjects')
                ->where(['course_id' => $course->id, 'subject_id' => $s['id']])
                ->countAllResults();
            if (! $exists) {
                $this->db->table('cd_course_subjects')->insert([
                    'course_id'  => $course->id,
                    'subject_id' => $s['id'],
                ]);
            }
            $linked[] = $s;
        }
        return $linked;
    }

    /* ---------------- BATCHES ---------------- */

    public function listBatches(int $tenantId, ?int $branchId, array $f, int $page, int $limit): array
    {
        $b = $this->db->table('cd_batches b')
            ->select('b.*, c.course_name, c.course_code')
            ->join('cd_courses c', 'c.id = b.course_id', 'left')
            ->where('b.tenant_id', $tenantId)
            ->where('b.deleted_at IS NULL');
        if ($branchId) $b->where('b.branch_id', $branchId);
        if (! empty($f['course_id'])) $b->where('b.course_id', (int) $f['course_id']);
        if (! empty($f['status']))    $b->where('b.status', $f['status']);
        if (! empty($f['search']))    $b->like('b.batch_name', $f['search']);

        $total = (clone $b)->countAllResults(false);
        $rows  = $b->orderBy('b.id', 'desc')->limit($limit, ($page - 1) * $limit)->get()->getResultArray();

        return ['items' => $rows, 'pagination' => $this->pag($page, $limit, $total)];
    }

    public function createBatch(int $tenantId, int $branchId, array $data): array
    {
        $course = $this->db->table('cd_courses')
            ->where(['tenant_id' => $tenantId, 'id' => (int) ($data['course_id'] ?? 0)])
            ->get()->getRow();
        if (! $course) throw new \RuntimeException('Invalid course');

        $code = trim($data['batch_code'] ?? '');
        if ($code === '') throw new \RuntimeException('batch_code required');

        $exists = $this->db->table('cd_batches')
            ->where(['tenant_id' => $tenantId, 'batch_code' => $code])
            ->countAllResults();
        if ($exists) throw new \RuntimeException('Batch code already exists');

        $row = [
            'public_id'  => $this->uuid(),
            'tenant_id'  => $tenantId,
            'branch_id'  => $branchId,
            'course_id'  => (int) $data['course_id'],
            'academic_session_id' => $data['academic_session_id'] ?? null,
            'batch_name' => $data['batch_name'],
            'batch_code' => $code,
            'capacity'   => (int) ($data['capacity'] ?? 30),
            'start_date' => $data['start_date'] ?? null,
            'end_date'   => $data['end_date'] ?? null,
            'timing_start' => $data['timing_start'] ?? null,
            'timing_end'   => $data['timing_end'] ?? null,
            'status'     => $data['status'] ?? 'upcoming',
        ];
        $this->db->table('cd_batches')->insert($row);
        $row['id'] = $this->db->insertID();
        return $row;
    }

    public function enrollStudent(int $tenantId, string $batchPublicId, string $studentPublicId): array
    {
        $batch = $this->db->table('cd_batches')
            ->where(['tenant_id' => $tenantId, 'public_id' => $batchPublicId, 'deleted_at' => null])
            ->get()->getRow();
        if (! $batch) throw new \RuntimeException('Batch not found');

        $student = $this->db->table('cd_students')
            ->where(['tenant_id' => $tenantId, 'public_id' => $studentPublicId, 'deleted_at' => null])
            ->get()->getRow();
        if (! $student) throw new \RuntimeException('Student not found');

        // Capacity check
        $enrolled = $this->db->table('cd_batch_students')
            ->where(['batch_id' => $batch->id, 'status' => 'active'])
            ->countAllResults();
        if ($enrolled >= $batch->capacity) throw new \RuntimeException('Batch is full');

        // Duplicate check
        $already = $this->db->table('cd_batch_students')
            ->where(['batch_id' => $batch->id, 'student_id' => $student->id, 'status' => 'active'])
            ->countAllResults();
        if ($already) throw new \RuntimeException('Student already enrolled');

        $this->db->table('cd_batch_students')->insert([
            'tenant_id'  => $tenantId,
            'batch_id'   => $batch->id,
            'student_id' => $student->id,
            'start_date' => date('Y-m-d'),
            'status'     => 'active',
        ]);

        return ['batch' => $batch->batch_name, 'student' => $student->name, 'enrolled' => true];
    }

    public function listBatchStudents(int $tenantId, string $batchPublicId): array
    {
        return $this->db->table('cd_batch_students bs')
            ->select('s.public_id, s.student_code, s.name, s.phone, s.status, bs.start_date, bs.status AS enrollment_status')
            ->join('cd_students s', 's.id = bs.student_id')
            ->join('cd_batches b', 'b.id = bs.batch_id')
            ->where('b.tenant_id', $tenantId)
            ->where('b.public_id', $batchPublicId)
            ->where('bs.status', 'active')
            ->get()->getResultArray();
    }

    /* ---------------- HELPERS ---------------- */

    protected function pag(int $page, int $limit, int $total): array
    {
        return ['page' => $page, 'limit' => $limit, 'total' => $total, 'totalPages' => (int) ceil($total / max(1, $limit))];
    }

    protected function uuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}