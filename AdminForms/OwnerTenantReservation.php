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
    $stmt->bind_param('s', $delete_id);
    if ($stmt->execute()) {
        echo "<script>alert('Reservation deleted successfully.');</script>";
    } else {
        echo "<script>alert('Error deleting reservation: " . $conn->error . "');</script>";
    }
    $stmt->close();
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
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Initialize filtering variables
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['reservation_date']) ? $_GET['reservation_date'] : '';
$time_filter = isset($_GET['reservation_time']) ? $_GET['reservation_time'] : '';

// Build the SQL query with filters
$sql = "SELECT * FROM ownertenantreservation WHERE 1=1";
if (!empty($status_filter)) {
    $sql .= " AND status = '" . $conn->real_escape_string($status_filter) . "'";
}
if (!empty($date_filter)) {
    $sql .= " AND reservation_date = '" . $conn->real_escape_string($date_filter) . "'";
}
if (!empty($time_filter)) {
    $sql .= " AND reservation_time = '" . $conn->real_escape_string($time_filter) . "'";
}
$result = $conn->query($sql);

// Debugging: Check if query was executed successfully
if (!$result) {
    die("Error executing query: " . $conn->error);
}
?> 

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
  <link
      href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css"
      rel="stylesheet"
    />
  <link rel="stylesheet" href="sidebar.css">
  <link rel="stylesheet" href="dashboard.css">
 <style>
   body {
        background-color: #121212; /* Dark background */
        color: #E0E0E0; /* Light text color */
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        padding: 20px;
        margin: 0;
    }
    
    .table-container {
        max-height: 400px; /* Adjusted for better visibility */
        overflow-y: auto;
        margin-bottom: 20px;
        border: 1px solid #444;
        border-radius: 8px;
        background-color: #1E1E1E; /* Slightly darker background */
        color: #DDD;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    th, td {
        padding: 12px;
        border: 1px solid #444;
        text-align: left;
    }
    
    th {
        background-color: #333;
        color: #E0E0E0;
        position: sticky;
        top: 0;
        z-index: 1;
        font-weight: bold;
    }
    
    td {
        background-color: #222;
        color: #DDD;
    }

    td:hover {
        background-color: #333;
        cursor: pointer;
    }

    button, select {
        background-color: #4CAF50;
        color: #FFF;
        border: none;
        padding: 8px 16px;
        margin: 5px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
        transition: background-color 0.3s ease;
    }
    
    button:hover, select:hover {
        background-color: #45A049;
    }
    
    h2 {
        color: #F0F0F0;
        margin-top: 20px;
        font-size: 24px;
    }
    
    .filter-container {
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .filter-container label {
        font-weight: bold;
    }
    
    select {
        background-color: #333;
        color: #E0E0E0;
        border-radius: 4px;
        padding: 8px;
        border: 1px solid #444;
    }
    
    select:focus, button:focus {
        outline: none;
        box-shadow: 0 0 5px rgba(72, 163, 72, 0.8);
    }

    button {
        transition: background-color 0.3s ease;
    }
    
     /* Container for the table with scrollbar */
        .table-container {
            max-width: 400%; /* Adjust width as needed */
            max-height: 250px; /* Adjust height as needed */
            overflow-y: auto; /* Enables vertical scrollbar */
            overflow-x: auto; /* Enables horizontal scrollbar */
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Table styling */
        table {
            width: 100%; /* Ensures table fits container width */
            border-collapse: collapse;
        }

        th, td {
            padding: 2px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #a52a2a; /* Darker color for header */
            color: #ffffff;
        }

        /* Hover effect for table rows */
        tr:hover {
            background-color: #f5f5f5;
        }
</style>
<body>
<div class="container">
      <aside>
           
         
         <!-- end top -->
          <div class="sidebar">

          <div class="top">
           <div class="close" id="close_btn">
            <span class="material-symbols-sharp">close</span>
           </div>
         </div>

           <a href="AdminHomepage.php" class="active">
              <span class="bx bx-grid-alt"></span>
              <h3>Overview</h3></a>
           <a href="UserManagement.php">       <!-- Owner, Tenant, Occupants, Helper, Contacts, Proof of Residency tables-->
              <span class="bx bx-user"></span>
              <h3>User Management</h3>
           </a>
            <a href="OwnerTenantAnnouncement.php">       <!-- OwnerTenantAnnouncement table-->
              <span class="bx bx-grid-alt"></span>
              <h3>Announcements</h3></a>
           <a href="AuditTrail.php">                        <!-- Audit Trail table-->
              <span class="bx bx-user"></span>
              <h3>Audit Logs</h3>
           </a>
           <a href="OwnerTenantMoveIn.php">                        <!-- Move In Table-->
              <span class="bx bx-file"></span>
              <h3>Move In</h3>
           </a>
          <a href="OwnerTenantMoveOut.php">                         <!-- Move Out Table-->
              <span class="bx bx-wallet"></span>
              <h3>Move Out</h3>
           </a>
           <a href="#">                        <!-- OwnerTenantReservation and Pool Tables-->
              <span class="bx bx-chat"></span>
              <h3>Amenitie Reservations</h3>
           </a>
            <a href="#">                       <!-- Pets Tables-->
            <span class="bx bx-file-blank"></span>
            <h3>Pet Registration</h3>
         </a>
            <a href="#">                       <!-- Gatepass, Guest Check In Tables-->
            <span class="bx bx-file-blank"></span>
            <h3>Security Forms</h3>
         </a>
            <a href="#">                       <!-- Work Permit Table-->
            <span class="bx bx-file-blank"></span>
            <h3>Maintenance Forms</h3>
         </a>
           <a href="#">
            <span class="bx bx-cog"></span>
            <h3>Settings</h3>
         </a>
            
           <a href="#">
              <span class="bx bx-log-out"></span>
              <h3>Logout</h3>
           </a>
             

          </div>

      </aside>
<div class="container">
    <main>
        <h1>Amenities Reservations</h1>

    <form method="GET" action="OwnerTenantReservation.php">
        <label for="status">Filter by Status:</label>
        <select name="status" id="status" onchange="this.form.submit()">
            <option value="">All</option>
            <option value="Pending" <?= $status_filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Approved" <?= $status_filter == 'Approved' ? 'selected' : '' ?>>Approved</option>
            <option value="Disapproved" <?= $status_filter == 'Disapproved' ? 'selected' : '' ?>>Disapproved</option>
        </select>
    </form> 
    
<form method="GET" action="OwnerTenantReservation.php">
        <label for="reservation_date">Filter by Date:</label>
        <input type="date" name="reservation_date" id="reservation_date" value="<?php echo htmlspecialchars($date_filter); ?>"><br>

        <label for="reservation_time">Filter by Time:</label>
        <input type="time" name="reservation_time" id="reservation_time" value="<?php echo htmlspecialchars($time_filter); ?>">
        
        <button type="submit">Apply DT</button>
</form> 
<button type="reset" onclick="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>';">Reset Filters</button>

        <!-- Reservation Table -->
        <div class="table-container">
            <table>
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
                    <?php if (isset($result) && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['Resident_Code']); ?></td>
                                <td><?php echo htmlspecialchars($row['user_type']); ?></td>
                                <td><?php echo htmlspecialchars($row['user_email']); ?></td>
                                <td><?php echo htmlspecialchars($row['amenity']); ?></td>
                                <td><?php echo htmlspecialchars($row['reservation_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['reservation_time']); ?></td>
                                <td><?php echo htmlspecialchars($row['number_of_people']); ?></td>
                                <td><?php echo htmlspecialchars($row['additional_request']); ?></td>
                                <td><?php echo htmlspecialchars($row['reservation_created_at']); ?></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="update_status_id" value="<?php echo htmlspecialchars($row['Resident_Code']); ?>">
                                        <select name="status">
                                            <option value="Approved" <?php if($row['status'] == 'Approved') echo 'selected'; ?>>Approved</option>
                                            <option value="Disapproved" <?php if($row['status'] == 'Disapproved') echo 'selected'; ?>>Disapproved</option>
                                        </select>
                                        <button type="submit">Update</button>
                                    </form>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this reservation?');">
                                        <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($row['Resident_Code']); ?>">
                                        <button type="submit">Delete</button>
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
    </main>
</div>
</body>
</html>
