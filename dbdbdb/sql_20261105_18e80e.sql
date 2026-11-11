-- =====================================================================
-- CoachDesk — Enterprise SaaS Coaching Institute Management System
-- MySQL 8.0+ | InnoDB | utf8mb4_0900_ai_ci
-- Table prefix: cd_   |   Internal IDs: INT(11) signed
-- Multi-tenant: every institute-owned row carries tenant_id
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- 1. SAAS / TENANT CORE
-- =====================================================================

CREATE TABLE IF NOT EXISTS cd_tenants (
  id                INT(11)      NOT NULL AUTO_INCREMENT,
  public_id         CHAR(36)     NOT NULL,
  name              VARCHAR(150) NOT NULL,
  code              VARCHAR(30)  NOT NULL,
  email             VARCHAR(150) NOT NULL,
  phone             VARCHAR(20)  NULL,
  website           VARCHAR(150) NULL,
  logo_path         VARCHAR(255) NULL,
  address_line1     VARCHAR(255) NULL,
  address_line2     VARCHAR(255) NULL,
  city              VARCHAR(100) NULL,
  state             VARCHAR(100) NULL,
  pincode           VARCHAR(10)  NULL,
  country           VARCHAR(60)  NOT NULL DEFAULT 'India',
  status            ENUM('trial','active','suspended','expired','cancelled') NOT NULL DEFAULT 'trial',
  trial_ends_at     DATETIME     NULL,
  timezone          VARCHAR(50)  NOT NULL DEFAULT 'Asia/Kolkata',
  currency          CHAR(3)      NOT NULL DEFAULT 'INR',
  locale            VARCHAR(10)  NOT NULL DEFAULT 'en-IN',
  created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at        DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tenants_public (public_id),
  UNIQUE KEY uq_tenants_code   (code),
  KEY idx_tenants_status       (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_branches (
  id            INT(11)      NOT NULL AUTO_INCREMENT,
  public_id     CHAR(36)     NOT NULL,
  tenant_id     INT(11)      NOT NULL,
  name          VARCHAR(150) NOT NULL,
  branch_code   VARCHAR(30)  NOT NULL,
  address_line1 VARCHAR(255) NULL,
  address_line2 VARCHAR(255) NULL,
  city          VARCHAR(100) NULL,
  state         VARCHAR(100) NULL,
  pincode       VARCHAR(10)  NULL,
  phone         VARCHAR(20)  NULL,
  email         VARCHAR(150) NULL,
  status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at    DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_branches_public      (public_id),
  UNIQUE KEY uq_branches_tenant_code (tenant_id, branch_code),
  KEY idx_branches_tenant            (tenant_id),
  CONSTRAINT fk_branches_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_subscription_plans (
  id             INT(11)      NOT NULL AUTO_INCREMENT,
  public_id      CHAR(36)     NOT NULL,
  name           VARCHAR(80)  NOT NULL,
  price          DECIMAL(12,2) NOT NULL,
  billing_cycle  ENUM('monthly','quarterly','yearly') NOT NULL,
  max_students   INT(11)      NOT NULL DEFAULT 100,
  max_staff      INT(11)      NOT NULL DEFAULT 5,
  max_branches   INT(11)      NOT NULL DEFAULT 1,
  features_json  JSON         NULL,
  is_active      TINYINT(1)   NOT NULL DEFAULT 1,
  sort_order     INT(11)      NOT NULL DEFAULT 0,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_plans_public (public_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_tenant_subscriptions (
  id          INT(11)      NOT NULL AUTO_INCREMENT,
  public_id   CHAR(36)     NOT NULL,
  tenant_id   INT(11)      NOT NULL,
  plan_id     INT(11)      NOT NULL,
  start_date  DATE         NOT NULL,
  end_date    DATE         NOT NULL,
  status      ENUM('trial','active','expired','cancelled') NOT NULL DEFAULT 'trial',
  auto_renew  TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_subs_public (public_id),
  KEY idx_subs_tenant       (tenant_id),
  KEY idx_subs_status_end   (status, end_date),
  CONSTRAINT fk_subs_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_subs_plan   FOREIGN KEY (plan_id)   REFERENCES cd_subscription_plans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_subscription_payments (
  id              INT(11)      NOT NULL AUTO_INCREMENT,
  public_id       CHAR(36)     NOT NULL,
  tenant_id       INT(11)      NOT NULL,
  subscription_id INT(11)      NOT NULL,
  amount          DECIMAL(12,2) NOT NULL,
  currency        CHAR(3)      NOT NULL DEFAULT 'INR',
  method          ENUM('upi','card','netbanking','cash','cheque','bank_transfer') NOT NULL,
  transaction_id  VARCHAR(120) NULL,
  idempotency_key VARCHAR(120) NULL,
  status          ENUM('pending','success','failed','refunded') NOT NULL DEFAULT 'pending',
  notes           TEXT         NULL,
  paid_at         DATETIME     NULL,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_subpay_public (public_id),
  UNIQUE KEY uq_subpay_idem   (idempotency_key),
  KEY idx_subpay_tenant       (tenant_id),
  CONSTRAINT fk_subpay_tenant FOREIGN KEY (tenant_id)       REFERENCES cd_tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_subpay_subs   FOREIGN KEY (subscription_id) REFERENCES cd_tenant_subscriptions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================================
-- 2. AUTH & ACCESS
-- =====================================================================

CREATE TABLE IF NOT EXISTS cd_users (
  id                INT(11)      NOT NULL AUTO_INCREMENT,
  public_id         CHAR(36)     NOT NULL,
  tenant_id         INT(11)      NULL,
  name              VARCHAR(150) NOT NULL,
  email             VARCHAR(190) NOT NULL,
  phone             VARCHAR(20)  NULL,
  password_hash     VARCHAR(255) NOT NULL,
  avatar_path       VARCHAR(255) NULL,
  email_verified_at DATETIME     NULL,
  phone_verified_at DATETIME     NULL,
  status            ENUM('active','inactive','locked') NOT NULL DEFAULT 'active',
  last_login_at     DATETIME     NULL,
  last_login_ip     VARCHAR(45)  NULL,
  failed_logins     SMALLINT     NOT NULL DEFAULT 0,
  locked_until      DATETIME     NULL,
  created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at        DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_public       (public_id),
  UNIQUE KEY uq_users_tenant_email (tenant_id, email),
  KEY idx_users_tenant             (tenant_id),
  KEY idx_users_email              (email),
  CONSTRAINT fk_users_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_roles (
  id         INT(11)      NOT NULL AUTO_INCREMENT,
  public_id  CHAR(36)     NOT NULL,
  tenant_id  INT(11)      NULL,
  name       VARCHAR(80)  NOT NULL,
  slug       VARCHAR(80)  NOT NULL,
  is_system  TINYINT(1)   NOT NULL DEFAULT 0,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_public      (public_id),
  UNIQUE KEY uq_roles_tenant_slug (tenant_id, slug),
  CONSTRAINT fk_roles_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_permissions (
  id             INT(11)      NOT NULL AUTO_INCREMENT,
  permission_key VARCHAR(100) NOT NULL,
  module         VARCHAR(50)  NOT NULL,
  description    VARCHAR(255) NULL,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_permissions_key (permission_key),
  KEY idx_permissions_module    (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_role_permissions (
  role_id       INT(11) NOT NULL,
  permission_id INT(11) NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_rp_role FOREIGN KEY (role_id)       REFERENCES cd_roles(id)       ON DELETE CASCADE,
  CONSTRAINT fk_rp_perm FOREIGN KEY (permission_id) REFERENCES cd_permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_user_roles (
  id         INT(11)  NOT NULL AUTO_INCREMENT,
  user_id    INT(11)  NOT NULL,
  role_id    INT(11)  NOT NULL,
  branch_id  INT(11)  NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_roles (user_id, role_id, branch_id),
  KEY idx_user_roles_role  (role_id),
  CONSTRAINT fk_ur_user   FOREIGN KEY (user_id)   REFERENCES cd_users(id)    ON DELETE CASCADE,
  CONSTRAINT fk_ur_role   FOREIGN KEY (role_id)   REFERENCES cd_roles(id)    ON DELETE CASCADE,
  CONSTRAINT fk_ur_branch FOREIGN KEY (branch_id) REFERENCES cd_branches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_user_permissions (
  id            INT(11) NOT NULL AUTO_INCREMENT,
  user_id       INT(11) NOT NULL,
  permission_id INT(11) NOT NULL,
  effect        ENUM('allow','deny') NOT NULL DEFAULT 'allow',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_perm (user_id, permission_id),
  CONSTRAINT fk_up_user FOREIGN KEY (user_id)       REFERENCES cd_users(id)       ON DELETE CASCADE,
  CONSTRAINT fk_up_perm FOREIGN KEY (permission_id) REFERENCES cd_permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_refresh_tokens (
  id          INT(11)      NOT NULL AUTO_INCREMENT,
  user_id     INT(11)      NOT NULL,
  token_hash  CHAR(64)     NOT NULL,
  device      VARCHAR(255) NULL,
  ip_address  VARCHAR(45)  NULL,
  expires_at  DATETIME     NOT NULL,
  revoked_at  DATETIME     NULL,
  replaced_by INT(11)      NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_rt_hash (token_hash),
  KEY idx_rt_user       (user_id),
  KEY idx_rt_expires    (expires_at),
  CONSTRAINT fk_rt_user FOREIGN KEY (user_id) REFERENCES cd_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_password_resets (
  id         INT(11)  NOT NULL AUTO_INCREMENT,
  user_id    INT(11)  NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at    DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pr_hash (token_hash),
  KEY idx_pr_user       (user_id),
  CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES cd_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================================
-- 3. ACADEMIC STRUCTURE
-- =====================================================================

CREATE TABLE IF NOT EXISTS cd_academic_sessions (
  id         INT(11)      NOT NULL AUTO_INCREMENT,
  public_id  CHAR(36)     NOT NULL,
  tenant_id  INT(11)      NOT NULL,
  name       VARCHAR(80)  NOT NULL,
  start_date DATE         NOT NULL,
  end_date   DATE         NOT NULL,
  is_current TINYINT(1)   NOT NULL DEFAULT 0,
  status     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sessions_public (public_id),
  KEY idx_sessions_tenant       (tenant_id),
  CONSTRAINT fk_sessions_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_courses (
  id          INT(11)      NOT NULL AUTO_INCREMENT,
  public_id   CHAR(36)     NOT NULL,
  tenant_id   INT(11)      NOT NULL,
  branch_id   INT(11)      NULL,
  course_name VARCHAR(150) NOT NULL,
  course_code VARCHAR(30)  NOT NULL,
  duration    VARCHAR(50)  NULL,
  fees        DECIMAL(12,2) NOT NULL DEFAULT 0,
  description TEXT         NULL,
  status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at  DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_courses_public      (public_id),
  UNIQUE KEY uq_courses_tenant_code (tenant_id, course_code),
  KEY idx_courses_tenant            (tenant_id),
  CONSTRAINT fk_courses_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_subjects (
  id           INT(11)      NOT NULL AUTO_INCREMENT,
  public_id    CHAR(36)     NOT NULL,
  tenant_id    INT(11)      NOT NULL,
  subject_name VARCHAR(120) NOT NULL,
  subject_code VARCHAR(30)  NULL,
  description  TEXT         NULL,
  status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at   DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_subjects_public (public_id),
  KEY idx_subjects_tenant       (tenant_id),
  CONSTRAINT fk_subjects_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_course_subjects (
  id         INT(11) NOT NULL AUTO_INCREMENT,
  course_id  INT(11) NOT NULL,
  subject_id INT(11) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_course_subject (course_id, subject_id),
  CONSTRAINT fk_cs_course  FOREIGN KEY (course_id)  REFERENCES cd_courses(id)  ON DELETE CASCADE,
  CONSTRAINT fk_cs_subject FOREIGN KEY (subject_id) REFERENCES cd_subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_classrooms (
  id         INT(11)      NOT NULL AUTO_INCREMENT,
  public_id  CHAR(36)     NOT NULL,
  tenant_id  INT(11)      NOT NULL,
  branch_id  INT(11)      NOT NULL,
  room_name  VARCHAR(80)  NOT NULL,
  capacity   INT(11)      NOT NULL DEFAULT 0,
  status     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_rooms_public (public_id),
  KEY idx_rooms_tenant       (tenant_id),
  CONSTRAINT fk_rooms_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_rooms_branch FOREIGN KEY (branch_id) REFERENCES cd_branches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_teachers (
  id             INT(11)      NOT NULL AUTO_INCREMENT,
  public_id      CHAR(36)     NOT NULL,
  tenant_id      INT(11)      NOT NULL,
  branch_id      INT(11)      NULL,
  user_id        INT(11)      NULL,
  employee_code  VARCHAR(30)  NULL,
  name           VARCHAR(150) NOT NULL,
  qualification  VARCHAR(150) NULL,
  experience     VARCHAR(80)  NULL,
  phone          VARCHAR(20)  NULL,
  email          VARCHAR(150) NULL,
  joining_date   DATE         NULL,
  status         ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at     DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_teachers_public (public_id),
  KEY idx_teachers_tenant       (tenant_id),
  CONSTRAINT fk_teachers_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_teachers_user   FOREIGN KEY (user_id)   REFERENCES cd_users(id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_batches (
  id                 INT(11)      NOT NULL AUTO_INCREMENT,
  public_id          CHAR(36)     NOT NULL,
  tenant_id          INT(11)      NOT NULL,
  branch_id          INT(11)      NOT NULL,
  course_id          INT(11)      NOT NULL,
  academic_session_id INT(11)     NULL,
  batch_name         VARCHAR(120) NOT NULL,
  batch_code         VARCHAR(30)  NOT NULL,
  capacity           INT(11)      NOT NULL DEFAULT 0,
  start_date         DATE         NULL,
  end_date           DATE         NULL,
  timing_start       TIME         NULL,
  timing_end         TIME         NULL,
  status             ENUM('upcoming','running','completed','cancelled') NOT NULL DEFAULT 'upcoming',
  created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at         DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_batches_public      (public_id),
  UNIQUE KEY uq_batches_tenant_code (tenant_id, batch_code),
  KEY idx_batches_tenant            (tenant_id),
  CONSTRAINT fk_batches_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_batches_course FOREIGN KEY (course_id) REFERENCES cd_courses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_batch_subjects (
  id         INT(11) NOT NULL AUTO_INCREMENT,
  batch_id   INT(11) NOT NULL,
  subject_id INT(11) NOT NULL,
  teacher_id INT(11) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_batch_subject (batch_id, subject_id),
  CONSTRAINT fk_bs_batch   FOREIGN KEY (batch_id)   REFERENCES cd_batches(id)  ON DELETE CASCADE,
  CONSTRAINT fk_bs_subject FOREIGN KEY (subject_id) REFERENCES cd_subjects(id) ON DELETE CASCADE,
  CONSTRAINT fk_bs_teacher FOREIGN KEY (teacher_id) REFERENCES cd_teachers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================================
-- 4. STUDENTS
-- =====================================================================

CREATE TABLE IF NOT EXISTS cd_students (
  id             INT(11)      NOT NULL AUTO_INCREMENT,
  public_id      CHAR(36)     NOT NULL,
  tenant_id      INT(11)      NOT NULL,
  branch_id      INT(11)      NOT NULL,
  student_code   VARCHAR(40)  NOT NULL,
  name           VARCHAR(150) NOT NULL,
  dob            DATE         NULL,
  gender         ENUM('male','female','other') NULL,
  phone          VARCHAR(20)  NULL,
  email          VARCHAR(150) NULL,
  address_line1  VARCHAR(255) NULL,
  address_line2  VARCHAR(255) NULL,
  city           VARCHAR(100) NULL,
  state          VARCHAR(100) NULL,
  pincode        VARCHAR(10)  NULL,
  photo_path     VARCHAR(255) NULL,
  admission_date DATE         NULL,
  status         ENUM('active','inactive','completed','cancelled') NOT NULL DEFAULT 'active',
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at     DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_students_public      (public_id),
  UNIQUE KEY uq_students_tenant_code (tenant_id, student_code),
  KEY idx_students_tenant            (tenant_id),
  KEY idx_students_branch            (branch_id),
  CONSTRAINT fk_students_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_students_branch FOREIGN KEY (branch_id) REFERENCES cd_branches(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_student_guardians (
  id           INT(11)      NOT NULL AUTO_INCREMENT,
  public_id    CHAR(36)     NOT NULL,
  tenant_id    INT(11)      NOT NULL,
  student_id   INT(11)      NOT NULL,
  name         VARCHAR(150) NOT NULL,
  relationship VARCHAR(50)  NULL,
  phone        VARCHAR(20)  NULL,
  email        VARCHAR(150) NULL,
  occupation   VARCHAR(100) NULL,
  is_primary   TINYINT(1)   NOT NULL DEFAULT 0,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_guardians_public (public_id),
  KEY idx_guardians_student      (student_id),
  CONSTRAINT fk_guardians_tenant  FOREIGN KEY (tenant_id)  REFERENCES cd_tenants(id)  ON DELETE CASCADE,
  CONSTRAINT fk_guardians_student FOREIGN KEY (student_id) REFERENCES cd_students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_student_documents (
  id            INT(11)      NOT NULL AUTO_INCREMENT,
  public_id     CHAR(36)     NOT NULL,
  tenant_id     INT(11)      NOT NULL,
  student_id    INT(11)      NOT NULL,
  document_type VARCHAR(80)  NOT NULL,
  file_path     VARCHAR(255) NOT NULL,
  mime_type     VARCHAR(80)  NULL,
  file_size     INT(11)      NULL,
  uploaded_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_docs_public (public_id),
  KEY idx_docs_student      (student_id),
  CONSTRAINT fk_docs_tenant  FOREIGN KEY (tenant_id)  REFERENCES cd_tenants(id)  ON DELETE CASCADE,
  CONSTRAINT fk_docs_student FOREIGN KEY (student_id) REFERENCES cd_students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_batch_students (
  id         INT(11)  NOT NULL AUTO_INCREMENT,
  tenant_id  INT(11)  NOT NULL,
  batch_id   INT(11)  NOT NULL,
  student_id INT(11)  NOT NULL,
  start_date DATE     NULL,
  end_date   DATE     NULL,
  status     ENUM('active','completed','transferred','dropped') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_batch_student (batch_id, student_id, status),
  KEY idx_bs_student          (student_id),
  CONSTRAINT fk_bst_tenant  FOREIGN KEY (tenant_id)  REFERENCES cd_tenants(id)  ON DELETE CASCADE,
  CONSTRAINT fk_bst_batch   FOREIGN KEY (batch_id)   REFERENCES cd_batches(id)  ON DELETE CASCADE,
  CONSTRAINT fk_bst_student FOREIGN KEY (student_id) REFERENCES cd_students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================================
-- 5. ATTENDANCE
-- =====================================================================

CREATE TABLE IF NOT EXISTS cd_attendance_sessions (
  id              INT(11)  NOT NULL AUTO_INCREMENT,
  public_id       CHAR(36) NOT NULL,
  tenant_id       INT(11)  NOT NULL,
  branch_id       INT(11)  NOT NULL,
  batch_id        INT(11)  NOT NULL,
  attendance_date DATE     NOT NULL,
  status          ENUM('draft','submitted','locked') NOT NULL DEFAULT 'draft',
  submitted_by    INT(11)  NULL,
  submitted_at    DATETIME NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_session_public (public_id),
  UNIQUE KEY uq_session_batch_date (batch_id, attendance_date),
  KEY idx_sessions_tenant (tenant_id),
  CONSTRAINT fk_sess_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_sess_batch  FOREIGN KEY (batch_id)  REFERENCES cd_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_attendance_records (
  id                INT(11) NOT NULL AUTO_INCREMENT,
  tenant_id         INT(11) NOT NULL,
  session_id        INT(11) NOT NULL,
  student_id        INT(11) NOT NULL,
  attendance_status ENUM('present','absent','late','half_day','on_leave') NOT NULL,
  remarks           VARCHAR(255) NULL,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_attendance (session_id, student_id),
  KEY idx_att_student      (student_id),
  CONSTRAINT fk_att_tenant  FOREIGN KEY (tenant_id)  REFERENCES cd_tenants(id)  ON DELETE CASCADE,
  CONSTRAINT fk_att_session FOREIGN KEY (session_id) REFERENCES cd_attendance_sessions(id) ON DELETE CASCADE,
  CONSTRAINT fk_att_student FOREIGN KEY (student_id) REFERENCES cd_students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_student_leave_requests (
  id         INT(11)      NOT NULL AUTO_INCREMENT,
  public_id  CHAR(36)     NOT NULL,
  tenant_id  INT(11)      NOT NULL,
  student_id INT(11)      NOT NULL,
  start_date DATE         NOT NULL,
  end_date   DATE         NOT NULL,
  reason     TEXT         NULL,
  status     ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  approved_by INT(11)     NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_leave_public (public_id),
  KEY idx_leave_student      (student_id),
  CONSTRAINT fk_leave_tenant  FOREIGN KEY (tenant_id)  REFERENCES cd_tenants(id)  ON DELETE CASCADE,
  CONSTRAINT fk_leave_student FOREIGN KEY (student_id) REFERENCES cd_students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================================
-- 6. FEES & PAYMENTS
-- =====================================================================

CREATE TABLE IF NOT EXISTS cd_fee_structures (
  id         INT(11)      NOT NULL AUTO_INCREMENT,
  public_id  CHAR(36)     NOT NULL,
  tenant_id  INT(11)      NOT NULL,
  course_id  INT(11)      NOT NULL,
  name       VARCHAR(120) NOT NULL,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  status     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_feestruct_public (public_id),
  KEY idx_feestruct_tenant       (tenant_id),
  CONSTRAINT fk_feestruct_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_feestruct_course FOREIGN KEY (course_id) REFERENCES cd_courses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_fee_structure_items (
  id              INT(11)      NOT NULL AUTO_INCREMENT,
  fee_structure_id INT(11)     NOT NULL,
  fee_type        VARCHAR(80)  NOT NULL,
  amount          DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_fsi_struct (fee_structure_id),
  CONSTRAINT fk_fsi_struct FOREIGN KEY (fee_structure_id) REFERENCES cd_fee_structures(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_student_fee_accounts (
  id              INT(11)      NOT NULL AUTO_INCREMENT,
  public_id       CHAR(36)     NOT NULL,
  tenant_id       INT(11)      NOT NULL,
  student_id      INT(11)      NOT NULL,
  fee_structure_id INT(11)     NULL,
  total_amount    DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  status          ENUM('active','closed') NOT NULL DEFAULT 'active',
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sfa_public (public_id),
  KEY idx_sfa_student      (student_id),
  CONSTRAINT fk_sfa_tenant  FOREIGN KEY (tenant_id)  REFERENCES cd_tenants(id)  ON DELETE CASCADE,
  CONSTRAINT fk_sfa_student FOREIGN KEY (student_id) REFERENCES cd_students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_fee_installments (
  id             INT(11)      NOT NULL AUTO_INCREMENT,
  public_id      CHAR(36)     NOT NULL,
  tenant_id      INT(11)      NOT NULL,
  fee_account_id INT(11)      NOT NULL,
  installment_name VARCHAR(80) NOT NULL,
  amount         DECIMAL(12,2) NOT NULL,
  due_date       DATE         NOT NULL,
  status         ENUM('pending','partial','paid','overdue') NOT NULL DEFAULT 'pending',
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_inst_public (public_id),
  KEY idx_inst_account      (fee_account_id),
  KEY idx_inst_due          (due_date),
  CONSTRAINT fk_inst_tenant  FOREIGN KEY (tenant_id)      REFERENCES cd_tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_inst_account FOREIGN KEY (fee_account_id) REFERENCES cd_student_fee_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_payments (
  id             INT(11)      NOT NULL AUTO_INCREMENT,
  public_id      CHAR(36)     NOT NULL,
  tenant_id      INT(11)      NOT NULL,
  student_id     INT(11)      NOT NULL,
  receipt_number VARCHAR(40)  NOT NULL,
  payment_date   DATE         NOT NULL,
  amount         DECIMAL(12,2) NOT NULL,
  method         ENUM('cash','upi','bank_transfer','cheque','card','online') NOT NULL,
  transaction_ref VARCHAR(120) NULL,
  status         ENUM('success','pending','failed','refunded') NOT NULL DEFAULT 'success',
  notes          TEXT         NULL,
  received_by    INT(11)      NULL,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pay_public   (public_id),
  UNIQUE KEY uq_pay_receipt  (tenant_id, receipt_number),
  KEY idx_pay_student        (student_id),
  CONSTRAINT fk_pay_tenant  FOREIGN KEY (tenant_id)  REFERENCES cd_tenants(id)  ON DELETE CASCADE,
  CONSTRAINT fk_pay_student FOREIGN KEY (student_id) REFERENCES cd_students(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_payment_allocations (
  id              INT(11)      NOT NULL AUTO_INCREMENT,
  tenant_id       INT(11)      NOT NULL,
  payment_id      INT(11)      NOT NULL,
  installment_id  INT(11)      NOT NULL,
  allocated_amount DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_pa_payment     (payment_id),
  KEY idx_pa_installment (installment_id),
  CONSTRAINT fk_pa_tenant      FOREIGN KEY (tenant_id)      REFERENCES cd_tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_pa_payment     FOREIGN KEY (payment_id)     REFERENCES cd_payments(id) ON DELETE CASCADE,
  CONSTRAINT fk_pa_installment FOREIGN KEY (installment_id) REFERENCES cd_fee_installments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_payment_refunds (
  id         INT(11)      NOT NULL AUTO_INCREMENT,
  public_id  CHAR(36)     NOT NULL,
  tenant_id  INT(11)      NOT NULL,
  payment_id INT(11)      NOT NULL,
  amount     DECIMAL(12,2) NOT NULL,
  reason     TEXT         NULL,
  status     ENUM('pending','approved','processed','rejected') NOT NULL DEFAULT 'pending',
  processed_by INT(11)    NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_refund_public (public_id),
  KEY idx_refund_payment      (payment_id),
  CONSTRAINT fk_refund_tenant  FOREIGN KEY (tenant_id)  REFERENCES cd_tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_refund_payment FOREIGN KEY (payment_id) REFERENCES cd_payments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_fee_adjustments (
  id             INT(11)      NOT NULL AUTO_INCREMENT,
  public_id      CHAR(36)     NOT NULL,
  tenant_id      INT(11)      NOT NULL,
  fee_account_id INT(11)      NOT NULL,
  amount         DECIMAL(12,2) NOT NULL,
  adjustment_type ENUM('discount','scholarship','waiver','penalty','correction') NOT NULL,
  reason         TEXT         NULL,
  created_by     INT(11)      NULL,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_adjust_public (public_id),
  KEY idx_adjust_account      (fee_account_id),
  CONSTRAINT fk_adjust_tenant  FOREIGN KEY (tenant_id)      REFERENCES cd_tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_adjust_account FOREIGN KEY (fee_account_id) REFERENCES cd_student_fee_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================================
-- 7. EXAMINATIONS
-- =====================================================================

CREATE TABLE IF NOT EXISTS cd_exams (
  id         INT(11)      NOT NULL AUTO_INCREMENT,
  public_id  CHAR(36)     NOT NULL,
  tenant_id  INT(11)      NOT NULL,
  branch_id  INT(11)      NOT NULL,
  batch_id   INT(11)      NOT NULL,
  exam_name  VARCHAR(150) NOT NULL,
  exam_date  DATE         NOT NULL,
  status     ENUM('scheduled','ongoing','completed','published') NOT NULL DEFAULT 'scheduled',
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_exams_public (public_id),
  KEY idx_exams_tenant       (tenant_id),
  CONSTRAINT fk_exams_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_exams_batch  FOREIGN KEY (batch_id)  REFERENCES cd_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_exam_subjects (
  id             INT(11) NOT NULL AUTO_INCREMENT,
  tenant_id      INT(11) NOT NULL,
  exam_id        INT(11) NOT NULL,
  subject_id     INT(11) NOT NULL,
  maximum_marks  INT(11) NOT NULL DEFAULT 100,
  passing_marks  INT(11) NOT NULL DEFAULT 33,
  PRIMARY KEY (id),
  UNIQUE KEY uq_exam_subject (exam_id, subject_id),
  CONSTRAINT fk_es_tenant  FOREIGN KEY (tenant_id)  REFERENCES cd_tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_es_exam    FOREIGN KEY (exam_id)    REFERENCES cd_exams(id)    ON DELETE CASCADE,
  CONSTRAINT fk_es_subject FOREIGN KEY (subject_id) REFERENCES cd_subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_exam_results (
  id         INT(11)      NOT NULL AUTO_INCREMENT,
  public_id  CHAR(36)     NOT NULL,
  tenant_id  INT(11)      NOT NULL,
  exam_id    INT(11)      NOT NULL,
  student_id INT(11)      NOT NULL,
  total_marks DECIMAL(8,2) NOT NULL DEFAULT 0,
  percentage DECIMAL(5,2) NOT NULL DEFAULT 0,
  grade      VARCHAR(10)  NULL,
  rank_no    INT(11)      NULL,
  status     ENUM('draft','published') NOT NULL DEFAULT 'draft',
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_results_public (public_id),
  UNIQUE KEY uq_results_exam_student (exam_id, student_id),
  KEY idx_results_student      (student_id),
  CONSTRAINT fk_res_tenant  FOREIGN KEY (tenant_id)  REFERENCES cd_tenants(id)  ON DELETE CASCADE,
  CONSTRAINT fk_res_exam    FOREIGN KEY (exam_id)    REFERENCES cd_exams(id)    ON DELETE CASCADE,
  CONSTRAINT fk_res_student FOREIGN KEY (student_id) REFERENCES cd_students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_exam_result_details (
  id              INT(11) NOT NULL AUTO_INCREMENT,
  result_id       INT(11) NOT NULL,
  subject_id      INT(11) NOT NULL,
  obtained_marks  DECIMAL(8,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_result_subject (result_id, subject_id),
  CONSTRAINT fk_erd_result  FOREIGN KEY (result_id)  REFERENCES cd_exam_results(id) ON DELETE CASCADE,
  CONSTRAINT fk_erd_subject FOREIGN KEY (subject_id) REFERENCES cd_subjects(id)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================================
-- 8. TIMETABLE
-- =====================================================================

CREATE TABLE IF NOT EXISTS cd_timetable (
  id          INT(11) NOT NULL AUTO_INCREMENT,
  public_id   CHAR(36) NOT NULL,
  tenant_id   INT(11) NOT NULL,
  branch_id   INT(11) NOT NULL,
  batch_id    INT(11) NOT NULL,
  subject_id  INT(11) NULL,
  teacher_id  INT(11) NULL,
  classroom_id INT(11) NULL,
  day_of_week TINYINT NOT NULL,  -- 0=Sun..6=Sat
  start_time  TIME    NOT NULL,
  end_time    TIME    NOT NULL,
  status      ENUM('active','cancelled') NOT NULL DEFAULT 'active',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tt_public (public_id),
  KEY idx_tt_tenant       (tenant_id),
  KEY idx_tt_teacher      (teacher_id, day_of_week, start_time),
  KEY idx_tt_room         (classroom_id, day_of_week, start_time),
  CONSTRAINT fk_tt_tenant  FOREIGN KEY (tenant_id)  REFERENCES cd_tenants(id)  ON DELETE CASCADE,
  CONSTRAINT fk_tt_batch   FOREIGN KEY (batch_id)   REFERENCES cd_batches(id)  ON DELETE CASCADE,
  CONSTRAINT fk_tt_teacher FOREIGN KEY (teacher_id) REFERENCES cd_teachers(id) ON DELETE SET NULL,
  CONSTRAINT fk_tt_room    FOREIGN KEY (classroom_id) REFERENCES cd_classrooms(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================================
-- 9. HOMEWORK / STUDY MATERIAL
-- =====================================================================

CREATE TABLE IF NOT EXISTS cd_homework (
  id          INT(11)      NOT NULL AUTO_INCREMENT,
  public_id   CHAR(36)     NOT NULL,
  tenant_id   INT(11)      NOT NULL,
  batch_id    INT(11)      NOT NULL,
  subject_id  INT(11)      NULL,
  title       VARCHAR(200) NOT NULL,
  description TEXT         NULL,
  due_date    DATE         NULL,
  created_by  INT(11)      NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_hw_public (public_id),
  KEY idx_hw_batch        (batch_id),
  CONSTRAINT fk_hw_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_hw_batch  FOREIGN KEY (batch_id)  REFERENCES cd_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_homework_submissions (
  id           INT(11)      NOT NULL AUTO_INCREMENT,
  public_id    CHAR(36)     NOT NULL,
  tenant_id    INT(11)      NOT NULL,
  homework_id  INT(11)      NOT NULL,
  student_id   INT(11)      NOT NULL,
  file_path    VARCHAR(255) NULL,
  submitted_at DATETIME     NULL,
  marks        DECIMAL(5,2) NULL,
  feedback     TEXT         NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_hwsub_public (public_id),
  UNIQUE KEY uq_hwsub_hw_student (homework_id, student_id),
  CONSTRAINT fk_hwsub_tenant  FOREIGN KEY (tenant_id)  REFERENCES cd_tenants(id)  ON DELETE CASCADE,
  CONSTRAINT fk_hwsub_hw      FOREIGN KEY (homework_id) REFERENCES cd_homework(id) ON DELETE CASCADE,
  CONSTRAINT fk_hwsub_student FOREIGN KEY (student_id) REFERENCES cd_students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_study_materials (
  id         INT(11)      NOT NULL AUTO_INCREMENT,
  public_id  CHAR(36)     NOT NULL,
  tenant_id  INT(11)      NOT NULL,
  batch_id   INT(11)      NULL,
  subject_id INT(11)      NULL,
  title      VARCHAR(200) NOT NULL,
  file_path  VARCHAR(255) NULL,
  video_url  VARCHAR(500) NULL,
  created_by INT(11)      NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mat_public (public_id),
  KEY idx_mat_tenant       (tenant_id),
  CONSTRAINT fk_mat_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================================
-- 10. COMMUNICATION
-- =====================================================================

CREATE TABLE IF NOT EXISTS cd_announcements (
  id          INT(11)      NOT NULL AUTO_INCREMENT,
  public_id   CHAR(36)     NOT NULL,
  tenant_id   INT(11)      NOT NULL,
  title       VARCHAR(200) NOT NULL,
  description TEXT         NULL,
  target_type ENUM('all','branch','batch','role') NOT NULL DEFAULT 'all',
  target_id   INT(11)      NULL,
  status      ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  created_by  INT(11)      NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ann_public (public_id),
  KEY idx_ann_tenant       (tenant_id),
  CONSTRAINT fk_ann_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_notifications (
  id         INT(11)      NOT NULL AUTO_INCREMENT,
  public_id  CHAR(36)     NOT NULL,
  tenant_id  INT(11)      NOT NULL,
  user_id    INT(11)      NOT NULL,
  title      VARCHAR(200) NOT NULL,
  message    TEXT         NULL,
  read_at    DATETIME     NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_notif_public (public_id),
  KEY idx_notif_user         (user_id, read_at),
  CONSTRAINT fk_notif_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_notif_user   FOREIGN KEY (user_id)   REFERENCES cd_users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_notification_templates (
  id           INT(11)      NOT NULL AUTO_INCREMENT,
  tenant_id    INT(11)      NOT NULL,
  channel      ENUM('inapp','email','sms','whatsapp') NOT NULL,
  template_key VARCHAR(80)  NOT NULL,
  subject      VARCHAR(200) NULL,
  content      TEXT         NOT NULL,
  is_active    TINYINT(1)   NOT NULL DEFAULT 1,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_nottpl_tenant_key (tenant_id, template_key, channel),
  CONSTRAINT fk_nottpl_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_notification_logs (
  id         INT(11)      NOT NULL AUTO_INCREMENT,
  tenant_id  INT(11)      NOT NULL,
  channel    ENUM('inapp','email','sms','whatsapp') NOT NULL,
  recipient  VARCHAR(150) NOT NULL,
  status     ENUM('queued','sent','failed','delivered') NOT NULL DEFAULT 'queued',
  response   TEXT         NULL,
  sent_at    DATETIME     NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notlog_tenant (tenant_id),
  CONSTRAINT fk_notlog_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================================
-- 11. EXPENSES / SUPPORT / SYSTEM
-- =====================================================================

CREATE TABLE IF NOT EXISTS cd_expenses (
  id           INT(11)      NOT NULL AUTO_INCREMENT,
  public_id    CHAR(36)     NOT NULL,
  tenant_id    INT(11)      NOT NULL,
  branch_id    INT(11)      NULL,
  category     VARCHAR(80)  NOT NULL,
  amount       DECIMAL(12,2) NOT NULL,
  expense_date DATE         NOT NULL,
  notes        TEXT         NULL,
  created_by   INT(11)      NULL,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_exp_public (public_id),
  KEY idx_exp_tenant       (tenant_id, expense_date),
  CONSTRAINT fk_exp_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_support_tickets (
  id         INT(11)      NOT NULL AUTO_INCREMENT,
  public_id  CHAR(36)     NOT NULL,
  tenant_id  INT(11)      NOT NULL,
  user_id    INT(11)      NOT NULL,
  subject    VARCHAR(200) NOT NULL,
  description TEXT        NULL,
  priority   ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  status     ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ticket_public (public_id),
  KEY idx_ticket_tenant       (tenant_id),
  CONSTRAINT fk_ticket_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_file_uploads (
  id         INT(11)      NOT NULL AUTO_INCREMENT,
  public_id  CHAR(36)     NOT NULL,
  tenant_id  INT(11)      NOT NULL,
  uploaded_by INT(11)     NULL,
  file_path  VARCHAR(255) NOT NULL,
  mime_type  VARCHAR(80)  NULL,
  file_size  INT(11)      NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_file_public (public_id),
  KEY idx_file_tenant       (tenant_id),
  CONSTRAINT fk_file_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_settings (
  id            INT(11)      NOT NULL AUTO_INCREMENT,
  tenant_id     INT(11)      NOT NULL,
  setting_key   VARCHAR(100) NOT NULL,
  setting_value JSON         NULL,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_settings_tenant_key (tenant_id, setting_key),
  CONSTRAINT fk_settings_tenant FOREIGN KEY (tenant_id) REFERENCES cd_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_platform_settings (
  id            INT(11)      NOT NULL AUTO_INCREMENT,
  setting_key   VARCHAR(100) NOT NULL,
  setting_value JSON         NULL,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_platform_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS cd_activity_logs (
  id          INT(11)      NOT NULL AUTO_INCREMENT,
  tenant_id   INT(11)      NULL,
  user_id     INT(11)      NULL,
  action      VARCHAR(80)  NOT NULL,
  module      VARCHAR(50)  NOT NULL,
  record_type VARCHAR(50)  NULL,
  record_id   INT(11)      NULL,
  metadata    JSON         NULL,
  ip_address  VARCHAR(45)  NULL,
  user_agent  VARCHAR(255) NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_logs_tenant_time (tenant_id, created_at),
  KEY idx_logs_user        (user_id),
  KEY idx_logs_module      (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED: system roles + starter subscription plans
-- =====================================================================

INSERT INTO cd_roles (public_id, tenant_id, name, slug, is_system) VALUES
  (UUID(), NULL, 'SaaS Super Admin', 'super_admin',   1),
  (UUID(), NULL, 'Institute Owner',  'owner',         1),
  (UUID(), NULL, 'Branch Admin',     'branch_admin',  1),
  (UUID(), NULL, 'Accountant',       'accountant',    1),
  (UUID(), NULL, 'Teacher',          'teacher',       1),
  (UUID(), NULL, 'Receptionist',     'receptionist',  1),
  (UUID(), NULL, 'Student',          'student',       1),
  (UUID(), NULL, 'Parent',           'parent',        1);

INSERT INTO cd_subscription_plans
  (public_id, name, price, billing_cycle, max_students, max_staff, max_branches, features_json, sort_order)
VALUES
  (UUID(), 'Starter',      499.00, 'monthly', 100,   3,  1, JSON_OBJECT('exams','basic','portal',0),  1),
  (UUID(), 'Basic',        999.00, 'monthly', 300,  10,  1, JSON_OBJECT('exams','basic','portal',1),  2),
  (UUID(), 'Professional',1999.00, 'monthly',1000,  30,  3, JSON_OBJECT('exams','advanced','portal',1),3),
  (UUID(), 'Enterprise',    0.00, 'yearly',  99999, 999, 99, JSON_OBJECT('exams','advanced','portal',1,'white_label',1), 4);