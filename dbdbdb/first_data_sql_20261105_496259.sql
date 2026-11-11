-- =====================================================================
-- CoachDesk — Dummy Seed Data (realistic, minimal)
-- Password for ALL seeded users: Password@123
-- Run AFTER coachdesk_schema.sql
-- =====================================================================
USE u816304761_coachdesk;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Cleanup (safe re-run)
-- ---------------------------------------------------------------------
TRUNCATE TABLE cd_activity_logs;
TRUNCATE TABLE cd_notification_logs;
TRUNCATE TABLE cd_notifications;
TRUNCATE TABLE cd_notification_templates;
TRUNCATE TABLE cd_announcements;
TRUNCATE TABLE cd_homework_submissions;
TRUNCATE TABLE cd_homework;
TRUNCATE TABLE cd_study_materials;
TRUNCATE TABLE cd_exam_result_details;
TRUNCATE TABLE cd_exam_results;
TRUNCATE TABLE cd_exam_subjects;
TRUNCATE TABLE cd_exams;
TRUNCATE TABLE cd_timetable;
TRUNCATE TABLE cd_fee_adjustments;
TRUNCATE TABLE cd_payment_refunds;
TRUNCATE TABLE cd_payment_allocations;
TRUNCATE TABLE cd_payments;
TRUNCATE TABLE cd_fee_installments;
TRUNCATE TABLE cd_student_fee_accounts;
TRUNCATE TABLE cd_fee_structure_items;
TRUNCATE TABLE cd_fee_structures;
TRUNCATE TABLE cd_student_leave_requests;
TRUNCATE TABLE cd_attendance_records;
TRUNCATE TABLE cd_attendance_sessions;
TRUNCATE TABLE cd_batch_students;
TRUNCATE TABLE cd_student_documents;
TRUNCATE TABLE cd_student_guardians;
TRUNCATE TABLE cd_students;
TRUNCATE TABLE cd_batch_subjects;
TRUNCATE TABLE cd_batches;
TRUNCATE TABLE cd_teachers;
TRUNCATE TABLE cd_classrooms;
TRUNCATE TABLE cd_course_subjects;
TRUNCATE TABLE cd_subjects;
TRUNCATE TABLE cd_courses;
TRUNCATE TABLE cd_academic_sessions;
TRUNCATE TABLE cd_user_permissions;
TRUNCATE TABLE cd_user_roles;
TRUNCATE TABLE cd_role_permissions;
TRUNCATE TABLE cd_permissions;
TRUNCATE TABLE cd_password_resets;
TRUNCATE TABLE cd_refresh_tokens;
TRUNCATE TABLE cd_users;
TRUNCATE TABLE cd_roles;
TRUNCATE TABLE cd_tenant_subscriptions;
TRUNCATE TABLE cd_subscription_payments;
TRUNCATE TABLE cd_branches;
TRUNCATE TABLE cd_tenants;
TRUNCATE TABLE cd_settings;
TRUNCATE TABLE cd_platform_settings;
TRUNCATE TABLE cd_support_tickets;
TRUNCATE TABLE cd_expenses;
TRUNCATE TABLE cd_file_uploads;

-- =====================================================================
-- 1. TENANTS + BRANCHES
-- =====================================================================
INSERT INTO cd_tenants
  (public_id, name, code, email, phone, website, address_line1, city, state, pincode, status, trial_ends_at)
VALUES
  ('11111111-1111-1111-1111-111111111111','ABC Coaching Institute','abc-coaching','owner@abc.edu','9810012345','https://abc-coaching.in','Sector 62','Greater Noida','Uttar Pradesh','201309','active', NULL),
  ('22222222-2222-2222-2222-222222222222','XYZ Academy','xyz-academy','owner@xyz.edu','9820011223','https://xyzacademy.in','Lajpat Nagar','New Delhi','Delhi','110024','trial', DATE_ADD(NOW(), INTERVAL 14 DAY));

SET @t_abc = (SELECT id FROM cd_tenants WHERE code = 'abc-coaching');
SET @t_xyz = (SELECT id FROM cd_tenants WHERE code = 'xyz-academy');

INSERT INTO cd_branches
  (public_id, tenant_id, name, branch_code, address_line1, city, state, pincode, phone, email, status)
VALUES
  ('b1111111-1111-1111-1111-111111111111', @t_abc,'Greater Noida Branch','GN','Sector 62','Greater Noida','Uttar Pradesh','201309','9810012345','gn@abc.edu','active'),
  ('b1111111-2222-2222-2222-222222222222', @t_abc,'Delhi Branch',        'DL','Lajpat Nagar','New Delhi','Delhi','110024','9810012346','delhi@abc.edu','active'),
  ('b2222222-1111-1111-1111-111111111111', @t_xyz,'Delhi Branch',        'DL','Lajpat Nagar','New Delhi','Delhi','110024','9820011223','delhi@xyz.edu','active');

SET @b_abc_gn = (SELECT id FROM cd_branches WHERE tenant_id=@t_abc AND branch_code='GN');
SET @b_abc_dl = (SELECT id FROM cd_branches WHERE tenant_id=@t_abc AND branch_code='DL');
SET @b_xyz_dl = (SELECT id FROM cd_branches WHERE tenant_id=@t_xyz AND branch_code='DL');

-- =====================================================================
-- 2. SUBSCRIPTIONS + PAYMENTS
-- =====================================================================
SET @plan_basic  = (SELECT id FROM cd_subscription_plans WHERE name='Basic');
SET @plan_pro    = (SELECT id FROM cd_subscription_plans WHERE name='Professional');

INSERT INTO cd_tenant_subscriptions
  (public_id, tenant_id, plan_id, start_date, end_date, status, auto_renew)
VALUES
  (UUID(), @t_abc, @plan_pro,   DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 335 DAY), 'active', 1),
  (UUID(), @t_xyz, @plan_basic, CURDATE(),                            DATE_ADD(CURDATE(), INTERVAL 14 DAY),  'trial',  1);

