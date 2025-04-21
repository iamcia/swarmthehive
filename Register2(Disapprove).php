<?php
session_start();
$servername = "localhost";
$db_username = "u113232969_Hives"; 
$db_password = "theSwarm4"; 
$dbname = "u113232969_SWARM";

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Retrieve and sanitize form data
    $accessCode = htmlspecialchars($_SESSION['accessCode']);
    $tower = htmlspecialchars($_SESSION['tower']);
    $unitNumber = htmlspecialchars($_SESSION['unitNumber']);
    $lastName = htmlspecialchars($_POST['lastName']);
    $firstName = htmlspecialchars($_POST['firstName']);
    $middleName = htmlspecialchars($_POST['middleName']);
    $mobileNumber = htmlspecialchars($_POST['mobileNumber']);
    $homeNumber = htmlspecialchars($_POST['homeNumber']);
    $email = htmlspecialchars($_POST['email']);
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']); // Consider hashing the password for security
    $nationality = htmlspecialchars($_POST['nationality']);
    $acrForeigner = htmlspecialchars($_POST['acrForeigner']);
    $monthIssue = htmlspecialchars($_POST['monthIssue']);
    $yearIssue = htmlspecialchars($_POST['yearIssue']);

   // Generate Owner_ID by concatenating tower, unitNumber, monthIssue, and yearIssue
$tenantId = $tower . $unitNumber . $monthIssue . $yearIssue;

