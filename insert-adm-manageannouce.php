<?php
include 'dbconn.php';

session_start();

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Get form data and sanitize it
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $body = mysqli_real_escape_string($conn, $_POST['message']); // Form field still called 'message'
    $status = 'Approved'; // Default status for new announcements
    
    // Get end_date if provided
    $end_date = !empty($_POST['end_date']) ? mysqli_real_escape_string($conn, $_POST['end_date']) : null;

    // Handle file upload (now called media)
    $media = null;
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] == 0) {
        // Create a timestamped filename to avoid duplicates
        $timestamp = time();
        $filename = $timestamp . '_' . basename($_FILES['picture']['name']);
        
        $targetDir = "announcement_media/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true); // Create directory if it doesn't exist
        }
        $targetFile = $targetDir . $filename;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        
        // Check if the file is an image or PDF
        $isValidFile = false;
        $fileType = $_FILES['picture']['type'];
        
        // Allow images (checked with getimagesize) or PDFs
        if ($fileType === 'application/pdf') {
            $isValidFile = true;
        } else {
            // Try to validate as image
            $check = getimagesize($_FILES['picture']['tmp_name']);
            $isValidFile = ($check !== false);
        }
        
        if ($isValidFile) {
            // Check file size (5MB max)
            if ($_FILES['picture']['size'] <= 5000000) {
                // Try to upload the file
                if (move_uploaded_file($_FILES['picture']['tmp_name'], $targetFile)) {
                    $media = $targetFile;
                    
                    // Log the upload success
                    error_log("File uploaded successfully: $targetFile");
                } else {
                    $_SESSION['message'] = "Sorry, there was an error uploading your file.";
                    error_log("File upload failed: " . error_get_last()['message']);
                }
            } else {
                $_SESSION['message'] = "Sorry, your file is too large. Maximum file size is 5MB.";
            }
        } else {
            $_SESSION['message'] = "Invalid file type. Please upload only images (JPG, PNG, GIF) or PDF files.";
        }
    }

    // Create audit log entry for tracking
    $admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 'unknown';
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $audit_message = "Admin $admin_id created announcement: '$title'";
    
    // Insert into audit logs table if you have one
    // $sql_audit = "INSERT INTO audit_logs (user_id, action, ip_address, created_at) VALUES ('$admin_id', '$audit_message', '$user_ip', NOW())";
    // $conn->query($sql_audit);

    // Updated SQL with new column names to insert the announcement
    $sql = "INSERT INTO announcements (title, body, created_at, end_date, status, media) 
            VALUES ('$title', '$body', NOW(), " . ($end_date ? "'$end_date'" : "NULL") . ", '$status', " . ($media ? "'$media'" : "NULL") . ")";
    
    if ($conn->query($sql) === TRUE) {
        $_SESSION['message'] = "Announcement posted successfully.";
    } else {
        $_SESSION['message'] = "Error posting announcement: " . $conn->error;
        error_log("Database error: " . $conn->error);
    }
    
    $conn->close();
    
    // Redirect back to the announcement management page
    header("Location: adm-manageannounce.php");
    exit();
} else {
    // If someone tries to access this file directly, redirect them
    header("Location: adm-manageannounce.php");
    exit();
}
?>
