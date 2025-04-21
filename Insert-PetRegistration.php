<?php
include('dbconn.php');
session_start();

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_pet'])) {
    // Initialize variables
    $message = '';
    
    // Get user information from form
    $residentCode = $_POST['resident_code'];
    $owner_name = $_POST['owner_name'];
    $userNumber = $_POST['contact'];
    $unitNumber = $_POST['unit_no'];
    $userEmail = $_POST['email'];
    $userType = $_POST['user_type'];
    $user_id = null; // Added user_id variable
    
    // Get the actual user ID (primary key) from ownerinformation table
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
        $nameQuery = "SELECT ID FROM ownerinformation WHERE First_Name = ? OR Username = ?";
        $stmt_name = $conn->prepare($nameQuery);
        $stmt_name->bind_param("ss", $owner_name, $owner_name);
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
    
    // Verify user_id exists
    if ($user_id === null) {
        $message = "Could not find a valid user ID for this resident. Please contact support.";
        header("Location: PetRegistration.php?error=1&message=" . urlencode($message));
        exit;
    }
    
    // Get form data
    $pet_name = $_POST['pet_name'];
    $breed = $_POST['breed'];
    $dob = $_POST['dob'];
    $vaccinated = $_POST['vaccinated'];
    $vaccine_duration = $_POST['vaccine_duration'];
    $signature_date = date('Y-m-d'); // Current date
    $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : ''; // Initialize remarks, if any
    
    // Validate required fields
    if (empty($pet_name) || empty($breed) || empty($dob) || empty($vaccinated) || empty($vaccine_duration)) {
        $message = "All required fields must be completed.";
        header("Location: PetRegistration.php?error=1&message=" . urlencode($message));
        exit;
    }

    // Directory to store uploaded files
    $upload_dir = 'PetID/';
    $pet_pic_path = '';
    $vaccine_image_path = '';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Handle pet picture upload
    if (isset($_FILES['pet_pic']) && $_FILES['pet_pic']['error'] == UPLOAD_ERR_OK) {
        $pet_pic_tmp = $_FILES['pet_pic']['tmp_name'];
        $pet_pic_name = basename($_FILES['pet_pic']['name']);
        $pet_pic_path = $upload_dir . uniqid() . '_' . $pet_pic_name;
        
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($pet_pic_path, PATHINFO_EXTENSION));
        
        // Check if file is an actual image
        $check = getimagesize($_FILES['pet_pic']['tmp_name']);
        if ($check === false) {
            $message = "Pet picture file is not an image.";
            $uploadOk = 0;
        }
        
        // Check file size (limit to 5MB)
        if ($_FILES['pet_pic']['size'] > 5000000) {
            $message = "Pet picture file is too large. Max size is 5MB.";
            $uploadOk = 0;
        }
        
        // Allow certain file formats
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            $message = "Only JPG, JPEG, PNG & GIF files are allowed for pet pictures.";
            $uploadOk = 0;
        }
        
        // Check if upload is ok
        if ($uploadOk == 0) {
            header("Location: PetRegistration.php?error=1&message=" . urlencode($message));
            exit;
        } else {
            if (!move_uploaded_file($pet_pic_tmp, $pet_pic_path)) {
                $message = "Error uploading pet picture file.";
                header("Location: PetRegistration.php?error=1&message=" . urlencode($message));
                exit;
            }
        }
    } else {
        $message = "Pet picture is required.";
        header("Location: PetRegistration.php?error=1&message=" . urlencode($message));
        exit;
    }

    // Handle vaccine certificate upload
    if (isset($_FILES['vaccine_image']) && $_FILES['vaccine_image']['error'] == UPLOAD_ERR_OK) {
        $vaccine_image_tmp = $_FILES['vaccine_image']['tmp_name'];
        $vaccine_image_name = basename($_FILES['vaccine_image']['name']);
        $vaccine_image_path = $upload_dir . uniqid() . '_' . $vaccine_image_name;
        
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($vaccine_image_path, PATHINFO_EXTENSION));
        
        // Check if file is an actual image
        $check = getimagesize($_FILES['vaccine_image']['tmp_name']);
        if ($check === false) {
            $message = "Vaccine certificate file is not an image.";
            $uploadOk = 0;
        }
        
        // Check file size (limit to 5MB)
        if ($_FILES['vaccine_image']['size'] > 5000000) {
            $message = "Vaccine certificate file is too large. Max size is 5MB.";
            $uploadOk = 0;
        }
        
        // Allow certain file formats
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            $message = "Only JPG, JPEG, PNG & GIF files are allowed for vaccine certificates.";
            $uploadOk = 0;
        }
        
        // Check if upload is ok
        if ($uploadOk == 0) {
            header("Location: PetRegistration.php?error=1&message=" . urlencode($message));
            exit;
        } else {
            if (!move_uploaded_file($vaccine_image_tmp, $vaccine_image_path)) {
                $message = "Error uploading vaccine certificate file.";
                header("Location: PetRegistration.php?error=1&message=" . urlencode($message));
                exit;
            }
        }
    } else {
        $message = "Vaccine certificate is required.";
        header("Location: PetRegistration.php?error=1&message=" . urlencode($message));
        exit;
    }

    // Get user signature from database if available
    $signature = "";
    if (!empty($residentCode)) {
        if ($userType === 'Owner') {
            $signatureQuery = "SELECT Signature FROM ownerinformation WHERE Owner_ID = ?";
            $signatureStmt = $conn->prepare($signatureQuery);
            $signatureStmt->bind_param("s", $residentCode);
            $signatureStmt->execute();
            $signatureResult = $signatureStmt->get_result();
            if ($signatureResult->num_rows > 0) {
                $signatureRow = $signatureResult->fetch_assoc();
                $signature = $signatureRow['Signature'];
            }
            $signatureStmt->close();
        } elseif ($userType === 'Tenant') {
            $signatureQuery = "SELECT Signature FROM tenantinformation WHERE Tenant_ID = ?";
            $signatureStmt = $conn->prepare($signatureQuery);
            $signatureStmt->bind_param("s", $residentCode);
            $signatureStmt->execute();
            $signatureResult = $signatureStmt->get_result();
            if ($signatureResult->num_rows > 0) {
                $signatureRow = $signatureResult->fetch_assoc();
                $signature = $signatureRow['Signature'];
            }
            $signatureStmt->close();
        }
    }

    // Start database transaction
    $conn->begin_transaction();
    
    try {
        // Insert into pets table
        $sql_insert_pet = $conn->prepare("INSERT INTO pets (Resident_Code, owner_name, contact, unit_no, email, pet_name, breed, dob, pet_pic, vaccinated, vaccine_card, vaccine_duration, remarks, user_signature, user_type, status, created_at, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $status = "Approval";
        $currentDateTime = date('Y-m-d H:i:s');
        
        $sql_insert_pet->bind_param("sssssssssssssssssi", 
            $residentCode, 
            $owner_name, 
            $userNumber, 
            $unitNumber, 
            $userEmail, 
            $pet_name, 
            $breed, 
            $dob, 
            $pet_pic_path, 
            $vaccinated, 
            $vaccine_image_path, 
            $vaccine_duration, 
            $remarks, 
            $signature, 
            $userType, 
            $status, 
            $currentDateTime,
            $user_id // Added user_id parameter
        );

        if ($sql_insert_pet->execute()) {
            // Get the ID of the newly inserted record
            $petId = $conn->insert_id;
            $serviceType = "PetRegistration";
            
            // Insert into servicerequests table with user_id
            $serviceRequestSql = "INSERT INTO servicerequests (service_id, service_type, user_id) VALUES (?, ?, ?)";
            $serviceStmt = $conn->prepare($serviceRequestSql);
            $serviceStmt->bind_param("isi", $petId, $serviceType, $user_id);
            $serviceStmt->execute();
            $serviceStmt->close();
            
            // Commit the transaction
            $conn->commit();
            
            $message = "Pet Registration Submitted Successfully!";
            $sql_insert_pet->close();
            $conn->close();
            
            // Redirect back to the form with success message
            header("Location: PetRegistration.php?success=1&message=" . urlencode($message));
            exit;
        } else {
            throw new Exception($sql_insert_pet->error);
        }
    } catch (Exception $e) {
        // Roll back the transaction on error
        $conn->rollback();
        
        $message = "Error: " . $e->getMessage();
        $conn->close();
        
        // Redirect back to the form with error message
        header("Location: PetRegistration.php?error=1&message=" . urlencode($message));
        exit;
    }
} else {
    // If accessed directly without form submission, redirect to the form page
    header("Location: PetRegistration.php");
    exit;
}
?>