SET @sub_abc = (SELECT id FROM cd_tenant_subscriptions WHERE tenant_id=@t_abc ORDER BY id DESC LIMIT 1);
SET @sub_xyz = (SELECT id FROM cd_tenant_subscriptions WHERE tenant_id=@t_xyz ORDER BY id DESC LIMIT 1);

INSERT INTO cd_subscription_payments
  (public_id, tenant_id, subscription_id, amount, currency, method, transaction_id, idempotency_key, status, paid_at)
VALUES
  (UUID(), @t_abc, @sub_abc, 1999.00,'INR','upi','UPI-TXN-ABC-0001','idem-abc-2026-01','success', DATE_SUB(NOW(), INTERVAL 30 DAY));

-- =====================================================================
-- 3. ROLES  (system roles already seeded; assign branch-scoped via user_roles)
-- =====================================================================
SET @r_super = (SELECT id FROM cd_roles WHERE slug='super_admin'  LIMIT 1);
SET @r_owner = (SELECT id FROM cd_roles WHERE slug='owner'        LIMIT 1);
SET @r_badm  = (SELECT id FROM cd_roles WHERE slug='branch_admin' LIMIT 1);
SET @r_acc   = (SELECT id FROM cd_roles WHERE slug='accountant'   LIMIT 1);
SET @r_teach = (SELECT id FROM cd_roles WHERE slug='teacher'      LIMIT 1);
SET @r_recp  = (SELECT id FROM cd_roles WHERE slug='receptionist' LIMIT 1);
SET @r_std   = (SELECT id FROM cd_roles WHERE slug='student'      LIMIT 1);
SET @r_par   = (SELECT id FROM cd_roles WHERE slug='parent'       LIMIT 1);

-- =====================================================================
-- 4. PERMISSIONS  (curated core set; ~45 rows, expandable later)
-- =====================================================================
INSERT INTO cd_permissions (permission_key, module, description) VALUES
-- dashboard
('dashboard.view','dashboard','View dashboard'),
-- institutes / branches
('institutes.view','institutes','View institute profile'),
('institutes.edit','institutes','Edit institute profile'),
('branches.view','branches','View branches'),
('branches.create','branches','Create branch'),
('branches.edit','branches','Edit branch'),
('branches.delete','branches','Delete branch'),
-- users / roles
('users.view','users','View users'),
('users.create','users','Create user'),
('users.edit','users','Edit user'),
('users.delete','users','Delete user'),
('roles.view','roles','View roles'),
('roles.manage','roles','Create/edit roles & permissions'),
-- students
('students.view','students','View students'),
('students.create','students','Admit new student'),
('students.edit','students','Edit student'),
('students.delete','students','Cancel/delete student'),
('students.import','students','Import students from Excel'),
('students.export','students','Export students'),
-- academic
('courses.view','courses','View courses'),
('courses.manage','courses','Create/edit courses'),
('subjects.view','subjects','View subjects'),
('subjects.manage','subjects','Create/edit subjects'),
('batches.view','batches','View batches'),
('batches.manage','batches','Create/edit batches'),
('teachers.view','teachers','View teachers'),
('teachers.manage','teachers','Create/edit teachers'),
('timetable.view','timetable','View timetable'),
('timetable.manage','timetable','Create/edit timetable'),
-- attendance
('attendance.view','attendance','View attendance'),
('attendance.mark','attendance','Mark attendance'),
('attendance.edit','attendance','Edit attendance'),
-- fees
('fees.view','fees','View fee records'),
('fees.collect','fees','Collect payment'),
('fees.refund','fees','Process refund'),
('fees.reports','fees','View fee reports'),
-- exams
('exams.view','exams','View exams'),
('exams.create','exams','Create exams'),
('exams.enter_marks','exams','Enter marks'),
('exams.publish_results','exams','Publish results'),
-- homework / materials
('homework.view','homework','View homework'),
('homework.manage','homework','Create/edit homework'),
('materials.view','materials','View study materials'),
('materials.manage','materials','Upload study materials'),
-- reports / comms / support
('reports.view','reports','View reports'),
('notifications.manage','notifications','Manage notification templates'),
('support.view','support','View support tickets'),
('support.manage','support','Manage support tickets'),
('settings.view','settings','View settings'),
('settings.manage','settings','Manage settings');

-- Quick lookup CTE-style via INSERT SELECT
-- Owner gets ALL permissions
INSERT INTO cd_role_permissions (role_id, permission_id)
SELECT @r_owner, id FROM cd_permissions;

-- Super Admin gets all permissions too (platform)
INSERT INTO cd_role_permissions (role_id, permission_id)
SELECT @r_super, id FROM cd_permissions;

-- Branch Admin: everything except refunds, role mgmt, settings.manage
INSERT INTO cd_role_permissions (role_id, permission_id)
SELECT @r_badm, id FROM cd_permissions
WHERE permission_key NOT IN ('roles.manage','fees.refund','settings.manage','institutes.edit');

-- Accountant: fees + reports + students.view
INSERT INTO cd_role_permissions (role_id, permission_id)
SELECT @r_acc, id FROM cd_permissions
WHERE permission_key IN (
  'dashboard.view','fees.view','fees.collect','fees.reports','reports.view',
  'students.view','students.export'
);

-- Teacher: attendance, exams, homework, timetable
INSERT INTO cd_role_permissions (role_id, permission_id)
SELECT @r_teach, id FROM cd_permissions
WHERE permission_key IN (
  'dashboard.view','students.view','batches.view','courses.view','subjects.view',
  'attendance.view','attendance.mark','exams.view','exams.enter_marks',
  'homework.view','homework.manage','materials.view','materials.manage',
  'timetable.view'
);

-- Receptionist: admissions + basic collection
INSERT INTO cd_role_permissions (role_id, permission_id)
SELECT @r_recp, id FROM cd_permissions
WHERE permission_key IN (
  'dashboard.view','students.view','students.create','students.edit','students.import',
  'courses.view','subjects.view','batches.view','fees.view','fees.collect'
);

