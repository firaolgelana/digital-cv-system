# SRS UI/UX Execution Checklist

## 1. Foundation and Design System

- [x] Replace old colorful/neon styling with neutral enterprise theme.
- [x] Use one subtle accent color only.
- [x] Standardize typography, spacing (8px system), borders, and corner radius (6-10px).
- [x] Remove gradient-heavy and dashboard-heavy visual patterns.

## 2. Student Flow (No Dashboard)

- [x] Build post-login student home with one primary CTA (Create/Continue CV).
- [x] Display simple CV status badge and recent activity block.
- [x] Build step-by-step CV wizard screen with 7 steps.
- [x] Include progress bar and auto-save status text.
- [x] Build clean A4 print-style CV preview with Edit/Download/Submit actions.
- [x] Build QR page with centered QR card and actions (Download, Copy link, Share).

## 3. Supervisor Flow (No Dashboard)

- [x] Build review queue page with list cards (student, department, date, status).
- [x] Add Review action per submission.
- [x] Build CV review layout with split screen (left preview, right action panel).
- [x] Include approve/reject/request changes with comments.
- [x] Keep decision panel sticky for usability.

## 4. Examiner Flow

- [x] Build CV library screen with filters (department, year, skills).
- [x] Use card/grid CV listings.
- [x] Include evaluation notes section.

## 5. Recruiter Flow (QR-first)

- [x] Build minimal scanner page with large scan area.
- [x] Build post-scan CV view page with profile, education, experience, skills, and download button.

## 6. Admin / Manager Flow (Minimal)

- [x] Build system overview with summary cards (users, CVs, approved, scans).
- [x] Add search bar for users/CVs.
- [x] Add activity log style list.

## 7. Responsiveness and Interaction

- [x] Ensure mobile responsive layouts across all pages.
- [x] Use subtle interaction transitions only.
- [x] Keep interactions predictable and low-clutter.

## 8. Routing Alignment (Frontend)

- [x] Route examiner role to dedicated examiner UI page.
- [x] Route supervisor/recruiter/admin/student to role-specific frontend pages.
