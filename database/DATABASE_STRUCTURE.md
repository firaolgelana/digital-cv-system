# DigiCV Database Structure Reference

This document explains the current database structure defined in `database/digital_cv_schema.sql`, including the purpose of each table and field.

## Database

- Name: `digicv_db`
- Charset/Collation: `utf8mb4` / `utf8mb4_unicode_ci`

## Core Entity Relationships

- One `role` has many `users`.
- One `user` can have one `student` profile (for student accounts).
- One `department` can have many `students`.
- One `student` can have many `cvs`.
- One `cv` has many child records: social links, education, experience, projects, skills, certifications, documents, reviews, evaluations.
- One `cv` can have one active QR row in `qr_codes`.
- One `qr_codes` row can have many access logs in `qr_access_logs`.

## Tables And Field Usage

## 1) roles

Purpose: Master list of account roles used for authorization and routing.

| Field       | Type               | Use                                                                            |
| ----------- | ------------------ | ------------------------------------------------------------------------------ |
| id          | INT UNSIGNED PK AI | Unique role identifier.                                                        |
| name        | VARCHAR(30) UNIQUE | Role code used by app logic (student, supervisor, examiner, recruiter, admin). |
| description | VARCHAR(120) NULL  | Human-readable role description.                                               |
| created_at  | TIMESTAMP          | Role creation timestamp.                                                       |

## 2) users

Purpose: Authentication and base profile data for all account types.

| Field         | Type                        | Use                                                 |
| ------------- | --------------------------- | --------------------------------------------------- |
| id            | INT UNSIGNED PK AI          | Unique user identifier.                             |
| role_id       | INT UNSIGNED FK -> roles.id | Links user to a single role.                        |
| full_name     | VARCHAR(120)                | Full name shown across dashboards and review pages. |
| email         | VARCHAR(150) UNIQUE         | Login identity and notification destination.        |
| password_hash | VARCHAR(255)                | Secure password hash, never plain text.             |
| phone         | VARCHAR(30) NULL            | Optional phone/contact number.                      |
| is_active     | TINYINT(1) default 1        | Soft account enable/disable flag for admin control. |
| created_at    | TIMESTAMP                   | Account creation timestamp.                         |
| updated_at    | TIMESTAMP ON UPDATE         | Last profile/auth row update timestamp.             |

## 3) departments

Purpose: Dynamic department catalog created at runtime from user/admin input.

| Field      | Type                | Use                                                 |
| ---------- | ------------------- | --------------------------------------------------- |
| id         | INT UNSIGNED PK AI  | Unique department identifier.                       |
| name       | VARCHAR(150) UNIQUE | Department name used in registration and filtering. |
| created_at | TIMESTAMP           | Department creation timestamp.                      |

Validation triggers:

- `trg_departments_before_insert`: trims `name`, blocks empty value.
- `trg_departments_before_update`: trims `name`, blocks empty value.

## 4) students

Purpose: Student-specific profile extension linked to users.

| Field          | Type                                   | Use                                                 |
| -------------- | -------------------------------------- | --------------------------------------------------- |
| id             | INT UNSIGNED PK AI                     | Internal student profile identifier.                |
| user_id        | INT UNSIGNED UNIQUE FK -> users.id     | Links student profile to account.                   |
| department_id  | INT UNSIGNED NULL FK -> departments.id | Student department (nullable if not assigned).      |
| student_number | VARCHAR(50) UNIQUE                     | Institutional student identifier.                   |
| address        | VARCHAR(180) NULL                      | Optional address/location profile info.             |
| portfolio_link | VARCHAR(255) NULL                      | Optional global portfolio link for student profile. |
| created_at     | TIMESTAMP                              | Student profile creation timestamp.                 |
| updated_at     | TIMESTAMP ON UPDATE                    | Last student profile update timestamp.              |

## 5) cvs

Purpose: Main CV entity per student version/submission.

| Field            | Type                             | Use                                                                    |
| ---------------- | -------------------------------- | ---------------------------------------------------------------------- |
| id               | INT UNSIGNED PK AI               | Unique CV identifier.                                                  |
| student_id       | INT UNSIGNED FK -> students.id   | Owner student profile.                                                 |
| internal_title   | VARCHAR(120)                     | Internal CV label from builder (`inp-cv-title`).                       |
| full_name        | VARCHAR(120) NULL                | Snapshot of name on CV (`inp-name`).                                   |
| contact_email    | VARCHAR(150) NULL                | Snapshot of CV email (`inp-email`).                                    |
| contact_phone    | VARCHAR(30) NULL                 | Snapshot of CV phone (`inp-phone`).                                    |
| location_address | VARCHAR(180) NULL                | Snapshot of CV location/address (`inp-address`).                       |
| target_headline  | VARCHAR(150) NULL                | Headline/role target (`inp-headline`).                                 |
| summary          | TEXT NULL                        | CV summary/objective (`inp-summary`).                                  |
| portfolio_link   | VARCHAR(255) NULL                | Portfolio URL (`inp-portfolio`).                                       |
| status           | ENUM                             | Workflow state: draft, pending, approved, rejected, changes_requested. |
| submitted_at     | DATETIME NULL                    | When student submitted CV for review.                                  |
| review_note      | TEXT NULL                        | Reviewer feedback attached to CV row.                                  |
| reviewed_at      | DATETIME NULL                    | When review decision was made.                                         |
| reviewer_id      | INT UNSIGNED NULL FK -> users.id | User who reviewed this CV (usually supervisor).                        |
| approved_at      | DATETIME NULL                    | Approval timestamp for QR/public release logic.                        |
| created_at       | TIMESTAMP                        | CV record creation timestamp.                                          |
| updated_at       | TIMESTAMP ON UPDATE              | Last CV modification timestamp.                                        |

