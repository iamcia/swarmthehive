<?php
include('dbconn.php');
session_start();

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_reservation'])) {
    // Initialize variables
    $message = '';
    $status = 'Approval';
    
    // Get form data
    $amenity = htmlspecialchars($_POST['amenity']);
    $reservationDate = htmlspecialchars($_POST['reservation_date']);
    $reservationTime = htmlspecialchars($_POST['reservation_time']);
    $numberOfPeople = htmlspecialchars($_POST['number_of_people']);
    $additionalRequest = htmlspecialchars($_POST['additional_request']);
    $residentCode = htmlspecialchars($_POST['resident_code']);
    $userEmail = htmlspecialchars($_POST['user_email']);
    $userType = isset($_POST['user_type']) ? htmlspecialchars($_POST['user_type']) : '';
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null; // Get user_id from form
    
    // If user_id not provided in form, try to find it
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
        
        // For tenants, try to find the associated owner ID
        if ($user_id === null && $userType == 'Tenant') {
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
        
        // Last resort: lookup any available user as fallback
        if ($user_id === null) {
            $listQuery = "SELECT ID FROM ownerinformation LIMIT 1";
            $list_result = $conn->query($listQuery);
            if ($list_result && $list_result->num_rows > 0) {
                $first_user = $list_result->fetch_assoc();
                $user_id = $first_user['ID'];
            }
        }
    }
    
    // Verify we found a valid user_id
    if ($user_id === null) {
        $_SESSION['reservation_error'] = "Could not find a valid user ID. Please contact support.";
        header("Location: OwnerReservation.php");
        exit;
    }
    
    // Convert to 12-hour format with AM/PM
    $dateTime = DateTime::createFromFormat('H:i', $reservationTime);
    $reservationTimeFormatted = $dateTime ? $dateTime->format('H:i A') : $reservationTime;
    
    $currentDateTime = date('Y-m-d H:i:s');
    
    // Validate inputs
    if (empty($amenity) || empty($reservationDate) || empty($reservationTime) || empty($numberOfPeople)) {
        $_SESSION['reservation_error'] = "All required fields must be filled out.";
        header("Location: OwnerReservation.php");
        exit;
    }
    
    // Validate dates (must be at least 48 hours in advance)
    $minimumDate = date('Y-m-d', strtotime('+2 days'));
    if ($reservationDate < $minimumDate) {
        $_SESSION['reservation_error'] = "Reservations must be made at least 48 hours in advance.";
        header("Location: OwnerReservation.php");
        exit;
    }
    
    // Validate number of people based on amenity
    $maxPeople = ($amenity == "Function Hall") ? 50 : 100;
    if ($numberOfPeople > $maxPeople) {
        $_SESSION['reservation_error'] = "Maximum capacity for $amenity is $maxPeople people.";
        header("Location: OwnerReservation.php");
        exit;
    }
    
    // Start a transaction
    $conn->begin_transaction();
    
    try {
        // Insert reservation into the database
        $sql = "INSERT INTO ownertenantreservation (amenity, reservation_date, reservation_time, number_of_people, 
                additional_request, reservation_created_at, user_type, user_email, resident_code, status, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssissssssi", $amenity, $reservationDate, $reservationTimeFormatted, $numberOfPeople, 
                    $additionalRequest, $currentDateTime, $userType, $userEmail, $residentCode, $status, $user_id);
        
        if ($stmt->execute()) {
            // Get the ID of the newly inserted reservation
            $reservationId = $conn->insert_id;
            
            // Create service request entry
            $serviceType = "AmenityReservation";
            $serviceRequestSql = "INSERT INTO servicerequests (service_id, service_type, user_id) VALUES (?, ?, ?)";
            $serviceStmt = $conn->prepare($serviceRequestSql);
            $serviceStmt->bind_param("isi", $reservationId, $serviceType, $user_id);
            $serviceStmt->execute();
            
            // Commit the transaction
            $conn->commit();
            
            // Set success message
            $_SESSION['reservation_success'] = "Reservation submitted successfully.";
        } else {
            throw new Exception("Error executing database query");
        }
        
        $stmt->close();
        if (isset($serviceStmt)) {
            $serviceStmt->close();
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['reservation_error'] = "Failed to submit reservation: " . $e->getMessage();
    }
    
    // Redirect back to the form
    header("Location: OwnerReservation.php");
    exit;
} else {
    // If accessed directly without form submission, redirect to the form page
    header("Location: OwnerReservation.php");
    exit;
}

$conn->close();
?>
