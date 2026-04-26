-- Create & select the database
CREATE DATABASE IF NOT EXISTS digital_cv_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE digital_cv_db;

CREATE TABLE IF NOT EXISTS roles (
    id         TINYINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name       VARCHAR(50)       NOT NULL UNIQUE,  -- student | supervisor | examiner | recruiter | admin
    created_at TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default roles
INSERT IGNORE INTO roles (name) VALUES
    ('student'),
    ('supervisor'),
    ('examiner'),
    ('recruiter'),
    ('admin');

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    role_id       TINYINT UNSIGNED NOT NULL,
    full_name     VARCHAR(150)     NOT NULL,
    email         VARCHAR(191)     NOT NULL UNIQUE,
    password_hash VARCHAR(255)     NOT NULL,              -- bcrypt hash
    phone         VARCHAR(20)               DEFAULT NULL,
    profile_photo VARCHAR(255)              DEFAULT NULL, -- relative path to uploads/
    is_active     TINYINT(1)       NOT NULL DEFAULT 1,
    created_at    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS departments (
    id         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(150)      NOT NULL UNIQUE,
    created_at TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS students (
    id              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED      NOT NULL UNIQUE,
    department_id   SMALLINT UNSIGNED          DEFAULT NULL,
    student_number  VARCHAR(50)       NOT NULL UNIQUE,
    graduation_year YEAR                       DEFAULT NULL,
    supervisor_id   INT UNSIGNED               DEFAULT NULL,  -- FK → users.id (supervisor)
    PRIMARY KEY (id),
    CONSTRAINT fk_students_user       FOREIGN KEY (user_id)       REFERENCES users(id)       ON DELETE CASCADE,
    CONSTRAINT fk_students_dept       FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    CONSTRAINT fk_students_supervisor FOREIGN KEY (supervisor_id) REFERENCES users(id)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cvs (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    student_id   INT UNSIGNED  NOT NULL,              -- FK → students.id
    title        VARCHAR(200)  NOT NULL DEFAULT 'My CV',
    summary      TEXT                   DEFAULT NULL,
    status       ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'draft',
    submitted_at TIMESTAMP              DEFAULT NULL,
    reviewed_at  TIMESTAMP              DEFAULT NULL,
    reviewer_id  INT UNSIGNED           DEFAULT NULL,  -- supervisor or examiner who acted
    review_note  TEXT                   DEFAULT NULL,
    created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_cvs_student  FOREIGN KEY (student_id)  REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_cvs_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE IF NOT EXISTS cv_education (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cv_id        INT UNSIGNED NOT NULL,
    institution  VARCHAR(200) NOT NULL,
    degree       VARCHAR(150)          DEFAULT NULL,
    field        VARCHAR(150)          DEFAULT NULL,
    start_year   YEAR                  DEFAULT NULL,
    end_year     YEAR                  DEFAULT NULL,
    grade        VARCHAR(50)           DEFAULT NULL,  -- e.g. "3.9 GPA" or "Distinction"
    description  TEXT                  DEFAULT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_edu_cv FOREIGN KEY (cv_id) REFERENCES cvs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE IF NOT EXISTS cv_experience (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cv_id        INT UNSIGNED NOT NULL,
    company      VARCHAR(200) NOT NULL,
    position     VARCHAR(150) NOT NULL,
    location     VARCHAR(150)          DEFAULT NULL,
    start_date   DATE                  DEFAULT NULL,
    end_date     DATE                  DEFAULT NULL,  -- NULL means "present"
    is_current   TINYINT(1)   NOT NULL DEFAULT 0,
    description  TEXT                  DEFAULT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_exp_cv FOREIGN KEY (cv_id) REFERENCES cvs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS cv_skills (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cv_id      INT UNSIGNED NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    level      ENUM('beginner','intermediate','advanced','expert') DEFAULT 'intermediate',
    PRIMARY KEY (id),
    CONSTRAINT fk_skills_cv FOREIGN KEY (cv_id) REFERENCES cvs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS cv_documents (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cv_id         INT UNSIGNED NOT NULL,
    doc_type      VARCHAR(80)  NOT NULL DEFAULT 'other',  -- 'certificate', 'portfolio', 'transcript', etc.
    original_name VARCHAR(255) NOT NULL,
    stored_path   VARCHAR(255) NOT NULL,                  -- relative path inside uploads/
    mime_type     VARCHAR(100)          DEFAULT NULL,
    file_size_kb  INT UNSIGNED          DEFAULT NULL,
    uploaded_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_docs_cv FOREIGN KEY (cv_id) REFERENCES cvs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS qr_codes (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cv_id        INT UNSIGNED NOT NULL UNIQUE,            -- one QR per approved CV
    token        VARCHAR(64)  NOT NULL UNIQUE,            -- secure random token in URL
    qr_image     VARCHAR(255)          DEFAULT NULL,      -- path to stored QR image file
    access_count INT UNSIGNED NOT NULL DEFAULT 0,
    expires_at   DATETIME              DEFAULT NULL,      -- optional expiry
    generated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_qr_cv FOREIGN KEY (cv_id) REFERENCES cvs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS qr_access_logs (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    qr_id       INT UNSIGNED NOT NULL,
    accessed_by INT UNSIGNED          DEFAULT NULL,       -- FK → users.id (recruiter), NULL = public
    ip_address  VARCHAR(45)           DEFAULT NULL,
    user_agent  VARCHAR(255)          DEFAULT NULL,
    accessed_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_log_qr   FOREIGN KEY (qr_id)        REFERENCES qr_codes(id) ON DELETE CASCADE,
    CONSTRAINT fk_log_user FOREIGN KEY (accessed_by)  REFERENCES users(id)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS notifications (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    title      VARCHAR(200) NOT NULL,
    message    TEXT         NOT NULL,
    is_read    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS password_resets (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    token      VARCHAR(64)  NOT NULL UNIQUE,
    expires_at DATETIME     NOT NULL,
    used       TINYINT(1)   NOT NULL DEFAULT 0,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
