<?php
// Set error handling to suppress PHP notices/warnings from being output directly
error_reporting(E_ERROR);
ini_set('display_errors', 0);

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Include database connection
    include 'dbconn.php';
    
    // Start session for admin authentication check
    session_start();
    
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Check if required data is provided
    if (!isset($_POST['concern_id']) || !isset($_POST['comment']) || empty($_POST['comment'])) {
        throw new Exception('Missing required data');
    }
    
    $concern_id = intval($_POST['concern_id']);
    $comment = $_POST['comment'];
    $admin_id = $_SESSION['admin_id'] ?? 1; // Default to admin ID 1 if not set
    
    // Check if database connection exists
    if (!isset($conn) || !$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Check if concern exists
    $concern_check = mysqli_prepare($conn, "SELECT ID FROM ownertenantconcerns WHERE ID = ?");
    if (!$concern_check) {
        throw new Exception('Failed to prepare concern check query');
    }
    
    mysqli_stmt_bind_param($concern_check, "i", $concern_id);
    if (!mysqli_stmt_execute($concern_check)) {
        throw new Exception('Failed to execute concern check query');
    }
    
    $concern_result = mysqli_stmt_get_result($concern_check);
    if (mysqli_num_rows($concern_result) == 0) {
        throw new Exception('Concern not found');
    }
    
    // Check if concern_comments table exists
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'concern_comments'");
    if (mysqli_num_rows($table_check) == 0) {
        // Create the table if it doesn't exist
        $create_table_sql = "CREATE TABLE concern_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            concern_id INT NOT NULL,
            admin_id INT NOT NULL,
            comment TEXT NOT NULL,
            created_at DATETIME NOT NULL
        )";
        
        if (!mysqli_query($conn, $create_table_sql)) {
            throw new Exception('Failed to create comments table');
        }
    }
    
    // Insert comment into database
    $comment_sql = "INSERT INTO concern_comments (concern_id, admin_id, comment, created_at) 
                   VALUES (?, ?, ?, NOW())";
    $comment_stmt = mysqli_prepare($conn, $comment_sql);
    if (!$comment_stmt) {
        throw new Exception('Failed to prepare comment insert query');
    }
    
    mysqli_stmt_bind_param($comment_stmt, "iis", $concern_id, $admin_id, $comment);
    
    if (!mysqli_stmt_execute($comment_stmt)) {
        throw new Exception('Failed to save comment: ' . mysqli_stmt_error($comment_stmt));
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Comment added successfully'
    ]);
    
} catch (Exception $e) {
    // Return error as JSON
    http_response_code(400); // Bad request
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
