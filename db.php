<?php
$conn = new mysqli("localhost", "root", "", "hr_system");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
?>