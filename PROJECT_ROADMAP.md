# Project Roadmap: SIWES Intern Tracking System using GPS Technology

> This document tracks what the project **is** (per the thesis), what the codebase **currently has**, and the **phased plan** to align the two. Updated September 2026.

---

## 1. What the Project Is (from the thesis)

**Title:** Development of SIWES Intern Tracking using GPS Technology System

**Author:** Asuku Onouroiza Jovita (FT22BCMP0861)

**Institution:** Nasarawa State University, Keffi — BSc. Computer Science

**Core idea:** A system that uses GPS and geofencing to automatically verify whether a SIWES intern is physically present at their host organization during working hours. The novel contribution is **location-based attendance verification** — not just an e-logbook.

**Architecture (thesis Section 3.3.3):** Three-tier
- **Presentation layer:** Mobile app for interns (thesis says Flutter) + web dashboard for supervisors/admins (thesis says React)
- **Application layer:** REST API with a Geofencing and Distance Engine (Haversine formula)
- **Data layer:** MySQL relational database

**Users (thesis Section 3.3.3):**
1. Interns (students) — use the mobile/web app to check in and submit log entries
2. Industry-based supervisors — at the host organization
3. School-based supervisors / SIWES coordinators — at the institution
4. System administrator

**Core module:** Location and Geofencing Engine

**Supporting modules:** Mobile GPS Capture, Attendance and E-logbook, Alert and Notification, Organization and Geofence Management, Reporting, Admin Panel

---

## 2. What the Codebase Currently Has

| Feature | Status | Notes |
|---|---|---|
| User registration and login | Done | Student, supervisor, admin/coordinator roles |
| Daily logbook entries with activity descriptions | Done | `log_entries` table with `activity`, `date`, GPS coordinates |
| Supervisor approval/rejection of log entries | Done | `status` column (pending/approved/rejected) |
| Weekly summaries | Done | `weekly_summaries` table |
| Monthly summaries | Done | `monthly_summaries` table |
| Evaluations | Done (schema) | `evaluations` table, model exists |
| Notifications (basic) | Done | `notifications` table, model exists |
| Admin user management | Done | Admin pages for students/supervisors |
| GPS coordinate capture on log entries | Partial | Coordinates stored but never checked against a geofence |
| Workplace location stored per student | Partial | Lat/long/address on `users` row, no geofence radius |

**Tech stack (actual):** Plain PHP, MySQL, Bootstrap 5, HTML5 Geolocation, PHP sessions. No framework, no mobile app, no React.

**Database (actual):** 6 tables — `users`, `log_entries`, `weekly_summaries`, `monthly_summaries`, `evaluations`, `notifications`

---

## 3. Implementation Phases

### PHASE 1: Database Foundation & Geofence Engine
> **Goal:** Create the missing database tables and the core geofencing logic.
> **Status:** Not started

**Tasks:**
- [ ] 1.1 Create `organizations` table (org_name, address, geo_latitude, geo_longitude, geofence_radius_m)
- [ ] 1.2 Add `organization_id` column to `users` table (links students to their org)
- [ ] 1.3 Add `supervisor_type` column to `users` table (ENUM: industry, school)
- [ ] 1.4 Migrate existing workplace data from `users` to `organizations`
- [ ] 1.5 Create `location_logs` table (intern_id, latitude, longitude, captured_at, in_geofence)
- [ ] 1.6 Create `attendance` table (intern_id, date, check_in_time, check_out_time, status)
- [ ] 1.7 Add `geofence_breach` to `notifications` type enum
- [ ] 1.8 Create `backend/models/Geofence.php` — Haversine distance + isWithinGeofence
- [ ] 1.9 Create `backend/models/Organization.php` — CRUD for organizations
- [ ] 1.10 Create `backend/models/LocationLog.php` — log GPS pings with geofence result
- [ ] 1.11 Create `backend/models/Attendance.php` — check-in/check-out logic
- [ ] 1.12 Update `db.php` to create all new tables on first run
- [ ] 1.13 Add test organization with geofence coordinates

