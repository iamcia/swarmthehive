<?php
// Start the session
session_start();

// Database credentials
$servername = "localhost";
$db_username = "u113232969_Hives";
$db_password = "theSwarm4";
$dbname = "u113232969_SWARM";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set PHP default timezone
date_default_timezone_set('Asia/Manila');

// Connect to the database
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check the database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure PetID directory exists
$uploadDir = "PetID/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Handle file uploads
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $residentCode = $_POST['resident_code'];
    $userType = $_POST['user_type'];
    $ownerName = $_POST['owner_name'];
    $contact = $_POST['contact'];
    $unitNo = $_POST['unit_no'];
    $email = $_POST['email'];
    $petName = $_POST['pet_name'];
    $breed = $_POST['breed'];
    $dob = $_POST['dob'];
    $vaccinated = $_POST['vaccinated'];
    $vaccineDuration = $_POST['vaccine_duration'];
    $remarks = $_POST['remarks'];
    $status = "Pending"; // Default status

    // Image upload handling
    $petPicPath = "";
    $vaccineCardPath = "";
    $signaturePath = "";

    if (!empty($_FILES['pet_pic']['name'])) {
        $petPicPath = $uploadDir . $residentCode . "_pet." . pathinfo($_FILES['pet_pic']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['pet_pic']['tmp_name'], $petPicPath);
    }

    if (!empty($_FILES['vaccine_card']['name'])) {
        $vaccineCardPath = $uploadDir . $residentCode . "_vaccine." . pathinfo($_FILES['vaccine_card']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['vaccine_card']['tmp_name'], $vaccineCardPath);
    }

    if (!empty($_FILES['user_signature']['name'])) {
        $signaturePath = $uploadDir . $residentCode . "_signature." . pathinfo($_FILES['user_signature']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['user_signature']['tmp_name'], $signaturePath);
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO pets (Resident_Code, user_type, owner_name, contact, unit_no, email, pet_name, breed, dob, pet_pic, vaccinated, vaccine_card, vaccine_duration, remarks, user_signature, Status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssssssss", $residentCode, $userType, $ownerName, $contact, $unitNo, $email, $petName, $breed, $dob, $petPicPath, $vaccinated, $vaccineCardPath, $vaccineDuration, $remarks, $signaturePath, $status);
    if ($stmt->execute()) {
        echo "<script>alert('Pet registered successfully!'); window.location.href='PetInformation.php';</script>";
    } else {
        echo "<script>alert('Error registering pet');</script>";
    }
    $stmt->close();
}

// Handle actions: approve, disapprove, delete
if (isset($_GET['action'], $_GET['id'])) {
    $residentCode = $_GET['id'];
    $sql = "";

    if ($_GET['action'] === 'approve') {
        $sql = "UPDATE pets SET Status = 'Approved' WHERE Resident_Code = ?";
    } elseif ($_GET['action'] === 'disapprove') {
        $sql = "UPDATE pets SET Status = 'Disapproved' WHERE Resident_Code = ?";
    } elseif ($_GET['action'] === 'delete') {
        // Delete images from folder
        $stmt = $conn->prepare("SELECT pet_pic, vaccine_card, user_signature FROM pets WHERE Resident_Code = ?");
        $stmt->bind_param("s", $residentCode);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result) {
            if (file_exists($result['pet_pic'])) unlink($result['pet_pic']);
            if (file_exists($result['vaccine_card'])) unlink($result['vaccine_card']);
            if (file_exists($result['user_signature'])) unlink($result['user_signature']);
        }

        // Delete record from database
        $sql = "DELETE FROM pets WHERE Resident_Code = ?";
    }

    if (!empty($sql)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $residentCode);
        if ($stmt->execute()) {
            $actionMessage = ($_GET['action'] === 'delete') ? 'Pet entry deleted successfully' : "Pet status updated to " . ucfirst($_GET['action']);
            echo "<script>alert('$actionMessage'); window.location.href='PetInformation.php';</script>";
        } else {
            echo "<script>alert('Error processing request');</script>";
        }
        $stmt->close();
    }
}


// Search and filter logic
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : "";
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : "";

$sql = "SELECT * FROM pets WHERE 1=1";
$params = [];
$types = "";

if (!empty($searchKeyword)) {
    $sql .= " AND (Resident_Code LIKE ? OR owner_name LIKE ? OR unit_no LIKE ?)";
    $searchParam = "%$searchKeyword%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    $types .= "sss";
}

if (!empty($statusFilter)) {
    $sql .= " AND Status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result_pets = $stmt->get_result();
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
    <h1>Pet Information</h1>
    <div class="search-filter-container">
        <form method="GET" action="">
            <input type="text" name="search" placeholder="Search by Name or Unit Number" value="<?= htmlspecialchars($searchKeyword); ?>">
            <select name="status">
                <option value="">Filter by Status</option>
                <option value="Pending" <?= ($statusFilter == "Pending") ? "selected" : ""; ?>>Pending</option>
                <option value="Approved" <?= ($statusFilter == "Approved") ? "selected" : ""; ?>>Approved</option>
                <option value="Disapproved" <?= ($statusFilter == "Disapproved") ? "selected" : ""; ?>>Disapproved</option>
            </select>
            <button type="submit">Search</button>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Resident Code</th>
                    <th>Owner Name</th>
                    <th>Contact</th>
                    <th>Unit No</th>
                    <th>Email</th>
                    <th>Pet Name</th>
                    <th>Breed</th>
                    <th>Date of Birth</th>
                    <th>Pet Picture</th>
                    <th>Vaccinated</th>
                    <th>Vaccine Card</th>
                    <th>Vaccine Duration (Days)</th>
                    <th>Remarks</th>
                    <th>User Signature</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result_pets->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Resident_Code']); ?></td>
                        <td><?= htmlspecialchars($row['owner_name']); ?></td>
                        <td><?= htmlspecialchars($row['contact']); ?></td>
                        <td><?= htmlspecialchars($row['unit_no']); ?></td>
                        <td><?= htmlspecialchars($row['email']); ?></td>
                        <td><?= htmlspecialchars($row['pet_name']); ?></td>
                        <td><?= htmlspecialchars($row['breed']); ?></td>
                        <td><?= htmlspecialchars($row['dob']); ?></td>
                        <td><img src="<?= $row['pet_pic']; ?>" alt="Pet Picture" width="50"></td>
                        <td><?= htmlspecialchars($row['vaccinated']); ?></td>
                        <td><img src="<?= $row['vaccine_card']; ?>" alt="Vaccine Card" width="50"></td>
                        <td><?= htmlspecialchars($row['vaccine_duration']); ?></td>
                        <td><?= htmlspecialchars($row['remarks']); ?></td>
                        <td><img src="<?= $row['user_signature']; ?>" alt="Signature" width="50"></td>
                        <td><?= htmlspecialchars($row['Status']); ?></td>
                        <td>
    <a href="?action=approve&id=<?= htmlspecialchars($row['Resident_Code']); ?>" style="color: #00ffcc; text-decoration: none;">Approve</a> |
    <a href="?action=disapprove&id=<?= htmlspecialchars($row['Resident_Code']); ?>" style="color: #ff6347; text-decoration: none;">Disapprove</a> |
    <a href="?action=delete&id=<?= htmlspecialchars($row['Resident_Code']); ?>" style="color: #ffcc00; text-decoration: none;">Delete</a>
</td>

                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>

<?php $conn->close(); ?>

