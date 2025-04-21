<?php
session_start();

// Database connection settings
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
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Handle update of status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $residentCode = $conn->real_escape_string($_POST['resident_code']);
        $status = $conn->real_escape_string($_POST['status']);

        $updateSql = "UPDATE ownertenantmovein SET Status = ? WHERE Resident_Code = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("ss", $status, $residentCode);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Record updated successfully!";
        } else {
            $_SESSION['message'] = "Error updating record: " . $stmt->error;
        }
        $stmt->close();
        header("Location: OwnerTenantMoveIn.php");
        exit;
    }

    // Handle record deletion
    if (isset($_POST['delete_record'])) {
        $residentCodeToDelete = $conn->real_escape_string($_POST['resident_code_to_delete']);

        $deleteSql = "DELETE FROM ownertenantmovein WHERE Resident_Code = ?";
        $stmt = $conn->prepare($deleteSql);
        $stmt->bind_param("s", $residentCodeToDelete);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Record deleted successfully!";
        } else {
            $_SESSION['message'] = "Error deleting record: " . $stmt->error;
        }
        $stmt->close();
        header("Location: OwnerTenantMoveIn.php");
        exit;
    }

    // Clear filters
    if (isset($_POST['clear_filters'])) {
        header("Location: OwnerTenantMoveIn.php");
        exit;
    }
}

// Search and filter logic
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';

$sql = "SELECT * FROM ownertenantmovein WHERE 1=1";
$params = [];
$types = "";

if (!empty($searchKeyword)) {
    $sql .= " AND Resident_Code LIKE ?";
    $params[] = "%{$searchKeyword}%";
    $types .= "s";
}

if (!empty($filterStatus)) {
    $sql .= " AND Status = ?";
    $params[] = $filterStatus;
    $types .= "s";
}

// Prepare and execute statement
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
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
        <h2>Owner Tenant Move-In Records</h2>

        <div class="search-filter-container">
            <form method="GET" action="">
                <div class="form-group">
                    <input type="text" name="search" placeholder="Search by Resident Code" value="<?php echo htmlspecialchars($searchKeyword); ?>">
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

        <form method="POST" action="OwnerTenantMoveIn.php">
            <button type="submit" name="clear_filters">Clear Filters</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Resident Code</th>
                    <th>Date of Move-In</th>
                    <th>Resident Name</th>
                    <th>Parking Slot Number</th>
                    <th>Lease Expiry Date</th>
                    <th>Representative Name</th>
                    <th>Contact Number</th>
                    <th>Signature</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['Resident_Code']); ?></td>
                            <td><?php echo htmlspecialchars($row['Move_In_Date']); ?></td>
                            <td><?php echo htmlspecialchars($row['Resident_Name']); ?></td>
                            <td><?php echo htmlspecialchars($row['Parking_Slot_Number']); ?></td>
                            <td><?php echo htmlspecialchars($row['Lease_Expiry_Date']); ?></td>
                            <td><?php echo htmlspecialchars($row['Representative_Name']); ?></td>
                            <td><?php echo htmlspecialchars($row['Contact_Number']); ?></td>
                            <td>
                                <?php if (!empty($row['Signature'])) { ?>
                                    <img src="data:image/png;base64,<?php echo base64_encode($row['Signature']); ?>" alt="Signature" class="signature-image">
                                <?php } else { ?>
                                    No Signature
                                <?php } ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['Status']); ?></td>
                            <td><?php echo htmlspecialchars($row['Created_At']); ?></td>
                            <td>
                                <form method="POST" action="OwnerTenantMoveIn.php" style="display:inline-block;">
                                    <input type="hidden" name="resident_code" value="<?php echo htmlspecialchars($row['Resident_Code']); ?>">
                                    <select name="status">
                                        <option value="Pending" <?php if ($row['Status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                                        <option value="Approved" <?php if ($row['Status'] == 'Approved') echo 'selected'; ?>>Approved</option>
                                        <option value="Disapproved" <?php if ($row['Status'] == 'Disapproved') echo 'selected'; ?>>Disapproved</option>
                                    </select>
                                    <button type="submit" name="update_status">Update</button>
                                </form>

                                <form method="POST" action="OwnerTenantMoveIn.php" style="display:inline-block;">
                                    <input type="hidden" name="resident_code_to_delete" value="<?php echo htmlspecialchars($row['Resident_Code']); ?>">
                                    <button type="submit" name="delete_record" onclick="return confirm('Are you sure you want to delete this record?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php }
                } else { ?>
                    <tr><td colspan="11">No records found</td></tr>
                <?php } ?>
            </tbody>
        </table>
    </main>
</body>
</html>