// Store Owner_ID in the session
$_SESSION['tenantId'] = $tenantId;

    // Handle file uploads as blobs
    $govId = $signature = $proofResidency = null;

    if ($_FILES['govId']['error'] == UPLOAD_ERR_OK) {
        $govId = file_get_contents($_FILES['govId']['tmp_name']);
    }

    if ($_FILES['signature']['error'] == UPLOAD_ERR_OK) {
        $signature = file_get_contents($_FILES['signature']['tmp_name']);
    }

    if ($_FILES['proofResidency']['error'] == UPLOAD_ERR_OK) {
        $proofResidency = file_get_contents($_FILES['proofResidency']['tmp_name']);
    }

    // Prepare SQL statement to update owner information (using Resident_Code from Owner_ID)
    $sqlOwner = "UPDATE tenantinformation SET 
        Tower=?, Unit_Number=?, Tenant_ID=?, Last_Name=?, First_Name=?, Middle_Name=?, Mobile_Number=?, Home_Number=?, 
        Nationality=?, ACR_Foreigner=?, Government_ID=?, Signature=?, Proof_Residency=?, Email=?, 
        Username=?, Password=?, Month_Issue=?, Year_Issue=?, Status='Pending' WHERE Access_Code=?";

    $stmtOwner = $conn->prepare($sqlOwner);

    if ($stmtOwner) {
        $stmtOwner->bind_param("sssssssssssssssssss", 
    $tower, $unitNumber, $tenantId, $lastName, $firstName, $middleName, $mobileNumber, $homeNumber, 
    $nationality, $acrForeigner, $govId, $signature, $proofResidency, $email, $username, 
    $password, $monthIssue, $yearIssue, $accessCode
);
        // Execute SQL statement
        if ($stmtOwner->execute()) {
            echo "<script>alert('RIS Completed');</script>";
            header("Location: index.php");
            exit();
        } else {
            echo "Error executing SQL: " . $stmtOwner->error;
        }

        $stmtOwner->close();
    } else {
        echo "Failed to prepare SQL statement.";
    }

    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form - Page 1</title>
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

        .form-container {
            flex: 1;
            padding: 40px;
            max-width: 1200px;
            margin: 0 auto;
            max-height: 600px;
            overflow-y: auto;
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

        .sidebar ul li {
            padding: 10px;
            border-bottom: 1px solid #ccc;
        }

        .sidebar ul li.completed .circle {
            background-color: green;
            color: white;
            padding: 5px;
            border-radius: 50%;
        }

        .sidebar ul li.active {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="sidebar">
            <ul>
                <li class="completed"><span class="circle"><i class="fas fa-check"></i></span> Unit Resident's Information</li>
            </ul>
            <div class="help">
    <i class="fas fa-question-circle"></i>
    <span><a href="#" onclick="openChatbotPopup()">Need help?</a></span>
</div>
        </div>
       <div class="form-container" id="formContainer">
            <form id="registrationForm" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="lastName">Last Name</label>
                    <input type="text" id="lastName" name="lastName" placeholder="Required" required>
                </div>
                <div class="form-group">
                    <label for="firstName">First Name</label>
                    <input type="text" id="firstName" name="firstName" placeholder="Required" required>
                </div>
                <div class="form-group">
                    <label for="middleName">Middle Name</label>
                    <input type="text" id="middleName" name="middleName" placeholder="Optional">
                </div>
                <div class="form-group">
                    <label for="mobileNumber">Mobile Number</label>
                    <input type="tel" id="mobileNumber" name="mobileNumber" pattern="\d{11}" placeholder="Required" required title="Mobile number must be 11 digits" required>
                </div>
                <div class="form-group">
                    <label for="homeNumber">Home Number</label>
                    <input type="tel" id="homeNumber" name="homeNumber" placeholder="Required" pattern="\d{11}" required title="Mobile number must be 11 digits" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Required" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" pattern="[A-Za-z0-9]+" placeholder="Required" required title="Username must contain only letters and numbers" required>
                </div>
                <div class="form-group" style="position: relative;">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" 
           pattern="(?=.*\d)(?=.*[a-zA-Z])(?=.*[@#$%^&+=!]).{6,}" 
           placeholder="Required" required 
           title="Password must be at least 6 characters long and include letters, numbers, and a special character" 
           style="padding-right: 30px;">
    <button type="button" onclick="togglePassword()" 
            style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
        👁️
    </button>
</div>
                <div class="form-group">
    <label for="govId">Government ID</label>
    <input type="file" id="govId" name="govId" accept="image/*" placeholder="Required" required>
    <span class="file-name" id="govIdFileName"></span>
    <img id="govIdPreview" src="#" alt="Preview" style="display: none; width: 100px; height: auto; margin-top: 10px;">
</div>
<div class="form-group">
    <label for="signature">Signature</label>
    <input type="file" id="signature" name="signature" accept="image/*" placeholder="Required" required>
    <span class="file-name" id="signatureFileName"></span>
    <img id="signaturePreview" src="#" alt="Preview" style="display: none; width: 100px; height: auto; margin-top: 10px;">
</div>
<div class="form-group">
    <label for="proofResidency">Proof of Residency</label>
    <input type="file" id="proofResidency" name="proofResidency" accept="image/*" placeholder="Required" required>
    <span class="file-name" id="proofResidencyFileName"></span>
    <img id="proofResidencyPreview" src="#" alt="Preview" style="display: none; width: 100px; height: auto; margin-top: 10px;">
</div>
                <div class="form-group">
                    <label for="nationality">Nationality</label>
                    <input type="text" id="nationality" name="nationality" placeholder="Required" required>
                </div>
                <div class="form-group">
                    <label for="acrForeigner">ACR Number</label>
                    <input type="text" id="acrForeigner" name="acrForeigner" placeholder="For Foreign">
                </div>
                <div class="form-group">
    <label for="monthIssue">Month Issue</label>
    <input type="number" id="monthIssue" name="monthIssue" placeholder="01 to 12 only" required>
</div>

<div class="form-group">
    <label for="yearIssue">Year Issue</label>
    <select id="yearIssue" name="yearIssue" required>
        <option value="">Select Year</option>
        <option value="18">2018</option>
        <option value="19">2019</option>
        <option value="20">2020</option>
        <option value="21">2021</option>
        <option value="22">2022</option>
        <option value="23">2023</option>
        <option value="24">2024</option>
        <option value="25">2025</option>
        <option value="26">2026</option>
        <option value="27">2027</option>
    </select>
</div>
                <div class="btn-container">
                    <button type="submit">Submit</button>
                </div>
            </form>
        </div>
    </div>
</body>
<script>
document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function() {
        const fileNameElement = document.getElementById(this.id + 'FileName');
        const fileName = this.files.length > 0 ? this.files[0].name : 'No file chosen';
        fileNameElement.textContent = fileName; // Update the filename display
    });
});

document.getElementById('govId').addEventListener('change', function(event) {
    displayImagePreview(event, 'govIdPreview');
});
document.getElementById('signature').addEventListener('change', function(event) {
    displayImagePreview(event, 'signaturePreview');
});
document.getElementById('proofResidency').addEventListener('change', function(event) {
    displayImagePreview(event, 'proofResidencyPreview');
});

function displayImagePreview(event, previewId) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewImage = document.getElementById(previewId);
            previewImage.src = e.target.result;
            previewImage.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

function togglePassword() {
    const passwordField = document.getElementById('password');
    const button = passwordField.nextElementSibling;
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        button.textContent = '🙈'; // Optional: change icon to indicate hiding
    } else {
        passwordField.type = 'password';
        button.textContent = '👁️'; // Optional: revert icon to indicate showing
    }
}

function openChatbotPopup() {
        // Open a popup window for the chatbot page
        window.open("OwnerTenantChatbot2.php", "chatbotPopup", "width=500,height=500,top=300,left=300");
    }
</script>
</html>