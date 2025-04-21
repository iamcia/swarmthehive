<?php
include 'dbconn.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get form data
$requestId = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
$status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : '';
$rejectReason = isset($_POST['reject_reason']) ? mysqli_real_escape_string($conn, $_POST['reject_reason']) : '';
$adminNotes = isset($_POST['admin_notes']) ? mysqli_real_escape_string($conn, $_POST['admin_notes']) : '';

// Validate input
if ($requestId <= 0 || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

// If status is rejected, reject_reason is required
if ($status === 'rejected' && empty($rejectReason)) {
    echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
    exit;
}

// Update status in database
$query = "UPDATE servicerequests SET 
          status = ?, 
          reject_reason = ?, 
          admin_notes = ?,
          updated_at = NOW()
          WHERE id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "sssi", $status, $rejectReason, $adminNotes, $requestId);
$result = mysqli_stmt_execute($stmt);

if ($result) {
    // Log the action
    $adminId = $_SESSION['user_id'];
    $action = "Updated service request #$requestId status to '$status'";
    $logQuery = "INSERT INTO admin_logs (admin_id, action, created_at) VALUES (?, ?, NOW())";
    $logStmt = mysqli_prepare($conn, $logQuery);
    mysqli_stmt_bind_param($logStmt, "is", $adminId, $action);
    mysqli_stmt_execute($logStmt);
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}
?>
