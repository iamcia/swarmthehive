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

// Connect to the database
$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['delete'])) {
        $id = $_POST['Resident_Code'];
        $sql = "DELETE FROM ownertenantvisitor WHERE Resident_Code = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Visitor record deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting record: " . $stmt->error;
        }
        $stmt->close();
    }

    if (isset($_POST['update_status'])) {
        $id = $_POST['Resident_Code'];
        $status = $_POST['status'];

        $sql = "UPDATE ownertenantvisitor SET Status = ? WHERE Resident_Code = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $status, $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Visitor status updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating status: " . $stmt->error;
        }
        $stmt->close();
    }

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch all records after redirection
$sql = "SELECT * FROM ownertenantvisitor";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Owner and Tenant Visitors</title>
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
    <h1>Owner/Tenant Visitor Records</h1>

    <table>
        <thead>
            <tr>
                <th>Resident Code</th>
                <th>User Type</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Guest Info</th>
                <th>Valid ID</th>
                <th>Signature</th>
                <th>Status</th>
                <th>Submitted At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['Resident_Code']); ?></td>
        <td><?php echo htmlspecialchars($row['user_type']); ?></td>
        <td><?php echo htmlspecialchars($row['start_date']); ?></td>
        <td><?php echo htmlspecialchars($row['end_date']); ?></td>
        <td><?php echo htmlspecialchars($row['guest_info']); ?></td>
        <td>
            <?php if ($row['valid_id']): ?>
                <img src="<?php echo htmlspecialchars($row['valid_id']); ?>" alt="Valid ID">
            <?php else: ?>
                No valid ID uploaded
            <?php endif; ?>
        </td>
        <td>
            <?php if (!empty($row['signature'])): ?>
                <!-- Ensure signature is encoded properly -->
                <img src="data:image/png;base64,<?php echo base64_encode($row['signature']); ?>" alt="Signature">
            <?php else: ?>
                No signature uploaded
            <?php endif; ?>
        </td>
        <td><?php echo htmlspecialchars($row['Status']); ?></td>
        <td><?php echo htmlspecialchars($row['submitted_at']); ?></td>
        <td>
    <!-- Update Status Form -->
    <form method="POST" action="">
        <input type="hidden" name="Resident_Code" value="<?php echo htmlspecialchars($row['Resident_Code']); ?>">
        <select name="status">
            <option value="Pending" <?php if ($row['Status'] == 'Pending') echo 'selected'; ?>>Pending</option>
            <option value="Approved" <?php if ($row['Status'] == 'Approved') echo 'selected'; ?>>Approved</option>
            <option value="Rejected" <?php if ($row['Status'] == 'Rejected') echo 'selected'; ?>>Rejected</option>
        </select>
        <button type="submit" name="update_status" class="edit-btn">Update</button>
    </form>
    <!-- Delete Record Form -->
    <form method="POST" action="">
        <input type="hidden" name="Resident_Code" value="<?php echo htmlspecialchars($row['Resident_Code']); ?>">
        <button type="submit" name="delete" class="delete-btn" onclick="return confirm('Are you sure you want to delete this record?');">Delete</button>
    </form>
</td>
    </tr>
    <?php endwhile; ?>
</tbody>
</main>
</body>
</html>

<?php
$conn->close();
?>
