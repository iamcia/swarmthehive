<?php
require 'dbconn.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/PHPMailer-master/src/SMTP.php';

session_start();


$message = '';
$errorMessage = '';
$showVerificationForm = false;

// Handle the form submission for requesting a reset code
if (isset($_POST['request_reset'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    
    // Validate inputs
    if (empty($username) || empty($email)) {
        $errorMessage = "Both username and email are required";
    } else {
        // Check if user exists with given username and email
        $stmt = $conn->prepare("SELECT ID, Email FROM ownerinformation WHERE Username = ? AND Email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Generate a random 6-digit code
            $reset_code = mt_rand(100000, 999999);
            $expiry = date('Y-m-d H:i:s', time() + 3600); // Code expires in 1 hour
            
            // Store the code in the database
            $updateStmt = $conn->prepare("UPDATE ownerinformation SET reset_code = ?, reset_code_expiry = ? WHERE ID = ?");
            $updateStmt->bind_param("ssi", $reset_code, $expiry, $user['ID']);
            
            if ($updateStmt->execute()) {
                // Send email with reset code
                $mail = new PHPMailer(true);
                
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';  // Replace with your SMTP server
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'seanreptimiguel.ticzon@my.jru.edu'; // Replace with your email
                    $mail->Password   = 'zlgl daxk lspo yebb';   // Replace with your email password or app password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    
                    // Recipients
                    $mail->setFrom('no-reply@swarmthehive.com', 'SWARM Portal');
                    $mail->addAddress($user['Email']);
                    
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Code - SWARM Portal';
                    $mail->Body    = '
                        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #f1c40f; border-radius: 5px;">
                            <h2 style="color: #f1c40f; text-align: center;">SWARM Portal Password Reset</h2>
                            <p>Hello,</p>
                            <p>We received a request to reset your password. Your verification code is:</p>
                            <div style="text-align: center; padding: 10px; background-color: #f8f9fa; font-size: 24px; font-weight: bold; letter-spacing: 5px; margin: 20px 0;">
                                ' . $reset_code . '
                            </div>
                            <p>This code will expire in 1 hour.</p>
                            <p>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
                            <p>Thank you,<br>SWARM Portal Team</p>
                        </div>
                    ';
                    
                    $mail->send();
                    $_SESSION['reset_username'] = $username;
                    $showVerificationForm = true;
                    $message = "A verification code has been sent to your email address.";
                } catch (Exception $e) {
                    $errorMessage = "Error sending email: {$mail->ErrorInfo}";
                }
            } else {
                $errorMessage = "System error. Please try again later.";
            }
        } else {
            $errorMessage = "No account found with that username and email combination.";
        }
    }
}

// Handle verification code submission and password reset
if (isset($_POST['reset_password'])) {
    $verification_code = trim($_POST['verification_code']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    $username = $_SESSION['reset_username'] ?? '';
    
    // Validate inputs
    if (empty($verification_code) || empty($new_password) || empty($confirm_password)) {
        $errorMessage = "All fields are required";
        $showVerificationForm = true;
    } elseif ($new_password !== $confirm_password) {
        $errorMessage = "Passwords do not match";
        $showVerificationForm = true;
    } elseif (strlen($new_password) < 8) {
        $errorMessage = "Password must be at least 8 characters long";
        $showVerificationForm = true;
    } else {
        // Verify the code
        $stmt = $conn->prepare("SELECT ID FROM ownerinformation WHERE Username = ? AND reset_code = ? AND reset_code_expiry > NOW()");
        $stmt->bind_param("ss", $username, $verification_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update the password and clear the reset code
            $updateStmt = $conn->prepare("UPDATE ownerinformation SET Password = ?, reset_code = NULL, reset_code_expiry = NULL WHERE ID = ?");
            $updateStmt->bind_param("si", $hashed_password, $user['ID']);
            
            if ($updateStmt->execute()) {
                $message = "Your password has been successfully reset. You can now <a href='login.php'>login</a> with your new password.";
                $showVerificationForm = false;
                unset($_SESSION['reset_username']);
            } else {
                $errorMessage = "Error updating password. Please try again.";
                $showVerificationForm = true;
            }
        } else {
            $errorMessage = "Invalid or expired verification code";
            $showVerificationForm = true;
        }
    }
}
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
