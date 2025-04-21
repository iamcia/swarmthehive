<?php
include 'dbconn.php';
// Query to get all work permits with the most recent first
$sql = "SELECT * FROM workpermit ORDER BY submitted_at DESC";
$result = $conn->query($sql);

// Check if query was successful
if (!$result) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Query failed: ' . $conn->error]);
    exit;
}

// Fetch all results as an associative array
$workPermits = [];
while ($row = $result->fetch_assoc()) {
    // Process the signature path if it exists
    if (!empty($row['signature'])) {
        // No need to modify, just ensure we're using the correct field name
        // The signature is already stored correctly in the database
    }
    
    $workPermits[] = $row;
}

// Close the database connection
$conn->close();

// Return the work permits as JSON
header('Content-Type: application/json');
echo json_encode($workPermits);
?>
