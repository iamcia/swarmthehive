<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "swarm";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

// Check if POST request and ID is set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("UPDATE guestcheckinout SET Status = 'Approved' WHERE id = ?");
    
    if ($stmt) {
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Guest successfully approved']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update record: ' . $stmt->error]);
        }
        
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request. Guest ID is required.']);
}

$conn->close();
?>
