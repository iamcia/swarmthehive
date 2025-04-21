<?php
include 'dbconn.php';

// Get parameters
$id = isset($_GET['id']) ? trim($_GET['id']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';

// Validate parameters
if (empty($id) || empty($type)) {
    header("HTTP/1.0 400 Bad Request");
    die("Missing or invalid parameters");
}

// Sanitize input to prevent SQL injection
if (!in_array($type, ['Owner', 'Tenant'])) {
    header("HTTP/1.0 400 Bad Request");
    die("Invalid user type");
}

// Determine which table to query
$table = ($type == 'Owner') ? 'ownerinformation' : 'tenantinformation';
$id_field = ($type == 'Owner') ? 'Owner_ID' : 'Tenant_ID';

try {
    // Prepare and execute the query to get the Signature filename
    $sql = "SELECT Signature FROM $table WHERE $id_field = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Preparation failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execution failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['Signature'])) {
            // Redirect to the actual file
            header("Location: Signature/" . $row['Signature']);
            exit;
        } else {
            header("HTTP/1.0 404 Not Found");
            die("Signature not found");
        }
    } else {
        header("HTTP/1.0 404 Not Found");
        die("User not found");
    }
    
    $stmt->close();
} catch (Exception $e) {
    header("HTTP/1.0 500 Internal Server Error");
    die("Database error: " . $e->getMessage());
} finally {
    $conn->close();
}
?>
