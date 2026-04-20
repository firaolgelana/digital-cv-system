# Digital CV System with QR Code Integration

A comprehensive web-based platform designed for graduate students to create, manage, and share their CVs using dynamically generated QR codes. The system ensures academic validation through supervisor approvals and provides secure, controlled CV access for recruiters and company HR.

## 🚀 Project Overview

This project was built using **Core/Vanilla PHP, JavaScript, CSS, and HTML**. No external frameworks (like Laravel, React, or Bootstrap) were used, adhering strictly to core web development principles. The project features a clear separation between frontend views and backend processing logic.

## ✨ Features by User Role

### 🎓 1. Graduate Student
* Create, edit, and manage digital CVs.
* Upload supporting documents (certificates, portfolios).
* Submit CVs for supervisor approval.
* Receive an auto-generated **QR Code** upon CV approval.
* Share/download the QR code for job applications.

### 👨‍🏫 2. Project Supervisor
* Review submitted student CVs.
* Approve CVs (triggering QR generation) or reject with feedback/comments.
* Track and monitor student submission statuses.

### 📝 3. Examiner
* View and evaluate the quality of approved CVs.
* Provide academic validation.

### 🏢 4. Company HR / Recruiter
* Scan student QR codes to securely access their digital CVs.
* View detailed education, skills, and experience.
* Download the CV in PDF format.

### ⚙️ 5. System Manager
* Manage user accounts, roles, and permissions.
* Monitor system workflows, CV statuses, and QR code usage.
* Generate analytical reports on system usage and student activity.

## 🛠️ Technology Stack

* **Frontend:** HTML5, CSS3, Vanilla JavaScript (ES6+)
* **Backend:** Core/Vanilla PHP (v7.4 or v8+)
* **Database:** MySQL
* **Architecture:** Custom SSR (Server-Side Rendering) with separated Frontend/Backend directories.

## 📂 Folder Structure

```text
digital-cv-system/
│
├── frontend/                   # User Interface (HTML, CSS, JS, Views)
│   ├── assets/                 # CSS, JS, and image files
│   ├── components/             # Reusable UI parts (Header, Footer, Sidebar)
│   └── pages/                  # Views separated by actor (student, supervisor, etc.)
│
├── backend/                    # Core Logic, API, and Data Processing
│   ├── config/                 # Database configuration and constants
│   ├── core/                   # Auth, validation, and PHP QR Code library
│   ├── process/                # Form handlers & logic (login, submit CV, approve)
│   └── uploads/                # Secure storage for PDFs and images
│
├── database/                   # SQL schemas and dummy data
│   └── digital_cv_schema.sql
│
└── index.php                   # Main entry point (Redirects to frontend)