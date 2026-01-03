<?php
session_start();

// Function to check if user is logged in
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
}

// Function to check if current user is Admin or HR
function isAdminOrHr() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'hr');
}
?>