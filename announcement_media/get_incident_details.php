<?php
include 'dbconn.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "No incident ID provided"]);
    exit;
}

$id = intval($_GET['id']);

// Fetch incident details including security response
$query = "SELECT f.*, 
        o.Last_Name as owner_last_name, o.First_Name as owner_first_name, o.Tower as owner_tower, o.Unit_Number as owner_unit,
        t.Last_Name as tenant_last_name, t.First_Name as tenant_first_name, t.Tower as tenant_tower, t.Unit_Number as tenant_unit
        FROM feedback f
        LEFT JOIN ownerinformation o ON f.user_id = o.Owner_ID
        LEFT JOIN tenantinformation t ON f.tenant_id = t.Tenant_ID
        WHERE f.ID = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["error" => "Incident not found"]);
    exit;
}

$incident = $result->fetch_assoc();

// Fetch all security responses for this incident
$query = "SELECT * FROM security_response_history 
          WHERE feedback_id = ? 
          ORDER BY response_date ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$responseResult = $stmt->get_result();

$responseHistory = [];
while ($row = $responseResult->fetch_assoc()) {
    $responseHistory[] = $row;
}

// Add the response history to the incident data
$incident['response_history'] = $responseHistory;

// Return the incident data as JSON
header('Content-Type: application/json');
echo json_encode($incident);
?>
