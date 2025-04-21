<?php
include 'dbconn.php';

session_start();

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];

    // Posting an announcement
    if ($action == "post") {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $message = mysqli_real_escape_string($conn, $_POST['message']);
        $status = 'Pending'; // Default status for new announcements

        // Handle file upload (picture)
        $picture = null;
        if (isset($_FILES['picture']) && $_FILES['picture']['error'] == 0) {
            $targetDir = "AnnouncementID/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true); // Create directory if it doesn't exist
            }
            $targetFile = $targetDir . basename($_FILES['picture']['name']);
            $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

            // Check if the file is an image
            if (getimagesize($_FILES['picture']['tmp_name'])) {
                if ($_FILES['picture']['size'] <= 5000000) { // Max 5MB
                    if (move_uploaded_file($_FILES['picture']['tmp_name'], $targetFile)) {
                        $picture = $targetFile;
                    } else {
                        $_SESSION['message'] = "Sorry, there was an error uploading your file.";
                    }
                } else {
                    $_SESSION['message'] = "Sorry, your file is too large.";
                }
            } else {
                $_SESSION['message'] = "File is not an image.";
            }
        }

        $sql = "INSERT INTO announcements (title, message, created_at, status, picture) VALUES ('$title', '$message', NOW(), '$status', '$picture')";
        if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "Announcement posted successfully.";
        } else {
            $_SESSION['message'] = "Error posting announcement: " . $conn->error;
        }
    }

    // Handle Update Status
    if ($action == "update_status") {
        $announcement_id = $_POST['announcement_id'];
        $status = $_POST['status'];

        $announcement_id = mysqli_real_escape_string($conn, $announcement_id);
        $status = mysqli_real_escape_string($conn, $status);

        $sql = "UPDATE announcements SET status = '$status' WHERE id = $announcement_id";
        if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "Announcement status updated successfully.";
        } else {
            $_SESSION['message'] = "Error updating status: " . $conn->error;
        }
    }

    // Handle Delete Announcement
    elseif ($action == "delete") {
        $announcement_id = $_POST['announcement_id'];

        $announcement_id = mysqli_real_escape_string($conn, $announcement_id);

        $sql = "DELETE FROM announcements WHERE id = $announcement_id";
        if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "Announcement deleted successfully.";
        } else {
            $_SESSION['message'] = "Error deleting announcement: " . $conn->error;
        }
    }

    header("Location: OwnerTenantAnnouncement.php");
    exit();
}

// Fetch announcements
$sql = "SELECT * FROM announcements ORDER BY created_at DESC";
$result = $conn->query($sql);

// Set filters for date, time, and status
$dateFilter = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';
$timeFilter = isset($_GET['filter_time']) ? $_GET['filter_time'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT * FROM announcements WHERE 1";
if (!empty($dateFilter)) {
    $sql .= " AND DATE(created_at) = '$dateFilter'";
}
if (!empty($timeFilter)) {
    $sql .= " AND TIME(created_at) = '$timeFilter'";
}
if (!empty($statusFilter)) {
    $sql .= " AND status = '$statusFilter'";
}
$sql .= " ORDER BY created_at DESC";
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
    <div class="content">
        <h1>Owner/Tenant Announcements</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <p><?= $_SESSION['message'] ?></p>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-container">
            <form method="GET" action="OwnerTenantAnnouncement.php">
                <label for="status">Filter by Status:</label>
                <select name="status" id="status" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="Pending" <?= $statusFilter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Approved" <?= $statusFilter == 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Disapproved" <?= $statusFilter == 'Disapproved' ? 'selected' : '' ?>>Disapproved</option>
                </select>
            </form>

            <form method="GET" action="OwnerTenantAnnouncement.php">
                <label for="filter_date">Filter by Date:</label>
                <input type="date" id="filter_date" name="filter_date" value="<?= htmlspecialchars($dateFilter); ?>">
                <label for="filter_time">Filter by Time:</label>
                <input type="time" id="filter_time" name="filter_time" value="<?= htmlspecialchars($timeFilter); ?>">
                <button type="submit">Apply Filters</button>
            </form>
            <form method="GET" action="OwnerTenantAnnouncement.php" style="display:inline;">
    <button type="submit" style="background-color: #e74c3c; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer;">
        Clear Filters
    </button>
</form>
        </div>

        <div class="table-container">
        <?php if ($result && $result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Title</th>
                    <th>Message</th>
                    <th>Posted On</th>
                    <th>Status</th>
                    <th>Picture</th>
                    <th>Actions</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['title']); ?></td>
                    <td><?= htmlspecialchars($row['message']); ?></td>
                    <td><?= (new DateTime($row['created_at'], new DateTimeZone('UTC')))
                            ->setTimezone(new DateTimeZone('Asia/Manila'))
                            ->format('Y-m-d h:i A'); ?></td>
                    <td><?= htmlspecialchars($row['status']); ?></td>
                    <td>
    <?php if (!empty($row['picture'])): ?>
        <img src="<?= htmlspecialchars($row['picture']); ?>" alt="Announcement Picture" style="width: 100px; height: 100px; object-fit: cover;">
    <?php else: ?>
        <p>No Picture</p>
    <?php endif; ?>
</td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="announcement_id" value="<?= $row['id']; ?>">
                            <input type="hidden" name="action" value="update_status">
                            <select name="status">
                                <option value="Pending" <?= $row['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Approved" <?= $row['status'] == 'Approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="Disapproved" <?= $row['status'] == 'Disapproved' ? 'selected' : '' ?>>Disapproved</option>
                            </select>
                            <button type="submit">Update</button>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="announcement_id" value="<?= $row['id']; ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" style="background-color: #e74c3c;">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>No announcements found.</p>
        <?php endif; ?>
    </div>

        <!-- New Announcement Form -->
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" id="announcement_id" name="announcement_id">
            <input type="hidden" id="action" name="action" value="post">
            <div>
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div>
                <label for="message">Message:</label>
                <textarea id="message" name="message" rows="3" required></textarea>
            </div>
            <div>
                <label for="picture">Upload Picture:</label>
                <input type="file" id="picture" name="picture" accept="image/*">
            </div>
            <button type="submit">Submit</button>
        </form>
    </div>
    </main>
</body>
</html>
