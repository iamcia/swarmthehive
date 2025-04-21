<?php
include 'dbconn.php';

session_start();

date_default_timezone_set('Asia/Manila');

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize message variable
$message = "";

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    $deleteStmt = $conn->prepare("DELETE FROM audittrail WHERE id = ?");
    $deleteStmt->bind_param("i", $delete_id);
    $deleteStmt->execute();
    $deleteStmt->close();
    $message = "Record successfully deleted.";
}

// Function to update or insert Last_Login for a user
function updateLastLogin($conn, $username) {
    $firstName = null;
    $lastName = null;

    // Check OwnerInformation table for the user
    $ownerStmt = $conn->prepare("SELECT First_Name, Last_Name FROM OwnerInformation WHERE Username = ?");
    $ownerStmt->bind_param("s", $username);
    $ownerStmt->execute();
    $ownerResult = $ownerStmt->get_result();

    // Check TenantInformation table if not found in OwnerInformation
    if ($ownerResult->num_rows > 0) {
        $userRow = $ownerResult->fetch_assoc();
        $firstName = $userRow['First_Name'];
        $lastName = $userRow['Last_Name'];
    } else {
        $tenantStmt = $conn->prepare("SELECT First_Name, Last_Name FROM TenantInformation WHERE Username = ?");
        $tenantStmt->bind_param("s", $username);
        $tenantStmt->execute();
        $tenantResult = $tenantStmt->get_result();

        if ($tenantResult->num_rows > 0) {
            $userRow = $tenantResult->fetch_assoc();
            $firstName = $userRow['First_Name'];
            $lastName = $userRow['Last_Name'];
        }
    }

    if (!$firstName || !$lastName) {
        return;
    }

    $auditStmt = $conn->prepare("SELECT * FROM audittrail WHERE Username = ? AND First_Name = ? AND Last_Name = ?");
    $auditStmt->bind_param("sss", $username, $firstName, $lastName);
    $auditStmt->execute();
    $auditResult = $auditStmt->get_result();

    if ($auditResult->num_rows > 0) {
        $updateStmt = $conn->prepare("UPDATE audittrail SET Last_Login = NOW() WHERE Username = ? AND First_Name = ? AND Last_Name = ?");
        $updateStmt->bind_param("sss", $username, $firstName, $lastName);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        $insertStmt = $conn->prepare("INSERT INTO audittrail (Last_Name, First_Name, Username, Last_Login) VALUES (?, ?, ?, NOW())");
        $insertStmt->bind_param("sss", $lastName, $firstName, $username);
        $insertStmt->execute();
        $insertStmt->close();
    }

    $ownerStmt->close();
    if (isset($tenantStmt)) {
        $tenantStmt->close();
    }
    $auditStmt->close();
}

// Fetch records based on date and time filter if both are provided
$startDate = $_GET['start_date'] ?? '';
$startTime = $_GET['start_time'] ?? '';

$sql = "SELECT id, Last_Name, First_Name, Username, Last_Login FROM audittrail";
$conditions = [];
$params = [];

if ($startDate && $startTime) {
    $conditions[] = "Last_Login >= ? AND Last_Login < ?";
    $startDateTime = $startDate . ' ' . $startTime;
    $endDateTime = $startDate . ' 23:59:59';  // Filter until the end of the selected day
    $params[] = $startDateTime;
    $params[] = $endDateTime;
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY Last_Login DESC";

// Handle delete request only if form was submitted with 'delete_confirm' flag
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && isset($_POST['delete_confirm'])) {
    $usernameToDelete = $_POST['username'];
    
    $stmt = $conn->prepare("DELETE FROM audittrail WHERE Username = ?");
    $stmt->bind_param("s", $usernameToDelete);

    if ($stmt->execute()) {
        $message = "Record deleted successfully.";
        // Redirect to the same page to avoid form resubmission on refresh
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $message = "Error deleting record: " . $conn->error;
    }
    $stmt->close();
}

// Initialize login data for the chart
$loginData = [
    "morning" => 0,  // 6:00 AM - 11:59 AM
    "afternoon" => 0, // 12:00 PM - 5:59 PM
    "evening" => 0   // 6:00 PM - 11:59 PM
];

