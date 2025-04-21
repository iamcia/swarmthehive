<?php
// Set error handling to suppress PHP notices/warnings from being output directly
error_reporting(E_ERROR);
ini_set('display_errors', 0);

// Set headers for JSON response early to ensure they're sent
header('Content-Type: application/json');

// Wrap all code in try-catch to ensure JSON error response
try {
    // Include database connection
    include 'dbconn.php';
    
    // Start session for admin authentication check
    session_start();
    
    // Check if concern ID is provided
    if (!isset($_GET['id'])) {
        throw new Exception('No concern ID provided');
    }
    
    $concern_id = intval($_GET['id']);
    
    // Check if database connection exists
    if (!isset($conn) || !$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Verify the ownertenantconcerns table exists
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'ownertenantconcerns'");
    if (mysqli_num_rows($table_check) == 0) {
        throw new Exception('Concerns table not found in database');
    }
    
    // Fetch concern details
    $sql = "SELECT * FROM ownertenantconcerns WHERE ID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare query: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $concern_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute query: ' . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        throw new Exception('Concern not found');
    }
    
    $concern = mysqli_fetch_assoc($result);
    
    // Normalize status field - use concern_status if available, otherwise use status
    $concern['normalized_status'] = !empty($concern['concern_status']) ? $concern['concern_status'] : $concern['status'];
    
    // Check for media paths and prepend correct folder if needed
    if (!empty($concern['media_path']) && !preg_match('/^concern_media\//', $concern['media_path'])) {
        // If media paths don't already include the folder, add it
        $media_paths = explode(',', $concern['media_path']);
        $processed_paths = array_map(function($path) {
            return (strpos($path, '/') === false) ? 'concern_media/' . $path : $path;
        }, $media_paths);
        $concern['processed_media_path'] = implode(',', $processed_paths);
    } else {
        $concern['processed_media_path'] = $concern['media_path'];
    }
    
    // Initialize variables
    $resident_info = null;
    
    // Check for owner or tenant info
    if (!empty($concern['owner_id'])) {
        $owner_table_check = mysqli_query($conn, "SHOW TABLES LIKE 'owners'");
        if (mysqli_num_rows($owner_table_check) > 0) {
            $resident_sql = "SELECT * FROM owners WHERE owner_id = ?";
            $resident_stmt = mysqli_prepare($conn, $resident_sql);
            if ($resident_stmt) {
                mysqli_stmt_bind_param($resident_stmt, "i", $concern['owner_id']);
                if (mysqli_stmt_execute($resident_stmt)) {
                    $resident_result = mysqli_stmt_get_result($resident_stmt);
                    if (mysqli_num_rows($resident_result) > 0) {
                        $resident_info = mysqli_fetch_assoc($resident_result);
                    }
                }
            }
        }
    } elseif (!empty($concern['tenant_id'])) {
        $tenant_table_check = mysqli_query($conn, "SHOW TABLES LIKE 'tenants'");
        if (mysqli_num_rows($tenant_table_check) > 0) {
            $resident_sql = "SELECT * FROM tenants WHERE tenant_id = ?";
            $resident_stmt = mysqli_prepare($conn, $resident_sql);
            if ($resident_stmt) {
                mysqli_stmt_bind_param($resident_stmt, "i", $concern['tenant_id']);
                if (mysqli_stmt_execute($resident_stmt)) {
                    $resident_result = mysqli_stmt_get_result($resident_stmt);
                    if (mysqli_num_rows($resident_result) > 0) {
                        $resident_info = mysqli_fetch_assoc($resident_result);
                    }
                }
            }
        }
    }
    
    // Return all data as JSON
    echo json_encode([
        'concern' => $concern,
        'resident_info' => $resident_info
    ]);
    
} catch (Exception $e) {
    // Return error as JSON
    http_response_code(400); // Bad request
    echo json_encode(['error' => $e->getMessage()]);
}
?>
