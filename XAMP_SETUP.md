# XAMP Setup for SIWES Project
<!-- php -S localhost:8080 -t "C:\Users\USER\OneDrive\Documents\SIWES"  to start // -->

## ✅ Database Successfully Created

Your SIWES database has been successfully created and configured for XAMP.

## Database Details

- **Database Name:** `siwes_db`
- **Host:** localhost
- **Username:** root
- **Password:** (empty - default XAMP setting)
- **Port:** 3306 (default MySQL port)

## Tables Created

### 1. `users` Table
- Stores user accounts (students, supervisors, admins)
- Fields: id, name, email, matric_number, department, institution, password_hash, role, created_at

### 2. `log_entries` Table
- Stores student activity logs with GPS coordinates
- Fields: id, student_id, activity, date, latitude, longitude, status, supervisor_comment, supervisor_id, created_at

## Default Login Credentials

- **Admin:** admin@siwes.com / admin123
- **Supervisor:** supervisor@company.com / supervisor123
- **Student:** alice@student.com / student123

## XAMP Configuration

- **PHP Version:** 8.2.12 ✓
- **MySQL Extension:** Loaded ✓
- **Database Connection:** Working ✓

## Access Your Application

1. Start XAMP services (Apache & MySQL)
2. Open browser and go to: `http://localhost/SIWES/`
3. Use the default credentials to test different user roles

## Files Created

- `database.sql` - Complete SQL schema
- `create_database.php` - Database creation script
- `verify_database.php` - Database verification script
- `test_xamp_connection.php` - XAMP connection test

## Troubleshooting

If you encounter issues:

1. **MySQL Connection Failed:**
   - Ensure XAMP MySQL service is running
   - Check if port 3306 is available
   - Verify root password is empty

2. **PHP Not Found:**
   - Use `C:\xamp\php\php.exe` for command line PHP
   - Ensure XAMP Apache service is running

3. **Database Access Issues:**
   - Run `C:\xamp\php\php.exe verify_database.php` to check database status
   - Run `C:\xamp\php\php.exe test_xamp_connection.php` to test XAMP setup

Your SIWES application is ready to use! 🎉 