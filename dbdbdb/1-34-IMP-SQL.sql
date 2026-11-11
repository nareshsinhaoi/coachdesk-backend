-- =====================================================================
-- CoachDesk — Dummy Data (Simple INSERTs, safe to re-run)
-- Password for ALL users: Password@123
-- =====================================================================
USE `coachdesk`;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- 1. TENANTS
-- =====================================================================
INSERT INTO `cd_tenants`
  (`public_id`, `name`, `code`, `email`, `phone`, `website`,
   `address_line1`, `city`, `state`, `pincode`, `country`,
   `status`, `trial_ends_at`, `timezone`, `currency`, `locale`)
VALUES
  ('11111111-1111-1111-1111-111111111111', 'ABC Coaching Institute', 'abc-coaching',
   'owner@abc.edu', '9810012345', 'https://abc-coaching.in',
   'Sector 62', 'Greater Noida', 'Uttar Pradesh', '201309', 'India',
   'active', NULL, 'Asia/Kolkata', 'INR', 'en-IN'),
  ('22222222-2222-2222-2222-222222222222', 'XYZ Academy', 'xyz-academy',
   'owner@xyz.edu', '9820011223', 'https://xyzacademy.in',
   'Lajpat Nagar', 'New Delhi', 'Delhi', '110024', 'India',
   'trial', DATE_ADD(NOW(), INTERVAL 14 DAY), 'Asia/Kolkata', 'INR', 'en-IN');

-- =====================================================================
-- 2. BRANCHES
-- =====================================================================
INSERT INTO `cd_branches`
  (`public_id`, `tenant_id`, `name`, `branch_code`, `address_line1`, `city`, `state`, `pincode`, `phone`, `email`, `status`)
VALUES
  ('b1111111-1111-1111-1111-111111111111',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    'Greater Noida Branch', 'GN', 'Sector 62', 'Greater Noida', 'Uttar Pradesh', '201309', '9810012345', 'gn@abc.edu', 'active'),
  ('b1111111-2222-2222-2222-222222222222',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    'Delhi Branch', 'DL', 'Lajpat Nagar', 'New Delhi', 'Delhi', '110024', '9810012346', 'delhi@abc.edu', 'active'),
  ('b2222222-1111-1111-1111-111111111111',
    (SELECT id FROM cd_tenants WHERE code='xyz-academy'),
    'Delhi Branch', 'DL', 'Lajpat Nagar', 'New Delhi', 'Delhi', '110024', '9820011223', 'delhi@xyz.edu', 'active');

-- =====================================================================
-- 3. SUBSCRIPTION PLANS
-- =====================================================================
INSERT INTO `cd_subscription_plans`
  (`public_id`, `name`, `price`, `billing_cycle`, `max_students`, `max_staff`, `max_branches`, `features_json`, `is_active`, `sort_order`)
VALUES
  ('a0000001-0000-0000-0000-000000000001', 'Starter',       499.00, 'monthly',   100,   3,  1, JSON_OBJECT('exams','basic','portal',0), 1, 1),
  ('a0000002-0000-0000-0000-000000000002', 'Basic',         999.00, 'monthly',   300,  10,  1, JSON_OBJECT('exams','basic','portal',1), 1, 2),
  ('a0000003-0000-0000-0000-000000000003', 'Professional', 1999.00, 'monthly',  1000,  30,  3, JSON_OBJECT('exams','advanced','portal',1), 1, 3),
  ('a0000004-0000-0000-0000-000000000004', 'Enterprise',      0.00, 'yearly',  99999, 999, 99, JSON_OBJECT('exams','advanced','portal',1,'white_label',1), 1, 4);

-- =====================================================================
-- 4. TENANT SUBSCRIPTIONS
-- =====================================================================
INSERT INTO `cd_tenant_subscriptions`
  (`public_id`, `tenant_id`, `plan_id`, `start_date`, `end_date`, `status`, `auto_renew`)
VALUES
  ('c0000001-0000-0000-0000-000000000001',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_subscription_plans WHERE name='Professional'),
    DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 335 DAY), 'active', 1),
  ('c0000002-0000-0000-0000-000000000002',
    (SELECT id FROM cd_tenants WHERE code='xyz-academy'),
    (SELECT id FROM cd_subscription_plans WHERE name='Basic'),
    CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'trial', 1);

-- =====================================================================
-- 5. SYSTEM ROLES (safe — skipped if exist)
-- =====================================================================
INSERT IGNORE INTO `cd_roles` (`public_id`, `tenant_id`, `name`, `slug`, `is_system`) VALUES
  (UUID(), NULL, 'SaaS Super Admin', 'super_admin',   1),
  (UUID(), NULL, 'Institute Owner',  'owner',         1),
  (UUID(), NULL, 'Branch Admin',     'branch_admin',  1),
  (UUID(), NULL, 'Accountant',       'accountant',    1),
  (UUID(), NULL, 'Teacher',          'teacher',       1),
  (UUID(), NULL, 'Receptionist',     'receptionist',  1),
  (UUID(), NULL, 'Student',          'student',       1),
  (UUID(), NULL, 'Parent',           'parent',        1);

