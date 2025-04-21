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
    die("Connection failed: " . $conn->connect_error);
}

// Handle status update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['update_status']) && !empty($_POST['ticket_no']) && !empty($_POST['status'])) {
        $ticketNo = intval($_POST['ticket_no']); 
        $newStatus = $_POST['status'];

        $sql = "UPDATE gatepass SET Status = ? WHERE Ticket_No = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $newStatus, $ticketNo);

        if ($stmt->execute()) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "<script>alert('Error updating status: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    }

    // Handle delete action
    if (isset($_POST['delete_row']) && !empty($_POST['ticket_no'])) {
        $ticketNo = intval($_POST['ticket_no']);

        $stmt = $conn->prepare("DELETE FROM gatepass WHERE Ticket_No = ?");
        $stmt->bind_param("i", $ticketNo);

        if ($stmt->execute()) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "<script>alert('Error deleting row: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    }
}

// Search and filter logic
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : "";
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : "";

$sql = "SELECT * FROM gatepass WHERE 1=1";
$params = [];
$types = "";

if (!empty($searchKeyword)) {
    $sql .= " AND Resident_Code LIKE ?";
    $params[] = "%$searchKeyword%";
    $types .= "s";
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
$result = $stmt->get_result();
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
    <h1>Gate Pass Records</h1>

    <div class="search-filter-container">
        <form method="GET">
            <input type="text" name="search" placeholder="Search by Resident Code" value="<?php echo htmlspecialchars($searchKeyword); ?>">
            <select name="status">
                <option value="">Filter by Status</option>
                <option value="Pending" <?php echo ($statusFilter == "Pending") ? "selected" : ""; ?>>Pending</option>
                <option value="Approved" <?php echo ($statusFilter == "Approved") ? "selected" : ""; ?>>Approved</option>
                <option value="Disapproved" <?php echo ($statusFilter == "Disapproved") ? "selected" : ""; ?>>Disapproved</option>
            </select>
            <button type="submit">Search</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Ticket No</th>
                <th>Resident Code</th>
                <th>User Type</th>
                <th>Date</th>
                <th>Time</th>
                <th>Bearer</th>
                <th>Authorization</th>
                <th>Items</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['Ticket_No']); ?></td>
                    <td><?php echo htmlspecialchars($row['Resident_Code']); ?></td>
                    <td><?php echo htmlspecialchars($row['User_Type']); ?></td>
                    <td><?php echo htmlspecialchars($row['Date']); ?></td>
                    <td><?php echo htmlspecialchars($row['Time']); ?></td>
                    <td><?php echo htmlspecialchars($row['Bearer']); ?></td>
                    <td><?php echo htmlspecialchars($row['Authorization']); ?></td>
                    <td>
    <?php
    $items = json_decode($row['Items'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($items)) {
        foreach ($items as $item) {
            echo "<p><strong>Item:</strong> " . htmlspecialchars($item['description'] ?? 'Unknown') . 
                 " (Qty: " . htmlspecialchars($item['quantity'] ?? '0') . 
                 " " . htmlspecialchars($item['unit'] ?? '') . ")</p>";

            // Check if item_pics exists and is an array (to support multiple images)
            if (!empty($item['item_pics']) && is_array($item['item_pics'])) {
                foreach ($item['item_pics'] as $image) {
                    if (!empty($image)) {
                        echo "<img src='GateItem/" . htmlspecialchars($image) . "' alt='Item Picture' style='max-width: 100px; display: block;'><br>";
                    }
                }
            } else {
                echo "<p>No image available.</p>";
            }
        }
    } else {
        echo "<p>No items available.</p>";
    }
    ?>
</td>

                    <td><?php echo htmlspecialchars($row['Status']); ?></td>
                    <td><?php echo htmlspecialchars($row['Created_At']); ?></td>
                    <td>
                        <form method="POST" action="">
                            <input type="hidden" name="ticket_no" value="<?php echo htmlspecialchars($row['Ticket_No']); ?>">
                            <select name="status">
                                <option value="Pending" <?php echo ($row['Status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Approved" <?php echo ($row['Status'] === 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                <option value="Disapproved" <?php echo ($row['Status'] === 'Disapproved') ? 'selected' : ''; ?>>Disapproved</option>
                            </select>
                            <button type="submit" name="update_status">Update</button>
                        </form>
                        <form method="POST" action="">
                            <input type="hidden" name="ticket_no" value="<?php echo htmlspecialchars($row['Ticket_No']); ?>">
                            <button type="submit" name="delete_row">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</main>

</body>
</html>

<?php
$conn->close();
?>