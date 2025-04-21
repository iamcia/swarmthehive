<?php
// Enable error reporting to display issues during development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection
$servername = "localhost";
$db_username = "u113232969_Hives"; 
$db_password = "theSwarm4"; 
$dbname = "u113232969_SWARM";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Handle row deletion
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $sql = "DELETE FROM ownertenantreservation WHERE Resident_Code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $delete_id);  // Resident_Code is now VARCHAR
    if ($stmt->execute()) {
        echo "<script>alert('Reservation deleted successfully.');</script>";
    } else {
        echo "<script>alert('Error deleting reservation: " . $conn->error . "');</script>";
    }
    $stmt->close();

    // Redirect to the same page to clear POST data and avoid duplicate deletion on refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle status updates
if (isset($_POST['update_status_id'])) {
    $update_id = $_POST['update_status_id'];
    $status = $_POST['status'];
    $sql = "UPDATE ownertenantreservation SET status = ? WHERE Resident_Code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $status, $update_id);
    $stmt->execute();
    $stmt->close();

    // Redirect to the same page to clear POST data and avoid duplicate action on refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Search and filter logic
$searchKeyword = isset($_GET['search']) ? $_GET['search'] : '';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT * FROM ownertenantreservation WHERE 1=1";

if ($searchKeyword) {
    $sql .= " AND (Resident_Code LIKE ? OR user_type LIKE ? OR user_email LIKE ?)";
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
  <!-- Main Content -->
  <div class="main-content">
    <H2>Owner Tenant Reservations</h2>
<div class="search-filter-container">
        <!-- Search and filter form -->
        <form method="GET" action="">
            <div class="form-group">
                <input type="text" name="search" placeholder="Search by Code or usertype or email" value="<?php echo htmlspecialchars($searchKeyword); ?>">
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

        <!-- Reservation Table -->
        <div class="table-container" style="overflow-y: auto; height: 250px;">
            <table border="2" cellpadding="6">
                <thead>
                    <tr>
                        <th>Resident Code</th>
                        <th>User Type</th>
                        <th>User Email</th>
                        <th>Amenity</th>
                        <th>Reservation Date</th>
                        <th>Reservation Time</th>
                        <th>Number of People</th>
                        <th>Additional Request</th>
                        <th>Reservation Created At</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['Resident_Code']; ?></td>
                                <td><?php echo $row['user_type']; ?></td>
                                <td><?php echo $row['user_email']; ?></td>
                                <td><?php echo $row['amenity']; ?></td>
                                <td><?php echo $row['reservation_date']; ?></td>
                                <td><?php echo $row['reservation_time']; ?></td>
                                <td><?php echo $row['number_of_people']; ?></td>
                                <td><?php echo $row['additional_request']; ?></td>
                                <td><?php echo $row['reservation_created_at']; ?></td>
                                <td><?php echo $row['status']; ?></td>
                                <td>
                                    <!-- Approve/Disapprove Form -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="update_status_id" value="<?php echo $row['Resident_Code']; ?>">
                                        <select name="status">
                                            <option value="Pending" <?php if($row['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                                            <option value="Approved" <?php if($row['status'] == 'Approved') echo 'selected'; ?>>Approved</option>
                                            <option value="Disapproved" <?php if($row['status'] == 'Disapproved') echo 'selected'; ?>>Disapproved</option>
                                        </select>
                                        <button type="submit">Update</button>
                                    </form>
                                    <!-- Delete Form -->
                                        <form method="POST" onsubmit="return confirmDelete('<?php echo $row['Resident_Code']; ?>');" style="display: inline;">
        <input type="hidden" name="delete_id" value="<?php echo $row['Resident_Code']; ?>">
        <button type="submit">Delete</button>
    </form>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11">No reservations found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function confirmDelete(id) {
            return confirm("Are you sure you want to delete this reservation?");
        }
    </script>
    </main>
</body>
</html>
<?php $conn->close(); ?>
