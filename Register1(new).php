<?php
include('dbconn.php');
session_start();

// Define upload directories
$govIdDir = "OwnerID/";
$signatureDir = "Signature/";

// Retrieve Tower and Unit Number from the session
$tower = isset($_SESSION['tower']) ? $_SESSION['tower'] : '';
$unitNumber = isset($_SESSION['unitNumber']) ? $_SESSION['unitNumber'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and retrieve other form inputs
    $lastName = htmlspecialchars($_POST['lastName']);
    $firstName = htmlspecialchars($_POST['firstName']);
    $middleName = htmlspecialchars($_POST['middleName']);
    $mobileNumber = htmlspecialchars($_POST['mobileNumber']);
    $homeNumber = htmlspecialchars($_POST['homeNumber']);
    $nationality = htmlspecialchars($_POST['nationality']);
    $acrForeigner = htmlspecialchars($_POST['acrForeigner']);

    // Store Last Name and First Name in session for later use
    $_SESSION['lastName'] = $lastName;
    $_SESSION['firstName'] = $firstName;

    // File handling
    $govIdPath = $signaturePath = null;

    // Handle Government ID upload
    if ($_FILES['govId']['error'] === UPLOAD_ERR_OK) {
        $govIdName = uniqid() . "_" . basename($_FILES['govId']['name']);
        $govIdPath = $govIdDir . $govIdName;
        move_uploaded_file($_FILES['govId']['tmp_name'], $govIdPath);
    }

    // Handle Signature upload
    if ($_FILES['signature']['error'] === UPLOAD_ERR_OK) {
        $signatureName = uniqid() . "_" . basename($_FILES['signature']['name']);
        $signaturePath = $signatureDir . $signatureName;
        move_uploaded_file($_FILES['signature']['tmp_name'], $signaturePath);
    }

    // Update owner information with the data
    $sql = "UPDATE ownerinformation SET 
        Tower = ?, Unit_Number = ?, Last_Name = ?, First_Name = ?, Middle_Name = ?, 
        Mobile_Number = ?, Home_Number = ?, Nationality = ?, ACR_Foreigner = ?, 
        Government_ID = ?, Signature = ?, Status = 'Pending' 
        WHERE Access_Code = ?";

    // Prepare and execute the query with the provided values
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssssss", 
        $tower, $unitNumber, $lastName, $firstName, $middleName, 
        $mobileNumber, $homeNumber, $nationality, $acrForeigner, 
        $govIdPath, $signaturePath, $_SESSION['accessCode']
    );

    if ($stmt->execute()) {
        // Redirect to the next page for Proof of Residency
        echo "<script>alert('Registration completed');</script>";
        header("Location: OwnerProofResidency.php");  // Change redirection to OwnerProofResidency.php
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
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
    * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
}

body {
    background-color: #f2f2f2;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.main-container {
    display: flex;
    width: 90%;
    max-width: 1200px;
    background-color: #fff;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    border-radius: 10px;
    overflow: hidden;
}

.sidebar {
    background-color: #FFC107;
    padding: 20px;
    width: 300px;
    position: relative;
}

.sidebar ul {
    list-style: none;
}

.sidebar li {
    margin-bottom: 20px;
    padding: 10px;
    cursor: pointer;
    color: #000;
    font-weight: bold;
    display: flex;
    align-items: center;
    transition: background-color 0.3s ease, color 0.3s ease;
    border: 2px solid transparent;
    border-radius: 5px;
}

.sidebar li .circle {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 1.8px solid #000;
    border-radius: 50%;
    margin-right: 10px;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 12px;
}

.sidebar .help {
    position: absolute;
    bottom: 20px;
    left: 20px;
    display: flex;
    align-items: center;
    font-weight: bold;
}

.sidebar .help i {
    margin-right: 10px;
}

.form-container {
    flex: 1;
    padding: 40px;
}

h2 {
    margin-bottom: 20px;
    font-size: 24px;
    color: #333;
}

.form-group {
    display: flex;
    flex-direction: column;
    margin-bottom: 15px;
}