-- Student / Parent: read-only portals
INSERT INTO cd_role_permissions (role_id, permission_id)
SELECT @r_std, id FROM cd_permissions
WHERE permission_key IN (
  'dashboard.view','attendance.view','fees.view','exams.view','homework.view',
  'materials.view','timetable.view'
);
INSERT INTO cd_role_permissions (role_id, permission_id)
SELECT @r_par, id FROM cd_permissions
WHERE permission_key IN (
  'dashboard.view','attendance.view','fees.view','exams.view','timetable.view'
);

-- =====================================================================
-- 5. USERS  (password = Password@123)
-- =====================================================================
SET @PWD = '$2y$10$e0NRVQ0kY1hZ9mF3T7YzQO7QnJ8W7fQZpQK0sVvJ3z9QxH8o9kK5e'; -- BCrypt of Password@123

INSERT INTO cd_users
  (public_id, tenant_id, name, email, phone, password_hash, status, email_verified_at)
VALUES
  -- platform super admin
  ('u0000000-0000-0000-0000-000000000001', NULL, 'Platform Super Admin', 'super@coachdesk.in', '9000000001', @PWD,'active', NOW()),
  -- ABC tenant
  ('u1000000-0000-0000-0000-000000000001', @t_abc,'Rajesh Kumar (Owner)',    'owner@abc.edu',    '9810012345', @PWD,'active', NOW()),
  ('u1000000-0000-0000-0000-000000000002', @t_abc,'Sunita Sharma (GN Admin)','admin.gn@abc.edu', '9810012346', @PWD,'active', NOW()),
  ('u1000000-0000-0000-0000-000000000003', @t_abc,'Vikas Gupta (Accountant)','acc@abc.edu',     '9810012347', @PWD,'active', NOW()),
  ('u1000000-0000-0000-0000-000000000004', @t_abc,'Amit Verma (Teacher)',    'amit@abc.edu',    '9810012348', @PWD,'active', NOW()),
  ('u1000000-0000-0000-0000-000000000005', @t_abc,'Priya Singh (Teacher)',   'priya@abc.edu',   '9810012349', @PWD,'active', NOW()),
  ('u1000000-0000-0000-0000-000000000006', @t_abc,'Neha Receptionist',       'reception@abc.edu','9810012350',@PWD,'active', NOW()),
  -- XYZ tenant
  ('u2000000-0000-0000-0000-000000000001', @t_xyz,'Anil Yadav (Owner)',      'owner@xyz.edu',   '9820011223', @PWD,'active', NOW());

SET @u_super = (SELECT id FROM cd_users WHERE email='super@coachdesk.in');
SET @u_abc_owner = (SELECT id FROM cd_users WHERE email='owner@abc.edu');
SET @u_abc_gnadm = (SELECT id FROM cd_users WHERE email='admin.gn@abc.edu');
SET @u_abc_acc   = (SELECT id FROM cd_users WHERE email='acc@abc.edu');
SET @u_abc_amit  = (SELECT id FROM cd_users WHERE email='amit@abc.edu');
SET @u_abc_priya = (SELECT id FROM cd_users WHERE email='priya@abc.edu');
SET @u_abc_recp  = (SELECT id FROM cd_users WHERE email='reception@abc.edu');
SET @u_xyz_owner = (SELECT id FROM cd_users WHERE email='owner@xyz.edu');

-- assign roles (branch-scoped where relevant)
INSERT INTO cd_user_roles (user_id, role_id, branch_id) VALUES
  (@u_super,    @r_super, NULL),
  (@u_abc_owner,@r_owner, NULL),
  (@u_abc_gnadm,@r_badm,  @b_abc_gn),
  (@u_abc_acc,  @r_acc,   NULL),
  (@u_abc_amit, @r_teach, @b_abc_gn),
  (@u_abc_priya,@r_teach, @b_abc_gn),
  (@u_abc_recp, @r_recp,  @b_abc_gn),
  (@u_xyz_owner,@r_owner, NULL);

-- =====================================================================
-- 6. ACADEMIC SESSION / COURSES / SUBJECTS / TEACHERS / ROOMS / BATCHES
-- =====================================================================
INSERT INTO cd_academic_sessions (public_id, tenant_id, name, start_date, end_date, is_current, status) VALUES
  ('a1111111-0000-0000-0000-000000000001', @t_abc,'2025-2026', '2025-04-01','2026-03-31',1,'active'),
  ('a2222222-0000-0000-0000-000000000001', @t_xyz,'2025-2026', '2025-04-01','2026-03-31',1,'active');
SET @sess_abc = (SELECT id FROM cd_academic_sessions WHERE tenant_id=@t_abc LIMIT 1);
SET @sess_xyz = (SELECT id FROM cd_academic_sessions WHERE tenant_id=@t_xyz LIMIT 1);

INSERT INTO cd_courses (public_id, tenant_id, branch_id, course_name, course_code, duration, fees, description, status) VALUES
  (UUID(), @t_abc, @b_abc_gn,'Class 10 Maths',      'C10-MATH','12 Months',12000,'Class 10 Mathematics','active'),
  (UUID(), @t_abc, @b_abc_gn,'IIT-JEE Foundation',  'JEE-FDN', '12 Months',18000,'JEE Foundation','active'),
  (UUID(), @t_abc, @b_abc_gn,'Spoken English',      'SPK-ENG', '3 Months', 5000, 'Spoken English','active'),
  (UUID(), @t_xyz, @b_xyz_dl,'SSC Preparation',     'SSC-01',  '6 Months',15000,'SSC CGL Preparation','active');

SET @c1 = (SELECT id FROM cd_courses WHERE course_code='C10-MATH' AND tenant_id=@t_abc);
SET @c2 = (SELECT id FROM cd_courses WHERE course_code='JEE-FDN'  AND tenant_id=@t_abc);
SET @c3 = (SELECT id FROM cd_courses WHERE course_code='SPK-ENG'  AND tenant_id=@t_abc);
SET @c4 = (SELECT id FROM cd_courses WHERE course_code='SSC-01'   AND tenant_id=@t_xyz);

