<?php
// Database configuration for Tool-Kart
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'tool_kart';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8 for proper character handling
$conn->set_charset("utf8");

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>