.form-group img {
    width: 120px;
    height: auto;
    margin-top: 10px;
    border: 2px solid #ddd;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

label {
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}

input[type="text"],
input[type="tel"],
input[type="email"],
input[type="password"],
input[type="number"],
select[id="yearIssue"],
select[id="monthIssue"],
select[id="gender"]{
    width: 100%;
    padding: 12px 15px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 5px;
    outline: none;
    transition: border-color 0.3s ease;
}

input[type="text"]:focus,
input[type="tel"]:focus,
input[type="email"]:focus,
input[type="password"]:focus,
input[type="pnumber"]:focus,
select[id="yearIssue"]:focus,
select[id="monthIssue"]:focus,
select[id="gender"]:focus{
    border-color: #FF6F00;
}

input[type="file"] {
    -webkit-appearance: button; 
    color: white; 
    padding: 10px 20px; 
    border: none;
    border-radius: 4px; 
    cursor: pointer; 
    font-size: 16px; 
    font-family: Arial, sans-serif; 
}

input[type="file"]::file-selector-button {
    background-color: #f1c40f; 
    color: #ffffff;
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    transition: background-color 0.3s ease;
}

input[type="file"]::file-selector-button:hover {
    background-color: #e1b00f; 
}

button[type="submit"],
button[type="next"]{
    background-color: #FF6F00;
    color: #fff;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s ease;
    display: inline-block;
    margin-top: 20px;
}

button[type="submit"]:hover,
button[type="next"]:hover {
    background-color: #E65C00;
}

.file-name {
    margin-left: 10px;
    font-size: 14px; 
    color: #333;
    display: inline-block;
}

.previous-page {
    background-color: #ccc;
    color: #333;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s ease;
    display: inline-block;
    margin-right: 10px;
}

.previous-page:hover {
    background-color: #bbb;
}

@media (min-width: 768px) {
    .form-group {
        display: flex;
        justify-content: space-between;
    }
    .form-group label,
    .form-group input {
        flex: 1;
        margin: 5px;
    }
    .form-group label {
        margin-right: 10px;
    }
    .form-group input {
        margin-left: 10px;
    }
}


.help {
    position: absolute;
    bottom: 20px;
    left: 20px;
    display: flex;
    align-items: center;
    background-color: #fff;
    color: #000;
    padding: 10px 15px;
    border-radius: 10px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
    font-weight: bold;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.help:hover {
    transform: scale(1.05);
    box-shadow: 0px 6px 10px rgba(0, 0, 0, 0.25);
}

.help-icon i {
    font-size: 20px;
    margin-right: 8px;
}

.help-icon span {
    font-size: 16px;
    color: #333;
}

.help a {
    color: inherit;
    text-decoration: none;
}

.form-title {
            font-size: 26px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }

        .form-message {
            font-size: 16px;
            color: #555;
            margin-bottom: 20px;
            text-align: center;
        }

    
        .form-group {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 10px 20px;
            align-items: center;
            margin-bottom: 15px;
        }
      
      .form-group label span {
        color: red;
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
              <li class="active"><span class="circle"><i class="fas fa-highlighter"></i></span>Unit Owner Information</li>
                <li><span class="circle"></span>Proof of Residency</li>
                <li><span class="circle"></span>Owner Contact</li>
                <li><span class="circle"></span>Owner Occupants</li>
                <li><span class="circle"></span>Owner Helper</li>
                <li><span class="circle"></span>Create an Account</li>
            </ul>
        </div>
        <div class="form-container" id="formContainer">
            <form id="registrationForm" method="POST" enctype="multipart/form-data">
              
              <div class="form-title">Personal Information</div>
            <div class="form-message">Please fill out the following details. Fields marked with an asterisk (*) are required.</div>
        
                <div class="form-group">
                    <label for="lastName">Last Name <span>*</span></label>
                    <input type="text" id="lastName" name="lastName" placeholder="e.g., Dela Cruz" required>
                </div>
                <div class="form-group">
                    <label for="firstName">First Name <span>*</span></label>
                    <input type="text" id="firstName" name="firstName" placeholder="e.g., Juan" required>
                </div>
                <div class="form-group">
                    <label for="middleName">Middle Name</label>
                    <input type="text" id="middleName" name="middleName" placeholder="e.g., D">
                </div>
                <div class="form-group">
                    <label for="mobileNumber">Mobile Number <span>*</span></label>
                    <input type="tel" id="mobileNumber" name="mobileNumber" pattern="\d{11}" placeholder="e.g., 09123456789" required title="Mobile number must be 11 digits" required>
                </div>
                <div class="form-group">
                    <label for="homeNumber">Home Number</label>
                    <input type="tel" id="homeNumber" name="homeNumber" pattern="\d{10}" required title="Mobile number must be 10 digits" placeholder="e.g., (02) 1234-5678">
                </div>
                <div class="form-group">
                    <label for="govId">Government ID <span>*</span></label>
                    <input type="file" id="govId" name="govId" accept="image/*" placeholder="Required" required>
                    <span class="file-name" id="govIdFileName"></span>
                </div>
                <div class="form-group">
                    <label for="nationality">Nationality <span>*</span></label>
                    <input type="text" id="nationality" name="nationality" placeholder="e.g., Filipino" required>
                </div>
                <div class="form-group">
                    <label for="acrForeigner">ACR Number</label>
                    <input type="text" id="acrForeigner" name="acrForeigner" placeholder="For Foreign">
                </div>

                <!-- No need to display Tower and Unit Number here -->
                <!-- These will be used in the backend when saving to the database -->

                <div class="form-group">
                    <label for="signature">Signature <span>*</span></label>
                    <input type="file" id="signature" name="signature" accept="image/*" placeholder="Required" required>
                    <span class="file-name" id="signatureFileName"></span>
                </div>

                <div class="btn-container">
                    <button type="submit">Next</button>
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


function openChatbotPopup() {
        // Open a popup window for the chatbot page
        window.open("OwnerTenantChatbot2.php", "chatbotPopup", "width=500,height=500,top=300,left=300");
    }
</script>
</html>