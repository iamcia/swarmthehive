<?php
// Include database connection
include 'dbconn.php';

// Set up response headers
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => ''
];

// Check if we have the required POST parameters
if (!isset($_POST['request_id']) || !isset($_POST['status'])) {
    $response['message'] = 'Missing required parameters';
    echo json_encode($response);
    exit;
}

// Get and sanitize input
$request_id = intval($_POST['request_id']);
$status = mysqli_real_escape_string($conn, $_POST['status']);
$reject_reason = isset($_POST['reject_reason']) ? mysqli_real_escape_string($conn, $_POST['reject_reason']) : '';
$service_type = isset($_POST['service_type']) ? mysqli_real_escape_string($conn, $_POST['service_type']) : 'MoveIn'; // Default to MoveIn

// Validate request_id
if ($request_id <= 0) {
    $response['message'] = 'Invalid request ID';
    echo json_encode($response);
    exit;
}

// Start a transaction
mysqli_begin_transaction($conn);

try {
    // First, get the service_id from servicerequests table
    $query = "SELECT service_id FROM servicerequests WHERE id = $request_id AND service_type = '$service_type'";
    $result = mysqli_query($conn, $query);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        throw new Exception("No valid $service_type request found with ID: $request_id");
    }
    
    $row = mysqli_fetch_assoc($result);
    $service_id = $row['service_id'];
    
    // Update the status in the appropriate table based on service type
    if ($service_type == 'MoveIn') {
        $update_query = "UPDATE ownertenantmovein SET Status = '$status' WHERE id = $service_id";
    } else if ($service_type == 'MoveOut') {
        $update_query = "UPDATE ownertenantmoveout SET Status = '$status' WHERE moveoutID = $service_id";
    } else if ($service_type == 'GuestCheckIn') {
        $update_query = "UPDATE guestcheckinout SET Status = '$status' WHERE id = $service_id";
    } else if ($service_type == 'PetRegistration') {
        $update_query = "UPDATE pets SET Status = '$status' WHERE id = $service_id";
    } else if ($service_type == 'VisitorPass') {
        $update_query = "UPDATE ownertenantvisitor SET Status = '$status' WHERE id = $service_id";
    } else if ($service_type == 'WorkPermit') {
        $update_query = "UPDATE workpermit SET status = '$status' WHERE id = $service_id";
    } else if ($service_type == 'AmenityReservation') {
        $update_query = "UPDATE ownertenantreservation SET status = '$status' WHERE id = $service_id";
    } else if ($service_type == 'PoolReserve') {
        $update_query = "UPDATE poolreserve SET Status = '$status' WHERE id = $service_id";
    } else if ($service_type == 'gatepass') {
        $update_query = "UPDATE gatepass SET Status = '$status' WHERE Ticket_No = $service_id";
    } else {
        throw new Exception("Unsupported service type: $service_type");
    }
    
    $result = mysqli_query($conn, $update_query);
    
    if (!$result) {
        throw new Exception("Failed to update status: " . mysqli_error($conn));
    }
    
    // If rejection reason is provided, update servicerequests table
    if (!empty($reject_reason) && $status === 'Rejected') {
        $update_reject_query = "UPDATE servicerequests SET reject_reason = '$reject_reason' WHERE id = $request_id";
        $result = mysqli_query($conn, $update_reject_query);
        
        if (!$result) {
            throw new Exception("Failed to update rejection reason: " . mysqli_error($conn));
        }
    }
    
    // If we get here, everything was successful
    mysqli_commit($conn);
    
    $response['success'] = true;
    $response['message'] = 'Request status updated successfully';
} catch (Exception $e) {
    // Something went wrong, rollback the transaction
    mysqli_rollback($conn);
    
    $response['message'] = $e->getMessage();
}

// Return the JSON response
echo json_encode($response);
?>
