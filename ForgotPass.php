<?php
require 'dbconn.php';
require 'PHPMAILER/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

$showVerificationForm = false; // Initialize to false

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['request_reset'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);

        if (empty($username) || empty($email)) {
            $_SESSION['errorMessage'] = "Both username and email are required";
        } else {
            $conn->begin_transaction(); // Start transaction

            try {
                $stmt = $conn->prepare("SELECT Owner_ID AS ID, Email FROM ownerinformation WHERE Username = ? AND Email = ?");
                $stmt->bind_param("ss", $username, $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    $table = "ownerinformation";
                } else {
                    $stmt = $conn->prepare("SELECT Tenant_ID AS ID, Email FROM tenantinformation WHERE Username = ? AND Email = ?");
                    $stmt->bind_param("ss", $username, $email);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows === 1) {
                        $user = $result->fetch_assoc();
                        $table = "tenantinformation";
                    } else {
                        $_SESSION['errorMessage'] = "No account found with that username and email combination.";
                        header("Location: ForgotPass.php");
                        exit();
                    }
                }

                $reset_code = mt_rand(100000, 999999);
                $expiry = date('Y-m-d H:i:s', time() + 3600);

                $updateStmt = $conn->prepare("UPDATE $table SET reset_code = ?, reset_code_expiry = ? WHERE " . ($table == "ownerinformation" ? "Owner_ID" : "Tenant_ID") . " = ?");
                $updateStmt->bind_param("ssi", $reset_code, $expiry, $user['ID']);

                if ($updateStmt->execute()) {
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.hostinger.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'TheHiveResidences@swarmthehive.online';
                        $mail->Password = 'G#pdFHa7i';
                        $mail->SMTPSecure = 'tls';
                        $mail->Port = 587;
                        $mail->setFrom('TheHiveResidences@swarmthehive.online', 'TheHiveResidences');
                        $mail->addAddress($user['Email']);
                        $mail->isHTML(true);
                        $mail->Subject = 'Password Reset Code - SWARM Portal';
                        $mail->Body = "
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #f1c40f; border-radius: 5px;'>
                                <h2 style='color: #f1c40f; text-align: center;'>SWARM Portal Password Reset</h2>
                                <p>Hello,</p>
                                <p>We received a request to reset your password. Your verification code is:</p>
                                <div style='text-align: center; padding: 10px; background-color: #f8f9fa; font-size: 24px; font-weight: bold; letter-spacing: 5px; margin: 20px 0;'>
                                    $reset_code
                                </div>
                                <p>This code will expire in 1 hour.</p>
                                <p>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
                                <p>Thank you,<br>SWARM Portal Team</p>
                            </div>
                        ";
                        $mail->send();

                        $_SESSION['reset_username'] = $username;
                        $_SESSION['reset_table'] = $table;
                        $_SESSION['message'] = "A verification code has been sent to your email address.";
                        $_SESSION['alert'] = "success";

                        $conn->commit(); // Commit transaction

                        $showVerificationForm = true; // Show verification form
                    } catch (Exception $e) {
                        $_SESSION['errorMessage'] = "Error sending email: {$mail->ErrorInfo}";
                        $conn->rollback(); // Rollback transaction
                    }
                } else {
                    $_SESSION['errorMessage'] = "System error. Please try again later.";
                    $conn->rollback(); // Rollback transaction
                }
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['errorMessage'] = "An unexpected error occurred.";
            }
        }
    }

if (isset($_POST['reset_password'])) {
    $showVerificationForm = true;

    $verification_code = trim($_POST['verification_code']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    $username = $_SESSION['reset_username'] ?? '';
    $table = $_SESSION['reset_table'] ?? '';

    if (empty($verification_code) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['errorMessage'] = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['errorMessage'] = "Passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $_SESSION['errorMessage'] = "Password must be at least 8 characters long.";
    } else {
        // Fetch user info to verify email
        $stmt = $conn->prepare("SELECT " . ($table == "ownerinformation" ? "Owner_ID, Email" : "Tenant_ID, Email") . " FROM $table WHERE Username = ? AND reset_code = ? AND reset_code_expiry > NOW()");
        $stmt->bind_param("ss", $username, $verification_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $user_id = $user[($table == "ownerinformation") ? 'Owner_ID' : 'Tenant_ID'];
            $email = $user['Email'];  // Fetch the associated email

            // Ensure update is applied only to the verified user
            $updateStmt = $conn->prepare("UPDATE $table SET Password = ?, reset_code = NULL, reset_code_expiry = NULL WHERE " . ($table == "ownerinformation" ? "Owner_ID" : "Tenant_ID") . " = ? AND Username = ? AND Email = ?");
            $updateStmt->bind_param("siss", $new_password, $user_id, $username, $email);

            if ($updateStmt->execute() && $updateStmt->affected_rows > 0) {
                $_SESSION['message'] = "Your password has been successfully reset. You can now <a href='login.php'>login</a> with your new password.";
                $_SESSION['alert'] = "success";
                unset($_SESSION['reset_username']);
                unset($_SESSION['reset_table']);
                $showVerificationForm = false;
            } else {
                $_SESSION['errorMessage'] = "Error updating password. Please try again.";
            }
        } else {
            $_SESSION['errorMessage'] = "Invalid or expired verification code.";
        }
    }
}
}
$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | SWARM Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'swarm-yellow': '#f1c40f',
                        'swarm-dark-yellow': '#e1b00f',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-swarm-yellow to-swarm-dark-yellow p-4">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-white">SWARM Portal</h2>
                <p class="text-white opacity-80">Password Reset</p>
            </div>
        </div>
        
        <div class="p-6">
            <?php if (!empty($message)): ?>
                <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errorMessage)): ?>
                <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
                    <?php echo $errorMessage; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$showVerificationForm): ?>
            <!-- Request Reset Form -->
            <form method="post" action="">
                <div class="mb-4">
                    <label for="username" class="block text-gray-700 font-medium mb-2">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" id="username" name="username" class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-swarm-yellow" placeholder="Enter your username" required>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" id="email" name="email" class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-swarm-yellow" placeholder="Enter your email address" required>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <button type="submit" name="request_reset" class="w-full bg-swarm-yellow hover:bg-swarm-dark-yellow text-white font-medium py-2 px-4 rounded-md transition-colors">
                        Send Reset Code
                    </button>
                </div>
            </form>
            <?php else: ?>
            <!-- Verification and Password Reset Form -->
            <form method="post" action="">
                <div class="mb-4">
                    <label for="verification_code" class="block text-gray-700 font-medium mb-2">Verification Code</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-key text-gray-400"></i>
                        </div>
                        <input type="text" id="verification_code" name="verification_code" class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-swarm-yellow" placeholder="Enter 6-digit code" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="new_password" class="block text-gray-700 font-medium mb-2">New Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="new_password" name="new_password" class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-swarm-yellow" placeholder="Enter new password" required minlength="8">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Password must be at least 8 characters long</p>
                </div>
                
                <div class="mb-6">
                    <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="confirm_password" name="confirm_password" class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-swarm-yellow" placeholder="Confirm your password" required>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <button type="submit" name="reset_password" class="w-full bg-swarm-yellow hover:bg-swarm-dark-yellow text-white font-medium py-2 px-4 rounded-md transition-colors">
                        Reset Password
                    </button>
                </div>
            </form>
            <?php endif; ?>
            
            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    <a href="index.php" class="text-swarm-yellow hover:underline">Back to Login</a>
                </p>
            </div>
        </div>
    </div>
    
    <script>
        // Show password functionality
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
        }
    </script>
</body>
</html>
