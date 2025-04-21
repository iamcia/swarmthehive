<?php
include('dbconn.php');
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set PHP default timezone
date_default_timezone_set('Asia/Manila');

// Set the MySQL session time zone to Philippines time
$conn->query("SET time_zone = '+08:00'");

$message = '';
$lockoutTime = 20; // 20 seconds lockout
$maxAttempts = 3; // Maximum attempts before the timeout

// Initialize login attempt tracking
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if (!isset($_SESSION['last_attempt_time'])) {
    $_SESSION['last_attempt_time'] = time();
}

// Handle login POST request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $currentAttemptTime = time();
    $timeSinceLastAttempt = $currentAttemptTime - $_SESSION['last_attempt_time'];

    // Check if the user needs to wait before retrying
    if ($_SESSION['login_attempts'] >= $maxAttempts && $timeSinceLastAttempt < $lockoutTime) {
        $message = "Too many failed attempts. Please wait " . ($lockoutTime - $timeSinceLastAttempt) . " seconds before retrying.";
    } else {
        if ($timeSinceLastAttempt >= $lockoutTime) {
            // Reset attempts after lockout period
            $_SESSION['login_attempts'] = 0;
        }

        $username = htmlspecialchars($_POST['username']);
        $password = $_POST['password']; // Don't use htmlspecialchars for passwords that will be verified
        $lastName = "";
        $firstName = "";
        $userID = ""; // Will store OWNER_ID or TENANT_ID
        $status = '';
        $redirectUrl = '';
        $table = '';
        $authenticated = false;

        // Check in ownerinformation table - only search by username
        $sql = "SELECT * FROM ownerinformation WHERE Username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // User found in ownerinformation, verify password
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['Password'])) {
                $lastName = $row['Last_Name'];
                $firstName = $row['First_Name'];
                $status = $row['Status'];
                $userID = $row['Owner_ID'];
                $redirectUrl = 'OwnerAnnouncement.php';
                $_SESSION['username'] = $username;
                $_SESSION['user_id'] = $userID;
                $table = 'ownerinformation';
                $authenticated = true;
            }
        }

        // If not authenticated yet, check in tenantinformation table
        if (!$authenticated) {
            $sql = "SELECT * FROM tenantinformation WHERE Username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // User found in tenantinformation, verify password
                $row = $result->fetch_assoc();
                if (password_verify($password, $row['Password'])) {
                    $lastName = $row['Last_Name'];
                    $firstName = $row['First_Name'];
                    $status = $row['Status'];
                    $userID = $row['Tenant_ID'];
                    $redirectUrl = 'ten-announcement.php';
                    $_SESSION['username'] = $username;
                    $_SESSION['user_id'] = $userID;
                    $table = 'tenantinformation';
                    $authenticated = true;
                }
            }
        }

        // If not authenticated, show error message
        if (!$authenticated) {
            $message = "Invalid username or password.";
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
            
            if ($_SESSION['login_attempts'] >= $maxAttempts) {
                $message = "Too many failed attempts. Please wait $lockoutTime seconds before retrying.";
            }
        }

        // If user is successfully authenticated, insert or update the audittrail
        if ($authenticated) {
            // Check if an entry already exists in audittrail
            $auditCheckSql = "SELECT * FROM audittrail WHERE Username = ? AND Last_Name = ? AND First_Name = ?";
            $auditCheckStmt = $conn->prepare($auditCheckSql);
            $auditCheckStmt->bind_param("sss", $username, $lastName, $firstName);
            $auditCheckStmt->execute();
            $auditResult = $auditCheckStmt->get_result();

            if ($auditResult->num_rows > 0) {
                // User exists in audittrail, update Last_Login
                $updateAuditSql = "UPDATE audittrail SET Last_Login = NOW() WHERE Username = ? AND Last_Name = ? AND First_Name = ?";
                $updateAuditStmt = $conn->prepare($updateAuditSql);
                $updateAuditStmt->bind_param("sss", $username, $lastName, $firstName);
                $updateAuditStmt->execute();
                $updateAuditStmt->close();
            } else {
                // User does not exist in audittrail, insert new record
                $insertAuditSql = "INSERT INTO audittrail (Username, Last_Name, First_Name, Last_Login) VALUES (?, ?, ?, NOW())";
                $insertAuditStmt = $conn->prepare($insertAuditSql);
                $insertAuditStmt->bind_param("sss", $username, $lastName, $firstName);
                $insertAuditStmt->execute();
                $insertAuditStmt->close();
            }
            $auditCheckStmt->close();

            // Handle different statuses
            if ($status == 'Pending') {
                echo "<script type='text/javascript'>
                        alert('Your account is pending. Most of the tabs are locked.');
                        window.location.href = '$redirectUrl';
                      </script>";
                exit();
            } elseif ($status == 'Disapproved') {
                $redirectUrl = ($table === 'ownerinformation') ? 'Register1(Disapprove).php' : 'Register2(Disapprove).php';
                echo "<script type='text/javascript'>
                        alert('Your account has been disapproved. Please update your RIS information.');
                        window.location.href = '$redirectUrl';
                      </script>";
                exit();
            } elseif ($status == 'Approved') {
                echo "<script type='text/javascript'>
                        window.location.href = '$redirectUrl';
                      </script>";
                exit();
            }
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swarm | Login</title>
      <link rel="stylesheet" href="LogStyle.css?v=<?php echo time(); ?>">
    <script>
    function toggleForgotPassword() {
        var forgotPasswordBox = document.querySelector('.forgot-password-box');
        forgotPasswordBox.style.display = forgotPasswordBox.style.display === 'none' ? 'block' : 'none';
    }

    window.onload = function() {
        var loginAttempts = <?php echo $_SESSION['login_attempts']; ?>;
        var maxAttempts = <?php echo $maxAttempts; ?>;
        var timeSinceLastAttempt = <?php echo $timeSinceLastAttempt; ?>;
        var lockoutTime = <?php echo $lockoutTime; ?>;
        var loginForm = document.querySelector('.login-box form');
        var usernameInput = document.querySelector('input[name="username"]');
        var passwordInput = document.querySelector('input[name="password"]');
        var messageBox = document.querySelector('#invalid-password-msg');

        if (loginAttempts >= maxAttempts && timeSinceLastAttempt < lockoutTime) {
            usernameInput.disabled = true;
            passwordInput.disabled = true;
            setTimeout(function() {
                usernameInput.disabled = false;
                passwordInput.disabled = false;
                messageBox.innerHTML = "";
            }, (lockoutTime - timeSinceLastAttempt) * 1000);
        }

        if (loginAttempts >= maxAttempts + 1) {
            usernameInput.disabled = true;
            passwordInput.disabled = true;
            messageBox.innerHTML = "Too many failed attempts. You must reset your password.";
        }

        <?php if (!empty($showForgotPassword)): ?>
        toggleForgotPassword();
        <?php endif; ?>
    };
</script>

</head>
<body>
    
<script> window.chtlConfig = { chatbotId: "4832571958" } </script>
<script async data-id="4832571958" id="chatling-embed-script" type="text/javascript" src="https://chatling.ai/js/embed.js"></script>

<div class="container">
        <div class="brand-logo"></div>
        <div class="brand-title">SWARM</div>
        <div class="inputs">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <label>USERNAME</label>
                <input type="text" name="username" placeholder="Example123" />
                <label>PASSWORD</label>
                <input type="password" name="password" placeholder="Min 6 characters long" />
                <p id="invalid-password-msg" style="display: inline-block;"><?php echo $message; ?></p>
                <button type="submit" name="login" value="Login">LOGIN</button>
            </form>
            <button class="forgot-password-button" onclick="window.location.href='forgotpassword.php'">FORGOT PASSWORD?</button>
            <div class="forgot-password-box" style="display: none;">
                <h2>Reset Password</h2>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="newPassword" placeholder="New Password" required>
                    <input type="submit" name="reset" value="Reset Password">
                </form>
            </div>
        </div>
    </div>
    
    
      <!-- Add bees as divs or images -->
      <div class="bee bee1"></div>
      <div class="bee bee2"></div>
      <div class="bee bee3"></div>
      <div class="bee bee4"></div>

<script>
    function openChatbotPopup() {
      // Open a popup window for the chatbot page
      window.open("OwnerTenantChatbot4.php", "chatbotPopup", "width=500,height=500,top=300,left=300");
    }
  </script>
</body>
</html>