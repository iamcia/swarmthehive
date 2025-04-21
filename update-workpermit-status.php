<?php
include 'dbconn.php';

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if required parameters are provided
if (!isset($_POST['id']) || !isset($_POST['status'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Get the parameters
$id = $_POST['id'];
$status = $_POST['status'];

// Validate status (only allow certain status values)
$validStatuses = ['Pending', 'Approval', 'Completed', 'Reject'];
if (!in_array($status, $validStatuses)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

// Prepare and execute the update query
$stmt = $conn->prepare("UPDATE workpermit SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    // Check if the update affected any rows
    if ($stmt->affected_rows > 0) {
        $response = [
            'success' => true,
            'message' => 'Work permit status updated successfully',
            'id' => $id,
            'status' => $status
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'No work permit found with the provided ID',
            'id' => $id
        ];
    }
} else {
    $response = [
        'success' => false,
        'message' => 'Error updating work permit status: ' . $stmt->error,
        'id' => $id
    ];
}

$stmt->close();
$conn->close();

// Return the response as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
