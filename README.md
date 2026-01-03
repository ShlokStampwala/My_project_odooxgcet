# 🌊 Dayflow - HR Management System

**Dayflow** is a lightweight, role-based Human Resource Management System (HRMS) built with **PHP** and **MySQL**. It streamlines employee management, attendance tracking, leave management, and profile administration with a clean, responsive user interface.

## 🚀 Features

### 👤 User Roles
* **Admin:** Full access to manage employees, approve/reject leaves, view all attendance records, and manage salary structures.
* **Employee:** Limited access to mark attendance, apply for leaves, and view their own profile (read-only salary).

### 🔑 Key Modules
1.  **Dashboard:**
    * Real-time status indicators for all employees.
    * **🟢 Green Dot:** Present (Checked In).
    * **✈️ Blue Airplane:** On Approved Leave.
    * **🟡 Yellow Dot:** Absent.
    * Quick Check-in/Check-out widget.
    * Search bar to filter employees.
2.  **Attendance:**
    * Daily Check-in and Check-out logging.
    * Calculates total work hours and extra hours automatically.
3.  **Time Off (Leaves):**
    * Employees can request leaves (Paid, Sick, Unpaid).
    * Admins can **Approve** or **Reject** requests.
    * Visual "Airplane" status on dashboard for employees on leave.
4.  **Profile Management:**
    * Detailed employee information.
    * **Security:** Employees cannot edit others' profiles or view hidden Salary tabs.
    * **Salary:** Restricted view visible only to Admins.

---

## 📂 Project Structure

Ensure your project folder contains the following files:

```text
/dayflow
│
├── 📄 index.php           # Login Page
├── 📄 signup.php          # Register New Admin/Employee
├── 📄 dashboard.php       # Main Dashboard with Status Grid
├── 📄 attendance.php      # Attendance History & Logs
├── 📄 leaves.php          # Leave Application & Management
├── 📄 profile.php         # User Profile & Salary Info
├── 📄 logout.php          # Session Destroyer
├── 📄 db.php              # Database Connection (Self-Healing)
├── 🎨 global.css          # Universal Styling & Typography
│
└── 📁 uploads/            # Folder for Images
    ├── default_logo.png   # Default Company Logo
    └── default_user.png   # Default User Avatar