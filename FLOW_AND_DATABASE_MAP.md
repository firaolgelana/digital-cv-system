# Frontend Flow and Database Mapping (SRS Aligned)

## A. End-to-End Workflow

1. Student registers and logs in.
2. Student builds CV in 7-step wizard.
3. Student submits CV for review.
4. Supervisor opens review queue and approves/rejects/requests changes.
5. On approval, QR code is generated for that CV.
6. Student opens QR page and shares QR/link.
7. Recruiter scans QR and views secure CV profile.
8. Examiner can evaluate approved CV quality.
9. Admin monitors users, CV states, and QR scan activity.

## B. Screen to Table Mapping

### 1) Authentication Screens

- Pages: index.html, register.html
- Tables: users, roles, students, departments

### 2) Student Home + CV Builder

- Pages: student-dashboard.php, create-cv.html
- Tables: cvs, cv_education, cv_experience, cv_skills, cv_documents

### 3) CV Preview

- Page: cv-preview.html
- Tables: cvs + child CV section tables

### 4) Supervisor Queue + Review

- Page: supervisor-dashboard.html
- Tables: cvs, students, users, cv_documents
- Supervisor actions update: cvs.status, cvs.review_note, cvs.reviewed_at, cvs.reviewer_id

### 5) QR Code Management

- Page: qr-code.html
- Tables: qr_codes, qr_access_logs

### 6) Recruiter Scan + CV View

- Page: recruiter-view.html
- Tables: qr_codes, qr_access_logs, cvs, cv_education, cv_experience, cv_skills

### 7) Examiner Library

- Page: examiner-dashboard.html
- Tables: cvs, students, departments, users

### 8) Admin Overview

- Page: admin-dashboard.html
- Tables: users, roles, cvs, qr_codes, qr_access_logs, notifications

## C. Suggested API Contract (for later backend)

- POST /auth/login
- POST /auth/register
- GET /student/cv/current
- PUT /student/cv/step/:stepId
- POST /student/cv/submit
- GET /supervisor/submissions
- POST /supervisor/cv/:id/approve
- POST /supervisor/cv/:id/reject
- POST /supervisor/cv/:id/request-changes
- GET /cv/:token (QR public view)
- GET /admin/overview
- GET /admin/activity

## D. Notes

- Current implementation is frontend-focused and uses static/mock content for workflow demonstration.
- Existing SQL schema already supports these flows with minor backend service wiring.
