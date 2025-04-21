<?php
include('dbconn.php');
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_concern'])) {
    $conn = new mysqli($servername, $db_username, $db_password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get form data
    $resident_code = $_POST['resident_code'];
    $user_type = $_POST['user_type'];
    $user_email = $_POST['user_email'];
    $user_number = $_POST['user_number'];
    $unit_number = $_POST['unit_number'];
    $concern_type = $_POST['concern_type'];
    $concern_details = $_POST['concern_details'];
    $signature = $_POST['signature'];
    $concern_status = $_POST['concern_status'];
    $status = 'Pending'; // Initial status
    $media_path = null;

    // Handle file upload if exists
    if (isset($_FILES['concern_media']) && $_FILES['concern_media']['error'] == 0) {
        $target_dir = "uploads/concerns/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['concern_media']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('concern_') . '.' . $file_extension;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES['concern_media']['tmp_name'], $target_file)) {
            $media_path = $target_file;
        }
    }

    // Prepare SQL statement
    $sql = "INSERT INTO ownertenantconcerns (
        Resident_Code, user_type, user_email, user_number, unit_number,
        concern_type, concern_details, signature, status, concern_status,
        media_path
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssss",
        $resident_code, $user_type, $user_email, $user_number, $unit_number,
        $concern_type, $concern_details, $signature, $status, $concern_status,
        $media_path
    );

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Your concern has been submitted successfully!";
        header("Location: OwnerCommunityfeedback.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error submitting concern: " . $stmt->error;
        header("Location: OwnerCommunityfeedback.php");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: OwnerCommunityfeedback.php");
    exit();
}
?>
