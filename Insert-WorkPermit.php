<?php
include("dbconn.php");
session_start();

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Initialize variables
    $message = '';
    $status = 'Approval';
    
    // Get user details from session
    $residentCode = $_POST['Resident_Code'] ?? null;
    $userType = $_POST['user_type'] ?? null;
    $userEmail = $_POST['user_email'] ?? null;
    $signature = $_POST['signature'] ?? null;
    $user_id = $_POST['user_id'] ?? null;  // Get user_id from form

    // Check user type from session to redirect appropriately
    if ($_SESSION['User_Type'] == 'Owner') {
        // Redirect to WorkPermit.php if user is an Owner
        header("Location: WorkPermit.php");
        exit();
    } elseif ($_SESSION['User_Type'] == 'Tenant') {
        // Redirect to WorkPermit2.php if user is a Tenant
        header("Location: WorkPermit2.php");
        exit();
    }
    
    // Validate required session data
    if (!$residentCode || !$userType) {
        $_SESSION['error_message'] = "Missing user information. Please log in again.";
        header("Location: WorkPermit.php");
        exit();
    }
    
    // If user_id is not available, try to find it
    if (!$user_id) {
        // First try: lookup by Resident_Code
        $ownerQuery = "SELECT ID FROM ownerinformation WHERE Owner_ID = ?";
        $stmt_owner = $conn->prepare($ownerQuery);
        $stmt_owner->bind_param("s", $residentCode);
        $stmt_owner->execute();
        $owner_result = $stmt_owner->get_result();
        
        if ($owner_result->num_rows > 0) {
            $owner_row = $owner_result->fetch_assoc();
            $user_id = $owner_row['ID'];
        }
        $stmt_owner->close();
        
        // For tenants, try to find the associated owner ID
        if (!$user_id && $userType === 'Tenant') {
            $tenantQuery = "SELECT o.ID FROM ownerinformation o 
                           INNER JOIN tenantinformation t ON o.Owner_ID = t.Owner_ID 
                           WHERE t.Tenant_ID = ?";
            $stmt_tenant = $conn->prepare($tenantQuery);
            $stmt_tenant->bind_param("s", $residentCode);
            $stmt_tenant->execute();
            $tenant_result = $stmt_tenant->get_result();
            
            if ($tenant_result->num_rows > 0) {
                $tenant_row = $tenant_result->fetch_assoc();
                $user_id = $tenant_row['ID'];
            }
            $stmt_tenant->close();
        }
        
        // Last resort: find any available user ID
        if (!$user_id) {
            $listQuery = "SELECT ID FROM ownerinformation LIMIT 1";
            $list_result = $conn->query($listQuery);
            if ($list_result && $list_result->num_rows > 0) {
                $first_user = $list_result->fetch_assoc();
                $user_id = $first_user['ID'];
            }
        }
    }
    
    // Verify user_id exists
    if (!$user_id) {
        $_SESSION['error_message'] = "Could not determine a valid user ID. Please contact support.";
        header("Location: WorkPermit.php");
        exit();
    }
    
    // Get form data
    $workType = isset($_POST['type']) ? implode(", ", $_POST['type']) : '';
    $ownerName = $_POST['owner_name'];
    $authorizeRep = $_POST['authorize'];
    $contractor = $_POST['contractor'] ?? null;
    $periodFrom = $_POST['period_from'];
    $periodTo = $_POST['period_to'];
    $tasks = $_POST['task'] ?? [];
    $personnel = $_POST['personnel'] ?? [];
    
    // Validate required fields
    if (empty($workType) || empty($ownerName) || empty($authorizeRep) || 
        empty($periodFrom) || empty($periodTo) || empty($tasks) || empty($personnel)) {
        $_SESSION['error_message'] = "All required fields must be filled out.";
        header("Location: WorkPermit.php");
        exit();
    }
    
    // Create task and personnel details
    $taskDetails = implode(", ", $tasks);
    $personnelDetails = implode(", ", $personnel);
    $currentDateTime = date('Y-m-d H:i:s');
    
    // Start transaction for database operations
    $conn->begin_transaction();
    
    try {
        // Insert into workpermit table
        $query = "INSERT INTO workpermit (Resident_Code, user_type, user_email, work_type, owner_name, authorize_rep, 
                  contractor, period_from, period_to, task_details, personnel_details, signature, submitted_at, Status, user_id) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssssssi", $residentCode, $userType, $userEmail, $workType, $ownerName, $authorizeRep, 
                         $contractor, $periodFrom, $periodTo, $taskDetails, $personnelDetails, $signature, $currentDateTime, $status, $user_id);
        
        if ($stmt->execute()) {
            // Get the work permit ID
            $workPermitId = $conn->insert_id;
            
            // Insert into servicerequests table for tracking
            $serviceType = "WorkPermit";
            $serviceRequestSql = "INSERT INTO servicerequests (service_id, service_type, user_id) VALUES (?, ?, ?)";
            $serviceStmt = $conn->prepare($serviceRequestSql);
            $serviceStmt->bind_param("isi", $workPermitId, $serviceType, $user_id);
            $serviceStmt->execute();
            
            // Commit the transaction
            $conn->commit();
            
            // Set success message and redirect
            $_SESSION['success_message'] = "Work permit filed successfully!";
            header("Location: WorkPermit.php");
            exit();
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }
    } catch (Exception $e) {
        // Roll back the transaction if any error occurs
        $conn->rollback();
        
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: WorkPermit.php");
        exit();
    } finally {
        // Close statement and connection
        if (isset($stmt)) {
            $stmt->close();
        }
        if (isset($serviceStmt)) {
            $serviceStmt->close();
        }
        $conn->close();
    }
} else {
    // If accessed directly without form submission, redirect to form page
    header("Location: WorkPermit.php");
    exit();
}
?>
