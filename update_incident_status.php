<?php
include 'dbconn.php';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get report ID, new status, and comment from POST data
    $reportId = isset($_POST['reportId']) ? intval($_POST['reportId']) : 0;
    $newStatus = isset($_POST['status']) ? $_POST['status'] : '';
    $comment = isset($_POST['comment']) ? $_POST['comment'] : '';
    
    // Validate inputs
    if ($reportId <= 0 || empty($newStatus)) {
        http_response_code(400);
        echo "Invalid input data";
        exit;
    }
    
    // Validate status value
    $allowedStatuses = ['Open', 'Pending', 'Completed'];
    if (!in_array($newStatus, $allowedStatuses)) {
        http_response_code(400);
        echo "Invalid status value";
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // 1. Update the current status in the feedback table
        $query = "UPDATE feedback SET status = ? WHERE ID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $newStatus, $reportId);
        $stmt->execute();
        
        // 2. If there's a comment, add it to the security_response_history table
        if (!empty($comment)) {
            // You might want to get the security staff ID from the session
            $securityStaffId = isset($_SESSION['security_id']) ? $_SESSION['security_id'] : null;
            
            $query = "INSERT INTO security_response_history (feedback_id, response_text, response_status, security_staff_id) 
                      VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isss", $reportId, $comment, $newStatus, $securityStaffId);
            $stmt->execute();
            
            // Also update the latest response in the main feedback table if needed
            $query = "UPDATE feedback SET security_response = ? WHERE ID = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $comment, $reportId);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Return success response
        http_response_code(200);
        echo "Status and comment updated successfully";
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        
        // Return error response
        http_response_code(500);
        echo "Error updating: " . $e->getMessage();
    }
} else {
    // Return method not allowed error for non-POST requests
    http_response_code(405);
    echo "Method not allowed";
}
?>
