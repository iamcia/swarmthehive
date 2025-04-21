<?php
include("dbconn.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accessCode = htmlspecialchars($_POST['accessCode']);
    $tower = htmlspecialchars($_POST['tower']);
    $unitNumber = htmlspecialchars($_POST['unitNumber']);

    // Store these values in session for later use
    $_SESSION['accessCode'] = $accessCode;
    $_SESSION['tower'] = $tower;
    $_SESSION['unitNumber'] = $unitNumber;

    //First, check if the unit already exists in the unitinformation table (Optional part you can remove)
    $sqlCheckUnit = "SELECT * FROM unitinformation WHERE Tower = ? AND Unit_Number = ?";
    $stmtCheckUnit = $conn->prepare($sqlCheckUnit);
    $stmtCheckUnit->bind_param("ss", $tower, $unitNumber);
    $stmtCheckUnit->execute();
    $resultCheckUnit = $stmtCheckUnit->get_result();

    // If the unit already exists, stop further execution and show an alert
    // Commented out since you don't want to insert into `unitinformation`
    if ($resultCheckUnit->num_rows > 0) {
     echo "<script>alert('Tower, Unit Number already occupied. Please choose another unit.');</script>";
    echo "<script>window.location.href = 'WelcomeReg.php';</script>";
    exit();
    }

    // Proceed with checking the access code in ownerinformation and tenantinformation
    $sqlOwner = "SELECT * FROM ownerinformation WHERE Access_Code = ?";
    $stmtOwner = $conn->prepare($sqlOwner);
    $stmtOwner->bind_param("s", $accessCode);
    $stmtOwner->execute();
    $resultOwner = $stmtOwner->get_result();

    $sqlTenant = "SELECT * FROM tenantinformation WHERE Email = ?";
    $stmtTenant = $conn->prepare($sqlTenant);
    $stmtTenant->bind_param("s", $accessCode); // Using accessCode as the email for tenant information
    $stmtTenant->execute();
    $resultTenant = $stmtTenant->get_result();

    if ($resultOwner->num_rows > 0) {
        // If access code is found in ownerinformation, fetch the row
        $rowOwner = $resultOwner->fetch_assoc();

        // Check if Tower and Unit Number are empty for new registration
        if (empty($rowOwner['Tower']) && empty($rowOwner['Unit_Number'])) {
            // Update Tower and Unit Number in the ownerinformation table
            $sqlUpdateOwner = "UPDATE ownerinformation SET Tower = ?, Unit_Number = ? WHERE Access_Code = ?";
            $stmtUpdateOwner = $conn->prepare($sqlUpdateOwner);
            $stmtUpdateOwner->bind_param("sss", $tower, $unitNumber, $accessCode);
            $stmtUpdateOwner->execute();
        } else {
            // Tower and Unit Number already assigned, show an error and redirect back
            echo "<script>alert('The unit is already assigned.');</script>";
            echo "<script>window.location.href = 'WelcomeReg.php';</script>";
            exit();
        }
    } elseif ($resultTenant->num_rows > 0) {
        // If access code is found in tenantinformation, handle the tenant logic (optional)
        // You can add additional logic for tenant updates or actions here
    } else {
        // If access code not found in either owner or tenant tables, show an error and redirect back
        echo "<script>alert('Access code not found.');</script>";
        echo "<script>window.location.href = 'WelcomeReg.php';</script>";
        exit();
    }

    // After successful processing, redirect to Register1(new).php
    header("Location: Register1(new).php");
    exit();

    // Close statements and connection
    $stmtOwner->close();
    $stmtTenant->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Condo Portal Registration</title>
    <link rel="stylesheet" href="WelReg.css">
</head>
<body>
  <div class="container">
    <div class="registration-form">
      <div class="header">
        <p>
          <img src='img/swarm logo.png' width="125px" height="24px" border='0' style="margin-bottom: 10px;">
          <span class="white-text">of</span>
          <img src='img/The Hive Logo.png' width="80px" height="40px" style="margin-bottom: 22px;" border='0'>
        </p>
        <h1>Welcome, Resident!</h1><br>
        <img src='img/lines.png' width="300px" height="50px" border='0'>
      </div>
      <form id="registrationForm" method="POST">
        <div class="form-group">
          <label for="accessCode">Access Code:</label>
          <input type="text" id="accessCode" name="accessCode" required>
        </div>
        <div class="form-group">
          <label for="tower">Tower:</label>
          <input type="text" id="tower" name="tower" required>
        </div>
        <div class="form-group">
          <label for="unitNumber">Unit Number:</label>
          <input type="text" id="unitNumber" name="unitNumber" required>
        </div>
        <button type="submit">Activate</button>
      </form>
    </div>
  </div>

<script> window.chtlConfig = { chatbotId: "4832571958" } </script>
<script async data-id="4832571958" id="chatling-embed-script" type="text/javascript" src="https://chatling.ai/js/embed.js"></script>

  <script>
    function openChatbotPopup() {
      // Open a popup window for the chatbot page
      window.open("OwnerTenantChatbot1.php", "chatbotPopup", "width=500,height=500,top=300,left=300");
    }
  </script>
</body>
</html>