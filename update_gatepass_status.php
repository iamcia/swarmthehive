<?php
// Turn on error reporting only for debugging
// Comment this out in production
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors directly to the output

// Include database connection
include 'dbconn.php';

// Ensure output is clean JSON (no warnings or notices mixed in)
ob_start();

header('Content-Type: application/json');

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . mysqli_connect_error()]));
}

try {
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Check if required parameters are provided
    if (!isset($_POST['ticket_no']) || !isset($_POST['checked_by'])) {
        throw new Exception('Missing required parameters');
    }

    $ticketNo = $_POST['ticket_no'];
    $checkedBy = $_POST['checked_by']; // This is now Management_ID instead of a text name
    $personnelName = $_POST['personnel_name'] ?? ''; // Full name for logging purposes

    // Validate ticket number
    if (empty($ticketNo) || !is_numeric($ticketNo)) {
        throw new Exception('Invalid ticket number');
    }

    // Validate checked by
    if (empty($checkedBy)) {
        throw new Exception('Security personnel name is required');
    }

    // Update the status in the database
    $sql = "UPDATE gatepass SET Status = 'Completed', checked_by = ? WHERE Ticket_No = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $stmt->bind_param("ss", $checkedBy, $ticketNo);
    $result = $stmt->execute();

    if ($result) {
        // Optional: Add a log entry if you want to track who completed the gate pass
        $logSql = "INSERT INTO activity_logs (activity_type, description, performed_by, reference_id) 
                  VALUES ('Gate Pass Completed', ?, ?, ?)";
        $logStmt = $conn->prepare($logSql);

        if ($logStmt) {
            $description = "Gate pass #$ticketNo was verified and marked as complete by $personnelName";
            $logStmt->bind_param("sss", $description, $checkedBy, $ticketNo);
            $logStmt->execute();
            $logStmt->close();
        }

        $response = ['success' => true, 'message' => 'Gate pass marked as completed successfully'];
    } else {
        throw new Exception('Failed to update gate pass status: ' . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
} finally {
    $conn->close();
}

// Clear any output that might have been generated
ob_end_clean();

// Send clean JSON response
echo json_encode($response);
exit;
?>
