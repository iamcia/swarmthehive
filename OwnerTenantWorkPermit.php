<?php
// Database connection
$servername = "localhost";
$db_username = "u113232969_Hives"; 
$db_password = "theSwarm4"; 
$dbname = "u113232969_SWARM";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_query = "DELETE FROM workpermit WHERE Resident_Code = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("s", $delete_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Record deleted successfully');</script>";
        // Redirect to the same page to clear the delete_id parameter from the URL
        header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
    $stmt->close();
}

// Handle status update request
if (isset($_POST['update_status'])) {
    $resident_code = $_POST['resident_code'];
    $new_status = $_POST['status'];
    $update_query = "UPDATE workpermit SET status=? WHERE Resident_Code=?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ss", $new_status, $resident_code);

    if ($stmt->execute()) {
        echo "Status updated successfully";
    } else {
        echo "Error updating status: " . $conn->error;
    }
    $stmt->close();
}

// Fetch work permits
$query = "SELECT * FROM workpermit";
$result = $conn->query($query);

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
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
</head>

<style>
       body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 20px;
            text-align: center;
            color: white;
        }
        .card-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            padding: 20px;
        }
        .card {
            padding: 15px;
            width: 250px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            transition: transform 0.3s;
            background-color: #F5B7B1; /* Set the background color */
    color: white; /* Ensure text is readable */
        }
        .card:hover {
            transform: scale(1.05);
        }
        .hidden-form {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            width: 400px;
            text-align: left;
            color: white;
            background: #333;
        }
        .close-btn {
            background: red;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 10px;
        }
        .movein-details p {
            margin: 5px 0;
        }
        
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

.header-container {
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        .redirect-btn {
            position: absolute;
            right: 20px;
            background: #fff;
            color: black;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        .redirect-btn:hover {
            background: #E8E4C9;
        }
        .document-style {
            font-family: "Times New Roman", Times, serif;
            padding: 20px;
            background: #fff;
            border: 1px solid #ccc;
        }
        .export-btn {
            margin-top: 15px;
            padding: 10px;
            background: #28a745;
            color: white;
            border: none;
            cursor: pointer;
        }
        img {
    max-width: 100%;
    height: auto;
    display: block;
    margin-top: 10px;
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
    <h1>Work Permit Requests</h1>
    
    <table>
        <thead>
            <tr>
                <th>Resident Code</th>
                <th>User Type</th>
                <th>User Email</th>
                <th>Work Type</th>
                <th>Owner Name</th>
                <th>Authorize Rep</th>
                <th>Contractor</th>
                <th>Period From</th>
                <th>Period To</th>
                <th>Task Details</th>
                <th>Personnel Details</th>
                <th>Signature</th>
                <th>Status</th>
                <th>Submitted At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['Resident_Code'] . "</td>";
                    echo "<td>" . $row['user_type'] . "</td>";
                    echo "<td>" . $row['user_email'] . "</td>";
                    echo "<td>" . $row['work_type'] . "</td>";
                    echo "<td>" . $row['owner_name'] . "</td>";
                    echo "<td>" . $row['authorize_rep'] . "</td>";
                    echo "<td>" . $row['contractor'] . "</td>";
                    echo "<td>" . $row['period_from'] . "</td>";
                    echo "<td>" . $row['period_to'] . "</td>";
                    echo "<td>" . $row['task_details'] . "</td>";
                    echo "<td>" . $row['personnel_details'] . "</td>";
                    
                    // Display the signature as an image if it's a base64-encoded string
                    if (!empty($row['signature'])) {
                        // Check if 'data:image/png;base64,' is already included; if not, add it
                        $signature_data = strpos($row['signature'], 'data:image') === 0 
                                          ? $row['signature'] 
                                          : 'data:image/png;base64,' . $row['signature'];
                        echo "<td><img src='" . $signature_data . "' alt='Signature' class='signature'/></td>";
                    } else {
                        echo "<td>No signature</td>";
                    }

                    echo "<td>" . $row['status'] . "</td>";
                    echo "<td>" . $row['submitted_at'] . "</td>";
                    echo "<td class='action-buttons'>
                        <form method='POST' action=''>
                            <input type='hidden' name='resident_code' value='" . $row['Resident_Code'] . "'>
                            <select name='status' class='status-select'>
                                <option value='Pending'" . ($row['status'] == 'Pending' ? 'selected' : '') . ">Pending</option>
                                <option value='Approved'" . ($row['status'] == 'Approved' ? 'selected' : '') . ">Approved</option>
                                <option value='Disapproved'" . ($row['status'] == 'Disapproved' ? 'selected' : '') . ">Disapproved</option>
                                <option value='Completed'" . ($row['status'] == 'Completed' ? 'selected' : '') . ">Completed</option>
                            </select>
                            <button type='submit' name='update_status' class='btn btn-update'>Update</button>
                        </form>
                        <a href='OwnerTenantWorkPermit.php?delete_id=" . $row['Resident_Code'] . "' class='btn btn-delete'>Delete</a>
                    </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='15'>No work permit requests found</td></tr>";
            }
            ?>
        </tbody>
    </table>
    </main>
</body>
</html>
