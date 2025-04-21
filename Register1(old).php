<?php
session_start();
$servername = "localhost";
$db_username = "u113232969_Hives"; 
$db_password = "theSwarm4"; 
$dbname = "u113232969_SWARM";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sessionEmail = $_SESSION['email'];
$emailReadonly = false;

// Check if the email exists in the OwnerInformation or TenantInformation table
$sqlCheckOwner = "SELECT Email FROM ownerinformation WHERE Owner_ID = ?";
$sqlCheckTenant = "SELECT Email FROM tenantinformation WHERE Tenant_ID = ?";

$stmtOwner = $conn->prepare($sqlCheckOwner);
$stmtOwner->bind_param("s", $_SESSION['Owner_ID']);
$stmtOwner->execute();
$stmtOwner->bind_result($ownerEmail);
$stmtOwner->fetch();
$stmtOwner->close();

$stmtTenant = $conn->prepare($sqlCheckTenant);
$stmtTenant->bind_param("s", $_SESSION['Tenant_ID']);
$stmtTenant->execute();
$stmtTenant->bind_result($tenantEmail);
$stmtTenant->fetch();
$stmtTenant->close();

if ($ownerEmail) {
    $sessionEmail = $ownerEmail;
    $emailReadonly = true;
} elseif ($tenantEmail) {
    $sessionEmail = $tenantEmail;
    $emailReadonly = true;
}

// Form handling logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accessCode = $_SESSION['accessCode'];
    $tower = $_SESSION['tower'];
    $unitNumber = $_SESSION['unitNumber'];
    $username = htmlspecialchars($_POST['username']);
    $email = $sessionEmail; // Email is read-only
    $password = htmlspecialchars($_POST['password']); 

    // Prepare and execute owner update statement
    $sqlOwner = "UPDATE ownerinformation SET Username = ?, Password = ?, Tower = ?, Unit_Number = ? WHERE Access_Code = ?";
    $stmtOwner = $conn->prepare($sqlOwner);
    $stmtOwner->bind_param("sssss", $username, $password, $tower, $unitNumber, $accessCode);
    $ownerUpdated = $stmtOwner->execute();
    $stmtOwner->close();

    // Prepare and execute tenant update statement
    $sqlTenant = "UPDATE tenantinformation SET Username = ?, Password = ?, Tower = ?, Unit_Number = ? WHERE Access_Code = ?";
    $stmtTenant = $conn->prepare($sqlTenant);
    $stmtTenant->bind_param("sssss", $username, $password, $tower, $unitNumber, $accessCode);
    $tenantUpdated = $stmtTenant->execute();
    $stmtTenant->close();

    if ($ownerUpdated || $tenantUpdated) {
        echo "<script>
                alert('Account updated successfully.');
                window.location.href = 'index.php';
              </script>";
        exit();
    } else {
        echo "Error: " . $conn->error;
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swarm | Registration For Old Resident</title>
    <link rel="stylesheet" href="RegStyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .form-group {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 10px 20px;
            align-items: center;
            margin-bottom: 15px;
        }
      
      .form-group span {
        color: red;
      }


        .form-container {
            flex: 1;
            padding: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .btn-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

         .sidebar ul {
            list-style-type: none;
            padding: 0;
        }

        .sidebar ul li.completed .circle {
            background-color: green;
            color: white;
            padding: 5px;
            border-radius: 50%;
        }
      
      .sidebar ul li .circle {
    transition: background-color 0.3s ease, transform 0.3s ease;
}

.sidebar ul li.active .circle {
    background-color: #FF6F00;
    color: #fff;
    transform: scale(1.2); /* Slightly larger */
}

.sidebar ul li.completed .circle {
    transform: scale(1.2); /* Slightly larger */
}
    </style>
</head>
<body>
    <div class="main-container">
        <div class="sidebar">
            <ul>
                <li class="active"><span class="circle"><i class="fas fa-highlighter"></i></span>Update Your Account</li>
            </ul>
            <div class="help">
                <i class="fas fa-question-circle"></i>
                <span>Need help?</span>
            </div>
        </div>
        <div class="form-container" id="formContainer">
           <div class="form-title">Update Your Account</div>
          <div class="form-message">Please fill out the following details. Fields marked with an asterisk (*) are required.</div>
 
            <form id="registrationForm" method="POST">
                <div class="form-group">
                    <label for="username">Username <span>*</span></label>
                    <input type="text" id="username" name="username" pattern="[A-Za-z0-9]+" placeholder="e.g., Example_Juan1" required title="Username must contain only letters and numbers">
                </div>
                <div class="form-group">
                    <label for="email">Email <span>*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($sessionEmail); ?>" readonly>
                </div>
                <div class="form-group" style="position: relative;">
                    <label for="password">Password <span>*</span></label>
                    <input type="text" id="password" name="password" 
                           pattern="(?=.*\d)(?=.*[a-zA-Z])(?=.*[@#$%^&+=!]).{6,}" 
                           placeholder="Enter your password" required 
                           title="Password must be at least 6 characters long and include letters, numbers, and a special character" 
                           style="padding-right: 30px;">
                    <button type="button" onclick="togglePassword('password', this)" 
                            style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                        👁️
                    </button>
                </div>
                <div class="form-group" style="position: relative;">
                    <label for="confirmpass">Confirm Password <span>*</span></label>
                    <input type="text" id="confirmpass" name="confirmpass" 
                           pattern="(?=.*\d)(?=.*[a-zA-Z])(?=.*[@#$%^&+=!]).{6,}" 
                           placeholder="Re-enter your password" required 
                           title="Password must be at least 6 characters long and include letters, numbers, and a special character" 
                           style="padding-right: 30px;">
                    <button type="button" onclick="togglePassword('confirmpass', this)" 
                            style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                        👁️
                    </button>
                </div>
                <div class="btn-container">
                    <button type="submit" class="btn">Submit</button>
                </div>
            </form>
        </div>
    </div>
    <script>
     function openChatbotPopup() {
        // Open a popup window for the chatbot page
        window.open("OwnerTenantChatbot2.php", "chatbotPopup", "width=500,height=500,top=300,left=300");
    }
      
     function togglePassword(fieldId, button) {
    const passwordField = document.getElementById(fieldId);
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        button.textContent = '🙈'; // Change icon to indicate hiding
    } else {
        passwordField.type = 'password';
        button.textContent = '👁️'; // Revert icon to indicate showing
    }
}

// Set initial input type to 'password' on first keystroke if it's 'text'
document.getElementById('password').addEventListener('input', function() {
    if (this.type === 'text') {
        this.type = 'password';
        document.querySelector('button[onclick="togglePassword(\'password\', this)"]').textContent = '👁️';
    }
});

document.getElementById('confirmpass').addEventListener('input', function() {
    if (this.type === 'text') {
        this.type = 'password';
        document.querySelector('button[onclick="togglePassword(\'confirmpass\', this)"]').textContent = '👁️';
    }

    // Check if passwords match
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    if (confirmPassword !== password) {
        this.setCustomValidity('Passwords must match');
    } else {
        this.setCustomValidity('');
    }
});
</script>
</body>
</html>