**Deliverable:** All database tables exist, all models work, geofence engine can calculate distance and determine inside/outside.

---

### PHASE 2: Admin Organization Management
> **Goal:** Admin can create/edit organizations and set geofence boundaries on a map.
> **Status:** Not started

**Tasks:**
- [ ] 2.1 Create `admin/organizations.php` — list all organizations
- [ ] 2.2 Create `admin/organization-add.php` — add new organization with map picker
- [ ] 2.3 Create `admin/organization-edit.php` — edit organization geofence
- [ ] 2.4 Use Leaflet.js map for picking geofence center + radius
- [ ] 2.5 Add "Organizations" link to admin sidebar
- [ ] 2.6 Assign students to organizations from admin student management
- [ ] 2.7 Set supervisor type (industry/school) from admin supervisor management

**Deliverable:** Admin can manage organizations, set geofence boundaries visually on a map, and assign students/supervisors to organizations.

---

### PHASE 3: Student Check-in/Check-out & Attendance
> **Goal:** Students can check in with GPS verification and the system records attendance.
> **Status:** Not started

**Tasks:**
- [ ] 3.1 Create `student/attendance.php` — check-in/check-out page
- [ ] 3.2 Capture GPS coordinates on check-in using HTML5 Geolocation
- [ ] 3.3 Run geofence check on check-in (call Geofence::isWithinGeofence)
- [ ] 3.4 Record attendance: Present (inside geofence), Outside Zone (outside geofence)
- [ ] 3.5 Log every GPS submission to `location_logs` with `in_geofence` result
- [ ] 3.6 Implement check-out logic
- [ ] 3.7 Show student their current attendance status
- [ ] 3.8 Show student their attendance history (calendar/list view)
- [ ] 3.9 Add "Attendance" link to student sidebar
- [ ] 3.10 Wire geofence check into existing log entry submission too

**Deliverable:** Students can check in/out with GPS, system verifies they're inside the geofence, attendance is recorded, location is logged.

---

### PHASE 4: Geofence Breach Alerts
> **Goal:** Supervisors get notified when a student leaves the geofence during working hours.
> **Status:** Not started

**Tasks:**
- [ ] 4.1 Define working hours (e.g., 8:00 AM - 5:00 PM, configurable)
- [ ] 4.2 When geofence check returns "outside" during working hours, create notification for supervisor
- [ ] 4.3 Add `geofence_breach` notification type with distinctive styling
- [ ] 4.4 Show breach alerts prominently on supervisor dashboard
- [ ] 4.5 Show breach alerts on admin dashboard
- [ ] 4.6 Add breach alert count to header notification badge

**Deliverable:** When a student is outside the geofence during work hours, their supervisor gets an alert automatically.

---

### PHASE 5: Supervisor Map Dashboard & Monitoring
> **Goal:** Supervisors can see their students' locations on a map in real time.
> **Status:** Not started

**Tasks:**
- [ ] 5.1 Add map view to supervisor dashboard using Leaflet.js + OpenStreetMap
- [ ] 5.2 Show each assigned student's latest location as a marker
- [ ] 5.3 Show the organization geofence as a circle on the map
- [ ] 5.4 Color-code markers: green (inside geofence), red (outside)
- [ ] 5.5 Add student location history view (trail of GPS pings for a date)
- [ ] 5.6 Add attendance summary view (which students checked in today)
- [ ] 5.7 Add breach alert feed to supervisor dashboard
- [ ] 5.8 Auto-refresh map every 30 seconds

**Deliverable:** Supervisor dashboard shows a live map with student locations, geofence circles, and breach alerts.

---

### PHASE 6: Reporting & Analytics
> **Goal:** Supervisors and admins can generate attendance and movement reports.
> **Status:** Not started

