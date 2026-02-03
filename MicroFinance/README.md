# MicroFinance Banking System

A comprehensive web-based platform designed to streamline banking operations, loan management, and customer services for micro-credit institutions.

## 📌 Project Overview

This system provides a secure and efficient environment for borrowers to apply for loans and for administrators to manage approvals, disbursals, and repayments. Built with a focus on usability and security, it automates key financial workflows, reducing manual paperwork and ensuring transparent transaction tracking.

## 🚀 Key Features

- **User Management**: Secure registration with CNIC verification and role-based access for Administrators and Customers.
- **Loan Management**: Complete lifecycle handling including application, review, approval, rejection, and fund disbursal.
- **Repayment System**: Automated tracking of loan repayments, outstanding balances, and payment status updates.
- **Admin Dashboard**: Centralized control panel for user oversight, loan requests, analytics, and notifications.
- **Security**: 
  - Encrypted password storage
  - OTP (One-Time Password) verification for critical actions
  - CSRF protection and Input validation
- **Responsive Design**: Mobile-friendly interface accessible on all devices.

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: Native PHP
- **Database**: MySQL
- **Email Service**: PHPMailer (SMTP)
- **Server Environment**: XAMPP (Apache/MySQL)

## ⚙️ Setup & Installation

Follow these steps to set up the project locally:

1. **Clone or Download**
   - Download the project zip or clone the repository.
   - Move the project folder to your XAMPP `htdocs` directory (e.g., `C:\xampp\htdocs\MicroFinance`).

2. **Database Configuration**
   - Open **XAMPP Control Panel** and start **Apache** and **MySQL**.
   - Open your browser and go to `http://localhost/phpmyadmin`.
   - Create a new database named `microfinance_db`.
   - Click on the database, go to **Import**, and select the `database.sql` file located in the project root directory.
   - Click **Go** to import the tables and data.

3. **Verify Connection**
   - Open `includes/db_connect.php` and verify the credentials match your local setup:
     ```php
     $servername = "localhost";
     $username = "root";  // Default XAMPP username
     $password = "";      // Default XAMPP password is empty
     $dbname = "microfinance_db";
     ```

4. **Launch Application**
   - Open your browser and visit: `http://localhost/MicroFinance`

## 📖 Usage

### Admin Login
- Access the login page and use your admin credentials.
- Navigate to the **Admin Dashboard** to manage users and loans.

### Customer Registration
- New users can sign up via the **Registration** page.
- After logging in, users can apply for loans and view their application status.

## 👥 Contributors

- **Ahmed Mohsen** (S2024266221)
- **Shan Ahmed** (S2024266154)
- **Maryam Hasnat** (S2024266194)
- **Fajr Asim** (S2024266030)

---
&copy; 2026 MicroFinance Bank. All Rights Reserved.
