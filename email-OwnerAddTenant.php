<?php
// Email notification system for tenant assignments
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Use the same PHPMailer path as forgotpassword.php
require 'PHPMailer-master/PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/PHPMailer-master/src/SMTP.php';

/**
 * Send an email notification to a tenant who has been assigned to a unit
 * 
 * @param string $tenantEmail The email address of the tenant
 * @param array $ownerInfo Information about the owner (name, email, etc.)
 * @param array $unitInfo Information about the unit (tower, unit number, etc.)
 * @param string $baseUrl The base URL of the website for constructing links
 * @return array An array with status and message
 */
function sendTenantAssignmentEmail($tenantEmail, $ownerInfo, $unitInfo, $baseUrl = '') {
    // If baseUrl is not provided, try to determine it
    if (empty($baseUrl)) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = $protocol . $host;
    }
    
    // Get owner ID from the database based on email
    global $conn;
    $ownerID = null;
    
    if (isset($ownerInfo['email'])) {
        $stmt = $conn->prepare("SELECT Owner_ID FROM ownerinformation WHERE Email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $ownerInfo['email']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $ownerID = $row['Owner_ID'];
            }
            $stmt->close();
        }
    }
    
    // Extract tower letter if it contains "Tower " prefix
    $towerValue = $unitInfo['tower'];
    if (strpos($towerValue, 'Tower ') === 0) {
        $towerValue = substr($towerValue, 6, 1); // Extract just the letter
    }
    
    // Registration link with tenant email, tower, and unit number parameters
    $registrationLink = $baseUrl . '/Register2(new).php?owner_id=' . urlencode($ownerID) . 
                      '&tower=' . urlencode($towerValue) . 
                      '&unit_number=' . urlencode($unitInfo['unit_num']);
    
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'TheHiveResidences@swarmthehive.online';
        $mail->Password = 'G#pdFHa7i';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom('TheHiveResidences@swarmthehive.online', 'SWARM - The Hive Residences');
        
        // Recipients
        $mail->setFrom('no-reply@swarmthehive.com', 'SWARM Portal');
        $mail->addAddress($tenantEmail);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'You have been assigned to a unit at The Hive Residences';
        
        // Email body HTML - updated to match forgotpassword.php design
        $ownerName = htmlspecialchars($ownerInfo['name'] ?? 'The property owner');
        $towerNumber = htmlspecialchars($towerValue);
        $unitNumber = htmlspecialchars($unitInfo['unit_num']);
        
        $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #f1c40f; border-radius: 5px;">
                <h2 style="color: #f1c40f; text-align: center;">SWARM Portal - Unit Assignment</h2>
                <p>Hello,</p>
                <p>You have been assigned as a tenant to a unit at <strong>The Hive Residences</strong> by ' . $ownerName . '.</p>
                <p><strong>Unit Details:</strong></p>
                <div style="text-align: center; padding: 10px; background-color: #f8f9fa; margin: 20px 0;">
                    <p style="margin: 5px 0;"><strong>Tower:</strong> ' . $towerNumber . '</p>
                    <p style="margin: 5px 0;"><strong>Unit Number:</strong> ' . $unitNumber . '</p>
                </div>
                <p>To complete your registration and access the SWARM tenant portal, please click the button below:</p>
                <div style="text-align: center; margin: 25px 0;">
                    <a href="' . $registrationLink . '" style="background-color: #f1c40f; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">Register as Tenant</a>
                </div>
                <p>If you believe this email was sent to you by mistake, please ignore this email or contact support.</p>
                <p>Thank you,<br>SWARM Portal Team</p>
            </div>
        ';
        
        // Plain text version for non-HTML mail clients
        $mail->AltBody = "Hello,\n\n"
            . "You have been assigned as a tenant to a unit at The Hive Residences by {$ownerName}.\n\n"
            . "Unit Details:\n"
            . "Tower: {$towerNumber}\n"
            . "Unit Number: {$unitNumber}\n\n"
            . "To complete your registration and access the SWARM tenant portal, please visit:\n"
            . "{$registrationLink}\n\n"
            . "If you believe this email was sent to you by mistake, please disregard it or contact the property management.\n\n"
            . "Thank you,\n"
            . "SWARM Portal Team";
        
        $mail->send();
        return [
            'status' => 'success',
            'message' => "Email notification has been sent to {$tenantEmail}"
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => "Failed to send email notification: {$mail->ErrorInfo}"
        ];
    }
}

// If this file is called directly, provide a test function
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    // For testing purposes only
    function testEmailSend() {
        $tenantEmail = 'test@example.com';
        $ownerInfo = [
            'name' => 'John Doe',
            'email' => 'owner@example.com'
        ];
        $unitInfo = [
            'tower' => 'A',
            'unit_num' => '101'
        ];
        
        $result = sendTenantAssignmentEmail($tenantEmail, $ownerInfo, $unitInfo);
        echo '<pre>';
        print_r($result);
        echo '</pre>';
    }
    
    // Uncomment to test the email sending
    // testEmailSend();
    
    echo '<h1>Email-OwnerAddTenant.php</h1>';
    echo '<p>This file provides email notification functionality for tenant assignments.</p>';
    echo '<p>To use this functionality, include this file and call the sendTenantAssignmentEmail() function.</p>';
}
?>