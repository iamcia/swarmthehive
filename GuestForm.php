<?php

include('dbconn.php');
session_start();

// Handle status update
if (isset($_POST['updateStatus'])) {
    $residentCode = $_POST['residentCode'];
    $newStatus = $_POST['status'];
    $updateQuery = "UPDATE guestcheckinout SET Status = '$newStatus' WHERE Resident_Code = '$residentCode'";
    if (!$conn->query($updateQuery)) {
        echo "Error updating status: " . $conn->error;
    }
}

// Handle deletion
if (isset($_POST['deleteRow'])) {
    $residentCode = $_POST['residentCode'];
    $deleteQuery = "DELETE FROM guestcheckinout WHERE Resident_Code = '$residentCode'";
    if (!$conn->query($deleteQuery)) {
        echo "Error deleting row: " . $conn->error;
    }
}

// Search and filter logic
$searchKeyword = isset($_GET['search']) ? $_GET['search'] : '';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT * FROM guestcheckinout WHERE 1=1";

if ($searchKeyword) {
    $sql .= " AND (Resident_Code LIKE ? OR user_type LIKE ?)";
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
    <style>
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
    width: 120%;
    color: #ffffff; /* White text for dark backgrounds */
}

th, td {
    padding: 7px 11px;
    text-align: left;
    border: 2px solid #ddd;
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
    <h2>Guest Check-in/Out Management</h2>
    <div class="search-filter-container">
        <!-- Search and filter form -->
        <form method="GET" action="">
            <div class="form-group">
                <input type="text" name="search" placeholder="Search by Code or usertype" value="<?php echo htmlspecialchars($searchKeyword); ?>">
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

    <?php if ($result->num_rows > 0): ?>
    <!-- Added the scrollable container -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Resident Code</th>
                    <th>User Type</th>
                    <th>Check-in Date</th>
                    <th>Check-out Date</th>
                    <th>Days of Stay</th>
                    <th>Unit Type</th>
                    <th>Guest Information</th>
                    <th>Valid ID</th>
                    <th>Vaccine Card</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['Resident_Code']); ?></td>
                        <td><?php echo htmlspecialchars($row['User_Type']); ?></td>
                        <td><?php echo htmlspecialchars($row['Checkin_Date']); ?></td>
                        <td><?php echo htmlspecialchars($row['Checkout_Date']); ?></td>
                        <td><?php echo htmlspecialchars($row['Days_Of_Stay']); ?></td>
                        <td><?php echo htmlspecialchars($row['Unit_Type']); ?></td>
                        <td>
                            <?php
                            $guestInfo = json_decode($row['Guest_Info'], true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($guestInfo)) {
                                foreach ($guestInfo as $guest) {
                                    echo "<p><strong>Guest No:</strong> " . htmlspecialchars($guest['guest_no']) . "</p>";
                                    echo "<p><strong>Name:</strong> " . htmlspecialchars($guest['name']) . "</p>";
                                    echo "<p><strong>Contact:</strong> " . htmlspecialchars($guest['contact']) . "</p>"; 
                                    echo "<p><strong>Relationship:</strong> " . htmlspecialchars($guest['relationship']) . "</p>";
                                }
                            } else {
                                echo "No guest information available or invalid data.";
                            }
                            ?>
                        </td>
                        <td>
    <?php if ($row['Valid_ID']): ?>
        <img src="<?php echo htmlspecialchars($row['Valid_ID']); ?>" alt="Valid ID" style="width:50px; height:auto;">
    <?php else: ?>
        No
    <?php endif; ?>
</td>
<td>
    <?php if ($row['Vaccine_Card']): ?>
        <img src="<?php echo htmlspecialchars($row['Vaccine_Card']); ?>" alt="Vaccine Card" style="width:50px; height:auto;">
    <?php else: ?>
        No
    <?php endif; ?>
</td>

                        <td><?php echo htmlspecialchars($row['Status']); ?></td>
                        <td><?php echo htmlspecialchars($row['Created_At']); ?></td>
                        <td class="actions">
                            <form method="post">
                                <input type="hidden" name="residentCode" value="<?php echo $row['Resident_Code']; ?>">
                                <select name="status">
                                    <option value="Pending" <?php echo $row['Status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Approved" <?php echo $row['Status'] === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="Disapproved" <?php echo $row['Status'] === 'Disapproved' ? 'selected' : ''; ?>>Disapproved</option>
                                </select>
                                <button type="submit" name="updateStatus">Update</button>
                            </form>
                            <form method="post">
                                <input type="hidden" name="residentCode" value="<?php echo $row['Resident_Code']; ?>">
                                <button type="submit" name="deleteRow" onclick="return confirm('Are you sure you want to delete this record?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <p>No records found in the guest check-in/out table.</p>
    <?php endif; ?>
</main>

<?php
$conn->close();
?>