INSERT INTO cd_subjects (public_id, tenant_id, subject_name, subject_code, status) VALUES
  (UUID(), @t_abc,'Mathematics','MATH','active'),
  (UUID(), @t_abc,'Physics',    'PHY','active'),
  (UUID(), @t_abc,'Chemistry',  'CHEM','active'),
  (UUID(), @t_abc,'English',    'ENG','active'),
  (UUID(), @t_xyz,'Reasoning',  'REA','active'),
  (UUID(), @t_xyz,'English',    'ENG2','active');

SET @sub_math  = (SELECT id FROM cd_subjects WHERE subject_code='MATH' AND tenant_id=@t_abc);
SET @sub_phy   = (SELECT id FROM cd_subjects WHERE subject_code='PHY'  AND tenant_id=@t_abc);
SET @sub_chem  = (SELECT id FROM cd_subjects WHERE subject_code='CHEM' AND tenant_id=@t_abc);
SET @sub_eng   = (SELECT id FROM cd_subjects WHERE subject_code='ENG'  AND tenant_id=@t_abc);
SET @sub_rea   = (SELECT id FROM cd_subjects WHERE subject_code='REA'  AND tenant_id=@t_xyz);
SET @sub_eng2  = (SELECT id FROM cd_subjects WHERE subject_code='ENG2' AND tenant_id=@t_xyz);

INSERT INTO cd_course_subjects (course_id, subject_id) VALUES
  (@c1,@sub_math),(@c2,@sub_phy),(@c2,@sub_chem),(@c2,@sub_math),(@c3,@sub_eng),(@c4,@sub_rea),(@c4,@sub_eng2);

INSERT INTO cd_classrooms (public_id, tenant_id, branch_id, room_name, capacity, status) VALUES
  (UUID(), @t_abc, @b_abc_gn,'Room A101',30,'active'),
  (UUID(), @t_abc, @b_abc_gn,'Room A102',30,'active'),
  (UUID(), @t_abc, @b_abc_dl,'Room D201',25,'active'),
  (UUID(), @t_xyz, @b_xyz_dl,'Room X301',20,'active');
SET @room_a101 = (SELECT id FROM cd_classrooms WHERE tenant_id=@t_abc AND room_name='Room A101');
SET @room_a102 = (SELECT id FROM cd_classrooms WHERE tenant_id=@t_abc AND room_name='Room A102');

INSERT INTO cd_teachers
  (public_id, tenant_id, branch_id, user_id, employee_code, name, qualification, experience, phone, email, joining_date, status)
VALUES
  (UUID(), @t_abc, @b_abc_gn, @u_abc_amit, 'EMP-001','Amit Verma', 'M.Sc Maths', '8 years', '9810012348','amit@abc.edu', DATE_SUB(CURDATE(), INTERVAL 730 DAY),'active'),
  (UUID(), @t_abc, @b_abc_gn, @u_abc_priya,'EMP-002','Priya Singh','M.Sc Physics','5 years','9810012349','priya@abc.edu',DATE_SUB(CURDATE(), INTERVAL 500 DAY),'active');
SET @t_amit  = (SELECT id FROM cd_teachers WHERE employee_code='EMP-001');
SET @t_priya = (SELECT id FROM cd_teachers WHERE employee_code='EMP-002');

INSERT INTO cd_batches
  (public_id, tenant_id, branch_id, course_id, academic_session_id, batch_name, batch_code, capacity, start_date, timing_start, timing_end, status)
VALUES
  (UUID(), @t_abc, @b_abc_gn, @c1, @sess_abc,'Class 10 Maths - Morning', 'MATH-A',  30, CURDATE(), '08:00:00','09:00:00','running'),
  (UUID(), @t_abc, @b_abc_gn, @c1, @sess_abc,'Class 10 Maths - Evening', 'MATH-B',  30, CURDATE(), '18:00:00','19:00:00','running'),
  (UUID(), @t_abc, @b_abc_gn, @c2, @sess_abc,'JEE Foundation - Weekend', 'JEE-WE',  25, CURDATE(), '10:00:00','12:00:00','running'),
  (UUID(), @t_xyz, @b_xyz_dl, @c4, @sess_xyz,'SSC Evening Batch',        'SSC-EVE', 20, CURDATE(), '17:00:00','19:00:00','running');

SET @b_math_a = (SELECT id FROM cd_batches WHERE batch_code='MATH-A');
SET @b_math_b = (SELECT id FROM cd_batches WHERE batch_code='MATH-B');
SET @b_jee_we = (SELECT id FROM cd_batches WHERE batch_code='JEE-WE');

INSERT INTO cd_batch_subjects (batch_id, subject_id, teacher_id) VALUES
  (@b_math_a, @sub_math, @t_amit),
  (@b_math_b, @sub_math, @t_amit),
  (@b_jee_we, @sub_phy,  @t_priya),
  (@b_jee_we, @sub_chem, @t_amit),
  (@b_jee_we, @sub_math, @t_amit);

-- =====================================================================
-- 7. STUDENTS + GUARDIANS (12 students across batches)
-- =====================================================================
INSERT INTO cd_students
  (public_id, tenant_id, branch_id, student_code, name, dob, gender, phone, email, city, state, admission_date, status)