## 6) cv_social_links

Purpose: Repeating social/professional links section per CV.

| Field         | Type                      | Use                                   |
| ------------- | ------------------------- | ------------------------------------- |
| id            | INT UNSIGNED PK AI        | Unique social link row identifier.    |
| cv_id         | INT UNSIGNED FK -> cvs.id | Parent CV reference.                  |
| platform_name | VARCHAR(60)               | Label such as LinkedIn, GitHub, X.    |
| link_url      | VARCHAR(255)              | URL value for the platform.           |
| sort_order    | INT UNSIGNED default 1    | Preserves user-defined display order. |
| created_at    | TIMESTAMP                 | Link row creation timestamp.          |

## 7) cv_education

Purpose: Repeating education entries per CV.

| Field        | Type                      | Use                                          |
| ------------ | ------------------------- | -------------------------------------------- |
| id           | INT UNSIGNED PK AI        | Unique education row identifier.             |
| cv_id        | INT UNSIGNED FK -> cvs.id | Parent CV reference.                         |
| institution  | VARCHAR(150)              | School/university name.                      |
| degree       | VARCHAR(150)              | Degree/program name.                         |
| start_period | VARCHAR(50) NULL          | Start date string (year or Month YYYY).      |
| end_period   | VARCHAR(50) NULL          | End date string (year, Month YYYY, Present). |
| sort_order   | INT UNSIGNED default 1    | Entry order in CV preview.                   |
| created_at   | TIMESTAMP                 | Education row creation timestamp.            |

## 8) cv_experience

Purpose: Repeating experience/work entries per CV.

| Field        | Type                      | Use                                                |
| ------------ | ------------------------- | -------------------------------------------------- |
| id           | INT UNSIGNED PK AI        | Unique experience row identifier.                  |
| cv_id        | INT UNSIGNED FK -> cvs.id | Parent CV reference.                               |
| role_company | VARCHAR(180)              | Combined role and company text from frontend form. |
| start_period | VARCHAR(50) NULL          | Start period text.                                 |
| end_period   | VARCHAR(50) NULL          | End period text.                                   |
| description  | TEXT NULL                 | Scope, achievements, and impact details.           |
| sort_order   | INT UNSIGNED default 1    | Entry order in CV preview.                         |
| created_at   | TIMESTAMP                 | Experience row creation timestamp.                 |

## 9) cv_projects

Purpose: Repeating project entries per CV.

| Field       | Type                      | Use                                   |
| ----------- | ------------------------- | ------------------------------------- |
| id          | INT UNSIGNED PK AI        | Unique project row identifier.        |
| cv_id       | INT UNSIGNED FK -> cvs.id | Parent CV reference.                  |
| title       | VARCHAR(160)              | Project title/name.                   |
| proof_link  | VARCHAR(255) NULL         | Optional repository/demo/proof URL.   |
| description | TEXT NULL                 | Project summary, stack, and outcomes. |
| sort_order  | INT UNSIGNED default 1    | Project ordering on preview/output.   |
| created_at  | TIMESTAMP                 | Project row creation timestamp.       |

## 10) cv_skills

Purpose: Atomic skills list per CV, searchable by keyword.

| Field      | Type                      | Use                           |
| ---------- | ------------------------- | ----------------------------- |
| id         | INT UNSIGNED PK AI        | Unique skill row identifier.  |
| cv_id      | INT UNSIGNED FK -> cvs.id | Parent CV reference.          |
| skill_name | VARCHAR(120)              | Individual skill value.       |
| sort_order | INT UNSIGNED default 1    | Skill display order.          |
| created_at | TIMESTAMP                 | Skill row creation timestamp. |

Constraint:

- Unique key `(cv_id, skill_name)` prevents duplicate skill values within one CV.

## 11) cv_certifications

Purpose: Repeating certification/link entries per CV.

| Field      | Type                      | Use                                   |
| ---------- | ------------------------- | ------------------------------------- |
| id         | INT UNSIGNED PK AI        | Unique certification row identifier.  |
| cv_id      | INT UNSIGNED FK -> cvs.id | Parent CV reference.                  |
| cert_name  | VARCHAR(180)              | Certification title/name.             |
| cert_link  | VARCHAR(255)              | Verification or document URL.         |
| sort_order | INT UNSIGNED default 1    | Certification display order.          |
| created_at | TIMESTAMP                 | Certification row creation timestamp. |