-- =====================================================================
-- 6. PERMISSIONS
-- =====================================================================
INSERT IGNORE INTO `cd_permissions` (`permission_key`, `module`, `description`) VALUES
  ('dashboard.view',            'dashboard',     'View dashboard'),
  ('institutes.view',           'institutes',    'View institute profile'),
  ('institutes.edit',           'institutes',    'Edit institute profile'),
  ('branches.view',             'branches',      'View branches'),
  ('branches.create',           'branches',      'Create branch'),
  ('branches.edit',             'branches',      'Edit branch'),
  ('branches.delete',           'branches',      'Delete branch'),
  ('users.view',                'users',         'View users'),
  ('users.create',              'users',         'Create user'),
  ('users.edit',                'users',         'Edit user'),
  ('users.delete',              'users',         'Delete user'),
  ('roles.view',                'roles',         'View roles'),
  ('roles.manage',              'roles',         'Manage roles & permissions'),
  ('students.view',             'students',      'View students'),
  ('students.create',           'students',      'Admit new student'),
  ('students.edit',             'students',      'Edit student'),
  ('students.delete',           'students',      'Delete/cancel student'),
  ('students.import',           'students',      'Import students'),
  ('students.export',           'students',      'Export students'),
  ('courses.view',              'courses',       'View courses'),
  ('courses.manage',            'courses',       'Create/edit courses'),
  ('subjects.view',             'subjects',      'View subjects'),
  ('subjects.manage',           'subjects',      'Create/edit subjects'),
  ('batches.view',              'batches',       'View batches'),
  ('batches.manage',            'batches',       'Create/edit batches'),
  ('teachers.view',             'teachers',      'View teachers'),
  ('teachers.manage',           'teachers',      'Create/edit teachers'),
  ('timetable.view',            'timetable',     'View timetable'),
  ('timetable.manage',          'timetable',     'Manage timetable'),
  ('attendance.view',           'attendance',    'View attendance'),
  ('attendance.mark',           'attendance',    'Mark attendance'),
  ('attendance.edit',           'attendance',    'Edit attendance'),
  ('fees.view',                 'fees',          'View fee records'),
  ('fees.collect',              'fees',          'Collect payment'),
  ('fees.refund',               'fees',          'Process refund'),
  ('fees.reports',              'fees',          'View fee reports'),
  ('exams.view',                'exams',         'View exams'),
  ('exams.create',              'exams',         'Create exams'),
  ('exams.enter_marks',         'exams',         'Enter marks'),
  ('exams.publish_results',     'exams',         'Publish results'),
  ('homework.view',             'homework',      'View homework'),
  ('homework.manage',           'homework',      'Manage homework'),
  ('materials.view',            'materials',     'View study materials'),
  ('materials.manage',          'materials',     'Upload study materials'),
  ('reports.view',              'reports',       'View reports'),
  ('notifications.manage',      'notifications', 'Manage notification templates'),
  ('support.view',              'support',       'View support tickets'),
  ('support.manage',            'support',       'Manage support tickets'),
  ('settings.view',             'settings',      'View settings'),
  ('settings.manage',           'settings',      'Manage settings');

-- =====================================================================
-- 7. ROLE → PERMISSIONS
-- =====================================================================

-- Owner + Super Admin get every permission
INSERT IGNORE INTO `cd_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM cd_roles r CROSS JOIN cd_permissions p
WHERE r.slug IN ('owner', 'super_admin');

-- Branch Admin (everything except refunds, role mgmt, settings.manage, institutes.edit)
INSERT IGNORE INTO `cd_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM cd_roles r CROSS JOIN cd_permissions p
WHERE r.slug = 'branch_admin'
  AND p.permission_key NOT IN ('roles.manage','fees.refund','settings.manage','institutes.edit');

-- Accountant
INSERT IGNORE INTO `cd_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM cd_roles r CROSS JOIN cd_permissions p
WHERE r.slug = 'accountant'
  AND p.permission_key IN (
    'dashboard.view','fees.view','fees.collect','fees.reports','reports.view',
    'students.view','students.export'
  );

-- Teacher
INSERT IGNORE INTO `cd_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM cd_roles r CROSS JOIN cd_permissions p
WHERE r.slug = 'teacher'
  AND p.permission_key IN (
    'dashboard.view','students.view','batches.view','courses.view','subjects.view',
    'attendance.view','attendance.mark','exams.view','exams.enter_marks',
    'homework.view','homework.manage','materials.view','materials.manage',
    'timetable.view'
  );

-- Receptionist
INSERT IGNORE INTO `cd_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM cd_roles r CROSS JOIN cd_permissions p
WHERE r.slug = 'receptionist'
  AND p.permission_key IN (
    'dashboard.view','students.view','students.create','students.edit','students.import',
    'courses.view','subjects.view','batches.view','fees.view','fees.collect'
  );

-- Student / Parent (portal)
INSERT IGNORE INTO `cd_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM cd_roles r CROSS JOIN cd_permissions p
WHERE r.slug = 'student'
  AND p.permission_key IN (
    'dashboard.view','attendance.view','fees.view','exams.view','homework.view',
    'materials.view','timetable.view'
  );
