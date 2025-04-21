<?php
// Start session
session_start();
include('dbconn.php');

// Retrieve session variables
$accessCode = $_SESSION['accessCode'];
$tower = $_SESSION['tower'];
$unitNumber = $_SESSION['unitNumber'];

// Fetch owner data
$sql = "SELECT * FROM ownerinformation WHERE Access_Code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $accessCode);
$stmt->execute();
$result = $stmt->get_result();
$ownerData = $result->fetch_assoc();

// Fetch tenant data (if any)
$sqlTenant = "SELECT * FROM tenantinformation WHERE Email = ?";
$stmtTenant = $conn->prepare($sqlTenant);
$stmtTenant->bind_param("s", $accessCode);
$stmtTenant->execute();
$resultTenant = $stmtTenant->get_result();
$tenantData = $resultTenant->fetch_assoc();

// Get First Name and Last Name from the session or the appropriate data
if ($ownerData) {
    // User is an owner, use owner data
    $firstName = $ownerData['First_Name'];
    $lastName = $ownerData['Last_Name'];
    $ownerId = $ownerData['Owner_ID']; // Use Owner_ID from ownerinformation
    $tenantId = '';  // No tenant ID for owner
} elseif ($tenantData) {
    // User is a tenant, use tenant data
    $firstName = $tenantData['First_Name'];
    $lastName = $tenantData['Last_Name'];
    $tenantId = $tenantData['Tenant_ID']; // Use Tenant_ID from tenantinformation
    $ownerId = '';  // No owner ID for tenant
} else {
    // Handle case where neither owner nor tenant is found
    echo "Error: User not found in both owner and tenant records.";
    exit;
}

// Handle form submission
$success = false;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form inputs
    $monthIssue = $_POST['monthIssue'];
    $yearIssue = $_POST['yearIssue'];

    // Generate ownerId
    $ownerId = $tower . $unitNumber . $monthIssue . $yearIssue;
    $_SESSION['ownerId'] = $ownerId;

    // File upload logic for Proof of Residency
    $targetDir = "ProofResidence/"; // Ensure this directory exists on your server
    $proofResidencyFileName = '';

    if (isset($_FILES['proofResidency']) && $_FILES['proofResidency']['error'] === UPLOAD_ERR_OK) {
        $proofResidency = $_FILES['proofResidency']['name'];
        $proofResidencyTemp = $_FILES['proofResidency']['tmp_name'];
        $proofResidencyFileName = $targetDir . basename($proofResidency);

        // Move uploaded file to target directory
        if (!move_uploaded_file($proofResidencyTemp, $proofResidencyFileName)) {
            die("Error uploading the file. Please try again.");
        }
    }

    // Update ownerinformation table
    $updateSQL = "
        UPDATE ownerinformation 
        SET Proof_Residency = ?, Month_Issue = ?, Year_Issue = ?, Owner_ID = ? 
        WHERE Access_Code = ?";
    $updateStmt = $conn->prepare($updateSQL);
    $updateStmt->bind_param("sssss", $proofResidencyFileName, $monthIssue, $yearIssue, $ownerId, $accessCode);

    if ($updateStmt->execute()) {
        // Store unit_number and tower in session again
        $_SESSION['unitNumber'] = $unitNumber;
        $_SESSION['tower'] = $tower;

        // Insert into unitinformation table with Owner_ID or Tenant_ID
        $sqlUnit = "INSERT INTO unitinformation (Owner_ID, Tenant_ID, Last_Name, First_Name, Tower, Unit_Number, Status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'Vacant')";
        $stmtUnit = $conn->prepare($sqlUnit);

        // Insert Owner_ID or Tenant_ID and Last_Name, First_Name
        if ($ownerData) {
            // Owner, populate Owner_ID and use Last_Name, First_Name from owner
            $stmtUnit->bind_param("ssssss", $ownerId, $tenantId, $lastName, $firstName, $tower, $unitNumber);
        } else {
            // Tenant, populate Tenant_ID and use Last_Name, First_Name from tenant
            $stmtUnit->bind_param("ssssss", $ownerId, $tenantId, $lastName, $firstName, $tower, $unitNumber);
        }

        $stmtUnit->execute();
        $stmtUnit->close();

        $success = true;

        // Redirect to the next page
        header("Location: OwnerContact.php");
        exit;
    } else {
        die("Error updating data: " . $updateStmt->error);
    }

    $updateStmt->close();
}

$stmt->close();
$stmtTenant->close();
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

        .form-group label span {
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
            gap: 10px;
            margin-top: 20px;
            align-items: center;
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
            transform: scale(1.2);
        }

        .sidebar ul li.completed .circle {
            transform: scale(1.2);
        }

        select[id="gender"] {
            margin-left: 10px;
        }

        select[id="monthIssue"],
        select[id="yearIssue"] {
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="sidebar">
            <ul>
                <li class="completed"><span class="circle"><i class="fas fa-check"></i></span>Unit Owner Information</li>
                <li class="active"><span class="circle"><i class="fas fa-highlighter"></i></span>Proof of Residency</li>
                <li><span class="circle"></span>Owner Contact</li>
                <li><span class="circle"></span>Owner Occupants</li>
                <li><span class="circle"></span>Owner Helper</li>
                <li><span class="circle"></span>Create an Account</li>
            </ul>
            <div class="help" title="Click here for assistance">
                <i class="fas fa-question-circle"></i>
                <span><a href="#" onclick="openChatbotPopup()">Need help?</a></span>
            </div>
        </div>
        <div class="form-container">
            <div class="form-title">Proof of Residency</div>
            <div class="form-message">Please fill out the following details. Fields marked with an asterisk (*) are required.</div>
            <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="proofResidency">Proof of Residency <span>*</span></label>
                <input type="file" id="proofResidency" name="proofResidency" accept="image/*" required>
                <span class="file-name" id="proofResidencyFileName"></span>
                <img id="proofResidencyPreview" src="#" alt="Preview" style="display: none; width: 100px; height: auto; margin-top: 10px;">
            </div>

                <div class="form-group">
                    <label for="monthIssue">Month Issue <span>*</span></label>
                    <select id="monthIssue" name="monthIssue" required>
                        <option value="" selected disabled>Select Month</option>
                        <option value="01">January</option>
                        <option value="02">February</option>
                        <option value="03">March</option>
                        <option value="04">April</option>
                        <option value="05">May</option>
                        <option value="06">June</option>
                        <option value="07">July</option>
                        <option value="08">August</option>
                        <option value="09">September</option>
                        <option value="10">October</option>
                        <option value="11">November</option>
                        <option value="12">December</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="yearIssue">Year Issue <span>*</span></label>
                    <select id="yearIssue" name="yearIssue" required>
                        <option value="" selected disabled>Select Year</option>
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
                    <button type="button" class="btn previous-page" onclick="goBack()">Previous</button>
                    <button type="submit">Next</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('proofResidency').addEventListener('change', function(event) {
            displayImagePreview(event, 'proofResidencyPreview');
            const fileName = event.target.files.length > 0 ? event.target.files[0].name : 'No file chosen';
            document.getElementById('proofResidencyFileName').textContent = fileName;
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

        function goBack() {
            window.history.back();
        }
    </script>
</body>
</html>  
