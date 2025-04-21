<?php
include 'dbconn.php';
session_start();

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Initialize variables
    $message = '';
    $stats = 'Pending';
    $signature = '';
    
    // Get form data
    $residentCode = $_POST['residentCode'];
    $residentName = $_POST['name'];
    $parkingSlotNumber = $_POST['number'];
    $daysPriorMoveout = $_POST['daysPriorMoveout'];
    $representativeName = $_POST['repName'];
    $residentContact = $_POST['repContact'];
    
    // Handle file upload (for signature)
    if (!empty($_FILES["file-upload"]["name"])) {
        $targetDir = "Signature/"; // Directory to store uploaded files
        
        // Create directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $signatureFile = $targetDir . basename($_FILES["file-upload"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($signatureFile, PATHINFO_EXTENSION));

        // Check if file is a valid image
        $check = getimagesize($_FILES["file-upload"]["tmp_name"]);
        if ($check === false) {
            $message = "File is not an image.";
            $uploadOk = 0;
        }

        // Check file size (optional)
        if ($_FILES["file-upload"]["size"] > 500000) {
            $message = "Sorry, your file is too large.";
            $uploadOk = 0;
        }

        // Allow only certain formats
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            $message = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $uploadOk = 0;
        }

        // Check if uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            $message = "Sorry, your file was not uploaded.";
            header("Location: MoveOutNotice.php?error=1&message=" . urlencode($message));
            exit;
        } else {
            // Move the file to the target directory
            if (move_uploaded_file($_FILES["file-upload"]["tmp_name"], $signatureFile)) {
                $signature = $signatureFile; // Use the uploaded file as the signature
            } else {
                $message = "Sorry, there was an error uploading your file.";
                header("Location: MoveOutNotice.php?error=1&message=" . urlencode($message));
                exit;
            }
        }
    } else {
        $message = "No signature file was uploaded.";
        header("Location: MoveOutNotice.php?error=1&message=" . urlencode($message));
        exit;
    }
    
    // Get the actual user ID (primary key) from ownerinformation table
    $user_id = null;
    
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
    
    // Second try: lookup by resident name
    if ($user_id === null) {
        $nameQuery = "SELECT ID FROM ownerinformation WHERE Resident_Name = ? OR Username = ?";
        $stmt_name = $conn->prepare($nameQuery);
        $stmt_name->bind_param("ss", $residentName, $residentName);
        $stmt_name->execute();
        $name_result = $stmt_name->get_result();
        
        if ($name_result->num_rows > 0) {
            $name_row = $name_result->fetch_assoc();
            $user_id = $name_row['ID'];
        }
        $stmt_name->close();
    }
    
    // Third try: use first available user as fallback
    if ($user_id === null) {
        $listQuery = "SELECT ID FROM ownerinformation LIMIT 1";
        $list_result = $conn->query($listQuery);
        if ($list_result && $list_result->num_rows > 0) {
            $first_user = $list_result->fetch_assoc();
            $user_id = $first_user['ID'];
        }
    }

    // Verify user_id exists and is valid before proceeding
    if ($user_id !== null) {
        // Double-check the user exists
        $verifyQuery = "SELECT ID FROM ownerinformation WHERE ID = ?";
        $verify_stmt = $conn->prepare($verifyQuery);
        $verify_stmt->bind_param("i", $user_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows === 0) {
            $message = "Error: Invalid user ID found. Please contact support.";
            header("Location: MoveOutNotice.php?error=1&message=" . urlencode($message));
            exit;
        }
        $verify_stmt->close();
        
        // Start database transaction for multiple inserts
        $conn->begin_transaction();
        
        try {
            // Prepare the SQL statement to insert data into the ownertenantmoveout table
            $sql = "INSERT INTO ownertenantmoveout (Resident_Code, Resident_Name, parkingSlotNumber, days_prior_moveout, representativeName, Resident_Contact, Signature, Status, user_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssissssi", $residentCode, $residentName, $parkingSlotNumber, $daysPriorMoveout, $representativeName, $residentContact, $signature, $stats, $user_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error adding move-out notice: " . $stmt->error);
            }
            
            // Get the ID of the newly inserted record
            $moveOutId = $conn->insert_id;
            $serviceType = "MoveOut"; // Set service type
            
            // Insert into servicerequests table
            $serviceRequestSql = "INSERT INTO servicerequests (service_id, service_type, user_id) VALUES (?, ?, ?)";
            $serviceStmt = $conn->prepare($serviceRequestSql);
            $serviceStmt->bind_param("isi", $moveOutId, $serviceType, $user_id);
            
            if (!$serviceStmt->execute()) {
                throw new Exception("Error adding service request: " . $serviceStmt->error);
            }
            
            // If everything is successful, commit the transaction
            $conn->commit();
            
            $message = "Move-Out Notice and Service Request added successfully!";
            $stmt->close();
            $serviceStmt->close();
            
            // Redirect back to the form with success message
            header("Location: MoveOutNotice.php?success=1&message=" . urlencode($message));
            exit;
        } catch (Exception $e) {
            // An error occurred, roll back the transaction
            $conn->rollback();
            
            $message = "Error: " . $e->getMessage();
            
            // Redirect back to the form with error message
            header("Location: MoveOutNotice.php?error=1&message=" . urlencode($message));
            exit;
        }
    } else {
        $message = "Error: Could not find a valid user ID for this resident. Please contact support.";
        header("Location: MoveOutNotice.php?error=1&message=" . urlencode($message));
        exit;
    }
} else {
    // If accessed directly without form submission, redirect to the form page
    header("Location: MoveOutNotice.php");
    exit;
}

// Close the database connection
$conn->close();
?>
