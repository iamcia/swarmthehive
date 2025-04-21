<?php
include('dbconn.php');
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create a debug log function
function debug_log($message, $data = null) {
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log .= ": " . print_r($data, true);
    }
    error_log($log . "\n", 3, "debug_services.log");
}

debug_log("Service request started");
debug_log("User ID", $_SESSION['user_id'] ?? 'not set');

if (!isset($_SESSION['user_id'])) {
    debug_log("Authentication failed - no user_id");
    http_response_code(401);
    die(json_encode(['error' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];
debug_log("Processing request for user_id", $user_id);

debug_log("Original user_id from session", $_SESSION['user_id']);

// Get the correct user ID from ownerinformation
$userCheckQuery = "SELECT o.ID, o.Owner_ID, o.Status 
                  FROM ownerinformation o 
                  WHERE o.Owner_ID = ?";
try {
    $userStmt = $conn->prepare($userCheckQuery);
    debug_log("Checking user with Owner_ID", $_SESSION['user_id']);
    $userStmt->bind_param("s", $_SESSION['user_id']);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $userData = $userResult->fetch_assoc();
    
    debug_log("User data found", $userData);
    
    if (!$userData) {
        throw new Exception("User not found in ownerinformation");
    }

    $db_user_id = $userData['ID'];
    $owner_id = $userData['Owner_ID'];
    
    debug_log("User verification", [
        'db_user_id' => $db_user_id,
        'owner_id' => $owner_id,
        'session_user_id' => $_SESSION['user_id']
    ]);
    
} catch (Exception $e) {
    debug_log("Error checking user", $e->getMessage());
    die(json_encode(['error' => 'User validation failed']));
}

// Fixed query with correct table names, field names, and service types
$query = "SELECT sr.id, sr.service_id, sr.service_type, sr.reject_reason,
    CASE sr.service_type
        WHEN 'MoveIn' THEN mi.Status
        WHEN 'gatepass' THEN g.Status
        WHEN 'GuestCheckIn' THEN gc.Status
        WHEN 'MoveOut' THEN mo.Status
        WHEN 'AmenityReservation' THEN ar.status
        WHEN 'VisitorPass' THEN vp.Status
        WHEN 'PetRegistration' THEN p.Status
        WHEN 'poolreserve' THEN pr.Status
        WHEN 'WorkPermit' THEN wp.status
        ELSE 'pending'
    END as status,
    CASE sr.service_type
        WHEN 'MoveIn' THEN mi.Created_At
        WHEN 'gatepass' THEN g.Created_At
        WHEN 'GuestCheckIn' THEN gc.Created_At
        WHEN 'MoveOut' THEN mo.Created_At
        WHEN 'AmenityReservation' THEN ar.reservation_created_at
        WHEN 'VisitorPass' THEN vp.submitted_at
        WHEN 'PetRegistration' THEN p.created_at
        WHEN 'poolreserve' THEN pr.Created_At
        WHEN 'WorkPermit' THEN wp.submitted_at
        ELSE NOW()
    END as created_at
FROM servicerequests sr
LEFT JOIN ownertenantmovein mi ON sr.service_id = mi.id AND sr.service_type = 'MoveIn'
LEFT JOIN gatepass g ON sr.service_id = g.Ticket_No AND sr.service_type = 'gatepass'
LEFT JOIN guestcheckinout gc ON sr.service_id = gc.id AND sr.service_type = 'GuestCheckIn'
LEFT JOIN ownertenantmoveout mo ON sr.service_id = mo.moveoutID AND sr.service_type = 'MoveOut'
LEFT JOIN ownertenantreservation ar ON sr.service_id = ar.id AND sr.service_type = 'AmenityReservation'
LEFT JOIN ownertenantvisitor vp ON sr.service_id = vp.id AND sr.service_type = 'VisitorPass'
LEFT JOIN pets p ON sr.service_id = p.id AND sr.service_type = 'PetRegistration'
LEFT JOIN poolreserve pr ON sr.service_id = pr.id AND sr.service_type = 'poolreserve'
LEFT JOIN workpermit wp ON sr.service_id = wp.id AND sr.service_type = 'WorkPermit'
WHERE sr.user_id = ?
ORDER BY sr.id DESC
LIMIT 5";

// Move debug response here, after query is defined
if (isset($_GET['debug'])) {
    debug_log("Debug mode activated");
    // Test query for service count
    $testQuery = "SELECT COUNT(*) as count FROM servicerequests WHERE user_id = ?";
    $testStmt = $conn->prepare($testQuery);
    $testStmt->bind_param("i", $db_user_id);
    $testStmt->execute();
    $testResult = $testStmt->get_result()->fetch_assoc();
    
    echo json_encode([
        'session_data' => $_SESSION,
        'user_data' => $userData,
        'query' => $query,
        'parameters' => [
            'db_user_id' => $db_user_id,
            'owner_id' => $owner_id
        ],
        'service_count' => $testResult['count'],
        'database_error' => $conn->error ?? 'None'
    ]);
    exit;
}

try {
    debug_log("Preparing service query with user_id", $db_user_id);
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        debug_log("Prepare failed", $conn->error);
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Add test query to check if user has any services
    $testQuery = "SELECT COUNT(*) as count FROM servicerequests WHERE user_id = ?";
    $testStmt = $conn->prepare($testQuery);
    $testStmt->bind_param("i", $db_user_id);
    $testStmt->execute();
    $testResult = $testStmt->get_result()->fetch_assoc();
    debug_log("Service count for user", $testResult['count']);

    // Only bind one parameter now since we removed the redundant user_id conditions in joins
    $stmt->bind_param("i", $db_user_id);
    
    debug_log("Executing query with user_id", $db_user_id);
    if (!$stmt->execute()) {
        debug_log("Execute failed", $stmt->error);
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    
    // Test the result
    if (!$result) {
        throw new Exception("No result set");
    }
    
    debug_log("Result info", [
        'num_rows' => $result->num_rows,
        'field_count' => $result->field_count
    ]);
    
    $services = [];
    while ($row = $result->fetch_assoc()) {
        debug_log("Raw row data", $row);
        $services[] = [
            'type' => $row['service_type'] ?? 'Unknown',
            'status' => $row['status'] ?? 'Pending',
            'created_at' => $row['created_at'] ?? date('Y-m-d H:i:s'),
            'reject_reason' => $row['reject_reason'] ?? null,
            'service_id' => $row['service_id'] ?? 0
        ];
    }
    
    debug_log("Final services array", $services);

    $stmt->close();
    $conn->close();

    debug_log("Sending response");
    header('Content-Type: application/json');
    echo json_encode($services);
    debug_log("Response sent successfully");

} catch (Exception $e) {
    debug_log("Error occurred", $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
