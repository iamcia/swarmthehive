<?php
include 'dbconn.php';

session_start();

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle delete request
if (isset($_POST['delete'])) {
    $tenant_id = $_POST['delete'];
    $delete_sql = "DELETE FROM tenantinformation WHERE Tenant_ID = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("s", $tenant_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle approval/disapproval request
if (isset($_POST['approve']) || isset($_POST['disapprove'])) {
    $tenant_id = isset($_POST['approve']) ? $_POST['approve'] : $_POST['disapprove'];
    $status = isset($_POST['approve']) ? "Approved" : "Disapproved";
    $update_sql = "UPDATE tenantinformation SET Status = ? WHERE Tenant_ID = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ss", $status, $tenant_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}


if (isset($_POST['add'])) {
    $tower = $_POST['tower'];
    $unitNumber = $_POST['unitNumber'];
    $monthIssue = $_POST['monthIssue'];
    $yearIssue = $_POST['yearIssue'];
    $lastName = $_POST['lastName'];
    $firstName = $_POST['firstName'];
    $middleName = $_POST['middleName'];
    $mobileNumber = $_POST['mobileNumber'];
    $homeNumber = $_POST['homeNumber'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Generate ownerId
    $tenantId = $tower . $unitNumber . $monthIssue . $yearIssue;

    // File upload directories
    $uploadDir = 'TenantID/';
    $uploadDir2 = 'Signature/';
    $uploadDir3 = 'ProofResidence/';

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    if (!is_dir($uploadDir2)) mkdir($uploadDir2, 0755, true);
    if (!is_dir($uploadDir3)) mkdir($uploadDir3, 0755, true);

    $govIdPath = $uploadDir . basename($_FILES['govId']['name']);
    $proofResidencyPath = $uploadDir3 . basename($_FILES['proofResidency']['name']);
    $signaturePath = $uploadDir2 . basename($_FILES['signature']['name']);

    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (
        in_array($_FILES['govId']['type'], $allowedTypes) &&
        in_array($_FILES['proofResidency']['type'], $allowedTypes) &&
        in_array($_FILES['signature']['type'], $allowedTypes)
    ) {
        if (move_uploaded_file($_FILES['govId']['tmp_name'], $govIdPath) &&
            move_uploaded_file($_FILES['proofResidency']['tmp_name'], $proofResidencyPath) &&
            move_uploaded_file($_FILES['signature']['tmp_name'], $signaturePath)) {

                // Check for a row with only Access_Code and Email
    $select_sql = "SELECT * FROM tenantinformation WHERE Access_Code IS NOT NULL AND Tower IS NULL AND Unit_Number IS NULL LIMIT 1";
    $stmt = $conn->prepare($select_sql);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update the existing row
        $update_sql = "UPDATE tenantinformation SET 
                        Tower = ?, Unit_Number = ?, Last_Name = ?, First_Name = ?, Middle_Name = ?, 
                        Mobile_Number = ?, Home_Number = ?, Month_Issue = ?, Year_Issue = ?, 
                        Username = ?, Password = ?, Government_ID = ?, Proof_Residency = ?, Signature = ?, Status = ?, Tenant_ID = ?
                       WHERE Access_Code IS NOT NULL AND Tower IS NULL AND Unit_Number IS NULL 
                       ORDER BY Tenant_ID ASC LIMIT 1";
        $stmt = $conn->prepare($update_sql);
        $status = "Pending";
        $stmt->bind_param(
            "ssssssssssssssss", 
            $tower, $unitNumber, $lastName, $firstName, $middleName, 
            $mobileNumber, $homeNumber, $monthIssue, $yearIssue, 
            $username, $password, $govIdPath, $proofResidencyPath, $signaturePath, 
            $status, $tenantId
        );
        $stmt->execute();
    } else {
        // Insert a new record
        $insert_sql = "INSERT INTO tenantinformation (Tower, Unit_Number, Last_Name, First_Name, Middle_Name, 
                        Mobile_Number, Home_Number, Month_Issue, Year_Issue, Username, Password, Government_ID, 
                        Proof_Residency, Signature, Status, Tenant_ID, Access_Code, Email) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL)";
        $stmt = $conn->prepare($insert_sql);
        $status = "Pending";
        $stmt->bind_param(
            "sssssssssssssss", 
            $tower, $unitNumber, $lastName, $firstName, $middleName, 
            $mobileNumber, $homeNumber, $monthIssue, $yearIssue, 
            $username, $password, $govIdPath, $proofResidencyPath, $signaturePath, 
            $status, $tenantId
        );
        $stmt->execute();
    }
            $stmt->close();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "File upload failed.";
        }
    } else {
        echo "Invalid file type. Only JPG, JPEG, and PNG files are allowed.";
    }
}

