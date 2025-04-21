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

// Fetch data from the table
$sql = "SELECT Resident_Code, Resident_Name, parkingSlotNumber, days_prior_moveout, representativeName, Resident_Contact, Signature, Status, created_at FROM ownertenantmoveout WHERE Status = 'Pending'";
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
            background-color: #196F3D; /* Set the background color */
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
        <h2>Owner Tenant Move-Out Requests</h2>
        <button class="redirect-btn" onclick="window.location.href='OwnerTenantMoveOut.php'">Go To Table</button>
    </div>
    <div class="card-container">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="card" onclick="showForm('<?php echo $row['Resident_Code']; ?>', '<?php echo $row['Resident_Name']; ?>', '<?php echo $row['parkingSlotNumber']; ?>', '<?php echo $row['days_prior_moveout']; ?>', '<?php echo $row['representativeName']; ?>', '<?php echo $row['Resident_Contact']; ?>', '<?php echo $row['Signature']; ?>', '<?php echo $row['Status']; ?>', '<?php echo $row['created_at']; ?>')">
                    <h4>Resident Code: <?php echo $row['Resident_Code']; ?></h4>
                    <p>Resident Name: <?php echo $row['Resident_Name']; ?></p>
                    <p>Parking Slot: <?php echo $row['parkingSlotNumber']; ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No move-out requests found</p>
        <?php endif; ?>
    </div>

    <div id="form-container" class="hidden-form">
        <button class="close-btn" onclick="closeForm()">Close</button>
        <h3>Move-Out Request Details</h3>
        <div class="moveout-details">
            <p><strong>Resident Code:</strong> <span id="form-resident-code"></span></p>
            <p><strong>Resident Name:</strong> <span id="form-resident-name"></span></p>
            <p><strong>Parking Slot:</strong> <span id="form-parking-slot"></span></p>
            <p><strong>Days Prior to Move-Out:</strong> <span id="form-days-prior"></span></p>
            <p><strong>Representative:</strong> <span id="form-representative"></span></p>
            <p><strong>Contact:</strong> <span id="form-contact"></span></p>
            <p><strong>Signature:</strong> <span id="form-signature"></span></p>
            <p><strong>Status:</strong> <span id="form-status"></span></p>
            <p><strong>Created At:</strong> <span id="form-created"></span></p>
        </div>
        <button class="export-btn" onclick="exportPDF()">Export to PDF</button>
    </div>
</main>

<script>
    function showForm(residentCode, residentName, parkingSlot, daysPrior, representative, contact, signature, status, createdAt) {
        document.getElementById('form-resident-code').textContent = residentCode;
        document.getElementById('form-resident-name').textContent = residentName;
        document.getElementById('form-parking-slot').textContent = parkingSlot;
        document.getElementById('form-days-prior').textContent = daysPrior;
        document.getElementById('form-representative').textContent = representative;
        document.getElementById('form-contact').textContent = contact;
        document.getElementById('form-signature').textContent = signature;
        document.getElementById('form-status').textContent = status;
        document.getElementById('form-created').textContent = createdAt;
        document.getElementById('form-container').style.display = 'block';
    }

    function closeForm() {
        document.getElementById('form-container').style.display = 'none';
    }

    function exportPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        let content = `
            Move-Out Request Details
            ------------------------
            Resident Code: ${document.getElementById('form-resident-code').textContent}
            Resident Name: ${document.getElementById('form-resident-name').textContent}
            Parking Slot: ${document.getElementById('form-parking-slot').textContent}
            Days Prior to Move-Out: ${document.getElementById('form-days-prior').textContent}
            Representative: ${document.getElementById('form-representative').textContent}
            Contact: ${document.getElementById('form-contact').textContent}
            Signature: ${document.getElementById('form-signature').textContent}
            Status: ${document.getElementById('form-status').textContent}
            Created At: ${document.getElementById('form-created').textContent}
        `;

        doc.setFont("times", "normal");
        doc.text(content, 10, 10);
        doc.save("moveout-details.pdf");
    }
</script>
</body>
</html>
<?php $conn->close(); ?>
