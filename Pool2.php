<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection
$servername = "localhost";
$db_username = "u113232969_Hives"; 
$db_password = "theSwarm4"; 
$dbname = "u113232969_SWARM";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch pending pool reservations
$sql = "SELECT Resident_Code, User_Type, names, towerunitnum, schedule, Status FROM poolreserve WHERE Status = 'Pending'";
$result = $conn->query($sql);
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
            background-color: #5DADE2; /* Set the background color */
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
    <div class="header-container">
        <h2>Pool Reservation Requests</h2>
        <button class="redirect-btn" onclick="window.location.href='Pool.php'">Go To Table</button>
    </div>

    <div class="card-container">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php 
                    $names = json_decode($row['names'], true);
                    $formattedNames = $names ? implode(", ", $names) : 'N/A';
                ?>

                <div class="card" onclick="showForm(
                    '<?= htmlspecialchars($row['Resident_Code']) ?>',
                    '<?= htmlspecialchars($row['User_Type']) ?>',
                    '<?= htmlspecialchars($formattedNames) ?>',
                    '<?= htmlspecialchars($row['towerunitnum']) ?>',
                    '<?= htmlspecialchars($row['schedule']) ?>',
                    '<?= htmlspecialchars($row['Status']) ?>'
                )">
                    <h4>Resident Code: <?= htmlspecialchars($row['Resident_Code']) ?></h4>
                    <p>User Type: <?= htmlspecialchars($row['User_Type']) ?></p>
                    <p>Scheduled Date: <?= htmlspecialchars($row['schedule']) ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No pool reservation requests found</p>
        <?php endif; ?>
    </div>
</main>

<!-- Form Display -->
<div id="form-container" class="hidden-form">
    <button class="close-btn" onclick="closeForm()">Close</button>
    <h3>Pool Reservation Details</h3>
    <div class="reservation-details">
        <p><strong>Resident Code:</strong> <span id="form-resident-code"></span></p>
        <p><strong>User Type:</strong> <span id="form-user-type"></span></p>
        <p><strong>Names:</strong> <span id="form-names"></span></p>
        <p><strong>Tower/Unit Number:</strong> <span id="form-towerunitnum"></span></p>
        <p><strong>Schedule:</strong> <span id="form-schedule"></span></p>
        <p><strong>Status:</strong> <span id="form-status"></span></p>
    </div>
</div>
</main>
</html>
</body>

<script>
function showForm(residentCode, userType, names, towerUnitNum, schedule, status) {
    const fields = {
        'form-resident-code': residentCode,
        'form-user-type': userType,
        'form-names': names,
        'form-towerunitnum': towerUnitNum,
        'form-schedule': schedule,
        'form-status': status
    };
    
    Object.entries(fields).forEach(([id, value]) => {
        document.getElementById(id).textContent = value || 'N/A';
    });

    document.getElementById('form-container').style.display = 'block';
}

function closeForm() {
    document.getElementById('form-container').style.display = 'none';
}

function exportPoolReservationToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    doc.text("Pool Reservation Form", 10, 10);

    let y = 20;

    function addText(label, value) {
        doc.text(`${label}: ${value || 'N/A'}`, 10, y);
        y += 10;
    }

    addText("Resident Code", document.getElementById('form-resident-code').textContent);
    addText("User Type", document.getElementById('form-user-type').textContent);
    addText("Names", document.getElementById('form-names').textContent);
    addText("Tower/Unit Number", document.getElementById('form-towerunitnum').textContent);
    addText("Schedule", document.getElementById('form-schedule').textContent);
    addText("Status", document.getElementById('form-status').textContent);

    function addImage(id, x, y) {
        const imgElement = document.getElementById(id);
        if (imgElement && imgElement.src && imgElement.src !== window.location.href) {
            doc.addImage(imgElement.src, 'JPEG', x, y, 50, 50);
        }
    }

    addText("Valid ID", "");
    addImage("form-valid-id", 80, y - 10);
    y += 60;

    doc.save("Pool_Reservation.pdf");
}


</script>

<?php $conn->close(); ?>
