<?php
include 'dbconn.php';
session_start();

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Initialize variables

    
    // Get form data
    $residentCode = $_POST['residentCode'];
    $ownerName = $_POST['ownerName'];
    $currentDate = $_POST['currentDate'];
    $parkingSlotNumber = $_POST['number'];
    $leaseExpiryDate = $_POST['date'];
    $representativeName = $_POST['repName'];
    $residentContact = $_POST['repContact'];
    
    // File upload handling for signature
    if (!empty($_FILES["file-upload"]["name"])) {
        $targetDir = "RepresID/";
        
        // Create directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $signatureFile = $targetDir . basename($_FILES["file-upload"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($signatureFile, PATHINFO_EXTENSION));

        // Check if file is an image
        $check = getimagesize($_FILES["file-upload"]["tmp_name"]);
        if ($check === false) {
            echo "<script>alert('File is not an image.');</script>";
            $uploadOk = 0;
        }

        // Check if uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            echo "<script>alert('Sorry, your file was not uploaded.');</script>";
        } else {
            // Move the file to the target directory
            if (move_uploaded_file($_FILES["file-upload"]["tmp_name"], $signatureFile)) {
                $signature = $signatureFile;
            } else {
                echo "<script>alert('Sorry, there was an error uploading your file.');</script>";
                header("Location: MoveInNotice.php?error=upload_failed");
                exit;
            }
        }
    } else {
        echo "<script>alert('No signature file was uploaded.');</script>";
        header("Location: MoveInNotice.php?error=no_signature");
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
    
    // Second try: lookup by owner name
    if ($user_id === null) {
        $nameQuery = "SELECT ID FROM ownerinformation WHERE Resident_Name = ? OR Username = ?";
        $stmt_name = $conn->prepare($nameQuery);
        $stmt_name->bind_param("ss", $ownerName, $ownerName);
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
            echo "<script>
                alert('Error: Invalid user ID found. Please contact support.');
                window.location.href = 'MoveInNotice.php?error=1&message=invalid_user_id';
            </script>";
            exit;
        }
        $verify_stmt->close();
        
        // Start a transaction for data consistency
        $conn->begin_transaction();
        
        try {
            // First insert into ownertenantmovein table
            $sql = "INSERT INTO ownertenantmovein (Resident_Code, currentDate, Resident_Name, parkingSlotNumber, leaseExpiryDate, representativeName, Resident_Contact, Signature, Status, user_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            
            $stmt->bind_param("sssssssssi", $residentCode, $currentDate, $ownerName, $parkingSlotNumber, $leaseExpiryDate, $representativeName, $residentContact, $signature, $stats, $user_id);
            
            $result1 = $stmt->execute();
            if (!$result1) {
                throw new Exception("Error adding move-in notice: " . $stmt->error);
            }
            
            $moveInId = $conn->insert_id;
            $serviceType = "MoveIn";
            
            // Now insert into servicerequests table
            $serviceRequestSql = "INSERT INTO servicerequests (service_id, service_type, user_id) VALUES (?, ?, ?)";
            $serviceStmt = $conn->prepare($serviceRequestSql);
            
            if (!$serviceStmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            
            $serviceStmt->bind_param("isi", $moveInId, $serviceType, $user_id);
            
            $result2 = $serviceStmt->execute();
            if (!$result2) {
                throw new Exception("Error adding service request: " . $serviceStmt->error);
            }
            
            // If everything is OK, commit the transaction
            $conn->commit();
            
            echo "<script>
                alert('Move-In Notice submitted successfully!');
                window.location.href = 'MoveInNotice.php?success=1';
            </script>";
            exit;
            
        } catch (Exception $e) {
            // An error occurred, rollback changes
            $conn->rollback();
            echo "<script>
                alert('Error: " . $e->getMessage() . "');
                window.location.href = 'MoveInNotice.php?error=1&message=" . urlencode($e->getMessage()) . "';
            </script>";
            exit;
        } finally {
            // Close statements
            if (isset($stmt)) $stmt->close();
            if (isset($serviceStmt)) $serviceStmt->close();
        }
    } else {
        echo "<script>
            alert('Error: Could not find a valid user ID for this resident. Please contact support.');
            window.location.href = 'MoveInNotice.php?error=1&message=no_user_id';
        </script>";
        exit;
    }
} else {
    // If accessed directly without form submission, redirect to the form page
    header("Location: MoveInNotice.php");
    exit;
}

// Close the database connection
$conn->close();
?>

<style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
        margin: 20px;
        padding: 20px;
        background-color: #f8f9fa;
    }
    .error {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 5px;
    }
    .success {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 5px;
    }
    .info {
        background-color: #e2f0fb;
        border: 1px solid #bee5eb;
        color: #0c5460;
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 5px;
    }
</style>
