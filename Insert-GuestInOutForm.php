<?php
include 'dbconn.php';
session_start();

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Initialize variables
    $message = '';
    $status = 'Approval';
    
    // Get user information from session
    $residentCode = '';
    $userType = '';
    $user_id = null; // Added user_id variable
    
    if (isset($_SESSION['username'])) {
        $ownerUsername = $_SESSION['username'];

        // Check OwnerInformation table first
        $sql = "SELECT ID, Owner_ID, Status FROM ownerinformation WHERE Username = ?"; // Added ID to select
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $ownerUsername);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $userType = 'Owner';
            $residentCode = $row['Owner_ID'];
            $user_id = $row['ID']; // Store the user ID
        } else {
            // Check TenantInformation table
            $sql = "SELECT Tenant_ID, Status FROM tenantinformation WHERE Username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $ownerUsername);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $userType = 'Tenant';
                $residentCode = $row['Tenant_ID'];
                
                // For tenants, we need to find their associated owner's ID
                $ownerQuery = "SELECT o.ID FROM ownerinformation o 
                               INNER JOIN tenantinformation t ON o.Owner_ID = t.Owner_ID 
                               WHERE t.Tenant_ID = ?";
                $stmt_owner = $conn->prepare($ownerQuery);
                $stmt_owner->bind_param("s", $residentCode);
                $stmt_owner->execute();
                $owner_result = $stmt_owner->get_result();
                
                if ($owner_result->num_rows > 0) {
                    $owner_row = $owner_result->fetch_assoc();
                    $user_id = $owner_row['ID'];
                }
                $stmt_owner->close();
            } else {
                $message = "User information not found.";
                header("Location: GuestInOutForm.php?error=1&message=" . urlencode($message));
                exit;
            }
        }
        $stmt->close();
    } else {
        $message = "User session expired. Please login again.";
        header("Location: login.php?message=" . urlencode($message));
        exit;
    }
    
    // Verify user_id exists and is valid
    if ($user_id === null) {
        // Try to find user_id by resident code as fallback
        $idQuery = "SELECT ID FROM ownerinformation WHERE Owner_ID = ?";
        $stmt_id = $conn->prepare($idQuery);
        $stmt_id->bind_param("s", $residentCode);
        $stmt_id->execute();
        $id_result = $stmt_id->get_result();
        
        if ($id_result->num_rows > 0) {
            $id_row = $id_result->fetch_assoc();
            $user_id = $id_row['ID'];
        }
        $stmt_id->close();
        
        if ($user_id === null) {
            $message = "Could not determine a valid user ID. Please contact support.";
            header("Location: GuestInOutForm.php?error=1&message=" . urlencode($message));
            exit;
        }
    }
    
    // Get form data
    $checkinDate = $_POST['checkin_date'];
    $checkoutDate = $_POST['checkout_date'];
    $daysOfStay = $_POST['days_of_stay'];
    $unitType = $_POST['unit_type'];
    $guestInfo = json_encode($_POST['guest_info']); 
    $termsAgreed = isset($_POST['terms_conditions']) ? 1 : 0;
    
    // Validate required data
    if (empty($checkinDate) || empty($checkoutDate) || empty($daysOfStay) || empty($unitType) || empty($guestInfo)) {
        $message = "All required fields must be completed.";
        header("Location: GuestInOutForm.php?error=1&message=" . urlencode($message));
        exit;
    }
    
    // Validate terms agreement
    if (!$termsAgreed) {
        $message = "You must agree to the terms and conditions.";
        header("Location: GuestInOutForm.php?error=1&message=" . urlencode($message));
        exit;
    }

    // Directory to store uploaded files
    $uploadDir = 'ValidID/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Handle Valid ID upload
    $validIDPath = '';
    if (!empty($_FILES['valid_id']['tmp_name'])) {
        $validIDPath = $uploadDir . basename($_FILES['valid_id']['name']);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($validIDPath, PATHINFO_EXTENSION));
        
        // Check if file is an actual image
        $check = getimagesize($_FILES['valid_id']['tmp_name']);
        if ($check === false) {
            $message = "Valid ID file is not an image.";
            $uploadOk = 0;
        }
        
        // Check file size (limit to 5MB)
        if ($_FILES['valid_id']['size'] > 5000000) {
            $message = "Valid ID file is too large. Max size is 5MB.";
            $uploadOk = 0;
        }
        
        // Allow certain file formats
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            $message = "Only JPG, JPEG, PNG & GIF files are allowed for Valid ID.";
            $uploadOk = 0;
        }
        
        // Check if upload is ok
        if ($uploadOk == 0) {
            header("Location: GuestInOutForm.php?error=1&message=" . urlencode($message));
            exit;
        } else {
            if (!move_uploaded_file($_FILES['valid_id']['tmp_name'], $validIDPath)) {
                $message = "Error uploading Valid ID file.";
                header("Location: GuestInOutForm.php?error=1&message=" . urlencode($message));
                exit;
            }
        }
    } else {
        $message = "Valid ID file is required.";
        header("Location: GuestInOutForm.php?error=1&message=" . urlencode($message));
        exit;
    }

    // Handle Vaccine Card upload (optional)
    $vaccineCardPath = '';
    if (!empty($_FILES['vaccine_card']['tmp_name'])) {
        $vaccineCardPath = $uploadDir . basename($_FILES['vaccine_card']['name']);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($vaccineCardPath, PATHINFO_EXTENSION));
        
        // Check if file is an actual image
        $check = getimagesize($_FILES['vaccine_card']['tmp_name']);
        if ($check === false) {
            $message = "Vaccine Card file is not an image.";
            $uploadOk = 0;
        }
        
        // Check file size (limit to 5MB)
        if ($_FILES['vaccine_card']['size'] > 5000000) {
            $message = "Vaccine Card file is too large. Max size is 5MB.";
            $uploadOk = 0;
        }
        
        // Allow certain file formats
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            $message = "Only JPG, JPEG, PNG & GIF files are allowed for Vaccine Card.";
            $uploadOk = 0;
        }
        
        // Check if upload is ok
        if ($uploadOk == 0) {
            header("Location: GuestInOutForm.php?error=1&message=" . urlencode($message));
            exit;
        } else {
            if (!move_uploaded_file($_FILES['vaccine_card']['tmp_name'], $vaccineCardPath)) {
                $message = "Error uploading Vaccine Card file.";
                header("Location: GuestInOutForm.php?error=1&message=" . urlencode($message));
                exit;
            }
        }
    }

    // Get current timestamp
    $currentDateTime = date('Y-m-d H:i:s');
    
    // Start database transaction
    $conn->begin_transaction();
    
    try {
        // Insert into guestcheckinout table
        $query = "INSERT INTO guestcheckinout (Resident_Code, User_Type, Checkin_Date, Checkout_Date, Days_Of_Stay, Unit_Type, Guest_Info, Valid_ID, Vaccine_Card, Status, Created_At, user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssissssssi", $residentCode, $userType, $checkinDate, $checkoutDate, $daysOfStay, $unitType, $guestInfo, $validIDPath, $vaccineCardPath, $status, $currentDateTime, $user_id);
        $stmt->execute();
        
        // Get the ID of the newly inserted record
        $guestCheckInId = $conn->insert_id;
        $serviceType = "GuestCheckIn"; // Set service type as GuestCheckIn
        
        // Insert into servicerequests table
        $serviceRequestSql = "INSERT INTO servicerequests (service_id, service_type, user_id) VALUES (?, ?, ?)";
        $serviceStmt = $conn->prepare($serviceRequestSql);
        $serviceStmt->bind_param("isi", $guestCheckInId, $serviceType, $user_id);
        $serviceStmt->execute();
        
        // If everything is successful, commit the transaction
        $conn->commit();
        
        $message = "Guest Check-In/Out record submitted successfully!";
        $stmt->close();
        $serviceStmt->close();
        $conn->close();
        
        // Redirect back to the form with success message
        header("Location: GuestInOutForm.php?success=1&message=" . urlencode($message));
        exit;
    } catch (Exception $e) {
        // An error occurred, roll back the transaction
        $conn->rollback();
        
        $message = "Error: " . $e->getMessage();
        $conn->close();
        
        // Redirect back to the form with error message
        header("Location: GuestInOutForm.php?error=1&message=" . urlencode($message));
        exit;
    }
} else {
    // If accessed directly without form submission, redirect to the form page
    header("Location: GuestInOutForm.php");
    exit;
}
?>
