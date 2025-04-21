<?php
include('dbconn.php');
session_start();
date_default_timezone_set('Asia/Manila');


// Calculate the fee based on the number of guests
function calculateFee($numGuests) {
    if ($numGuests <= 3) {
        return $numGuests * 300;
    } else {
        return $numGuests * 500;
    }
}

// Update the status of a reservation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $residentCode = $_POST['resident_code'];
    $newStatus = $_POST['new_status'];
    
    $updateQuery = "UPDATE poolreserve SET Status = ? WHERE Resident_Code = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("si", $newStatus, $residentCode);

    if ($stmt->execute()) {
        echo "<script>alert('Reservation status updated successfully!');</script>";
    } else {
        echo "<script>alert('Error updating status: " . $stmt->error . "');</script>";
    }
    $stmt->close();
    // Redirect to avoid resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Delete a reservation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_reservation'])) {
    $residentCode = $_POST['resident_code'];
    
    $deleteQuery = "DELETE FROM poolreserve WHERE Resident_Code = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $residentCode);

    if ($stmt->execute()) {
        echo "<script>alert('Reservation deleted successfully!');</script>";
    } else {
        echo "<script>alert('Error deleting reservation: " . $stmt->error . "');</script>";
    }
    $stmt->close();
    // Redirect to avoid resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Retrieve all reservations - now including user_id
$query = "SELECT p.*, o.ID as user_id FROM poolreserve p 
          LEFT JOIN ownerinformation o ON p.user_id = o.ID";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pool Reservations</title>
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
      <!-- --------------
        end sidebar
      -------------------- -->

<!-- -------------- 
      start main part 
 --------------- -->

<main>

    <h2>Pool Reservations</h2>
    <table border="1">
        <tr>
            <th>Resident Code</th>
            <th>User Type</th>
            <th>User ID</th>
            <th>Names</th>
            <th>Tower and Unit</th>
            <th>Schedule</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Number of Guests</th>
            <th>Total Fee</th>
            <th>Actions</th>
        </tr>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php $totalFee = calculateFee($row['num_guests']); ?>
                <tr>
                    <td><?php echo $row['Resident_Code']; ?></td>
                    <td><?php echo $row['User_Type']; ?></td>
                    <td><?php echo $row['user_id']; ?></td>
                    <td><?php echo $row['names']; ?></td>
                    <td><?php echo $row['towerunitnum']; ?></td>
                    <td><?php echo $row['schedule']; ?></td>
                    <td><?php echo $row['Status']; ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                    <td><?php echo $row['num_guests']; ?></td>
                    <td><?php echo $totalFee; ?> PHP</td>
                    <td>
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="resident_code" value="<?php echo $row['Resident_Code']; ?>">
                            <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                            <select name="new_status">
                                <option value="Approved">Approved</option>
                                <option value="Disapproved">Disapproved</option>
                            </select>
                            <button type="submit" name="update_status">Update</button>
                        </form>
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="resident_code" value="<?php echo $row['Resident_Code']; ?>">
                            <button type="submit" name="delete_reservation">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="11">No reservations found.</td>
            </tr>
        <?php endif; ?>
    </table>

    <?php $conn->close(); ?>
    </main>
</body>
</html>