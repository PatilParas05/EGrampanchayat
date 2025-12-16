# 🏛 e-Grampanchayat Digital Portal

**This is a complete full-stack website project designed to digitize and manage the services of a village council (Grampanchayat) in India. It features a government-style portal with three distinct user roles: Admin/Officer, Staff/Gram Sevak, and Citizen/User.
The project is built using a traditional LAMP/XAMPP stack (PHP, MySQL, HTML, CSS, JavaScript) without any modern frameworks, adhering to simple, clean code practices.**

---

## 🌟 Features

### 1. Public Portal
- Home: Displays recent news, public notices, and quick links.
- Government Schemes: Searchable list of central and state schemes with eligibility details.
- Panchayat Profile: Information about elected members and contact details.
- Login/Register: Authentication system for all user roles.

### 2. Citizen Module (Role: citizen)
- Registration & Login: Secure access using email/password and Aadhaar number verification.
- Online Applications: Apply for various certificates (Birth, Income, Residence, etc.) by uploading documents.
- Application Tracking: Real-time status tracking for submitted applications.
- Grievance Submission: Submit and track complaints/grievances online.
- Certificate Download: Download approved digital certificates.

### 3. Admin/Staff Module (Roles: admin, staff)
- Dashboard Overview: Summary of users, pending applications, and new complaints.
- Application Management: View uploaded documents, approve/reject applications, and generate mock digital certificates (simulated PDF creation).
- Grievance Management: View submitted complaints, update their status (New, In Progress, Resolved, Closed), assign complaints to specific officers/departments, and provide official responses via a modal interface.
- Content Management (Admin Only): Add/edit announcements, news, and government scheme details.
---
### 🛠️ Technology Stack

- Backend: PHP (Native, secure operations using PDO)
- Database: MySQL (Schema provided in e_grampanchayat.sql)
- Frontend: HTML5, CSS (Custom/Tailwind CSS CDN for responsive design), JavaScript (for basic form validation and modal control)
- Security: Password hashing (password_hash), PDO for parameterized queries.

## 📦 Project Structure

***The project is organized following best practices for a native PHP application:***
```bash
e-Grampanchayat/
├── admin_dashboard.php  <-- Central Admin/Staff routing and logic (used here for single-file deployment)
├── dashboard.php        <-- Central Citizen routing and logic
├── index.php            <-- Public homepage and core router (login/register/public pages)
├── logout.php           <-- Session termination script
├── e_grampanchayat.sql  <-- **Database Schema File**
├── includes/
│   └── db_connect.php   <-- Database configuration (PDO)
├── assets/              <-- (Not explicitly generated, but assumed for CSS/JS)
├── uploads/             <-- Directory for uploaded citizen documents and generated certificates
```
---

## ⚙️ Setup and Installation

These instructions assume you are using a local server environment like XAMPP, WAMP, or MAMP.
- Step 1: Clone and Configure
Clone the repository:
``` 
 git clone https://github.com/PatilParas05/EGrampanchayat.git
```
  > Move to your server root: Place the entire EGram folder inside your server's web root directory (e.g., htdocs for XAMPP).<br>
  > Create Directories: Ensure the following essential directories exist in the EGram folder and have write permissions: <br>
   includes/<br>
   uploads/
- Step 2: Database Setup
>Start Services: Ensure your Apache and MySQL services are running.<br>
>Access phpMyAdmin: Open your browser and navigate to http://localhost/phpmyadmin.<br>
>Create Database: Create a new database named e_grampanchayat.<br>
>Import Schema: Select the newly created database, go to the Import tab, and upload the e_grampanchayat.sql file.<br>
- Step 3: Update Database Connection
>Edit the includes/db_connect.php file to match your MySQL credentials.
```
// Example Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'e_grampanchayat');
define('DB_USER', 'root');
define('DB_PASS', '');
```
- Step 4: Access the Application
>Open your web browser and go to:<br>

`http://localhost/EGram/`

## 🤝 Contribution

>**We welcome contributions to improve the e-Grampanchayat project! Whether you're fixing a bug, adding a new feature, or improving documentation, your help is appreciated.**<br>
>>If you find any bugs or have suggestions for new features, please open an issue in the repository. Provide a detailed description of the problem, including steps to reproduce it, or a clear explanation of the desired feature.

### 🔑 Default Login Credentials
- The database script (e_grampanchayat.sql) includes pre-configured test users:<br>

| Role             | Email               | Password   | Access File         |
|------------------|---------------------|------------|----------------------|
| Admin Officer    | admin@gp.com        | password   | admin_dashboard.php |
| Staff/Gram Sevak | staff@gp.com        | password   | admin_dashboard.php |
| Citizen/User     | citizen@example.com | password | dashboard.php       |
