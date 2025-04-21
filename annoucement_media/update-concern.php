<?php
// Include database connection
include 'dbconn.php'; // Changed from 'includes/db_connection.php' to match your project structure

// Start session to track user actions for audit logs
session_start();

if (isset($_GET['id']) && isset($_GET['action'])) {
    $concern_id = $_GET['id'];
    $action = $_GET['action'];
    $admin_id = $_SESSION['admin_id'] ?? 0; // Get admin ID from session
    
    // Determine new status based on action
    $new_status = '';
    switch ($action) {
        case 'resolve':
            $new_status = 'Resolved';
            break;
        case 'progress':
            $new_status = 'In Progress';
            break;
        default:
            header("Location: adm-comminsights.php?error=invalid_action");
            exit;
    }
    
    // Check if the concern_status field exists in the table
    $field_check_query = "SHOW COLUMNS FROM ownertenantconcerns LIKE 'concern_status'";
    $field_check_result = mysqli_query($conn, $field_check_query);
    $has_concern_status = mysqli_num_rows($field_check_result) > 0;
    
    // Check if the status field exists in the table
    $status_field_check_query = "SHOW COLUMNS FROM ownertenantconcerns LIKE 'status'";
    $status_field_check_result = mysqli_query($conn, $status_field_check_query);
    $has_status = mysqli_num_rows($status_field_check_result) > 0;
    
    // Build update query based on available fields
    if ($has_concern_status && $has_status) {
        // Update both fields if both exist
        $update_sql = "UPDATE ownertenantconcerns SET concern_status = ?, status = ? WHERE ID = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "ssi", $new_status, $new_status, $concern_id);
    } elseif ($has_concern_status) {
        // Update only concern_status if it exists
        $update_sql = "UPDATE ownertenantconcerns SET concern_status = ? WHERE ID = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "si", $new_status, $concern_id);
    } elseif ($has_status) {
        // Update only status if it exists
        $update_sql = "UPDATE ownertenantconcerns SET status = ? WHERE ID = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "si", $new_status, $concern_id);
    } else {
        // Neither field exists
        header("Location: adm-comminsights.php?error=missing_status_field");
        exit;
    }
    
    if (mysqli_stmt_execute($stmt)) {
        // Log the action in audit logs
        $action_description = "Updated concern ID $concern_id status to $new_status";
        $log_sql = "INSERT INTO audit_logs (admin_id, action_type, description, performed_at) 
                    VALUES (?, 'Status Update', ?, NOW())";
        $log_stmt = mysqli_prepare($conn, $log_sql);
        mysqli_stmt_bind_param($log_stmt, "is", $admin_id, $action_description);
        mysqli_stmt_execute($log_stmt);
        
        // Redirect back to community insights page with success message
        header("Location: adm-comminsights.php?success=status_updated");
        exit;
    } else {
        // Redirect with error message
        header("Location: adm-comminsights.php?error=update_failed");
        exit;
    }
} else {
    // Redirect if required parameters are missing
    header("Location: adm-comminsights.php?error=missing_parameters");
    exit;
}
?>
