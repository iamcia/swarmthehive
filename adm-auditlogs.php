<?php
include 'dbconn.php';
session_start();

// Check if the user is logged in and has the correct position (Finance)
if (!isset($_SESSION['Management_Code']) || $_SESSION['position'] != 'Admin') {
    header("Location: management-index.php");
    exit();
}

date_default_timezone_set('Asia/Manila');

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
    <title>Swarm | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/admin_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/adm-auditlogs-style.css?v=<?php echo time(); ?>">
</head>
<style>
    .main-content {
        padding: 30px;
        background: #f8f9fa;
        border-radius: 15px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    }

    .table-container {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        padding: 20px;
        margin: 20px 0;
        overflow: hidden;
    }

    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 14px;
    }

    th {
        background: #f4f6f8;
        color: #2c3e50;
        font-weight: 600;
        padding: 15px;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e9ecef;
    }

    td {
        padding: 15px;
        border-bottom: 1px solid #e9ecef;
        color: #444;
        vertical-align: middle;
    }

    tr:hover td {
        background: #f8f9fa;
    }

    .filter-container {
        background: #fff;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .filter-container input[type="date"],
    .filter-container input[type="time"] {
        border: 1px solid #dee2e6;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 14px;
    }

    .filter-container label {
        color: #495057;
        font-weight: 500;
    }

    button {
        background: #4361ee;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    button:hover {
        background: #3249d1;
        transform: translateY(-1px);
    }

    button[type="button"] {
        background: #dc3545;
    }

    button[type="button"]:hover {
        background: #c82333;
    }

    .chart-container {
        background: #fff;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        margin-top: 25px;
    }

    .chart-container h3 {
        color: #2c3e50;
        font-size: 18px;
        margin-bottom: 20px;
        font-weight: 600;
    }

    h2 {
        color: #2c3e50;
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 25px;
    }
</style>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo-container">
                <img src="./img/logo swarm.png" alt="Logo" class="logo">
                <span class="logo-text">SWARM</span>
            </div>

            <div class="menu-title">
                <i class='bx bxs-group'></i>
                <span>Admin</span>
            </div>

            <nav class="sidebar-menu">
                <ul>
                    <li>
                        <a href="adm-dashboard.php">
                            <i class='bx bxs-dashboard'></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-usermanage.php">
                            <i class='bx bx-user'></i>
                            <span>User Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-manageannounce.php">
                            <i class='bx bx-notification'></i>
                            <span>Announcement</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-servicerequest.php">
                            <i class='bx bx-file'></i>
                            <span>Service Requests</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-financialrec.php">
                            <i class='bx bx-wallet'></i>
                            <span>Finance Records</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-comminsights.php">
                            <i class='bx bx-chat'></i>
                            <span>Community Insights</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="#">
                            <i class='bx bx-file-blank'></i>
                            <span>Audit Logs</span>
                        </a>
                    </li>
                    <div class="divider"></div>
                    <li>
                        <a href="logout.php" class="nav-item logout">
                            <i class='bx bx-log-out'></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

      <!-- --------------
        end sidebar
      -------------------- -->

      <!-- --------------
        start main part
      --------------- -->

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
    </div>
</main>
</body>
</html>