# Microfinance System PHP Conversion Walkthrough

I have converted your static HTML pages into a dynamic PHP web application. Here is how to run it.

## 1. Database Setup
1. Open your web browser and go to `http://localhost/phpmyadmin`.
2. Create a new database named `microfinance_db`.
3. Import the `database.sql` file located in your project folder into this database.
   - Click on the "Import" tab in phpMyAdmin.
   - Choose `database.sql`.
   - Click "Go".

## 2. Configuration
The database connection is configured in `db_connect.php`.
```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "microfinance_db";
```
If your MySQL configuration is different (e.g., you have a password for root), please edit this file.

## 3. Running the Application
1. Ensure your project folder `Project Pages` is in your local server's root directory (e.g., `C:\xampp\htdocs\Project Pages`) OR that you are running a virtual host pointing to it.
2. Open your browser.
3. Visit `http://localhost/Project Pages/index.php`.

## 4. Admin Access
- You can register a normal user via the Register page.
- To access the Admin Panel, you first need an admin account. 
  - **Option A**: Manually insert an admin into the `users` table via phpMyAdmin with `role` = 'admin'.
  - **Option B**: Register a new user, then go to phpMyAdmin -> `users` table -> find the user -> change `role` from 'user' to 'admin'.
- Once you have an admin account, login via `admin_login.php` (or use the link in the footer usually, but I've kept the direct file access).

## 5. Features
- **User**: Register, Login, Apply for Loan, View History, View Notifications, Make Repayments.
- **Admin**: View Dashboard Stats, Manage Users, Approve/Reject Loans, Verify Repayments.

## Files Created
- **Core**: `db_connect.php`, `auth_session.php`, `logout.php`
- **User Pages**: `index.php`, `login.php`, `register.php`, `dashboard.php`, `profile.php`, `loan_application.php`, `loan_status.php`, `loan_history.php`, `repayment_schedule.php`, `make_repayment.php`, `notifications.php`, `loan_calculator.php`, `otp.php`
- **Admin Pages**: `admin_login.php`, `admin_dashboard.php`, `admin_users.php`, `admin_loans.php`, `admin_repayments.php`
