<?php
include('dbconn.php');
session_start();

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Initialize variables
    $message = '';
    $status = 'Approval';
    
    // Get resident information from form
    $residentCode = $_POST['resident_code'];
    $userType = $_POST['user_type'];
    $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : null; // Get user_id from form if available
    
    // If user_id is not provided in the form, try to find it
    if ($user_id === null) {
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
        
        // If still null and it's a tenant, try to find the associated owner
        if ($user_id === null && $userType === 'Tenant') {
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
        
        // Last resort: use first available user as fallback
        if ($user_id === null) {
            $listQuery = "SELECT ID FROM ownerinformation LIMIT 1";
            $list_result = $conn->query($listQuery);
            if ($list_result && $list_result->num_rows > 0) {
                $first_user = $list_result->fetch_assoc();
                $user_id = $first_user['ID'];
            }
        }
    }
    
    // Verify we have a valid user_id
    if ($user_id === null) {
        $message = "Could not determine a valid user ID. Please contact support.";
        header("Location: VisitorPass.php?error=1&message=" . urlencode($message));
        exit;
    }
    
    // Get visitor schedule information
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    
    // Process guest information
    if (isset($_POST['guest_info']) && is_array($_POST['guest_info'])) {
        // Format guest information properly
        $guestInfo = array_values($_POST['guest_info']); // Re-index the array to ensure it's sequential
        
        // Debug: Log the guest info for troubleshooting
        error_log("Guest Info before JSON encoding: " . print_r($guestInfo, true));
        
        // Convert to JSON
        $guestInfoJson = json_encode($guestInfo);
        
        // Debug: Log the JSON string
        error_log("Guest Info JSON: " . $guestInfoJson);
    } else {
        $message = "No visitor information provided";
        header("Location: VisitorPass.php?error=1&message=" . urlencode($message));
        exit;
    }
    
    // Validate terms acceptance
    if (!isset($_POST['terms'])) {
        $message = "You must accept the terms and conditions.";
        header("Location: VisitorPass.php?error=1&message=" . urlencode($message));
        exit;
    }
    
    // Get user signature from database or uploaded file
    $signature = "";
    
    // Handle valid ID upload
    $validIdPath = null;
    if (isset($_FILES['valid_id']) && $_FILES['valid_id']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'ValidID/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $validIdFile = $_FILES['valid_id'];
        $validIdName = uniqid() . '_' . basename($validIdFile['name']);
        $validIdPath = $uploadDir . $validIdName;
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        if (!in_array($validIdFile['type'], $allowedTypes)) {
            $message = "Invalid file type for Valid ID. Allowed types: JPG, PNG, GIF, PDF.";
            header("Location: VisitorPass.php?error=1&message=" . urlencode($message));
            exit;
        }
        
        // Validate file size (5MB limit)
        if ($validIdFile['size'] > 5000000) {
            $message = "Valid ID file size exceeds limit (5MB).";
            header("Location: VisitorPass.php?error=1&message=" . urlencode($message));
            exit;
        }
        
        // Upload file
        if (!move_uploaded_file($validIdFile['tmp_name'], $validIdPath)) {
            $message = "Failed to upload Valid ID file.";
            header("Location: VisitorPass.php?error=1&message=" . urlencode($message));
            exit;
        }
    } else {
        $message = "Valid ID is required.";
        header("Location: VisitorPass.php?error=1&message=" . urlencode($message));
        exit;
    }
    
    // Signature upload (if no signature exists in the database)
    if (isset($_FILES['signature']) && $_FILES['signature']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'Signature/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $sigFile = $_FILES['signature'];
        $sigName = uniqid() . '_' . basename($sigFile['name']);
        $sigPath = $uploadDir . $sigName;
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($sigFile['type'], $allowedTypes)) {
            $message = "Invalid file type for Signature. Allowed types: JPG, PNG, GIF.";
            header("Location: VisitorPass.php?error=1&message=" . urlencode($message));
            exit;
        }
        
        // Validate file size (2MB limit)
        if ($sigFile['size'] > 2000000) {
            $message = "Signature file size exceeds limit (2MB).";
            header("Location: VisitorPass.php?error=1&message=" . urlencode($message));
            exit;
        }
        
        // Upload file
        if (!move_uploaded_file($sigFile['tmp_name'], $sigPath)) {
            $message = "Failed to upload Signature file.";
            header("Location: VisitorPass.php?error=1&message=" . urlencode($message));
            exit;
        }
        
        $signature = $sigPath;
    } else {
        // Try to get signature from database if not uploaded
        if ($userType === 'Owner') {
            $signatureQuery = "SELECT Signature FROM ownerinformation WHERE Owner_ID = ?";
        } else {
            $signatureQuery = "SELECT Signature FROM tenantinformation WHERE Tenant_ID = ?";
        }
        
        $signatureStmt = $conn->prepare($signatureQuery);
        $signatureStmt->bind_param("s", $residentCode);
        $signatureStmt->execute();
        $signatureResult = $signatureStmt->get_result();
        
        if ($signatureResult->num_rows > 0) {
            $signatureRow = $signatureResult->fetch_assoc();
            $signature = $signatureRow['Signature'];
        } else {
            $message = "No signature found. Please upload a signature.";
            header("Location: VisitorPass.php?error=1&message=" . urlencode($message));
            exit;
        }
    }
    
    // Start database transaction
    $conn->begin_transaction();
    
    try {
        // Current timestamp for submitted_at
        $currentDateTime = date('Y-m-d H:i:s');
        
        // Insert into ownertenantvisitor table - Updated to include user_id
        $sql = "INSERT INTO ownertenantvisitor (Resident_Code, user_type, start_date, end_date, guest_info, valid_id, signature, Status, submitted_at, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        // Prepare and execute the insert with correct column count
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssi", 
            $residentCode, 
            $userType, 
            $startDate, 
            $endDate, 
            $guestInfoJson, 
            $validIdPath, 
            $signature, 
            $status, 
            $currentDateTime,
            $user_id // Added user_id parameter
        );
        
        if ($stmt->execute()) {
            // Get the ID of the newly inserted record
            $visitorPassId = $conn->insert_id;
            $serviceType = "VisitorPass";
            
            // Insert into servicerequests table for admin dashboard, now with user_id
            $serviceRequestSql = "INSERT INTO servicerequests (service_id, service_type, user_id) VALUES (?, ?, ?)";
            $serviceStmt = $conn->prepare($serviceRequestSql);
            $serviceStmt->bind_param("isi", $visitorPassId, $serviceType, $user_id);
            $serviceStmt->execute();
            
            // Commit the transaction
            $conn->commit();
            
            $message = "Visitor Pass submitted successfully!";
            $stmt->close();
            $serviceStmt->close();
            $conn->close();
            
            // Redirect back to the form with success message
            header("Location: VisitorPass.php?success=1&message=" . urlencode($message));
            exit;
        } else {
            throw new Exception("SQL Error: " . $stmt->error);
        }
    } catch (Exception $e) {
        // Roll back the transaction on error
        $conn->rollback();
        
        $message = "Error: " . $e->getMessage();
        error_log("Visitor Pass Error: " . $e->getMessage());
        $conn->close();
        
        // Redirect back to the form with error message
        header("Location: VisitorPass.php?error=1&message=" . urlencode($message));
        exit;
    }
} else {
    // If accessed directly without form submission, redirect to the form page
    header("Location: VisitorPass.php");
    exit;
}
?>
