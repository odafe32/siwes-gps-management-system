# Project Roadmap: SIWES Intern Tracking System using GPS Technology

> This document tracks what the project **is** (per the thesis), what the codebase **currently has**, and what we **need to build** to align the two. Updated September 2026.

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

## 3. What's Missing (the gaps)

### Gap 1: No Organizations table with geofence data
**Thesis (Table 3.2):** `organizations` table with `org_name`, `address`, `geo_latitude`, `geo_longitude`, `geofence_radius_m` (default 100m).

**Code:** Workplace info is stored on each student's `users` row. No separate organizations table. No geofence radius.

**To do:**
- [ ] Create `organizations` table
- [ ] Add `organization_id` foreign key to `users` (for students)
- [ ] Migrate existing workplace data from `users` to `organizations`
- [ ] Add admin page to manage organizations and set geofence boundaries

### Gap 2: No geofencing engine (Haversine distance check)
**Thesis (Section 3.5):** When a student submits GPS coordinates, the system compares them against the organization's geofence boundary using the Haversine formula to determine if they're inside or outside.

**Code:** Nothing. GPS coordinates are stored but never checked.

**To do:**
- [ ] Create `backend/models/Geofence.php` class
- [ ] Implement `haversineDistance($lat1, $lng1, $lat2, $lng2)` — returns distance in meters
- [ ] Implement `isWithinGeofence($userLat, $userLng, $orgLat, $orgLng, $radiusM)` — returns boolean
- [ ] Call the geofence check when a student submits a log entry
- [ ] Call the geofence check when a student checks in

### Gap 3: No Attendance table with check-in/check-out
**Thesis (Table 3.4):** `attendance` table with `intern_id`, `date`, `check_in_time`, `check_out_time`, `status` (Present / Absent / Outside Zone).

**Code:** No attendance tracking at all.

**To do:**
- [ ] Create `attendance` table
- [ ] Create `backend/models/Attendance.php` class
- [ ] Implement check-in logic: when student's GPS is inside geofence, record check-in time
- [ ] Implement check-out logic: when student leaves or day ends, record check-out time
- [ ] Set status: Present (inside geofence during work hours), Absent (no check-in), Outside Zone (left geofence during work hours)
- [ ] Add student attendance view (see own attendance record)
- [ ] Add supervisor attendance view (see assigned students' attendance)

### Gap 4: No Location Logs table with in_geofence flag
**Thesis (Table 3.3):** `location_logs` table with `intern_id`, `latitude`, `longitude`, `captured_at`, `in_geofence` (boolean).

**Code:** `log_entries` stores lat/long but only on manual submission. No `in_geofence` flag. No periodic tracking.

**To do:**
- [ ] Create `location_logs` table
- [ ] Create `backend/models/LocationLog.php` class
- [ ] Log every GPS submission (from log entry or check-in) to `location_logs` with the `in_geofence` result
- [ ] Add supervisor view to see a student's location history

### Gap 5: No geofence breach alerts
**Thesis (Functional Requirements):** "Send real-time alerts to supervisors when an intern leaves the geofenced area during working hours."

**Code:** `notifications` table exists but nothing triggers breach alerts.

**To do:**
- [ ] When geofence check returns "outside zone" during working hours, insert a notification for the student's supervisor
- [ ] Add notification type `geofence_breach` to the enum
- [ ] Show breach alerts prominently on supervisor dashboard

### Gap 6: No distinction between industry-based and school-based supervisors
**Thesis (Table 3.5):** Supervisors have a `role` column: "Industry-based or School-based."

**Code:** Single `supervisor` role, no subtype.

**To do:**
- [ ] Add `supervisor_type` column to `users` (ENUM: `industry`, `school`) for supervisor-role users
- [ ] Industry supervisors linked to an organization; school supervisors linked to an institution
- [ ] Update admin supervisor management page to set supervisor type

### Gap 7: No supervisor dashboard with map view
**Thesis (Use Case, Section 3.6):** School-based supervisors view an intern's real-time location on a map, receive breach alerts, and generate attendance/movement reports.

**Code:** Supervisor pages show pending logs and assigned students. No map, no attendance view, no breach alerts.

**To do:**
- [ ] Add a map view to supervisor dashboard using Leaflet.js + OpenStreetMap (free, no API key)
- [ ] Show each assigned student's latest location as a marker
- [ ] Show the organization geofence as a circle on the map
- [ ] Color-code markers: green (inside geofence), red (outside)
- [ ] Add attendance report view (daily/weekly summary of check-ins)
- [ ] Add movement report view (location history for a date range)

### Gap 8: No student check-in/check-out interface
**Thesis (Section 3.5, Flowchart 3.3):** Students open the app, the app captures GPS, and the system determines check-in/check-out status.

**Code:** Students can only submit log entries. No dedicated check-in/check-out flow.

**To do:**
- [ ] Create `student/attendance.php` page with a check-in button
- [ ] On check-in, capture GPS, run geofence check, record attendance
- [ ] Show current status (Checked In / Outside Zone / Not Checked In)
- [ ] Add check-out button at end of day

---

## 4. Database Schema: Current vs Target

### Current tables
- `users` (id, name, username, full_name, email, password, role, matric_number, student_id, department, institution, siwes_start_date, siwes_end_date, workplace_name, workplace_latitude, workplace_longitude, workplace_address, phone, level, is_active, supervisor_id, reference_id, created_at, updated_at)
- `log_entries` (id, student_id, activity, date, latitude, longitude, location_address, status, accuracy, altitude, heading, speed, file_upload, supervisor_comment, created_at, updated_at)
- `weekly_summaries` (id, student_id, week_number, summary_of_work, problems_encountered, suggestions_for_improvement, status, supervisor_id, supervisor_comment, created_at, updated_at)
- `monthly_summaries` (id, student_id, month_number, learning_outcomes, innovations_initiatives, general_reflections, status, supervisor_id, supervisor_comment, created_at, updated_at)
- `evaluations` (id, student_id, supervisor_id, evaluation_type, period_reference, punctuality_rating, technical_skill_rating, communication_rating, attitude_rating, final_recommendation, digital_signature, comments, created_at, updated_at)
- `notifications` (id, user_id, title, message, type, is_read, created_at)

### Tables to add
- `organizations` (organization_id, org_name, address, geo_latitude, geo_longitude, geofence_radius_m)
- `attendance` (attendance_id, intern_id, date, check_in_time, check_out_time, status)
- `location_logs` (log_id, intern_id, latitude, longitude, captured_at, in_geofence)

### Columns to add to `users`
- `organization_id` (INT, foreign key to organizations) — for students
- `supervisor_type` (ENUM: `industry`, `school`) — for supervisors

---

## 5. Build Order (recommended sequence)

1. **Organizations table + admin management page** — foundation for geofencing
2. **Geofence class (Haversine)** — the core engine
3. **Location logs table + model** — records every GPS ping with geofence result
4. **Attendance table + model + student check-in page** — the core feature
5. **Geofence breach alerts** — notifications when student leaves during work hours
6. **Supervisor dashboard with map view** — visualize locations and geofences
7. **Supervisor type column** — distinguish industry vs school supervisors
8. **Attendance and movement reports** — for supervisors and coordinators

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
