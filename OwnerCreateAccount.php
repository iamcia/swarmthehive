<?php
// Start session
session_start();
include('dbconn.php');

// Ensure Owner_ID is set in session
if (!isset($_SESSION['ownerId'])) {
    die("Error: Owner_ID not found in session.");
}
$ownerId = $_SESSION['ownerId'];

// Fetch email for the logged-in Owner_ID
$email = '';
$stmt = $conn->prepare("SELECT Email FROM ownerinformation WHERE Owner_ID = ?");
$stmt->bind_param("s", $ownerId);
$stmt->execute();
$stmt->bind_result($email);
$stmt->fetch();
$stmt->close();

// Check if form is submitted
$success = false;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form data
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);
    $confirmPassword = htmlspecialchars($_POST['confirmpass']); // Confirm password field

    // Ensure passwords match
    if ($password !== $confirmPassword) {
        echo "<script>alert('Passwords do not match!');</script>";
    } else {
        // Hash the password before storing it in the database
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Prepare and bind SQL update statement
        $stmt = $conn->prepare("UPDATE ownerinformation SET Username = ?, Password = ? WHERE Owner_ID = ?");
        $stmt->bind_param("sss", $username, $hashedPassword, $ownerId);

        // Execute the statement
        if ($stmt->execute()) {
            $success = true;
            echo "<script>alert('Account creation successful');</script>";
            header("Location: index.php"); // Redirect on success
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }

        // Close statement
        $stmt->close();
    }
}

// Close connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swarm | Registration For New Resident</title>
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
            min-height: 600px;
            max-height: 600px;
            overflow-y: auto;
        }

        .btn-container {
            display: flex;
            justify-content: space-between;
            gap: 10px; /* Space between buttons */
    margin-top: 20px;
    align-items: center; /* Vertically aligns buttons */
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
      
      select[id="gender"]{
    margin-left: 10px;
}
      
      select[id="yearIssue"]{
    margin-left: 10px;
}
    </style>
</head>
<body>
    <div class="main-container">
        <div class="sidebar">
            <ul>
                <li class="completed"><span class="circle"><i class="fas fa-check"></i></span>Unit Owner Information</li>
                <li class="completed"><span class="circle"><i class="fas fa-check"></i></span>Owner Contact</li>
                <li class="completed"><span class="circle"><i class="fas fa-check"></i></span>Owner Occupants</li>
                <li class="completed"><span class="circle"><i class="fas fa-check"></i></span>Owner Helper</li>
                <li class="completed"><span class="circle"><i class="fas fa-check"></i></span>Proof of Residency</li>
                <li class="active"><span class="circle"><i class="fas fa-highlighter"></i></span>Create an Account</li>
            </ul>
            <div class="help" title="Click here for assistance">
                <i class="fas fa-question-circle"></i>
                <span><a href="#" onclick="openChatbotPopup()">Need help?</a></span>
            </div>
        </div>
        <div class="form-container">
             <div class="form-title">Create an Account</div>
    <div class="form-message">Please fill out the following details. Fields marked with an asterisk (*) are required.</div>
    <form method="POST">
        <div class="form-group">
            <label for="email">Email <span>*</span></label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
        </div>
        <div class="form-group">
            <label for="username">Username <span>*</span></label>
            <input type="text" id="username" name="username" pattern="[A-Za-z0-9]+" placeholder="e.g., Example_Juan1" required title="Username must contain only letters and numbers">
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
            <button type="button" class="btn previous-page" onclick="goBack()">Previous</button>
            <button type="submit" class="btn next-page">Submit</button>
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