// Fetch records from ownerinformation table
$sql = "SELECT * FROM tenantinformation";
$result = $conn->query($sql);

//Random Gen Number
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Include Composer autoload
require 'PHPMAILER/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function generateAccessCode() {
    return rand(1000, 9999); // Generate a 4-digit random number
}

$message = '';  // Variable to store the alert message

// Preserve email after form submission
$email = isset($_POST['email']) ? $_POST['email'] : ''; // Get email from the form submission, or set to empty if not submitted

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['generate'])) {
        $_SESSION['accessCode'] = generateAccessCode();
    } elseif (isset($_POST['saveOwner'])) {
        $accessCode = $_SESSION['accessCode'];
        $email = htmlspecialchars($_POST['email']); // Get email for owner
        
        // Insert access code and email into ownerinformation table
        $sql = "INSERT INTO ownerinformation (Access_Code, Email) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $accessCode, $email);
        $stmt->execute();
        $stmt->close();
        
        // Set success message for alert
        $message = "Access code and email saved as owner.";
    } elseif (isset($_POST['saveTenant'])) {
        $accessCode = $_SESSION['accessCode'];
        $email = htmlspecialchars($_POST['email']); // Get email for tenant
        
        // Insert access code and email into tenantinformation table
        $sql = "INSERT INTO tenantinformation (Access_Code, Email) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $accessCode, $email);
        $stmt->execute();
        $stmt->close();
        
        // Set success message for alert
        $message = "Access code and email saved as tenant.";
    } elseif (isset($_POST['send'])) {
        $emails = $_POST['emails'];
        $subject = $_POST['subject'];
        $accessCode = $_SESSION['accessCode'];
        $emailMessage = $_POST['message'];

        if (sendAccessCodeEmail($emails, $subject, $emailMessage)) {
            $message = "Email sent successfully!";
        } else {
            $message = "Failed to send email.";
        }
    }
}