**Tasks:**
- [ ] 6.1 Create `supervisor/reports.php` — attendance report for assigned students
- [ ] 6.2 Create `admin/reports.php` — system-wide attendance report
- [ ] 6.3 Daily attendance summary (present/absent/outside zone counts)
- [ ] 6.4 Weekly attendance summary per student
- [ ] 6.5 Movement report (location history for a date range)
- [ ] 6.6 Export reports as CSV/PDF
- [ ] 6.7 Add date range filter to all reports

**Deliverable:** Supervisors and admins can generate and export attendance and movement reports.

---

## 4. Database Schema: Current vs Target

### Current tables
- `users` (id, name, username, full_name, email, password, role, matric_number, student_id, department, institution, siwes_start_date, siwes_end_date, workplace_name, workplace_latitude, workplace_longitude, workplace_address, phone, level, is_active, supervisor_id, reference_id, created_at, updated_at)
- `log_entries` (id, student_id, activity, date, latitude, longitude, location_address, status, accuracy, altitude, heading, speed, file_upload, supervisor_comment, created_at, updated_at)
- `weekly_summaries` (id, student_id, week_number, summary_of_work, problems_encountered, suggestions_for_improvement, status, supervisor_id, supervisor_comment, created_at, updated_at)
- `monthly_summaries` (id, student_id, month_number, learning_outcomes, innovations_initiatives, general_reflections, status, supervisor_id, supervisor_comment, created_at, updated_at)
- `evaluations` (id, student_id, supervisor_id, evaluation_type, period_reference, punctuality_rating, technical_skill_rating, communication_rating, attitude_rating, final_recommendation, digital_signature, comments, created_at, updated_at)
- `notifications` (id, user_id, title, message, type, is_read, created_at)

### Tables to add (Phase 1)
- `organizations` (organization_id, org_name, address, geo_latitude, geo_longitude, geofence_radius_m, created_at)
- `attendance` (attendance_id, intern_id, date, check_in_time, check_out_time, status, created_at)
- `location_logs` (log_id, intern_id, latitude, longitude, captured_at, in_geofence)

### Columns to add to `users` (Phase 1)
- `organization_id` (INT, foreign key to organizations) — for students
- `supervisor_type` (ENUM: `industry`, `school`) — for supervisors

### Notification type enum update (Phase 1)
- Add `geofence_breach` to the existing `type` ENUM on `notifications`

---

## 5. Phase Summary

| Phase | Name | Key Deliverable | Depends on |
|---|---|---|---|
| 1 | Database Foundation & Geofence Engine | All tables + models + Haversine engine | Nothing |
| 2 | Admin Organization Management | Admin can manage orgs + geofences on a map | Phase 1 |
| 3 | Student Check-in/Check-out & Attendance | Students check in with GPS verification | Phase 1, 2 |
| 4 | Geofence Breach Alerts | Supervisors get auto-alerts on breach | Phase 1, 3 |
| 5 | Supervisor Map Dashboard & Monitoring | Live map with student locations + geofences | Phase 1, 2, 3, 4 |
| 6 | Reporting & Analytics | Attendance and movement reports, exportable | Phase 1, 3, 5 |

---

## 6. What We Are NOT Building (out of scope per thesis Section 1.5)

- A native mobile app (Flutter) — we use a responsive web app instead
- Dedicated GPS hardware devices
- Integration with the national ITF SIWES portal
- Biometric or facial-recognition verification
- Payment and stipend processing

---

## 7. Test Credentials (current database)

| Role | Email | Matric Number | Password |
|---|---|---|---|
| Student | student@test.com | 2021/123456 | 12345678 |
| Supervisor | supervisor@test.com | — | 12345678 |
| Admin | admin@test.com | — | 12345678 |

> Student login uses matric number. Supervisor and admin login use email.

---

## 8. Development Environment

- **PHP:** 8.3.30
- **MySQL:** via Laragon (localhost:3306)
- **Database:** `siwes_db` (auto-created by `backend/config/db.php` on first run)
- **No framework:** plain PHP with PDO
- **No Composer dependencies yet**
- **Map library:** Leaflet.js + OpenStreetMap (planned, free, no API key)
- **Server:** `php -S localhost:8080 -t .`
