<?php
include 'dbconn.php';
session_start();

// Ensure the user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get POST parameters
$requestId = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
$serviceId = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
$serviceType = isset($_POST['service_type']) ? $_POST['service_type'] : '';
$status = isset($_POST['status']) ? $_POST['status'] : '';
$reason = isset($_POST['reason']) ? mysqli_real_escape_string($conn, $_POST['reason']) : '';

if (!$requestId || !$serviceId || empty($serviceType) || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Update the service request status
    $updateServiceRequest = "UPDATE servicerequests SET status = '$status'";
    
    if ($status === 'Rejected' && !empty($reason)) {
        $updateServiceRequest .= ", reject_reason = '$reason'";
    }
    
    $updateServiceRequest .= " WHERE id = $requestId";
    
    if (!mysqli_query($conn, $updateServiceRequest)) {
        throw new Exception("Error updating service request: " . mysqli_error($conn));
    }
    
    // Update the specific service table based on service_type
    if ($serviceType === 'MoveIn') {
        $updateService = "UPDATE ownertenantmovein SET Status = '$status' WHERE id = $serviceId";
        
        if (!mysqli_query($conn, $updateService)) {
            throw new Exception("Error updating move-in request: " . mysqli_error($conn));
        }
    }
    // Add more service types as needed
    // else if ($serviceType === 'MoveOut') { ... }
    
    // Add audit log
    $adminId = $_SESSION['user_id'];
    $action = $status === 'Approved' ? 'approved' : ($status === 'Rejected' ? 'rejected' : 'updated');
    $details = "Admin $adminId $action service request #$requestId of type $serviceType";
    
    $insertLog = "INSERT INTO audit_logs (user_id, action, details, timestamp) 
                 VALUES ($adminId, '$action', '$details', NOW())";
    
    if (!mysqli_query($conn, $insertLog)) {
        throw new Exception("Error adding audit log: " . mysqli_error($conn));
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

mysqli_close($conn);
?>