$chartQuery = "SELECT HOUR(Last_Login) AS login_hour FROM audittrail";
$chartResult = $conn->query($chartQuery);

if ($chartResult) {
    while ($row = $chartResult->fetch_assoc()) {
        $hour = intval($row['login_hour']);
        if ($hour >= 6 && $hour < 12) {
            $loginData['morning']++;
        } elseif ($hour >= 12 && $hour < 18) {
            $loginData['afternoon']++;
        } elseif ($hour >= 18 && $hour <= 23) {
            $loginData['evening']++;
        }
    }
}


// Fetch records to display
$sql = "SELECT Last_Name, First_Name, Username, Last_Login FROM audittrail ORDER BY Last_Login DESC";
$result = $conn->query($sql);


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
    body {
        background-color: #121212;
        color: #E0E0E0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        padding: 20px;
        margin: 0;
    }
    
    .table-container {
        max-height: 350px;
        overflow-y: auto;
        margin-bottom: 20px;
        border: 1px solid #444;
        border-radius: 8px;
        background-color: #1E1E1E;
        color: #DDD;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    
    table {
        width: 80%;
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
    
    .table-container::-webkit-scrollbar {
        width: 8px;
    }

    .table-container::-webkit-scrollbar-thumb {
        background-color: #4CAF50;
        border-radius: 10px;
    }

    .table-container::-webkit-scrollbar-thumb:hover {
        background-color: #45A049;
    }

    button[type="button"] {
    background-color: #f44336; /* Red background */
    color: #FFF; /* White text */
}

button[type="button"]:hover {
    background-color: #e53935; /* Darker red on hover */
}

 .sidebar .dropdown {
  position: relative;
}

.sidebar .dropdown-btn {
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  font-size: 15px; /* Adjusted font size */
  color: white;
}

.sidebar .dropdown-content {
  display: none;
  flex-direction: column;
  padding-left: 18px;
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

.table-container {
            overflow-x: auto;
            overflow-y: auto;
            max-height: 400px;
            border: 1px solid #ccc;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
        }

        .chart-container {
            margin-top: 20px;
            max-width: 600px;
        }
</Style>
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
    <div class="main-content">
        <h2>Audit Trail</h2>
        <?php if (!empty($message)): ?>
            <p><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <div class="filter-container">
            <form method="GET" action="">
                <label for="start_date">Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($_GET['start_date'] ?? ''); ?>">
                
                <label for="start_time">Time:</label>
                <input type="time" id="start_time" name="start_time" value="<?php echo htmlspecialchars($_GET['start_time'] ?? ''); ?>">

                <button type="submit">Filter</button>
                <button type="button" onclick="window.location.href='AuditTrail.php';">Clear</button>
            </form>
        </div>
        
        <div class="table-container">
            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Last Name</th>
                            <th>First Name</th>
                            <th>Username</th>
                            <th>Last Login</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['Last_Name']); ?></td>
                                <td><?php echo htmlspecialchars($row['First_Name']); ?></td>
                                <td><?php echo htmlspecialchars($row['Username']); ?></td>
                                <td><?php echo htmlspecialchars($row['Last_Login']); ?></td>
                                <td>
                                    <form method="POST" action="">
                                        <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($row['Username']); ?>">
                                        <button type="submit" onclick="return confirm('Are you sure you want to delete this record?');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No records found.</p>
            <?php endif; ?>
        </div>

        <div class="chart-container">
            <div id="chartContainer" style="height: 300px; width: 100%;"></div>
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
        </div>
    </div>
</main>
<script>
    window.onload = function () {

var chart = new CanvasJS.Chart("chartContainer", {
	animationEnabled: true,
	title:{
		text: "User Login Patterns"
	},
	axisX:{
		title: "Time of Day",
		labelAngle: -30
	},
	axisY: {
		title: "Login Count",
		labelFormatter: function(e) {
			return e.value;
		}
	},
	data: [{
		type: "area",
		xValueFormatString: "String",
		yValueFormatString: "##0",
		dataPoints: [
			{ label: "Morning", y: <?php echo $loginData['morning']; ?> },
			{ label: "Afternoon", y: <?php echo $loginData['afternoon']; ?> },
			{ label: "Evening", y: <?php echo $loginData['evening']; ?> }
		]
	}]
});
chart.render();

}
</script>
</body>
</html>