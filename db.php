<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hr_system";

// 1. Create connection to MySQL (Without selecting DB yet)
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Create Database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    // Database created successfully or already exists
} else {
    die("Error creating database: " . $conn->error);
}

// 3. Select the Database
$conn->select_db($dbname);

// 4. Create Tables if they don't exist

// --- TABLE: EMPLOYEES ---
$sql_employees = "CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(50) UNIQUE NOT NULL,
    company_name VARCHAR(100),
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255),
    role VARCHAR(20) DEFAULT 'employee',
    profile_pic VARCHAR(255) DEFAULT 'default_user.png',
    company_logo VARCHAR(255) DEFAULT 'default_logo.png',
    designation VARCHAR(100) DEFAULT 'Employee',
    department VARCHAR(100),
    dob DATE,
    address TEXT,
    nationality VARCHAR(50) DEFAULT 'Indian',
    gender VARCHAR(20),
    marital_status VARCHAR(20),
    joining_date DATE DEFAULT CURRENT_DATE,
    bank_name VARCHAR(100),
    account_number VARCHAR(50),
    ifsc_code VARCHAR(20),
    pan_number VARCHAR(20),
    uan_number VARCHAR(20),
    personal_email VARCHAR(100),
    manager VARCHAR(100),
    location VARCHAR(100),
    
    -- Salary Fields
    month_wage DECIMAL(10,2) DEFAULT 0.00,
    yearly_wage DECIMAL(10,2) DEFAULT 0.00,
    working_days INT DEFAULT 26,
    break_time VARCHAR(50),
    basic_salary DECIMAL(10,2) DEFAULT 0.00,
    hra DECIMAL(10,2) DEFAULT 0.00,
    standard_allowance DECIMAL(10,2) DEFAULT 0.00,
    performance_bonus DECIMAL(10,2) DEFAULT 0.00,
    lta DECIMAL(10,2) DEFAULT 0.00,
    fixed_allowance DECIMAL(10,2) DEFAULT 0.00,
    pf_employee DECIMAL(10,2) DEFAULT 0.00,
    pf_employer DECIMAL(10,2) DEFAULT 0.00,
    professional_tax DECIMAL(10,2) DEFAULT 0.00,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql_employees);

// --- TABLE: ATTENDANCE ---
$sql_attendance = "CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    check_in TIME,
    check_out TIME,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
)";
$conn->query($sql_attendance);

// --- TABLE: LEAVES ---
$sql_leaves = "CREATE TABLE IF NOT EXISTS leaves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    status VARCHAR(20) DEFAULT 'Pending',
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
)";
$conn->query($sql_leaves);

// --- TABLE: PASSWORD RESETS ---
$sql_resets = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    otp VARCHAR(10) NOT NULL,
    expiry DATETIME NOT NULL
)";
$conn->query($sql_resets);

?>