<?php
// Secure this page for admin use only
session_start();
include 'dbconn.php';

header('Content-Type: application/json');

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['UserType']) || $_SESSION['UserType'] != 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_GET['userType']) || !isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$userType = $_GET['userType'];
$id = $_GET['id'];

// Sanitize inputs
$userType = filter_var($userType, FILTER_SANITIZE_STRING);
$id = filter_var($id, FILTER_SANITIZE_STRING);

// Determine which table to query based on user type
if ($userType == 'Owner') {
    $table = 'ownerinformation';
    $idField = 'Owner_ID';
} elseif ($userType == 'Tenant') {
    $table = 'tenantinformation';
    $idField = 'Tenant_ID';
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid user type']);
    exit;
}

// Prepare and execute the query
$sql = "SELECT * FROM $table WHERE $idField = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo json_encode(['success' => true, 'user' => $user]);
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}

$stmt->close();
$conn->close();
?>
