<?php
include('dbconn.php');
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set PHP default timezone
date_default_timezone_set('Asia/Manila');

// Check if the form was submitted - simplified condition to catch all POST submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Connect to the database
        $conn = new mysqli($servername, $db_username, $db_password, $dbname);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Get and sanitize form data
        $residentCode = isset($_POST['resident_code']) ? trim($_POST['resident_code']) : '';
        $userType = isset($_POST['user_type']) ? trim($_POST['user_type']) : '';
        $userEmail = isset($_POST['user_email']) ? trim($_POST['user_email']) : '';
        $userNumber = isset($_POST['user_number']) ? trim($_POST['user_number']) : '';
        $unitNumber = isset($_POST['unit_number']) ? trim($_POST['unit_number']) : '';
        $concernCategory = isset($_POST['concern_type']) ? trim($_POST['concern_type']) : '';
        $concernDetails = isset($_POST['concern_details']) ? trim($_POST['concern_details']) : '';
        $concernStatus = isset($_POST['concern_status']) ? trim($_POST['concern_status']) : '';
        $signature = isset($_POST['signature']) ? trim($_POST['signature']) : '';
        $currentDateTime = date('Y-m-d H:i:s');
        
        // Validate required fields
        if (empty($residentCode) || empty($userType) || empty($concernCategory) || 
            empty($concernDetails) || empty($concernStatus)) {
            throw new Exception("Required fields are missing");
        }
        
        // Get the correct ID from ownerinformation table based on Owner_ID
        $userId = null;
        if ($userType == 'Owner') {
            $userQuery = "SELECT ID FROM ownerinformation WHERE Owner_ID = ?";
        } else {
            // For tenants, we still need to reference an owner ID since the foreign key 
            // points to ownerinformation table
            $userQuery = "SELECT o.ID 
                          FROM ownerinformation o 
                          JOIN tenantinformation t ON o.Unit_Number = t.Unit_Number 
                          WHERE t.Tenant_ID = ?";
        }
        
        $stmt = $conn->prepare($userQuery);
        if (!$stmt) {
            throw new Exception("Error preparing user query: " . $conn->error);
        }
        
        $stmt->bind_param("s", $residentCode);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $userId = $row['ID'];
        } else {
            throw new Exception("Cannot find a valid user ID for the provided resident code");
        }
        
        $stmt->close();
        
        // Default concern tracking status is 'Open' (new concern)
        $status = 'Open';
        
        // Media file handling
        $mediaPath = null;
        if(isset($_FILES['concern_media']) && $_FILES['concern_media']['error'] == 0) {
            $upload_dir = 'concern_media/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . $_FILES['concern_media']['name'];
            $file_path = $upload_dir . $file_name;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['concern_media']['tmp_name'], $file_path)) {
                $mediaPath = $file_path;
            } else {
                throw new Exception("Failed to upload image.");
            }
        }
        
        // Prepare SQL and bind parameters - updated to match the feedback table structure
        $sql = "INSERT INTO feedback 
                (concern_category, concern_details, concern_media, Created_At, 
                concern_status, status, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }
        
        $stmt->bind_param(
            "ssssssi", 
            $concernCategory,
            $concernDetails,
            $mediaPath,
            $currentDateTime,
            $concernStatus,
            $status,
            $userId
        );
        
        // Execute the statement
        if (!$stmt->execute()) {
            throw new Exception("Error executing statement: " . $stmt->error);
        }
        
        // Success message and redirect - Fixed with proper escaping
        echo "<script>
            alert(" . json_encode('Your concern has been submitted successfully!') . ");
            window.location.href = 'OwnerCommunityfeedback.php';
        </script>";
        
        $stmt->close();
        $conn->close();
        exit; // Stop execution after redirect
        
    } catch (Exception $e) {
        // Error handling - Fixed with proper escaping to prevent syntax errors
        $errorMessage = str_replace("'", "\\'", $e->getMessage());
        echo "<script>
            alert(" . json_encode('Error: ' . $errorMessage) . ");
            window.location.href = 'OwnerCommunityfeedback.php';
        </script>";
        
        if (isset($conn)) {
            $conn->close();
        }
        exit;
    }
} else {
    // Not a POST request, redirect back to the form
    header("Location: OwnerCommunityfeedback.php");
    exit;
}
?>
