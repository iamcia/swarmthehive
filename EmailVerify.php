<?php
include 'dbconn.php';
session_start();

require 'PHPMAILER/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_email'])) {
    if (!empty($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $email = trim($_POST['email']);
    } else {
        echo "<script>alert('Please enter a valid email.'); window.location.href='EmailVerify.php';</script>";
        exit;
    }

    // Generate secure token
    $token = bin2hex(random_bytes(32));
    $_SESSION['email_token'] = $token;
    $_SESSION['reset_email'] = $email;

    // Password reset link
    $reset_link = "https://swarmthehive.online/ForgotPass.php?token=$token";

    // Initialize PHPMailer
    $mail = new PHPMailer(true);

    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'TheHiveResidences@swarmthehive.online'; 
        $mail->Password = 'G#pdFHa7i'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587; 

        // Sender and recipient
        $mail->setFrom('TheHiveResidences@swarmthehive.online', 'TheHiveResidences');
        $mail->addAddress($email);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Code - SWARM Portal';
        $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #f1c40f; border-radius: 5px;">
                <h2 style="color: #f1c40f; text-align: center;">SWARM Portal Password Reset</h2>
                <p>Hello,</p>
                <p>We received a request to reset your password. Please click the button below:</p>
                <p style="text-align: center; margin: 20px 0;">
                    <a href="' . $reset_link . '" style="display: inline-block; padding: 10px 20px; background-color: #f1c40f; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">
                        Click Here to Reset Password
                    </a>
                </p>
                <p>This link will expire in 1 hour.</p>
                <p>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
                <p>Thank you,<br>SWARM Portal Team</p>
            </div>
        ';

        // Send email
        if ($mail->send()) {
            echo "<script>alert('An email has been sent to your address with a password reset link.'); window.location.href='EmailVerify.php';</script>";
        } else {
            error_log("Mailer Error: " . $mail->ErrorInfo);
            echo "<script>alert('Email could not be sent. Please try again later.'); window.location.href='EmailVerify.php';</script>";
        }
    } catch (Exception $e) {
        error_log("Mailer Exception: " . $mail->ErrorInfo);
        echo "<script>alert('Message could not be sent. Please try again later.'); window.location.href='EmailVerify.php';</script>";
    }
}

// Close database connection only if it's set
if (isset($conn)) {
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <link rel="stylesheet" href="LogStyle.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <div class="brand-logo"></div>
        <div class="brand-title">SWARM</div>
        <div class="inputs">
            <h2>Email Verification</h2>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <label>Enter your email</label>
                <input type="email" name="email" placeholder="Enter your email" required>
                <button type="submit" name="verify_email">Send Verification Email</button>
            </form>
        </div>
    </div>

    <div class="bee bee1"></div>
    <div class="bee bee2"></div>
    <div class="bee bee3"></div>
    <div class="bee bee4"></div>

    <script>
        function openChatbotPopup() {
            window.open("OwnerTenantChatbot4.php", "chatbotPopup", "width=500,height=500,top=300,left=300");
        }
    </script>
</body>
</html>
