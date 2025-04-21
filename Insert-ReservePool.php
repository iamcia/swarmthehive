<?php
// Start session for passing success/error messages
session_start();

// Include database connection
include 'dbconn.php';

// Define log file location in a dedicated logs directory
$logPath = 'logs/pool_insert_debug.log';

// Create logs directory if it doesn't exist
if (!file_exists('logs')) {
    mkdir('logs', 0777, true);
}

// Add debug logging
error_log("Insert-ReservePool.php accessed");
file_put_contents($logPath, date('Y-m-d H:i:s') . " - Form submission received\n", FILE_APPEND);

// Debugging function
function debugToLog($message, $data = null) {
    global $logPath;
    $logMessage = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $logMessage .= ": " . print_r($data, true);
    }
    file_put_contents($logPath, $logMessage . "\n", FILE_APPEND);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $resident_code = isset($_POST['Resident_Code']) ? $_POST['Resident_Code'] : '';
    $user_type = isset($_POST['user_type']) ? $_POST['user_type'] : '';
    $tower_unit = isset($_POST['tower_unit']) ? $_POST['tower_unit'] : '';
    $schedule = isset($_POST['schedule']) ? $_POST['schedule'] : '';
    $first_names = isset($_POST['first_name']) ? $_POST['first_name'] : array();
    $last_names = isset($_POST['last_name']) ? $_POST['last_name'] : array();
    $valid_ids = isset($_FILES['valid_id']) ? $_FILES['valid_id'] : null; // Get valid IDs
    $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : null; // Try to get user_id from form
    
    debugToLog("Form data received", [
        'Resident_Code' => $resident_code,
        'user_type' => $user_type,
        'tower_unit' => $tower_unit,
        'schedule' => $schedule,
        'first_names' => $first_names,
        'last_names' => $last_names,
        'user_id' => $user_id,
        'valid_ids' => $valid_ids
    ]);
    
    // Validate required fields
    if (empty($resident_code) || empty($user_type) || empty($tower_unit) || empty($schedule) || 
        empty($first_names) || empty($last_names) || empty($valid_ids)) {
        
        $_SESSION['form_error'] = true;
        $_SESSION['form_error_message'] = "All fields are required.";
        header("Location: ReservePool.php");
        exit;
    }
    
    // Format names and valid IDs as JSON array of objects as required by the database
    $names_array = [];
    for ($i = 0; $i < count($first_names); $i++) {
        if (!empty($first_names[$i]) && !empty($last_names[$i])) {
            // Handle file uploads for valid IDs
            $valid_id = isset($valid_ids['name'][$i]) ? $valid_ids['name'][$i] : '';
            $valid_id_tmp = isset($valid_ids['tmp_name'][$i]) ? $valid_ids['tmp_name'][$i] : '';
            $valid_id_path = '';

            // Ensure valid ID file is uploaded
            if ($valid_id && $valid_id_tmp) {
                // Set the directory for storing valid IDs
                $uploadDir = 'uploads/valid_ids/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true); // Create the directory if not exists
                }

                // Generate a unique name for the valid ID file to prevent overwriting
                $valid_id_path = $uploadDir . uniqid('valid_id_') . '.' . pathinfo($valid_id, PATHINFO_EXTENSION);

                // Move the uploaded file to the server directory
                if (move_uploaded_file($valid_id_tmp, $valid_id_path)) {
                    debugToLog("Valid ID uploaded for guest", $valid_id_path);
                } else {
                    debugToLog("Failed to upload valid ID for guest", $valid_id);
                    $_SESSION['form_error'] = true;
                    $_SESSION['form_error_message'] = "Error uploading valid ID for guest.";
                    header("Location: ReservePool.php");
                    exit;
                }
            }

            // Add guest name and valid ID to the array
            $names_array[] = [
                'first_name' => trim($first_names[$i]),
                'last_name' => trim($last_names[$i]),
                'valid_id' => $valid_id_path // Store valid ID path for each guest
            ];
        }
    }
    
    // Convert the array to a JSON string
    $names = json_encode($names_array);
    
    debugToLog("Names JSON format", $names);
    
    // If no user_id provided in form, try to find it
    if ($user_id === null) {
        // First try: lookup by Resident_Code
        $ownerQuery = "SELECT ID FROM ownerinformation WHERE Owner_ID = ?";
        $stmt_owner = $conn->prepare($ownerQuery);
        $stmt_owner->bind_param("s", $resident_code);
        $stmt_owner->execute();
        $owner_result = $stmt_owner->get_result();
        
        if ($owner_result->num_rows > 0) {
            $owner_row = $owner_result->fetch_assoc();
            $user_id = $owner_row['ID'];
        }
        $stmt_owner->close();
        
        // For tenants, try to find the associated owner ID
        if ($user_id === null && $user_type === 'Tenant') {
            $tenantQuery = "SELECT o.ID FROM ownerinformation o 
                           INNER JOIN tenantinformation t ON o.Owner_ID = t.Owner_ID 
                           WHERE t.Tenant_ID = ?";
            $stmt_tenant = $conn->prepare($tenantQuery);
            $stmt_tenant->bind_param("s", $resident_code);
            $stmt_tenant->execute();
            $tenant_result = $stmt_tenant->get_result();
            
            if ($tenant_result->num_rows > 0) {
                $tenant_row = $tenant_result->fetch_assoc();
                $user_id = $tenant_row['ID'];
            }
            $stmt_tenant->close();
        }
        
        // Last resort: find any available user ID
        if ($user_id === null) {
            $listQuery = "SELECT ID FROM ownerinformation LIMIT 1";
            $list_result = $conn->query($listQuery);
            if ($list_result && $list_result->num_rows > 0) {
                $first_user = $list_result->fetch_assoc();
                $user_id = $first_user['ID'];
            }
        }
    }
    
    // If we still don't have a user_id, log error and stop
    if ($user_id === null) {
        debugToLog("Could not find a valid user ID for resident code", $resident_code);
        $_SESSION['form_error'] = true;
        $_SESSION['form_error_message'] = "Could not determine a valid user ID. Please contact support.";
        header("Location: ReservePool.php");
        exit;
    }
    
    // Default status for new reservations
    $status = "Approval";
    
    // Current timestamp for created_at
    $created_at = date('Y-m-d H:i:s');
    
    try {
        // Prepare SQL statement
        $sql = "INSERT INTO poolreserve (Resident_Code, User_Type, names, towerunitnum, schedule, Status, Created_At, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        // Bind parameters
        $stmt->bind_param("sssssssi", $resident_code, $user_type, $names, $tower_unit, $schedule, $status, $created_at, $user_id);
        
        debugToLog("About to execute statement with JSON data and user_id", $user_id);
        
        // Execute statement and check if successful
        if ($stmt->execute()) {
            // Get the ID of the newly inserted pool reservation
            $poolreserve_id = $conn->insert_id;
            debugToLog("Pool reservation inserted with ID", $poolreserve_id);
            
            // Insert into servicerequests table
            $service_type = "poolreserve";
            $service_sql = "INSERT INTO servicerequests (service_id, service_type, user_id) VALUES (?, ?, ?)";
            $service_stmt = $conn->prepare($service_sql);
            $service_stmt->bind_param("isi", $poolreserve_id, $service_type, $user_id);
            
            debugToLog("About to insert into servicerequests table with user_id", $user_id);
            
            if ($service_stmt->execute()) {
                debugToLog("Service request entry created successfully");
            } else {
                debugToLog("Failed to create service request entry", $service_stmt->error);
            }
            
            $service_stmt->close();
            
            // Set success message
            $_SESSION['form_submitted'] = true;
            $_SESSION['form_success_message'] = "Your pool reservation has been submitted successfully.";
            debugToLog("Insert successful");
        } else {
            // Log the detailed error
            $error = $stmt->error;
            debugToLog("Insert failed", $error);
            
            $_SESSION['form_error'] = true;
            $_SESSION['form_error_message'] = "Error submitting your reservation. Please try again later.";
        }
        
        // Close statement
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['form_error'] = true;
        $_SESSION['form_error_message'] = "An unexpected error occurred. Please try again later.";
        debugToLog("Exception", $e->getMessage());
        debugToLog("Exception trace", $e->getTraceAsString());
    }
    
    // Close connection
    $conn->close();
    
    // Redirect back to the form
    header("Location: ReservePool.php");
    exit;
} else {
    // If not a POST request, redirect to the form
    header("Location: ReservePool.php");
    exit;
}
?>