VALUES
  ('s0000001-0000-0000-0000-000000000001', @t_abc, @b_abc_gn,'CD-GN-2026-00001','Rahul Sharma',   '2008-05-15','male','9876543210','rahul@example.com','Greater Noida','Uttar Pradesh', CURDATE(),'active'),
  ('s0000002-0000-0000-0000-000000000002', @t_abc, @b_abc_gn,'CD-GN-2026-00002','Amit Singh',     '2008-03-10','male','9876543211','amit.s@example.com','Greater Noida','Uttar Pradesh', CURDATE(),'active'),
  ('s0000003-0000-0000-0000-000000000003', @t_abc, @b_abc_gn,'CD-GN-2026-00003','Priya Verma',    '2008-07-22','female','9876543212','priya.v@example.com','Greater Noida','Uttar Pradesh', CURDATE(),'active'),
  ('s0000004-0000-0000-0000-000000000004', @t_abc, @b_abc_gn,'CD-GN-2026-00004','Neha Gupta',     '2008-11-02','female','9876543213','neha@example.com','Greater Noida','Uttar Pradesh', CURDATE(),'active'),
  ('s0000005-0000-0000-0000-000000000005', @t_abc, @b_abc_gn,'CD-GN-2026-00005','Rohit Kumar',    '2008-01-30','male','9876543214','rohit@example.com','Greater Noida','Uttar Pradesh', CURDATE(),'active'),
  ('s0000006-0000-0000-0000-000000000006', @t_abc, @b_abc_gn,'CD-GN-2026-00006','Anjali Mehta',   '2008-09-14','female','9876543215','anjali@example.com','Greater Noida','Uttar Pradesh', CURDATE(),'active'),
  ('s0000007-0000-0000-0000-000000000007', @t_abc, @b_abc_gn,'CD-GN-2026-00007','Vikram Chauhan', '2008-12-05','male','9876543216','vikram@example.com','Greater Noida','Uttar Pradesh', CURDATE(),'active'),
  ('s0000008-0000-0000-0000-000000000008', @t_abc, @b_abc_gn,'CD-GN-2026-00008','Sneha Rani',     '2008-06-18','female','9876543217','sneha@example.com','Greater Noida','Uttar Pradesh', CURDATE(),'active'),
  ('s0000009-0000-0000-0000-000000000009', @t_abc, @b_abc_gn,'CD-GN-2026-00009','Karan Bhatia',   '2008-04-25','male','9876543218','karan@example.com','Greater Noida','Uttar Pradesh', CURDATE(),'active'),
  ('s0000010-0000-0000-0000-000000000010', @t_abc, @b_abc_gn,'CD-GN-2026-00010','Deepa Nair',     '2008-08-08','female','9876543219','deepa@example.com','Greater Noida','Uttar Pradesh', CURDATE(),'active'),
  -- XYZ tenant students
  ('s0000011-0000-0000-0000-000000000011', @t_xyz, @b_xyz_dl,'XYZ-DL-2026-00001','Arjun Reddy',   '2007-02-11','male','9812340001','arjun@example.com','New Delhi','Delhi', CURDATE(),'active'),
  ('s0000012-0000-0000-0000-000000000012', @t_xyz, @b_xyz_dl,'XYZ-DL-2026-00002','Meera Iyer',    '2007-05-19','female','9812340002','meera@example.com','New Delhi','Delhi', CURDATE(),'active');