## 12) cv_documents

Purpose: Generic attached documents related to CV records.

| Field      | Type                                    | Use                                          |
| ---------- | --------------------------------------- | -------------------------------------------- |
| id         | INT UNSIGNED PK AI                      | Unique document row identifier.              |
| cv_id      | INT UNSIGNED FK -> cvs.id               | Parent CV reference.                         |
| doc_type   | ENUM('certificate','portfolio','other') | Category used for filtering and UI grouping. |
| doc_title  | VARCHAR(180)                            | Document label/title for display.            |
| doc_url    | VARCHAR(255)                            | File/public URL path.                        |
| created_at | TIMESTAMP                               | Document row creation timestamp.             |

## 13) cv_reviews

Purpose: Historical supervisor decision log for CV review workflow.

| Field         | Type                                            | Use                                   |
| ------------- | ----------------------------------------------- | ------------------------------------- |
| id            | INT UNSIGNED PK AI                              | Unique review row identifier.         |
| cv_id         | INT UNSIGNED FK -> cvs.id                       | Reviewed CV reference.                |
| supervisor_id | INT UNSIGNED FK -> users.id                     | Supervisor user who performed review. |
| action_taken  | ENUM('approved','rejected','requested_changes') | Review decision value.                |
| review_note   | TEXT NULL                                       | Reviewer comments/feedback content.   |
| reviewed_at   | TIMESTAMP                                       | Timestamp of review action.           |

## 14) cv_evaluations

Purpose: Examiner evaluation notes and optional score for approved CV quality checks.

| Field           | Type                        | Use                                  |
| --------------- | --------------------------- | ------------------------------------ |
| id              | INT UNSIGNED PK AI          | Unique evaluation row identifier.    |
| cv_id           | INT UNSIGNED FK -> cvs.id   | Evaluated CV reference.              |
| examiner_id     | INT UNSIGNED FK -> users.id | Examiner who created the evaluation. |
| evaluation_note | TEXT                        | Evaluation summary and findings.     |
| score           | DECIMAL(5,2) NULL           | Optional score/rating value.         |
| created_at      | TIMESTAMP                   | Evaluation creation timestamp.       |

## 15) qr_codes

Purpose: Maps approved CVs to public access tokens for QR sharing.

| Field        | Type                             | Use                                                 |
| ------------ | -------------------------------- | --------------------------------------------------- |
| id           | INT UNSIGNED PK AI               | Unique QR row identifier.                           |
| cv_id        | INT UNSIGNED UNIQUE FK -> cvs.id | CV mapped to this QR token (one active row per CV). |
| access_token | VARCHAR(120) UNIQUE              | Secure token embedded in QR/public link.            |
| is_active    | TINYINT(1) default 1             | Enables token rotation/deactivation behavior.       |
| generated_at | TIMESTAMP                        | QR generation timestamp.                            |
| expires_at   | DATETIME NULL                    | Optional expiration for temporary access use cases. |

## 16) qr_access_logs

Purpose: Tracks QR scans and access analytics.

| Field             | Type                             | Use                                                         |
| ----------------- | -------------------------------- | ----------------------------------------------------------- |
| id                | BIGINT UNSIGNED PK AI            | Unique scan log identifier.                                 |
| qr_id             | INT UNSIGNED FK -> qr_codes.id   | QR token row that was accessed.                             |
| recruiter_user_id | INT UNSIGNED NULL FK -> users.id | Recruiter account if authenticated; null for public viewer. |
| visitor_ip        | VARCHAR(45) NULL                 | Client IP for analytics/security.                           |
| user_agent        | VARCHAR(255) NULL                | Browser/device metadata.                                    |
| referrer          | VARCHAR(255) NULL                | Referrer URL/source if available.                           |
| scanned_at        | TIMESTAMP                        | Scan/access timestamp.                                      |

## 17) notifications

Purpose: In-app notification feed for user/system events.

| Field      | Type                                                  | Use                                                       |
| ---------- | ----------------------------------------------------- | --------------------------------------------------------- |
| id         | BIGINT UNSIGNED PK AI                                 | Unique notification row identifier.                       |
| user_id    | INT UNSIGNED NULL FK -> users.id                      | Target user; may be null for global/system-level notices. |
| title      | VARCHAR(150)                                          | Short notification heading.                               |
| message    | TEXT                                                  | Full notification body.                                   |
| type       | ENUM('system','review','approval','rejection','info') | Notification category for styling/filtering.              |
| is_read    | TINYINT(1) default 0                                  | Read/unread state.                                        |
| created_at | TIMESTAMP                                             | Notification creation timestamp.                          |

## Seed Data

The schema seeds `roles` with default values. Departments are not pre-seeded and are created dynamically from application flows.
