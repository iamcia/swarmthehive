<?php
// Database connection
$servername = "localhost";
$db_username = "u113232969_Hives";
$db_password = "theSwarm4";
$dbname = "u113232969_SWARM";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch pending work permit requests
$sql = "SELECT Resident_Code, user_type, user_email, work_type, owner_name, authorize_rep, contractor, period_from, period_to, task_details, personnel_details, status FROM workpermit WHERE status = 'Pending'";
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
    <div class="header-container">
        <h2>Work Permit Requests</h2>
        <button class="redirect-btn" onclick="window.location.href='OwnerTenantWorkPermit.php'">Go To Table</button>
    </div>

    <div class="card-container">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                // Determine card color based on work type
                $workType = $row['work_type'];
                $cardColor = "";

                if ($workType === "Installation") {
                    $cardColor = "#99A3A4";
                } elseif ($workType === "Renovation") {
                    $cardColor = "#F39C12";
                } elseif ($workType === "Maintenance") {
                    $cardColor = "#F5F0E8";
                }
                ?>

                <div class="card" style="background-color: <?= $cardColor ?>;" onclick="showForm(
                    '<?= htmlspecialchars($row['Resident_Code']) ?>',
                    '<?= htmlspecialchars($row['user_type']) ?>',
                    '<?= htmlspecialchars($row['user_email']) ?>',
                    '<?= htmlspecialchars($row['work_type']) ?>',
                    '<?= htmlspecialchars($row['owner_name']) ?>',
                    '<?= htmlspecialchars($row['authorize_rep']) ?>',
                    '<?= htmlspecialchars($row['contractor'] ?? 'N/A') ?>',
                    '<?= htmlspecialchars($row['period_from']) ?>',
                    '<?= htmlspecialchars($row['period_to']) ?>',
                    '<?= htmlspecialchars($row['task_details'] ?? 'N/A') ?>',
                    '<?= htmlspecialchars($row['personnel_details'] ?? 'N/A') ?>',
                    '<?= htmlspecialchars($row['status']) ?>'
                )">
                    <h4>Resident Code: <?= htmlspecialchars($row['Resident_Code']) ?></h4>
                    <p>Work Type: <?= htmlspecialchars($row['work_type']) ?></p>
                    <p>Period: <?= htmlspecialchars($row['period_from']) ?> to <?= htmlspecialchars($row['period_to']) ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No work permit requests found</p>
        <?php endif; ?>
    </div>

    <!-- Form Display -->
    <div id="form-container" class="hidden-form">
        <button class="close-btn" onclick="closeForm()">Close</button>
        <h3>Work Permit Request Details</h3>
        <div class="permit-details">
            <p><strong>Resident Code:</strong> <span id="form-resident-code"></span></p>
            <p><strong>User Type:</strong> <span id="form-user-type"></span></p>
            <p><strong>Email:</strong> <span id="form-user-email"></span></p>
            <p><strong>Work Type:</strong> <span id="form-work-type"></span></p>
            <p><strong>Owner Name:</strong> <span id="form-owner-name"></span></p>
            <p><strong>Authorized Representative:</strong> <span id="form-authorize-rep"></span></p>
            <p><strong>Contractor:</strong> <span id="form-contractor"></span></p>
            <p><strong>Period From:</strong> <span id="form-period-from"></span></p>
            <p><strong>Period To:</strong> <span id="form-period-to"></span></p>
            <p><strong>Task Details:</strong> <span id="form-task-details"></span></p>
            <p><strong>Personnel Details:</strong> <span id="form-personnel-details"></span></p>
            <p><strong>Status:</strong> <span id="form-status"></span></p>
        </div>
        <button onclick="exportWorkPermitToPDF()">Export to PDF</button>
    </div>
</main>


<script>
        function showForm(residentCode, userType, email, workType, ownerName, authorizeRep, contractor, periodFrom, periodTo, status) {
            const fields = {
                'form-resident-code': residentCode,
                'form-user-type': userType,
                'form-user-email': email,
                'form-work-type': workType,
                'form-owner-name': ownerName,
                'form-authorize-rep': authorizeRep,
                'form-contractor': contractor,
                'form-period-from': periodFrom,
                'form-period-to': periodTo,
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

        function exportWorkPermitToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            let y = 20;

            function addText(label, value) {
                doc.text(`${label}: ${value || 'N/A'}`, 10, y);
                y += 10;
            }

            doc.text("Work Permit Request", 10, 10);
            addText("Resident Code", document.getElementById('form-resident-code').textContent);
            addText("User Type", document.getElementById('form-user-type').textContent);
            addText("Email", document.getElementById('form-user-email').textContent);
            addText("Work Type", document.getElementById('form-work-type').textContent);
            addText("Owner Name", document.getElementById('form-owner-name').textContent);
            addText("Authorized Representative", document.getElementById('form-authorize-rep').textContent);
            addText("Contractor", document.getElementById('form-contractor').textContent);
            addText("Period From", document.getElementById('form-period-from').textContent);
            addText("Period To", document.getElementById('form-period-to').textContent);
            addText("Status", document.getElementById('form-status').textContent);
            
            doc.save("Work_Permit_Request.pdf");
        }
    </script>
    </main>
</body>
</html>

