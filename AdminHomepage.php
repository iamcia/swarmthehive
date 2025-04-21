<?php
include 'dbconn.php';

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// PENDING STATUS
$tables = [
    'pets' => 'Pets',
    'ownertenantconcerns' => 'Owner/Tenant Concerns',
    'ownertenantreservation' => 'Owner/Tenant Reservation',
    'workpermit' => 'Work Permit',
    'ownertenantmovein' => 'Owner/Tenant Move-In',
    'ownertenantmoveout' => 'Owner/Tenant Move-Out',
    'guestcheckinout' => 'Guest Check-In/Out',
    'gatepass' => 'Gate Pass',
    'poolreserve' => 'Pool Reservation',
    'announcements' => 'Announcements',
    'ownerinformation' => 'Owner Records',
    'tenantinformation' => 'Tenant Records'
];

$pending_counts = [];
$total_pending = 0;

foreach ($tables as $table => $displayName) {
    $query = "SELECT COUNT(*) AS count FROM `$table` WHERE `Status` = 'Pending'";
    $result = $conn->query($query);
    
    if ($result) {
        $row = $result->fetch_assoc();
        $count = (int) $row['count'];

        $pending_counts[$displayName] = $count;
        $total_pending += $count;
    } else {
        $pending_counts[$displayName] = 0;
    }
}

$pending_counts_json = json_encode($pending_counts);

//DISAPPROVED STATUS
$tables2 = [
    'pets' => 'Pets',
    'ownertenantconcerns' => 'Owner/Tenant Concerns',
    'ownertenantreservation' => 'Owner/Tenant Reservation',
    'workpermit' => 'Work Permit',
    'ownertenantmovein' => 'Owner/Tenant Move-In',
    'ownertenantmoveout' => 'Owner/Tenant Move-Out',
    'guestcheckinout' => 'Guest Check-In/Out',
    'gatepass' => 'Gate Pass',
    'poolreserve' => 'Pool Reservation',
    'announcements' => 'Announcements',
    'ownerinformation' => 'Owner Records',
    'tenantinformation' => 'Tenant Records'
];

$pending_counts2 = [];
$total_pending2 = 0;

// Loop through tables and fetch disapproved counts
foreach ($tables2 as $table2 => $displayName) {
    $query = "SELECT COUNT(*) AS count FROM `$table2` WHERE `Status` = 'Disapproved'";
    $result = $conn->query($query);
    
    if ($result) {
        $row = $result->fetch_assoc();
        $count = (int) $row['count'];

        $pending_counts2[$displayName] = $count; // ✅ Corrected line
        $total_pending2 += $count;
    } else {
        $pending_counts2[$displayName] = 0;
    }
}

// Convert data to JSON format for JavaScript
$pending_counts_json2 = json_encode($pending_counts2);

//APPROVED STATUS
$tables3 = [
    'pets' => 'Pets',
    'ownertenantconcerns' => 'Owner/Tenant Concerns',
    'ownertenantreservation' => 'Owner/Tenant Reservation',
    'workpermit' => 'Work Permit',
    'ownertenantmovein' => 'Owner/Tenant Move-In',
    'ownertenantmoveout' => 'Owner/Tenant Move-Out',
    'guestcheckinout' => 'Guest Check-In/Out',
    'gatepass' => 'Gate Pass',
    'poolreserve' => 'Pool Reservation',
    'announcements' => 'Announcements',
    'ownerinformation' => 'Owner Records',
    'tenantinformation' => 'Tenant Records'
];

$pending_counts3 = [];
$total_pending3 = 0;

// Loop through tables and fetch disapproved counts
foreach ($tables3 as $table3 => $displayName) {
    $query = "SELECT COUNT(*) AS count FROM `$table3` WHERE `Status` = 'Approved'";
    $result = $conn->query($query);
    
    if ($result) {
        $row = $result->fetch_assoc();
        $count = (int) $row['count'];

        $pending_counts3[$displayName] = $count; // ✅ Corrected line
        $total_pending3 += $count;
    } else {
        $pending_counts3[$displayName] = 0;
    }
}

