<?php
// Enable all error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// For debugging - log the request
error_log("Request received for get-workpermit.php with ID: " . (isset($_GET['id']) ? $_GET['id'] : 'none'));

// Connection test
if (isset($_GET['id']) && $_GET['id'] === 'test') {
    echo json_encode(["success" => true, "message" => "Connection test successful"]);
    exit;
}

// Include database connection
try {
    include 'dbconn.php';
    
    if (!isset($conn) || $conn->connect_error) {
        echo json_encode([
            "error" => "Database connection failed", 
            "details" => isset($conn) ? $conn->connect_error : "Connection variable not set"
        ]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(["error" => "Exception including database: " . $e->getMessage()]);
    exit;
}

// Process the request
if(isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id']; // Ensure integer type
    
    try {
        // Fetch work permit details
        $sql = "SELECT * FROM workpermit WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            echo json_encode(["error" => "Prepare failed: " . $conn->error]);
            exit;
        }
        
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        
        if (!$success) {
            echo json_encode(["error" => "Execute failed: " . $stmt->error]);
            exit;
        }
        
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            // Return the work permit details as JSON
            $data = $result->fetch_assoc();
            echo json_encode($data);
        } else {
            echo json_encode([
                "error" => "Work permit not found",
                "id" => $id,
                "sql" => "SELECT * FROM workpermit WHERE id = " . $id
            ]);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(["error" => "Exception processing request: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid or missing ID parameter"]);
}

// Close connection
if (isset($conn)) {
    $conn->close();
}
?>