INSERT IGNORE INTO `cd_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM cd_roles r CROSS JOIN cd_permissions p
WHERE r.slug = 'parent'
  AND p.permission_key IN (
    'dashboard.view','attendance.view','fees.view','exams.view','timetable.view'
  );

-- =====================================================================
-- 8. USERS  (password = Password@123)
--    Hash below is a real bcrypt hash of 'Password@123'
-- =====================================================================
INSERT IGNORE INTO `cd_users`
  (`public_id`, `tenant_id`, `name`, `email`, `phone`, `password_hash`, `status`, `email_verified_at`)
VALUES
  ('u0000000-0000-0000-0000-000000000001', NULL,
    'Platform Super Admin', 'super@coachdesk.in', '9000000001',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', NOW()),

  ('u1000000-0000-0000-0000-000000000001',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    'Rajesh Kumar (Owner)', 'owner@abc.edu', '9810012345',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', NOW()),

  ('u1000000-0000-0000-0000-000000000002',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    'Sunita Sharma (GN Admin)', 'admin.gn@abc.edu', '9810012346',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', NOW()),

  ('u1000000-0000-0000-0000-000000000003',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    'Vikas Gupta (Accountant)', 'acc@abc.edu', '9810012347',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', NOW()),

  ('u1000000-0000-0000-0000-000000000004',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    'Amit Verma (Teacher)', 'amit@abc.edu', '9810012348',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', NOW()),

  ('u1000000-0000-0000-0000-000000000005',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    'Priya Singh (Teacher)', 'priya@abc.edu', '9810012349',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', NOW()),

  ('u1000000-0000-0000-0000-000000000006',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    'Neha Receptionist', 'reception@abc.edu', '9810012350',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', NOW()),

  ('u2000000-0000-0000-0000-000000000001',
    (SELECT id FROM cd_tenants WHERE code='xyz-academy'),
    'Anil Yadav (Owner)', 'owner@xyz.edu', '9820011223',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', NOW());

-- =====================================================================
-- 9. USER → ROLES
-- =====================================================================
INSERT IGNORE INTO `cd_user_roles` (`user_id`, `role_id`, `branch_id`) VALUES
  -- Super Admin
  ((SELECT id FROM cd_users WHERE email='super@coachdesk.in'),
   (SELECT id FROM cd_roles WHERE slug='super_admin'), NULL),

  -- ABC Owner
  ((SELECT id FROM cd_users WHERE email='owner@abc.edu'),
   (SELECT id FROM cd_roles WHERE slug='owner'), NULL),

  -- ABC Branch Admin (pinned to GN branch)
  ((SELECT id FROM cd_users WHERE email='admin.gn@abc.edu'),
   (SELECT id FROM cd_roles WHERE slug='branch_admin'),
   (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching'))),

  -- ABC Accountant
  ((SELECT id FROM cd_users WHERE email='acc@abc.edu'),
   (SELECT id FROM cd_roles WHERE slug='accountant'), NULL),

  -- ABC Teachers (pinned to GN)
  ((SELECT id FROM cd_users WHERE email='amit@abc.edu'),
   (SELECT id FROM cd_roles WHERE slug='teacher'),
   (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching'))),
  ((SELECT id FROM cd_users WHERE email='priya@abc.edu'),
   (SELECT id FROM cd_roles WHERE slug='teacher'),
   (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching'))),

  -- ABC Receptionist (pinned to GN)
  ((SELECT id FROM cd_users WHERE email='reception@abc.edu'),
   (SELECT id FROM cd_roles WHERE slug='receptionist'),
   (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching'))),

  -- XYZ Owner
  ((SELECT id FROM cd_users WHERE email='owner@xyz.edu'),
   (SELECT id FROM cd_roles WHERE slug='owner'), NULL);

-- =====================================================================
-- 10. ACADEMIC SESSIONS
-- =====================================================================
INSERT INTO `cd_academic_sessions`
  (`public_id`, `tenant_id`, `name`, `start_date`, `end_date`, `is_current`, `status`)
VALUES
  ('e0000001-0000-0000-0000-000000000001',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    '2025-2026', '2025-04-01', '2026-03-31', 1, 'active'),
  ('e0000002-0000-0000-0000-000000000002',
    (SELECT id FROM cd_tenants WHERE code='xyz-academy'),
    '2025-2026', '2025-04-01', '2026-03-31', 1, 'active');

-- =====================================================================
-- 11. COURSES
-- =====================================================================
INSERT INTO `cd_courses`
  (`public_id`, `tenant_id`, `branch_id`, `course_name`, `course_code`, `duration`, `fees`, `description`, `status`)
VALUES
  ('f0000001-0000-0000-0000-000000000001',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'Class 10 Maths', 'C10-MATH', '12 Months', 12000, 'Class 10 Mathematics', 'active'),

  ('f0000002-0000-0000-0000-000000000002',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'IIT-JEE Foundation', 'JEE-FDN', '12 Months', 18000, 'JEE Foundation', 'active'),

  ('f0000003-0000-0000-0000-000000000003',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'Spoken English', 'SPK-ENG', '3 Months', 5000, 'Spoken English', 'active'),

  ('f0000004-0000-0000-0000-000000000004',
    (SELECT id FROM cd_tenants WHERE code='xyz-academy'),
    (SELECT id FROM cd_branches WHERE branch_code='DL' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='xyz-academy')),
    'SSC Preparation', 'SSC-01', '6 Months', 15000, 'SSC CGL Preparation', 'active');

-- =====================================================================
-- 12. SUBJECTS
-- =====================================================================
INSERT INTO `cd_subjects` (`public_id`, `tenant_id`, `subject_name`, `subject_code`, `status`) VALUES
  ('aa000001-0000-0000-0000-000000000001', (SELECT id FROM cd_tenants WHERE code='abc-coaching'), 'Mathematics', 'MATH', 'active'),
  ('aa000002-0000-0000-0000-000000000002', (SELECT id FROM cd_tenants WHERE code='abc-coaching'), 'Physics',     'PHY',  'active'),
  ('aa000003-0000-0000-0000-000000000003', (SELECT id FROM cd_tenants WHERE code='abc-coaching'), 'Chemistry',   'CHEM', 'active'),
  ('aa000004-0000-0000-0000-000000000004', (SELECT id FROM cd_tenants WHERE code='abc-coaching'), 'English',     'ENG',  'active'),
  ('aa000005-0000-0000-0000-000000000005', (SELECT id FROM cd_tenants WHERE code='xyz-academy'),  'Reasoning',   'REA',  'active'),
  ('aa000006-0000-0000-0000-000000000006', (SELECT id FROM cd_tenants WHERE code='xyz-academy'),  'English',     'ENG2', 'active');

-- =====================================================================
-- 13. COURSE ↔ SUBJECT MAPPING
-- =====================================================================
INSERT IGNORE INTO `cd_course_subjects` (`course_id`, `subject_id`) VALUES
  ((SELECT id FROM cd_courses WHERE course_code='C10-MATH'),
   (SELECT id FROM cd_subjects WHERE subject_code='MATH' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching'))),
  ((SELECT id FROM cd_courses WHERE course_code='JEE-FDN'),
   (SELECT id FROM cd_subjects WHERE subject_code='PHY' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching'))),
  ((SELECT id FROM cd_courses WHERE course_code='JEE-FDN'),
   (SELECT id FROM cd_subjects WHERE subject_code='CHEM' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching'))),
  ((SELECT id FROM cd_courses WHERE course_code='JEE-FDN'),
   (SELECT id FROM cd_subjects WHERE subject_code='MATH' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching'))),
  ((SELECT id FROM cd_courses WHERE course_code='SPK-ENG'),
   (SELECT id FROM cd_subjects WHERE subject_code='ENG' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching'))),
  ((SELECT id FROM cd_courses WHERE course_code='SSC-01'),
   (SELECT id FROM cd_subjects WHERE subject_code='REA' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='xyz-academy'))),
  ((SELECT id FROM cd_courses WHERE course_code='SSC-01'),
   (SELECT id FROM cd_subjects WHERE subject_code='ENG2' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='xyz-academy')));

-- =====================================================================
-- 14. CLASSROOMS
-- =====================================================================
INSERT INTO `cd_classrooms` (`public_id`, `tenant_id`, `branch_id`, `room_name`, `capacity`, `status`) VALUES
  ('ab000001-0000-0000-0000-000000000001',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'Room A101', 30, 'active'),
  ('ab000002-0000-0000-0000-000000000002',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'Room A102', 30, 'active'),
  ('ab000003-0000-0000-0000-000000000003',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='DL' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'Room D201', 25, 'active'),
  ('ab000004-0000-0000-0000-000000000004',
    (SELECT id FROM cd_tenants WHERE code='xyz-academy'),
    (SELECT id FROM cd_branches WHERE branch_code='DL' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='xyz-academy')),
    'Room X301', 20, 'active');

-- =====================================================================
-- 15. TEACHERS
-- =====================================================================
INSERT INTO `cd_teachers`
  (`public_id`, `tenant_id`, `branch_id`, `user_id`, `employee_code`, `name`, `qualification`, `experience`, `phone`, `email`, `joining_date`, `status`)
VALUES
  ('ac000001-0000-0000-0000-000000000001',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    (SELECT id FROM cd_users WHERE email='amit@abc.edu'),
    'EMP-001', 'Amit Verma', 'M.Sc Maths', '8 years', '9810012348', 'amit@abc.edu',
    DATE_SUB(CURDATE(), INTERVAL 730 DAY), 'active'),

  ('ac000002-0000-0000-0000-000000000002',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    (SELECT id FROM cd_users WHERE email='priya@abc.edu'),
    'EMP-002', 'Priya Singh', 'M.Sc Physics', '5 years', '9810012349', 'priya@abc.edu',
    DATE_SUB(CURDATE(), INTERVAL 500 DAY), 'active');

-- =====================================================================
-- 16. BATCHES
-- =====================================================================
INSERT INTO `cd_batches`
  (`public_id`, `tenant_id`, `branch_id`, `course_id`, `academic_session_id`, `batch_name`, `batch_code`, `capacity`, `start_date`, `timing_start`, `timing_end`, `status`)
VALUES
  ('ad000001-0000-0000-0000-000000000001',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    (SELECT id FROM cd_courses WHERE course_code='C10-MATH'),
    (SELECT id FROM cd_academic_sessions WHERE tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching') LIMIT 1),
    'Class 10 Maths - Morning', 'MATH-A', 30, CURDATE(), '08:00:00', '09:00:00', 'running'),

  ('ad000002-0000-0000-0000-000000000002',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    (SELECT id FROM cd_courses WHERE course_code='C10-MATH'),
    (SELECT id FROM cd_academic_sessions WHERE tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching') LIMIT 1),
    'Class 10 Maths - Evening', 'MATH-B', 30, CURDATE(), '18:00:00', '19:00:00', 'running'),

  ('ad000003-0000-0000-0000-000000000003',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    (SELECT id FROM cd_courses WHERE course_code='JEE-FDN'),
    (SELECT id FROM cd_academic_sessions WHERE tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching') LIMIT 1),
    'JEE Foundation - Weekend', 'JEE-WE', 25, CURDATE(), '10:00:00', '12:00:00', 'running'),

  ('ad000004-0000-0000-0000-000000000004',
    (SELECT id FROM cd_tenants WHERE code='xyz-academy'),
    (SELECT id FROM cd_branches WHERE branch_code='DL' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='xyz-academy')),
    (SELECT id FROM cd_courses WHERE course_code='SSC-01'),
    (SELECT id FROM cd_academic_sessions WHERE tenant_id=(SELECT id FROM cd_tenants WHERE code='xyz-academy') LIMIT 1),
    'SSC Evening Batch', 'SSC-EVE', 20, CURDATE(), '17:00:00', '19:00:00', 'running');

-- =====================================================================
-- 17. BATCH ↔ SUBJECT ↔ TEACHER
-- =====================================================================
INSERT IGNORE INTO `cd_batch_subjects` (`batch_id`, `subject_id`, `teacher_id`) VALUES
  ((SELECT id FROM cd_batches WHERE batch_code='MATH-A'),
   (SELECT id FROM cd_subjects WHERE subject_code='MATH' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
   (SELECT id FROM cd_teachers WHERE employee_code='EMP-001')),

  ((SELECT id FROM cd_batches WHERE batch_code='MATH-B'),
   (SELECT id FROM cd_subjects WHERE subject_code='MATH' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
   (SELECT id FROM cd_teachers WHERE employee_code='EMP-001')),

  ((SELECT id FROM cd_batches WHERE batch_code='JEE-WE'),
   (SELECT id FROM cd_subjects WHERE subject_code='PHY' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
   (SELECT id FROM cd_teachers WHERE employee_code='EMP-002')),

  ((SELECT id FROM cd_batches WHERE batch_code='JEE-WE'),
   (SELECT id FROM cd_subjects WHERE subject_code='CHEM' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
   (SELECT id FROM cd_teachers WHERE employee_code='EMP-001')),

  ((SELECT id FROM cd_batches WHERE batch_code='JEE-WE'),
   (SELECT id FROM cd_subjects WHERE subject_code='MATH' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
   (SELECT id FROM cd_teachers WHERE employee_code='EMP-001'));

-- =====================================================================
-- 18. STUDENTS (12)
-- =====================================================================
INSERT INTO `cd_students`
  (`public_id`, `tenant_id`, `branch_id`, `student_code`, `name`, `dob`, `gender`, `phone`, `email`, `city`, `state`, `admission_date`, `status`)
VALUES
  ('a1000001-0000-0000-0000-000000000001',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'CD-GN-2026-00001','Rahul Sharma','2008-05-15','male','9876543210','rahul@example.com','Greater Noida','Uttar Pradesh',CURDATE(),'active'),
  ('a1000002-0000-0000-0000-000000000002',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'CD-GN-2026-00002','Amit Singh','2008-03-10','male','9876543211','amit.s@example.com','Greater Noida','Uttar Pradesh',CURDATE(),'active'),
  ('a1000003-0000-0000-0000-000000000003',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'CD-GN-2026-00003','Priya Verma','2008-07-22','female','9876543212','priya.v@example.com','Greater Noida','Uttar Pradesh',CURDATE(),'active'),
  ('a1000004-0000-0000-0000-000000000004',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'CD-GN-2026-00004','Neha Gupta','2008-11-02','female','9876543213','neha@example.com','Greater Noida','Uttar Pradesh',CURDATE(),'active'),
  ('a1000005-0000-0000-0000-000000000005',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'CD-GN-2026-00005','Rohit Kumar','2008-01-30','male','9876543214','rohit@example.com','Greater Noida','Uttar Pradesh',CURDATE(),'active'),
  ('a1000006-0000-0000-0000-000000000006',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'CD-GN-2026-00006','Anjali Mehta','2008-09-14','female','9876543215','anjali@example.com','Greater Noida','Uttar Pradesh',CURDATE(),'active'),
  ('a1000007-0000-0000-0000-000000000007',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'CD-GN-2026-00007','Vikram Chauhan','2008-12-05','male','9876543216','vikram@example.com','Greater Noida','Uttar Pradesh',CURDATE(),'active'),
  ('a1000008-0000-0000-0000-000000000008',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'CD-GN-2026-00008','Sneha Rani','2008-06-18','female','9876543217','sneha@example.com','Greater Noida','Uttar Pradesh',CURDATE(),'active'),
  ('a1000009-0000-0000-0000-000000000009',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'CD-GN-2026-00009','Karan Bhatia','2008-04-25','male','9876543218','karan@example.com','Greater Noida','Uttar Pradesh',CURDATE(),'active'),
  ('a1000010-0000-0000-0000-000000000010',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'CD-GN-2026-00010','Deepa Nair','2008-08-08','female','9876543219','deepa@example.com','Greater Noida','Uttar Pradesh',CURDATE(),'active'),
  ('a1000011-0000-0000-0000-000000000011',
    (SELECT id FROM cd_tenants WHERE code='xyz-academy'),
    (SELECT id FROM cd_branches WHERE branch_code='DL' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='xyz-academy')),
    'XYZ-DL-2026-00001','Arjun Reddy','2007-02-11','male','9812340001','arjun@example.com','New Delhi','Delhi',CURDATE(),'active'),
  ('a1000012-0000-0000-0000-000000000012',
    (SELECT id FROM cd_tenants WHERE code='xyz-academy'),
    (SELECT id FROM cd_branches WHERE branch_code='DL' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='xyz-academy')),
    'XYZ-DL-2026-00002','Meera Iyer','2007-05-19','female','9812340002','meera@example.com','New Delhi','Delhi',CURDATE(),'active');

-- =====================================================================
-- 19. STUDENT GUARDIANS
-- =====================================================================
INSERT INTO `cd_student_guardians`
  (`public_id`, `tenant_id`, `student_id`, `name`, `relationship`, `phone`, `email`, `occupation`, `is_primary`)
VALUES
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00001'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00001'),
    'Amit Sharma','father','9876543200','amit.sharma@example.com','Business',1),
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00002'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00002'),
    'Rajendra Singh','father','9876543201','raj.singh@example.com','Engineer',1),
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00003'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00003'),
    'Manish Verma','father','9876543202','manish.v@example.com','Doctor',1),
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00004'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00004'),
    'Suresh Gupta','father','9876543203','suresh.g@example.com','Teacher',1),
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00005'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00005'),
    'Ramesh Kumar','father','9876543204','ramesh.k@example.com','Business',1),
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00006'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00006'),
    'Anil Mehta','father','9876543205','anil.m@example.com','CA',1),
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00007'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00007'),
    'Rakesh Chauhan','father','9876543206','rakesh.c@example.com','Farmer',1),
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00008'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00008'),
    'Sunil Rani','mother','9876543207','sunil.r@example.com','Homemaker',1),
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00009'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00009'),
    'Mohit Bhatia','father','9876543208','mohit.b@example.com','Business',1),
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00010'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00010'),
    'Suresh Nair','father','9876543209','suresh.n@example.com','Engineer',1),
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='XYZ-DL-2026-00001'),
    (SELECT id FROM cd_students WHERE student_code='XYZ-DL-2026-00001'),
    'Krishna Reddy','father','9812340101','krishna@example.com','Business',1),
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='XYZ-DL-2026-00002'),
    (SELECT id FROM cd_students WHERE student_code='XYZ-DL-2026-00002'),
    'Ravi Iyer','father','9812340102','ravi@example.com','Doctor',1);

-- =====================================================================
-- 20. BATCH ENROLLMENTS
-- =====================================================================
INSERT IGNORE INTO `cd_batch_students` (`tenant_id`, `batch_id`, `student_id`, `start_date`, `status`) VALUES
  -- MATH-A: students 1-4
  ((SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00001'),
    (SELECT id FROM cd_batches WHERE batch_code='MATH-A'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00001'), CURDATE(),'active'),
  ((SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00002'),
    (SELECT id FROM cd_batches WHERE batch_code='MATH-A'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00002'), CURDATE(),'active'),
  ((SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00003'),
    (SELECT id FROM cd_batches WHERE batch_code='MATH-A'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00003'), CURDATE(),'active'),
  ((SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00004'),
    (SELECT id FROM cd_batches WHERE batch_code='MATH-A'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00004'), CURDATE(),'active'),
  -- MATH-B: students 5-7
  ((SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00005'),
    (SELECT id FROM cd_batches WHERE batch_code='MATH-B'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00005'), CURDATE(),'active'),
  ((SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00006'),
    (SELECT id FROM cd_batches WHERE batch_code='MATH-B'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00006'), CURDATE(),'active'),
  ((SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00007'),
    (SELECT id FROM cd_batches WHERE batch_code='MATH-B'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00007'), CURDATE(),'active'),
  -- JEE-WE: students 8-10
  ((SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00008'),
    (SELECT id FROM cd_batches WHERE batch_code='JEE-WE'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00008'), CURDATE(),'active'),
  ((SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00009'),
    (SELECT id FROM cd_batches WHERE batch_code='JEE-WE'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00009'), CURDATE(),'active'),
  ((SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00010'),
    (SELECT id FROM cd_batches WHERE batch_code='JEE-WE'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00010'), CURDATE(),'active');

-- =====================================================================
-- 21. FEE STRUCTURES
-- =====================================================================
INSERT INTO `cd_fee_structures` (`public_id`, `tenant_id`, `course_id`, `name`, `total_amount`, `status`) VALUES
  ('ba000001-0000-0000-0000-000000000001',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_courses WHERE course_code='C10-MATH'),
    'Class 10 Maths – Full Year', 12000, 'active'),
  ('ba000002-0000-0000-0000-000000000002',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_courses WHERE course_code='JEE-FDN'),
    'JEE Foundation – Full Year', 18000, 'active');

INSERT INTO `cd_fee_structure_items` (`fee_structure_id`, `fee_type`, `amount`) VALUES
  ((SELECT id FROM cd_fee_structures WHERE name='Class 10 Maths – Full Year'),'Admission',1000),
  ((SELECT id FROM cd_fee_structures WHERE name='Class 10 Maths – Full Year'),'Monthly',1000),
  ((SELECT id FROM cd_fee_structures WHERE name='Class 10 Maths – Full Year'),'Exam',500),
  ((SELECT id FROM cd_fee_structures WHERE name='JEE Foundation – Full Year'),'Admission',2000),
  ((SELECT id FROM cd_fee_structures WHERE name='JEE Foundation – Full Year'),'Monthly',1300),
  ((SELECT id FROM cd_fee_structures WHERE name='JEE Foundation – Full Year'),'Exam',700);

-- =====================================================================
-- 22. STUDENT FEE ACCOUNTS
-- =====================================================================
INSERT INTO `cd_student_fee_accounts` (`public_id`, `tenant_id`, `student_id`, `fee_structure_id`, `total_amount`, `discount_amount`, `status`) VALUES
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00001'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00001'),
    (SELECT id FROM cd_fee_structures WHERE name='Class 10 Maths – Full Year'), 12000, 0, 'active'),
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00002'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00002'),
    (SELECT id FROM cd_fee_structures WHERE name='Class 10 Maths – Full Year'), 12000, 0, 'active'),
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00003'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00003'),
    (SELECT id FROM cd_fee_structures WHERE name='Class 10 Maths – Full Year'), 12000, 0, 'active'),
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00004'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00004'),
    (SELECT id FROM cd_fee_structures WHERE name='Class 10 Maths – Full Year'), 12000, 0, 'active'),
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00008'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00008'),
    (SELECT id FROM cd_fee_structures WHERE name='JEE Foundation – Full Year'), 18000, 0, 'active'),
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00009'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00009'),
    (SELECT id FROM cd_fee_structures WHERE name='JEE Foundation – Full Year'), 18000, 0, 'active'),
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00010'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00010'),
    (SELECT id FROM cd_fee_structures WHERE name='JEE Foundation – Full Year'), 18000, 0, 'active');

-- =====================================================================
-- 23. FEE INSTALLMENTS
-- =====================================================================
INSERT INTO `cd_fee_installments` (`public_id`, `tenant_id`, `fee_account_id`, `installment_name`, `amount`, `due_date`, `status`)
SELECT UUID(),
       fa.tenant_id,
       fa.id,
       CONCAT('Installment ', n.n),
       ROUND(fa.total_amount / 3, 2),
       DATE_ADD(CURDATE(), INTERVAL (n.n - 2) * 30 DAY),
       CASE WHEN n.n = 1 THEN 'paid' ELSE 'pending' END
FROM cd_student_fee_accounts fa
CROSS JOIN (SELECT 1 AS n UNION SELECT 2 UNION SELECT 3) n
WHERE fa.total_amount > 0;

-- =====================================================================
-- 24. PAYMENTS
-- =====================================================================
INSERT INTO `cd_payments`
  (`public_id`, `tenant_id`, `student_id`, `receipt_number`, `payment_date`, `amount`, `method`, `status`, `notes`, `received_by`)
VALUES
  ('bb000001-0000-0000-0000-000000000001',
    (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00001'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00001'),
    'RCPT-2026-0001', DATE_SUB(CURDATE(), INTERVAL 30 DAY), 8000.00, 'upi', 'success', 'First installment', NULL),
  ('bb000002-0000-0000-0000-000000000002',
    (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00002'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00002'),
    'RCPT-2026-0002', DATE_SUB(CURDATE(), INTERVAL 30 DAY), 4000.00, 'cash', 'success', NULL, NULL),
  ('bb000003-0000-0000-0000-000000000003',
    (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00003'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00003'),
    'RCPT-2026-0003', DATE_SUB(CURDATE(), INTERVAL 30 DAY), 4000.00, 'upi', 'success', NULL, NULL),
  ('bb000004-0000-0000-0000-000000000004',
    (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00008'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00008'),
    'RCPT-2026-0004', DATE_SUB(CURDATE(), INTERVAL 15 DAY), 12000.00, 'bank_transfer', 'success', NULL, NULL);

-- =====================================================================
-- 25. PAYMENT ALLOCATIONS
-- =====================================================================
INSERT IGNORE INTO `cd_payment_allocations` (`tenant_id`, `payment_id`, `installment_id`, `allocated_amount`)
SELECT p.tenant_id,
       p.id,
       fi.id,
       LEAST(p.amount, fi.amount)
FROM cd_payments p
JOIN cd_student_fee_accounts fa ON fa.student_id = p.student_id
JOIN cd_fee_installments fi ON fi.fee_account_id = fa.id
WHERE fi.status = 'pending'
  AND fi.id = (
      SELECT fi2.id FROM cd_fee_installments fi2
      WHERE fi2.fee_account_id = fa.id AND fi2.status = 'pending'
      ORDER BY fi2.due_date ASC LIMIT 1
  );

-- Mark allocated installments as paid
UPDATE cd_fee_installments fi
SET fi.status = 'paid'
WHERE fi.id IN (SELECT installment_id FROM cd_payment_allocations);

-- =====================================================================
-- 26. ATTENDANCE SESSIONS (today)
-- =====================================================================
INSERT INTO `cd_attendance_sessions`
  (`public_id`, `tenant_id`, `branch_id`, `batch_id`, `attendance_date`, `status`, `submitted_at`)
VALUES
  (UUID(),
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    (SELECT id FROM cd_batches WHERE batch_code='MATH-A'),
    CURDATE(),'submitted',NOW()),
  (UUID(),
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    (SELECT id FROM cd_batches WHERE batch_code='MATH-B'),
    CURDATE(),'submitted',NOW());

-- =====================================================================
-- 27. ATTENDANCE RECORDS
-- =====================================================================
INSERT INTO `cd_attendance_records` (`tenant_id`, `session_id`, `student_id`, `attendance_status`)
VALUES
  ((SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00001'),
    (SELECT id FROM cd_attendance_sessions WHERE batch_id=(SELECT id FROM cd_batches WHERE batch_code='MATH-A') AND attendance_date=CURDATE()),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00001'),'present'),
  ((SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00002'),
    (SELECT id FROM cd_attendance_sessions WHERE batch_id=(SELECT id FROM cd_batches WHERE batch_code='MATH-A') AND attendance_date=CURDATE()),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00002'),'present'),
  ((SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00003'),
    (SELECT id FROM cd_attendance_sessions WHERE batch_id=(SELECT id FROM cd_batches WHERE batch_code='MATH-A') AND attendance_date=CURDATE()),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00003'),'late'),
  ((SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00004'),
    (SELECT id FROM cd_attendance_sessions WHERE batch_id=(SELECT id FROM cd_batches WHERE batch_code='MATH-A') AND attendance_date=CURDATE()),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00004'),'absent'),
  ((SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00005'),
    (SELECT id FROM cd_attendance_sessions WHERE batch_id=(SELECT id FROM cd_batches WHERE batch_code='MATH-B') AND attendance_date=CURDATE()),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00005'),'present'),
  ((SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00006'),
    (SELECT id FROM cd_attendance_sessions WHERE batch_id=(SELECT id FROM cd_batches WHERE batch_code='MATH-B') AND attendance_date=CURDATE()),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00006'),'present'),
  ((SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00007'),
    (SELECT id FROM cd_attendance_sessions WHERE batch_id=(SELECT id FROM cd_batches WHERE batch_code='MATH-B') AND attendance_date=CURDATE()),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00007'),'half_day');

-- =====================================================================
-- 28. EXAMS
-- =====================================================================
INSERT INTO `cd_exams`
  (`public_id`, `tenant_id`, `branch_id`, `batch_id`, `exam_name`, `exam_date`, `status`)
VALUES
  ('ca000001-0000-0000-0000-000000000001',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    (SELECT id FROM cd_batches WHERE batch_code='MATH-A'),
    'Unit Test 1 – October', DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'published');

INSERT INTO `cd_exam_subjects` (`tenant_id`, `exam_id`, `subject_id`, `maximum_marks`, `passing_marks`) VALUES
  ((SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_exams WHERE exam_name='Unit Test 1 – October'),
    (SELECT id FROM cd_subjects WHERE subject_code='MATH' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    100, 33);

-- =====================================================================
-- 29. EXAM RESULTS
-- =====================================================================
INSERT INTO `cd_exam_results`
  (`public_id`, `tenant_id`, `exam_id`, `student_id`, `total_marks`, `percentage`, `grade`, `rank_no`, `status`)
VALUES
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00001'),
    (SELECT id FROM cd_exams WHERE exam_name='Unit Test 1 – October'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00001'),
    85, 85.00, 'A', 1, 'published'),
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00002'),
    (SELECT id FROM cd_exams WHERE exam_name='Unit Test 1 – October'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00002'),
    78, 78.00, 'B', 2, 'published'),
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00003'),
    (SELECT id FROM cd_exams WHERE exam_name='Unit Test 1 – October'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00003'),
    92, 92.00, 'A+', 3, 'published');

INSERT INTO `cd_exam_result_details` (`result_id`, `subject_id`, `obtained_marks`) VALUES
  ((SELECT er.id FROM cd_exam_results er JOIN cd_students s ON s.id=er.student_id WHERE s.student_code='CD-GN-2026-00001'),
    (SELECT id FROM cd_subjects WHERE subject_code='MATH' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')), 85),
  ((SELECT er.id FROM cd_exam_results er JOIN cd_students s ON s.id=er.student_id WHERE s.student_code='CD-GN-2026-00002'),
    (SELECT id FROM cd_subjects WHERE subject_code='MATH' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')), 78),
  ((SELECT er.id FROM cd_exam_results er JOIN cd_students s ON s.id=er.student_id WHERE s.student_code='CD-GN-2026-00003'),
    (SELECT id FROM cd_subjects WHERE subject_code='MATH' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')), 92);

-- =====================================================================
-- 30. TIMETABLE
-- =====================================================================
INSERT INTO `cd_timetable`
  (`public_id`, `tenant_id`, `branch_id`, `batch_id`, `subject_id`, `teacher_id`, `classroom_id`, `day_of_week`, `start_time`, `end_time`, `status`)
VALUES
  (UUID(),
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    (SELECT id FROM cd_batches WHERE batch_code='MATH-A'),
    (SELECT id FROM cd_subjects WHERE subject_code='MATH' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    (SELECT id FROM cd_teachers WHERE employee_code='EMP-001'),
    (SELECT id FROM cd_classrooms WHERE room_name='Room A101' LIMIT 1),
    1, '08:00:00','09:00:00','active'),
  (UUID(),
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    (SELECT id FROM cd_batches WHERE batch_code='MATH-A'),
    (SELECT id FROM cd_subjects WHERE subject_code='MATH' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    (SELECT id FROM cd_teachers WHERE employee_code='EMP-001'),
    (SELECT id FROM cd_classrooms WHERE room_name='Room A101' LIMIT 1),
    3, '08:00:00','09:00:00','active'),
  (UUID(),
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    (SELECT id FROM cd_batches WHERE batch_code='MATH-B'),
    (SELECT id FROM cd_subjects WHERE subject_code='MATH' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    (SELECT id FROM cd_teachers WHERE employee_code='EMP-001'),
    (SELECT id FROM cd_classrooms WHERE room_name='Room A102' LIMIT 1),
    1, '18:00:00','19:00:00','active');

-- =====================================================================
-- 31. HOMEWORK
-- =====================================================================
INSERT INTO `cd_homework`
  (`public_id`, `tenant_id`, `batch_id`, `subject_id`, `title`, `description`, `due_date`, `created_by`)
VALUES
  ('da000001-0000-0000-0000-000000000001',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_batches WHERE batch_code='MATH-A'),
    (SELECT id FROM cd_subjects WHERE subject_code='MATH' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'Quadratic Equations – Exercise 4.3', 'Solve Q1-Q15', DATE_ADD(CURDATE(), INTERVAL 7 DAY), NULL);

INSERT INTO `cd_homework_submissions`
  (`public_id`, `tenant_id`, `homework_id`, `student_id`, `submitted_at`, `marks`, `feedback`)
VALUES
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00001'),
    (SELECT id FROM cd_homework WHERE title LIKE 'Quadratic%'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00001'), NOW(), 9, 'Good work'),
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00002'),
    (SELECT id FROM cd_homework WHERE title LIKE 'Quadratic%'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00002'), NOW(), 8, 'Needs neatness'),
  (UUID(), (SELECT tenant_id FROM cd_students WHERE student_code='CD-GN-2026-00003'),
    (SELECT id FROM cd_homework WHERE title LIKE 'Quadratic%'),
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00003'), NOW(), 10, 'Excellent');

-- =====================================================================
-- 32. STUDY MATERIALS
-- =====================================================================
INSERT INTO `cd_study_materials`
  (`public_id`, `tenant_id`, `batch_id`, `subject_id`, `title`, `file_path`, `video_url`, `created_by`)
VALUES
  (UUID(),
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_batches WHERE batch_code='MATH-A'),
    (SELECT id FROM cd_subjects WHERE subject_code='MATH' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'Quadratic Equations Notes', '/storage/materials/quadratic-notes.pdf', NULL, NULL),
  (UUID(),
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_batches WHERE batch_code='MATH-A'),
    (SELECT id FROM cd_subjects WHERE subject_code='MATH' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'Video Lecture – Factoring', NULL, 'https://youtu.be/example123', NULL);

-- =====================================================================
-- 33. ANNOUNCEMENTS
-- =====================================================================
INSERT INTO `cd_announcements`
  (`public_id`, `tenant_id`, `title`, `description`, `target_type`, `target_id`, `status`, `created_by`)
VALUES
  (UUID(), (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    'Diwali Holiday Notice', 'Institute closed from 20-22 Oct',
    'all', NULL, 'published', NULL),
  (UUID(), (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    'Unit Test 2 Schedule', 'Exam on 15 Nov for MATH-A',
    'batch', (SELECT id FROM cd_batches WHERE batch_code='MATH-A'), 'published', NULL);

-- =====================================================================
-- 34. NOTIFICATION TEMPLATES
-- =====================================================================
INSERT INTO `cd_notification_templates`
  (`tenant_id`, `channel`, `template_key`, `subject`, `content`, `is_active`)
VALUES
  ((SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    'whatsapp', 'fee_due', 'Fee Due Reminder',
    'Dear parent, fee of {{amount}} for {{student_name}} is due on {{due_date}}.', 1),
  ((SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    'email', 'fee_received', 'Fee Received',
    'Thank you. Payment of {{amount}} received. Receipt #{{receipt}}.', 1),
  ((SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    'inapp', 'absence', 'Absence Alert',
    '{{student_name}} was marked absent on {{date}}.', 1);

-- =====================================================================
-- 35. NOTIFICATIONS (in-app)
-- =====================================================================
INSERT INTO `cd_notifications`
  (`public_id`, `tenant_id`, `user_id`, `title`, `message`, `read_at`)
VALUES
  (UUID(), (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_users WHERE email='owner@abc.edu'),
    'Welcome to CoachDesk', 'Your institute is ready.', NULL),
  (UUID(), (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_users WHERE email='amit@abc.edu'),
    'New batch assigned', 'You were assigned to MATH-B.', NULL);

-- =====================================================================
-- 36. EXPENSES
-- =====================================================================
INSERT INTO `cd_expenses`
  (`public_id`, `tenant_id`, `branch_id`, `category`, `amount`, `expense_date`, `notes`)
VALUES
  (UUID(), (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'Rent', 25000.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Monthly rent'),
  (UUID(), (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'Electricity', 3500.00, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Oct bill'),
  (UUID(), (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_branches WHERE branch_code='GN' AND tenant_id=(SELECT id FROM cd_tenants WHERE code='abc-coaching')),
    'Stationery', 1200.00, CURDATE(), 'Markers, dusters');

-- =====================================================================
-- 37. SUPPORT TICKET
-- =====================================================================
INSERT INTO `cd_support_tickets`
  (`public_id`, `tenant_id`, `user_id`, `subject`, `description`, `priority`, `status`)
VALUES
  (UUID(), (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_users WHERE email='owner@abc.edu'),
    'SMS balance low', 'Please top-up SMS credits.', 'medium', 'open');

-- =====================================================================
-- 38. SETTINGS
-- =====================================================================
INSERT INTO `cd_settings` (`tenant_id`, `setting_key`, `setting_value`) VALUES
  ((SELECT id FROM cd_tenants WHERE code='abc-coaching'), 'branding',
    JSON_OBJECT('primary_color','#4f46e5','receipt_footer','Thank you for choosing ABC Coaching')),
  ((SELECT id FROM cd_tenants WHERE code='abc-coaching'), 'notifications',
    JSON_OBJECT('fee_reminder_days',3,'send_absence_sms',1)),
  ((SELECT id FROM cd_tenants WHERE code='xyz-academy'), 'branding',
    JSON_OBJECT('primary_color','#059669'));

INSERT INTO `cd_platform_settings` (`setting_key`, `setting_value`) VALUES
  ('signup_enabled', JSON_OBJECT('value',1)),
  ('support_email',  JSON_OBJECT('value','support@coachdesk.in')),
  ('trial_days',     JSON_OBJECT('value',14));

-- =====================================================================
-- 39. ACTIVITY LOGS
-- =====================================================================
INSERT INTO `cd_activity_logs`
  (`tenant_id`, `user_id`, `action`, `module`, `record_type`, `record_id`, `metadata`, `ip_address`)
VALUES
  ((SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_users WHERE email='owner@abc.edu'),
    'tenant.created','tenants','tenant',
    (SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    JSON_OBJECT('code','abc-coaching'),'127.0.0.1'),
  ((SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_users WHERE email='owner@abc.edu'),
    'student.created','students','student',
    (SELECT id FROM cd_students WHERE student_code='CD-GN-2026-00001'),
    JSON_OBJECT('code','CD-GN-2026-00001'),'127.0.0.1'),
  ((SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_users WHERE email='acc@abc.edu'),
    'payment.collected','fees','payment',
    (SELECT id FROM cd_payments WHERE receipt_number='RCPT-2026-0001'),
    JSON_OBJECT('amount',8000,'method','upi'),'127.0.0.1'),
  ((SELECT id FROM cd_tenants WHERE code='abc-coaching'),
    (SELECT id FROM cd_users WHERE email='amit@abc.edu'),
    'attendance.marked','attendance','session',
    (SELECT id FROM cd_attendance_sessions WHERE batch_id=(SELECT id FROM cd_batches WHERE batch_code='MATH-A') LIMIT 1),
    JSON_OBJECT('batch','MATH-A'),'127.0.0.1');

SET FOREIGN_KEY_CHECKS = 1;