// Convert data to JSON format for JavaScript
$pending_counts_json3 = json_encode($pending_counts3);

//USER MANAGEMENT: OWNER AND TENANT
$tables4 = [
    'ownerinformation' => 'Owner Records',
    'tenantinformation' => 'Tenant Records'
];

$pending_counts4 = [];
$total_pending4 = 0;

// Loop through tables and fetch disapproved counts
foreach ($tables4 as $table4 => $displayName) {
    $query = "SELECT COUNT(*) AS count FROM `$table3`";
    $result = $conn->query($query);
    
    if ($result) {
        $row = $result->fetch_assoc();
        $count = (int) $row['count'];

        $pending_counts4[$displayName] = $count; // ✅ Corrected line
        $total_pending4 += $count;
    } else {
        $pending_counts4[$displayName] = 0;
    }
}

// Convert data to JSON format for JavaScript
$pending_counts_json4 = json_encode($pending_counts4);

//AUDIT TRAIL: AUDIT LOGS
$tables5 = [
    'audittrail' => 'Audit Trail'
];

$pending_counts5 = [];
$total_pending5 = 0;

// Loop through tables and fetch disapproved counts
foreach ($tables5 as $table5 => $displayName) {
    $query = "SELECT COUNT(*) AS count FROM `$table3`";
    $result = $conn->query($query);
    
    if ($result) {
        $row = $result->fetch_assoc();
        $count = (int) $row['count'];

        $pending_counts5[$displayName] = $count; // ✅ Corrected line
        $total_pending5 += $count;
    } else {
        $pending_counts4[$displayName] = 0;
    }
}

// Convert data to JSON format for JavaScript
$pending_counts_json5 = json_encode($pending_counts5);
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
    <h1>Dashboard</h1>

    <div class="insights">
        <!-- Start For Approval -->
<div class="approval" onclick="openModal()">
    <span class="material-symbols-sharp">trending_up</span>
    <div class="middle">
        <div class="left">
            <h3>Pending</h3>
            <h1><?php echo $total_pending; ?></h1>
        </div>
    </div>
    <small>Last 24 Hours</small>
</div>
        <!-- End For Approval -->

        <!-- Start Pending -->
        <div class="pending">
            <span class="material-symbols-sharp">hourglass_bottom</span>
            <div class="middle">
                <div class="left">
                    <h3>Rejected</h3>
                    <h1><?php echo $total_pending2; ?></h1>
                    <ul id="pendingList"></ul>
                </div>
            </div>
            <small>Last 24 Hours</small>
        </div>
        <!-- End Pending -->

        <!-- Start Completed -->
        <div class="completed">
            <span class="material-symbols-sharp">check_circle</span>
            <div class="middle">
                <div class="left">
                    <h3>Approved</h3>
                    <h1><?php echo $total_pending3; ?></h1>
                </div>
            </div>
            <small>Last 24 Hours</small>
        </div>
        <!-- End Completed -->


        <!-- Start User Management -->
        <div class="user-management large">
            <span class="material-symbols-sharp">group</span>
            <div class="middle">
                <div class="left">
                    <h3>User Management</h3>
                    <h1><?php echo $total_pending4; ?></h1>
                </div>
            </div>
            <small>Active Users</small>
        </div>
        <!-- End User Management -->

        <!-- Start Audit Logs -->
        <div class="audit-logs">
            <span class="material-symbols-sharp">assignment</span>
            <div class="middle">
                <div class="left">
                    <h3>Audit Logs</h3>
                   <h1><?php echo $total_pending5; ?></h1>
                </div>
            </div>
            <small>Last Audit</small>
        </div>
        <!-- End Audit Logs -->
</main>
<!-- ------------------ 
      end main 
------------------- -->



     
        <script>
</script>

</body>
</html>