// Function to send emails using PHPMailer
function sendAccessCodeEmail($emails, $subject, $emailMessage) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'TheHiveResidences@swarmthehive.online'; 
        $mail->Password = 'G#pdFHa7i'; 
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587; 

        $mail->setFrom('TheHiveResidences@swarmthehive.online', 'TsubaCody');

        $emailList = explode(',', $emails);
        foreach ($emailList as $email) {
            $mail->addAddress(trim($email));
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $emailMessage;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Search and filter logic
$searchKeyword = isset($_GET['search']) ? $_GET['search'] : '';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT * FROM tenantinformation WHERE 1=1";

if ($searchKeyword) {
    $sql .= " AND (Last_Name LIKE ? OR First_Name LIKE ? OR Unit_Number LIKE ?)";
}

if ($filterStatus) {
    $sql .= " AND Status = ?";
}

$stmt = $conn->prepare($sql);

if ($searchKeyword && $filterStatus) {
    $likeKeyword = "%$searchKeyword%";
    $stmt->bind_param("ssss", $likeKeyword, $likeKeyword, $likeKeyword, $filterStatus);
} elseif ($searchKeyword) {
    $likeKeyword = "%$searchKeyword%";
    $stmt->bind_param("sss", $likeKeyword, $likeKeyword, $likeKeyword);
} elseif ($filterStatus) {
    $stmt->bind_param("s", $filterStatus);
}

$stmt->execute();
$result = $stmt->get_result();

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
  <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="sidebar.css">
  <link rel="stylesheet" href="dashboard.css">
</head>
<Style>
.sidebar .dropdown {
  position: relative;
}

.sidebar .dropdown-btn {
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  font-size: 16px; /* Adjusted font size */
  color: white;
}

.sidebar .dropdown-content {
  display: none;
  flex-direction: column;
  padding-left: 20px;
}

.sidebar .dropdown-content a {
  margin: 5px 0;
  text-decoration: none;
  color: #cccccc; /* Slightly dimmed color for sub-items */
  font-size: 14px; /* Smaller font for sub-items */
}

.sidebar .dropdown-content a:hover {
  color: #00ffcc; /* Same hover color as main links */
}

.sidebar .dropdown:hover .dropdown-content {
  display: flex;
} 
        /* Scrollable container for the table */
.table-container {
    overflow-x: auto; /* Horizontal scroll */
    overflow-y: auto; /* Vertical scroll */
    max-height: 300px; /* Adjust as needed */
    max-width: 900px;
    border: 1px solid #ccc;
}

table {
    border-collapse: collapse;
    width: 80%;
    color: #ffffff; /* White text for dark backgrounds */
}

th, td {
    padding: 5px 9px;
    text-align: left;
    border: 1px solid #ddd;
}

th {
    background-color: #444; /* Dark gray header background for better contrast */
    color: #ffffff; /* White text for headers */
}

  form label {
            color: #ffffff; /* White font color for labels */
            font-size: 14px; /* Larger font size for better readability */
        }
        
        /* Style for search and filter container */

/* Style for the search input field */
.search-filter-container input[type="text"] {
    width: 250px;
    padding: 8px 10px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

/* Style for the dropdown */
.search-filter-container select {
    padding: 8px 10px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 5px;
    background-color: #fff;
    cursor: pointer;
}

/* Style for the submit button */
.search-filter-container button[type="submit"] {
    padding: 8px 15px;
    font-size: 14px;
    color: #fff;
    background-color: #007bff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.search-filter-container button[type="submit"]:hover {
    background-color: #0056b3;
}
    </style>
</head>
<body>
  <div class="container">
      <aside>
<div class="sidebar">
  <div class="top">
    <div class="close" id="close_btn">
      <span class="material-symbols-sharp">close</span>
    </div>
  </div>

  <a href="AdminHomepage.php">
    <span class="bx bx-grid-alt"></span>
    <h3>Overview</h3>
  </a>

  <!-- Dropdown for User Management -->
  <div class="dropdown">
    <a href="#" class="dropdown-btn">
      <span class="bx bx-user"></span>
      <h4>User Management</h4>
    </a>
    <div class="dropdown-content">
      <a href="OwnerInformation.php">Owner</a>
      <a href="TenantInformation.php">Tenant</a>
    </div>
  </div>

  <a href="OwnerTenantAnnouncement.php">
    <span class="bx bx-grid-alt"></span>
    <h3>Announcements</h3>
  </a>
  <a href="AuditTrail.php">
    <span class="bx bx-user"></span>
    <h3>Audit Logs</h3>
  </a>
  <!-- Dropdown for User Management -->
  <div class="dropdown">
    <a href="#" class="dropdown-btn">
      <span class="bx bx-user"></span>
      <h4>Service Requests</h4>
    </a>
<div class="dropdown-content">
    <a href="OwnerTenantReservation2.php">Amenities Reservation</a>
    <a href="OwnerTenantGatePass2.php">Gate Pass</a>
      <a href="GuestForm2.php">Guest Check-In</a>
      <a href="OwnerTenantMoveIn2.php">Move In</a>
      <a href="OwnerTenantMoveOut2.php">Move Out</a>
      <a href="PetInformation2.php">Pet Registration</a>
      <a href="Pool2.php">Pool Reservation for Guest</a>
      <a href="OwnerTenantVisitor2.php">Visitor Pass</a>
      <a href="OwnerTenantWorkPermit2.php">Work Permit</a>
    </div>
    </div>
    <div>
  <a href="#">
    <span class="bx bx-cog"></span>
    <h3>Settings</h3>
  </a>
  </div>
  <div>
  <a href="#">
    <span class="bx bx-log-out"></span>
    <h3>Logout</h3>
  </a>
  </div>
</div>
      </aside>
      <main>
    <br><h2>Tenant Information</h2>
    <div class="search-filter-container">
        <!-- Search and filter form -->
        <form method="GET" action="">
            <div class="form-group">
                <input type="text" name="search" placeholder="Search by Name or Unit Number" value="<?php echo htmlspecialchars($searchKeyword); ?>">
                <select name="status">
                    <option value="">Filter by Status</option>
                    <option value="Pending" <?php echo $filterStatus == "Pending" ? "selected" : ""; ?>>Pending</option>
                    <option value="Approved" <?php echo $filterStatus == "Approved" ? "selected" : ""; ?>>Approved</option>
                    <option value="Disapproved" <?php echo $filterStatus == "Disapproved" ? "selected" : ""; ?>>Disapproved</option>
                </select>
                <button type="submit">Search</button>
            </div>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Access Code</th>
                    <th>Tower</th>
                    <th>Unit Number</th>
                    <th>Tenant ID</th>
                    <th>Owner ID</th>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Mobile Number</th>
                    <th>Home Number</th>
                    <th>Nationality</th>
                    <th>ACR Foreigner</th>
                    <th>Month Issue</th>
                    <th>Year Issue</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Government ID</th>
                    <th>Signature</th>
                    <th>Proof of Residency</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['Access_Code']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Tower']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Unit_Number']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Tenant_ID']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Owner_ID']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Last_Name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['First_Name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Middle_Name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Mobile_Number']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Home_Number']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Nationality']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['ACR_Foreigner']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Month_Issue']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Year_Issue']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Email']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Username']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Password']) . "</td>";
                    echo "<td><img src='" . htmlspecialchars($row['Government_ID']) . "' height='100'/></td>";
                    echo "<td><img src='" . htmlspecialchars($row['Signature']) . "' height='100'/></td>";
                    echo "<td><img src='" . htmlspecialchars($row['Proof_Residency']) . "' height='100'/></td>";
                    echo "<td>" . htmlspecialchars($row['Status']) . "</td>";
                    echo "<td>
                            <form method='POST' style='display:inline;'>
                                <button type='submit' name='delete' value='" . htmlspecialchars($row['Tenant_ID']) . "'>Delete</button>
                                <button type='submit' name='approve' value='" . htmlspecialchars($row['Tenant_ID']) . "'>Approve</button>
                                <button type='submit' name='disapprove' value='" . htmlspecialchars($row['Tenant_ID']) . "'>Disapprove</button>
                            </form>
                          </td>";
                    echo "</tr>";
                } ?>
            </tbody>
        </table>
    </div>
    <form id="addForm" method="POST" enctype="multipart/form-data" style="display: none;">
        <div class="form-group">
            <label for="tower">Tower:</label>
            <input type="text" id="tower" name="tower" required>
        </div>
        <div class="form-group">
            <label for="unitNumber">Unit Number:</label>
            <input type="text" id="unitNumber" name="unitNumber" required>
        </div>
        <div class="form-group">
            <label for="monthIssue">Month Issue:</label>
            <input type="number" id="monthIssue" name="monthIssue" min="01" max="12" required>
        </div>
        <div class="form-group">
            <label for="yearIssue">Year Issue:</label>
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
        <div class="form-group">
            <label for="lastName">Last Name:</label>
            <input type="text" id="lastName" name="lastName">
        </div>
        <div class="form-group">
            <label for="firstName">First Name:</label>
            <input type="text" id="firstName" name="firstName">
        </div>
        <div class="form-group">
            <label for="middleName">Middle Name:</label>
            <input type="text" id="middleName" name="middleName">
        </div>
        <div class="form-group">
            <label for="mobileNumber">Mobile Number:</label>
            <input type="text" id="mobileNumber" name="mobileNumber">
        </div>
        <div class="form-group">
            <label for="homeNumber">Home Number:</label>
            <input type="text" id="homeNumber" name="homeNumber">
        </div>
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username">
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password">
        </div>

        <div class="form-group">
            <label for="govId">Government ID:</label>
            <input type="file" id="govId" name="govId" accept="image/*">
        </div>
        <div class="form-group">
            <label for="proofResidency">Proof of Residency:</label>
            <input type="file" id="proofResidency" name="proofResidency" accept="image/*">
        </div>
        <div class="form-group">
            <label for="signature">Signature:</label>
            <input type="file" id="signature" name="signature" accept="image/*">
        </div>
        <div class="form-group">
            <button type="submit" name="add">Add Tenant</button>
        </div>
    </form>
    
    <!-- Random Gen Number -->
    <div class="container">
        <div class="generate-form">
            <h2>Generate Access Code</h2>
            <form id="generateForm" method="POST">
                    <input type="text" id="accessCode" name="accessCode" value="<?php echo isset($_SESSION['accessCode']) ? $_SESSION['accessCode'] : ''; ?>" readonly>
                <div>
                    <button type="submit" name="generate">Generate Now</button>
                </div>
            </form>
        </div>
    </div>
        <div class="generate-form">
            <form method="POST">
                <div>
                    <button type="submit" name="saveTenant">Save In Tenant Table</button>
                </div>
            </form>
        </div>
    <div class="container">
    <div class="generate-form">
        <br><h2>Send Access Code</h2>
        <!-- Corrected form action here -->
        <form action="OwnerInformation.php" method="POST">
            <div class="form-group">
                <label for="emails">Email:</label>
                <input type="text" id="emails" name="emails" value="<?php echo $email; ?>" required>
            </div>
            <div class="form-group">
                <label for="subject">Subject:</label>
                <input type="text" id="subject" name="subject" value="Access Code" required>
            </div>
            <div class="form-group">
                <label for="message">Message:</label>
                <input type="text" id="message" name="message" value="Your Access Code is: <?php echo isset($_SESSION['accessCode']) ? $_SESSION['accessCode'] : ''; ?>">
            </div>
            <button type="submit" name="send">Send</button>
        </form>
    </div>
</div>
</main>
</body>
</html>