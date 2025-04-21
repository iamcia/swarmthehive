<?php
$servername = "mysql.hostinger.com"; // Change to Hostinger's MySQL server if needed
$db_username = "u113232969_Hives"; // Your actual MySQL username
$db_password = "NiCk|7x7o:"; // Your MySQL password
$dbname = "u113232969_SWARM"; // Your actual database name

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set PHP default timezone
date_default_timezone_set('Asia/Manila');

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

//echo "Connected successfully"; // For debugging, remove this in production

?>
