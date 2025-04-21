<?php
include 'dbconn.php';

// Check if it's an AJAX request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'checkEmail') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $response = array('exists' => false);

    if (!empty($email)) {
        // Check in owner table
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM ownerinformation WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $ownerResult = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Check in tenant table
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tenantinformation WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $tenantResult = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Set the response based on the results
        $response['exists'] = ($ownerResult['count'] > 0 || $tenantResult['count'] > 0);
    }

    // Return the JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// If this file is accessed directly without the proper request, redirect to the dashboard
header("Location: adm-dashboard.php");
exit;
?>