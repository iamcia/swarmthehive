<?php
include 'dbconn.php';

header('Content-Type: application/json');

if (isset($_GET['email'])) {
    $email = $_GET['email'];
    
    // Check both owner and tenant tables for the email
    $sql = "SELECT 'Owner' as type FROM ownerinformation WHERE Email = ? 
            UNION ALL 
            SELECT 'Tenant' as type FROM tenantinformation WHERE Email = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(['exists' => true, 'type' => $row['type']]);
    } else {
        echo json_encode(['exists' => false]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['error' => 'No email provided']);
}
?>