SET @st1  = (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00001');
SET @st2  = (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00002');
SET @st3  = (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00003');
SET @st4  = (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00004');
SET @st5  = (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00005');
SET @st6  = (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00006');
SET @st7  = (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00007');
SET @st8  = (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00008');
SET @st9  = (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00009');
SET @st10 = (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00010');
SET @st11 = (SELECT id FROM cd_students WHERE student_code='XYZ-DL-2026-00001');
SET @st12 = (SELECT id FROM cd_students WHERE student_code='XYZ-DL-2026-00002');

-- guardians (one primary each)
INSERT INTO cd_student_guardians
  (public_id, tenant_id, student_id, name, relationship, phone, email, occupation, is_primary)
VALUES
  (UUID(),@t_abc,@st1,'Amit Sharma','father','9876543200','amit.sharma@example.com','Business',1),
  (UUID(),@t_abc,@st2,'Rajendra Singh','father','9876543201','raj.singh@example.com','Engineer',1),
  (UUID(),@t_abc,@st3,'Manish Verma','father','9876543202','manish.v@example.com','Doctor',1),
  (UUID(),@t_abc,@st4,'Suresh Gupta','father','9876543203','suresh.g@example.com','Teacher',1),
  (UUID(),@t_abc,@st5,'Ramesh Kumar','father','9876543204','ramesh.k@example.com','Business',1),
  (UUID(),@t_abc,@st6,'Anil Mehta','father','9876543205','anil.m@example.com','CA',1),
  (UUID(),@t_abc,@st7,'Rakesh Chauhan','father','9876543206','rakesh.c@example.com','Farmer',1),
  (UUID(),@t_abc,@st8,'Sunil Rani','mother','9876543207','sunil.r@example.com','Homemaker',1),
  (UUID(),@t_abc,@st9,'Mohit Bhatia','father','9876543208','mohit.b@example.com','Business',1),
  (UUID(),@t_abc,@st10,'Suresh Nair','father','9876543209','suresh.n@example.com','Engineer',1),
  (UUID(),@t_xyz,@st11,'Krishna Reddy','father','9812340101','krishna@example.com','Business',1),
  (UUID(),@t_xyz,@st12,'Ravi Iyer','father','9812340102','ravi@example.com','Doctor',1);

-- enroll in batches (MATH-A: st1-4, MATH-B: st5-7, JEE-WE: st8-10)
INSERT INTO cd_batch_students (tenant_id, batch_id, student_id, start_date, status) VALUES
  (@t_abc,@b_math_a,@st1,CURDATE(),'active'),
  (@t_abc,@b_math_a,@st2,CURDATE(),'active'),
  (@t_abc,@b_math_a,@st3,CURDATE(),'active'),
  (@t_abc,@b_math_a,@st4,CURDATE(),'active'),
  (@t_abc,@b_math_b,@st5,CURDATE(),'active'),
  (@t_abc,@b_math_b,@st6,CURDATE(),'active'),
  (@t_abc,@b_math_b,@st7,CURDATE(),'active'),
  (@t_abc,@b_jee_we,@st8,CURDATE(),'active'),
  (@t_abc,@b_jee_we,@st9,CURDATE(),'active'),
  (@t_abc,@b_jee_we,@st10,CURDATE(),'active');

-- =====================================================================
-- 8. FEE STRUCTURE + ACCOUNTS + INSTALLMENTS + PAYMENTS
-- =====================================================================
INSERT INTO cd_fee_structures (public_id, tenant_id, course_id, name, total_amount, status) VALUES
  (UUID(), @t_abc, @c1,'Class 10 Maths – Full Year',12000,'active'),
  (UUID(), @t_abc, @c2,'JEE Foundation – Full Year',18000,'active');
SET @fs1 = (SELECT id FROM cd_fee_structures WHERE tenant_id=@t_abc AND course_id=@c1);
SET @fs2 = (SELECT id FROM cd_fee_structures WHERE tenant_id=@t_abc AND course_id=@c2);

INSERT INTO cd_fee_structure_items (fee_structure_id, fee_type, amount) VALUES
  (@fs1,'Admission',1000),(@fs1,'Monthly',1000),(@fs1,'Exam',500),
  (@fs2,'Admission',2000),(@fs2,'Monthly',1300),(@fs2,'Exam',700);

-- Fee accounts for st1..st10
INSERT INTO cd_student_fee_accounts (public_id, tenant_id, student_id, fee_structure_id, total_amount, discount_amount, status)
SELECT UUID(), @t_abc, s.id, @fs1, 12000, 0, 'active'
FROM cd_students s WHERE s.id IN (@st1,@st2,@st3,@st4);

INSERT INTO cd_student_fee_accounts (public_id, tenant_id, student_id, fee_structure_id, total_amount, discount_amount, status)
SELECT UUID(), @t_abc, s.id, @fs2, 18000, 0, 'active'
FROM cd_students s WHERE s.id IN (@st8,@st9,@st10);

SET @fa1 = (SELECT id FROM cd_student_fee_accounts WHERE tenant_id=@t_abc AND student_id=@st1);
SET @fa2 = (SELECT id FROM cd_student_fee_accounts WHERE tenant_id=@t_abc AND student_id=@st2);
SET @fa3 = (SELECT id FROM cd_student_fee_accounts WHERE tenant_id=@t_abc AND student_id=@st3);
SET @fa4 = (SELECT id FROM cd_student_fee_accounts WHERE tenant_id=@t_abc AND student_id=@st4);
SET @fa8 = (SELECT id FROM cd_student_fee_accounts WHERE tenant_id=@t_abc AND student_id=@st8);

-- 3 installments per student (practical demo)
INSERT INTO cd_fee_installments (public_id, tenant_id, fee_account_id, installment_name, amount, due_date, status) VALUES
  (UUID(),@t_abc,@fa1,'Installment 1',4000, DATE_SUB(CURDATE(), INTERVAL 60 DAY),'paid'),
  (UUID(),@t_abc,@fa1,'Installment 2',4000, DATE_SUB(CURDATE(), INTERVAL 30 DAY),'paid'),
  (UUID(),@t_abc,@fa1,'Installment 3',4000, DATE_ADD(CURDATE(), INTERVAL 30 DAY),'pending'),
  (UUID(),@t_abc,@fa2,'Installment 1',4000, DATE_SUB(CURDATE(), INTERVAL 60 DAY),'paid'),
  (UUID(),@t_abc,@fa2,'Installment 2',4000, DATE_SUB(CURDATE(), INTERVAL 30 DAY),'pending'),
  (UUID(),@t_abc,@fa2,'Installment 3',4000, DATE_ADD(CURDATE(), INTERVAL 30 DAY),'pending'),
  (UUID(),@t_abc,@fa3,'Installment 1',4000, DATE_SUB(CURDATE(), INTERVAL 60 DAY),'paid'),
  (UUID(),@t_abc,@fa3,'Installment 2',4000, DATE_SUB(CURDATE(), INTERVAL 30 DAY),'pending'),
  (UUID(),@t_abc,@fa3,'Installment 3',4000, DATE_ADD(CURDATE(), INTERVAL 30 DAY),'pending'),
  (UUID(),@t_abc,@fa4,'Installment 1',4000, DATE_SUB(CURDATE(), INTERVAL 60 DAY),'pending'),
  (UUID(),@t_abc,@fa4,'Installment 2',4000, DATE_SUB(CURDATE(), INTERVAL 30 DAY),'pending'),
  (UUID(),@t_abc,@fa4,'Installment 3',4000, DATE_ADD(CURDATE(), INTERVAL 30 DAY),'pending'),
  (UUID(),@t_abc,@fa8,'Installment 1',6000, DATE_SUB(CURDATE(), INTERVAL 60 DAY),'paid'),
  (UUID(),@t_abc,@fa8,'Installment 2',6000, DATE_SUB(CURDATE(), INTERVAL 30 DAY),'paid'),
  (UUID(),@t_abc,@fa8,'Installment 3',6000, DATE_ADD(CURDATE(), INTERVAL 30 DAY),'pending');

SET @i1_1 = (SELECT id FROM cd_fee_installments WHERE fee_account_id=@fa1 AND installment_name='Installment 1');
SET @i1_2 = (SELECT id FROM cd_fee_installments WHERE fee_account_id=@fa1 AND installment_name='Installment 2');
SET @i2_1 = (SELECT id FROM cd_fee_installments WHERE fee_account_id=@fa2 AND installment_name='Installment 1');
SET @i3_1 = (SELECT id FROM cd_fee_installments WHERE fee_account_id=@fa3 AND installment_name='Installment 1');
SET @i8_1 = (SELECT id FROM cd_fee_installments WHERE fee_account_id=@fa8 AND installment_name='Installment 1');
SET @i8_2 = (SELECT id FROM cd_fee_installments WHERE fee_account_id=@fa8 AND installment_name='Installment 2');

INSERT INTO cd_payments
  (public_id, tenant_id, student_id, receipt_number, payment_date, amount, method, status, received_by)
VALUES
  ('p0000001-0000-0000-0000-000000000001',@t_abc,@st1,'RCPT-2026-0001',DATE_SUB(CURDATE(), INTERVAL 60 DAY),8000,'upi','success',@u_abc_acc),
  ('p0000002-0000-0000-0000-000000000002',@t_abc,@st2,'RCPT-2026-0002',DATE_SUB(CURDATE(), INTERVAL 60 DAY),4000,'cash','success',@u_abc_acc),
  ('p0000003-0000-0000-0000-000000000003',@t_abc,@st3,'RCPT-2026-0003',DATE_SUB(CURDATE(), INTERVAL 60 DAY),4000,'upi','success',@u_abc_acc),
  ('p0000004-0000-0000-0000-000000000004',@t_abc,@st8,'RCPT-2026-0004',DATE_SUB(CURDATE(), INTERVAL 30 DAY),12000,'netbanking','success',@u_abc_acc);

SET @p1 = (SELECT id FROM cd_payments WHERE receipt_number='RCPT-2026-0001');
SET @p2 = (SELECT id FROM cd_payments WHERE receipt_number='RCPT-2026-0002');
SET @p3 = (SELECT id FROM cd_payments WHERE receipt_number='RCPT-2026-0003');
SET @p4 = (SELECT id FROM cd_payments WHERE receipt_number='RCPT-2026-0004');

INSERT INTO cd_payment_allocations (tenant_id, payment_id, installment_id, allocated_amount) VALUES
  (@t_abc,@p1,@i1_1,4000),
  (@t_abc,@p1,@i1_2,4000),
  (@t_abc,@p2,@i2_1,4000),
  (@t_abc,@p3,@i3_1,4000),
  (@t_abc,@p4,@i8_1,6000),
  (@t_abc,@p4,@i8_2,6000);

-- =====================================================================
-- 9. ATTENDANCE  (today's session for MATH-A + MATH-B)
-- =====================================================================
INSERT INTO cd_attendance_sessions (public_id, tenant_id, branch_id, batch_id, attendance_date, status, submitted_by, submitted_at) VALUES
  (UUID(), @t_abc, @b_abc_gn, @b_math_a, CURDATE(),'submitted', @u_abc_amit, NOW()),
  (UUID(), @t_abc, @b_abc_gn, @b_math_b, CURDATE(),'submitted', @u_abc_amit, NOW());
SET @as_a = (SELECT id FROM cd_attendance_sessions WHERE batch_id=@b_math_a AND attendance_date=CURDATE());
SET @as_b = (SELECT id FROM cd_attendance_sessions WHERE batch_id=@b_math_b AND attendance_date=CURDATE());

INSERT INTO cd_attendance_records (tenant_id, session_id, student_id, attendance_status) VALUES
  (@t_abc,@as_a,@st1,'present'),(@t_abc,@as_a,@st2,'present'),(@t_abc,@as_a,@st3,'late'),(@t_abc,@as_a,@st4,'absent'),
  (@t_abc,@as_b,@st5,'present'),(@t_abc,@as_b,@st6,'present'),(@t_abc,@as_b,@st7,'half_day');

-- =====================================================================
-- 10. EXAMS + RESULTS
-- =====================================================================
INSERT INTO cd_exams (public_id, tenant_id, branch_id, batch_id, exam_name, exam_date, status) VALUES
  (UUID(), @t_abc, @b_abc_gn, @b_math_a,'Unit Test 1 – October', DATE_SUB(CURDATE(), INTERVAL 15 DAY),'published');
SET @exam1 = (SELECT id FROM cd_exams WHERE tenant_id=@t_abc AND batch_id=@b_math_a LIMIT 1);

INSERT INTO cd_exam_subjects (tenant_id, exam_id, subject_id, maximum_marks, passing_marks) VALUES
  (@t_abc,@exam1,@sub_math,100,33);
SET @es_math = (SELECT id FROM cd_exam_subjects WHERE exam_id=@exam1 AND subject_id=@sub_math);

INSERT INTO cd_exam_results (public_id, tenant_id, exam_id, student_id, total_marks, percentage, grade, rank_no, status) VALUES
  (UUID(),@t_abc,@exam1,@st1, 85,85.00,'A', 1,'published'),
  (UUID(),@t_abc,@exam1,@st2, 78,78.00,'B', 2,'published'),
  (UUID(),@t_abc,@exam1,@st3, 92,92.00,'A+',3,'published');
SET @r1 = (SELECT id FROM cd_exam_results WHERE exam_id=@exam1 AND student_id=@st1);
SET @r2 = (SELECT id FROM cd_exam_results WHERE exam_id=@exam1 AND student_id=@st2);
SET @r3 = (SELECT id FROM cd_exam_results WHERE exam_id=@exam1 AND student_id=@st3);

INSERT INTO cd_exam_result_details (result_id, subject_id, obtained_marks) VALUES
  (@r1,@sub_math,85),(@r2,@sub_math,78),(@r3,@sub_math,92);

-- =====================================================================
-- 11. TIMETABLE
-- =====================================================================
INSERT INTO cd_timetable (public_id, tenant_id, branch_id, batch_id, subject_id, teacher_id, classroom_id, day_of_week, start_time, end_time, status) VALUES
  (UUID(),@t_abc,@b_abc_gn,@b_math_a,@sub_math,@t_amit,@room_a101,1,'08:00:00','09:00:00','active'),
  (UUID(),@t_abc,@b_abc_gn,@b_math_a,@sub_math,@t_amit,@room_a101,3,'08:00:00','09:00:00','active'),
  (UUID(),@t_abc,@b_abc_gn,@b_math_a,@sub_math,@t_amit,@room_a101,5,'08:00:00','09:00:00','active'),
  (UUID(),@t_abc,@b_abc_gn,@b_math_b,@sub_math,@t_amit,@room_a102,1,'18:00:00','19:00:00','active'),
  (UUID(),@t_abc,@b_abc_gn,@b_math_b,@sub_math,@t_amit,@room_a102,3,'18:00:00','19:00:00','active'),
  (UUID(),@t_abc,@b_abc_gn,@b_jee_we,@sub_phy, @t_priya,@room_a101,6,'10:00:00','12:00:00','active');

-- =====================================================================
-- 12. HOMEWORK + STUDY MATERIAL
-- =====================================================================
INSERT INTO cd_homework (public_id, tenant_id, batch_id, subject_id, title, description, due_date, created_by) VALUES
  (UUID(), @t_abc, @b_math_a, @sub_math, 'Quadratic Equations – Exercise 4.3', 'Solve Q1-Q15', DATE_ADD(CURDATE(), INTERVAL 7 DAY), @u_abc_amit);
SET @hw1 = (SELECT id FROM cd_homework WHERE tenant_id=@t_abc AND batch_id=@b_math_a LIMIT 1);

INSERT INTO cd_homework_submissions (public_id, tenant_id, homework_id, student_id, submitted_at, marks, feedback) VALUES
  (UUID(), @t_abc, @hw1, @st1, NOW(), 9, 'Good work'),
  (UUID(), @t_abc, @hw1, @st2, NOW(), 8, 'Needs neatness'),
  (UUID(), @t_abc, @hw1, @st3, NOW(), 10,'Excellent');

INSERT INTO cd_study_materials (public_id, tenant_id, batch_id, subject_id, title, file_path, video_url, created_by) VALUES
  (UUID(), @t_abc, @b_math_a, @sub_math, 'Quadratic Equations Notes', '/storage/materials/quadratic-notes.pdf', NULL, @u_abc_amit),
  (UUID(), @t_abc, @b_math_a, @sub_math, 'Video Lecture – Factoring', NULL, 'https://youtu.be/example123', @u_abc_amit);

-- =====================================================================
-- 13. ANNOUNCEMENTS + NOTIFICATIONS
-- =====================================================================
INSERT INTO cd_announcements (public_id, tenant_id, title, description, target_type, target_id, status, created_by) VALUES
  (UUID(), @t_abc,'Diwali Holiday Notice','Institute closed from 20-22 Oct','all',NULL,'published',@u_abc_owner),
  (UUID(), @t_abc,'Unit Test 2 Schedule','Exam on 15 Nov for MATH-A','batch',@b_math_a,'published',@u_abc_owner);

INSERT INTO cd_notification_templates (tenant_id, channel, template_key, subject, content, is_active) VALUES
  (@t_abc,'whatsapp','fee_due','Fee Due Reminder','Dear parent, fee of {{amount}} for {{student_name}} is due on {{due_date}}.'),
  (@t_abc,'email',   'fee_received','Fee Received','Thank you. Payment of {{amount}} received. Receipt #{{receipt}}.'),
  (@t_abc,'inapp',   'absence','Absence Alert','{{student_name}} was marked absent on {{date}}.');

INSERT INTO cd_notifications (public_id, tenant_id, user_id, title, message, read_at) VALUES
  (UUID(), @t_abc, @u_abc_owner,'Welcome to CoachDesk','Your institute is ready.',NULL),
  (UUID(), @t_abc, @u_abc_amit, 'New batch assigned','You were assigned to MATH-B.',NULL);

-- =====================================================================
-- 14. SUPPORT + EXPENSES + SETTINGS
-- =====================================================================
INSERT INTO cd_support_tickets (public_id, tenant_id, user_id, subject, description, priority, status) VALUES
  (UUID(), @t_abc, @u_abc_owner,'SMS balance low','Please top-up SMS credits.','medium','open');

INSERT INTO cd_expenses (public_id, tenant_id, branch_id, category, amount, expense_date, notes, created_by) VALUES
  (UUID(), @t_abc, @b_abc_gn,'Rent',25000, DATE_SUB(CURDATE(), INTERVAL 5 DAY),'Monthly rent', @u_abc_owner),
  (UUID(), @t_abc, @b_abc_gn,'Electricity',3500, DATE_SUB(CURDATE(), INTERVAL 3 DAY),'Oct bill', @u_abc_owner),
  (UUID(), @t_abc, @b_abc_gn,'Stationery',1200, CURDATE(),'Markers, dusters', @u_abc_owner);

INSERT INTO cd_settings (tenant_id, setting_key, setting_value) VALUES
  (@t_abc,'branding', JSON_OBJECT('primary_color','#4F46E5','receipt_footer','Thank you for choosing ABC Coaching')),
  (@t_abc,'notifications', JSON_OBJECT('fee_reminder_days',3,'send_absence_sms',1)),
  (@t_xyz,'branding', JSON_OBJECT('primary_color','#059669'));

INSERT INTO cd_platform_settings (setting_key, setting_value) VALUES
  ('signup_enabled',    JSON_OBJECT('value',1)),
  ('support_email',     JSON_OBJECT('value','support@coachdesk.in')),
  ('trial_days',        JSON_OBJECT('value',14));

-- =====================================================================
-- 15. AUDIT LOG SAMPLE
-- =====================================================================
INSERT INTO cd_activity_logs (tenant_id, user_id, action, module, record_type, record_id, metadata, ip_address) VALUES
  (@t_abc,@u_abc_owner,'tenant.created',   'tenants', 'tenant',@t_abc, JSON_OBJECT('code','abc-coaching'),'127.0.0.1'),
  (@t_abc,@u_abc_owner,'student.created',  'students','student',@st1, JSON_OBJECT('code','CD-GN-2026-00001'),'127.0.0.1'),
  (@t_abc,@u_abc_acc,  'payment.collected','fees',    'payment',@p1,  JSON_OBJECT('amount',8000,'method','upi'),'127.0.0.1'),
  (@t_abc,@u_abc_amit, 'attendance.marked','attendance','session',@as_a,JSON_OBJECT('batch','MATH-A'),'127.0.0.1');

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SANITY CHECKS (run manually to verify)
-- =====================================================================
-- SELECT 'tenants', COUNT(*) FROM cd_tenants
-- UNION ALL SELECT 'branches', COUNT(*) FROM cd_branches
-- UNION ALL SELECT 'users', COUNT(*) FROM cd_users
-- UNION ALL SELECT 'permissions', COUNT(*) FROM cd_permissions
-- UNION ALL SELECT 'role_permissions', COUNT(*) FROM cd_role_permissions
-- UNION ALL SELECT 'students', COUNT(*) FROM cd_students
-- UNION ALL SELECT 'batches', COUNT(*) FROM cd_batches
-- UNION ALL SELECT 'attendance_records', COUNT(*) FROM cd_attendance_records
-- UNION ALL SELECT 'payments', COUNT(*) FROM cd_payments
-- UNION ALL SELECT 'exam_results', COUNT(*) FROM cd_exam_results;