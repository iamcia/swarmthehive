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
$sql = "SELECT Resident_Code, User_Type, Checkin_Date, Checkout_Date, Days_Of_Stay, Unit_Type, Guest_Info, Valid_ID, Vaccine_Card, Status, Created_At FROM guestcheckinout WHERE status = 'Pending'";
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
    color: white;
    background-color: #EC1063;
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
        .reservation-details p {
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
        <div class="header-container">
            <h2>Check In and Out Records</h2>
            <button class="redirect-btn" onclick="window.location.href='GuestForm.php'">Go To Table</button>
        </div>
        <div class="card-container">
            <?php while ($row = $result->fetch_assoc()): ?>
    <?php 
        $guestInfo = json_decode($row['Guest_Info'], true);
        $guestInfoFormatted = "No guest information available.";
        if (json_last_error() === JSON_ERROR_NONE && is_array($guestInfo)) {
            $guestInfoFormatted = "";
            foreach ($guestInfo as $guest) {
                $guestInfoFormatted .= "Guest No: " . htmlspecialchars($guest['guest_no']);
                $guestInfoFormatted .= " Name: " . htmlspecialchars($guest['name']);
                $guestInfoFormatted .= " Contact: " . htmlspecialchars($guest['contact']);
                $guestInfoFormatted .= " Relationship: " . htmlspecialchars($guest['relationship']);
            }
        }
    ?>
    <div class="card" 
        data-resident="<?php echo htmlspecialchars($row['Resident_Code']); ?>"
        data-user="<?php echo htmlspecialchars($row['User_Type']); ?>"
        data-checkin="<?php echo htmlspecialchars($row['Checkin_Date']); ?>"
        data-checkout="<?php echo htmlspecialchars($row['Checkout_Date']); ?>"
        data-days="<?php echo htmlspecialchars($row['Days_Of_Stay']); ?>"
        data-unit="<?php echo htmlspecialchars($row['Unit_Type']); ?>"
        data-guest="<?php echo htmlspecialchars($guestInfoFormatted); ?>"
        data-id="<?php echo htmlspecialchars($row['Valid_ID']); ?>"
        data-vaccine="<?php echo htmlspecialchars($row['Vaccine_Card']); ?>"
        data-status="<?php echo htmlspecialchars($row['Status']); ?>"
        data-created="<?php echo htmlspecialchars($row['Created_At']); ?>">
        <h4>Resident Code: <?php echo htmlspecialchars($row['Resident_Code']); ?></h4>
        <p>User Type: <?php echo htmlspecialchars($row['User_Type']); ?></p>
        <p>Check-In: <?php echo htmlspecialchars($row['Checkin_Date']); ?></p>
        <p>Check-Out: <?php echo htmlspecialchars($row['Checkout_Date']); ?></p>
    </div>
<?php endwhile; ?>


        <div id="form-container" class="hidden-form">
            <button class="close-btn" onclick="closeForm()">Close</button>
            <h3>Guest Details</h3>
            <p><strong>Resident Code:</strong> <span id="form-resident-code"></span></p>
            <p><strong>User Type:</strong> <span id="form-user-type"></span></p>
            <p><strong>Check-in Date:</strong> <span id="form-checkin"></span></p>
            <p><strong>Check-out Date:</strong> <span id="form-checkout"></span></p>
            <p><strong>Days of Stay:</strong> <span id="form-days"></span></p>
            <p><strong>Unit Type:</strong> <span id="form-unit"></span></p>
            <p><strong>Guest Info:</strong> <span id="form-guest"></span></p>
<p><strong>Valid ID:</strong> <br> 
    <img id="form-id-image" src="" alt="Valid ID" style="width:100px; height:auto; display:none;">
</p>

<p><strong>Vaccine Card:</strong> <br> 
    <img id="form-vaccine-image" src="" alt="Vaccine Card" style="width:100px; height:auto; display:none;">
</p>
            <p><strong>Status:</strong> <span id="form-status"></span></p>
            <p><strong>Created At:</strong> <span id="form-created"></span></p>
            <button class="export-btn" onclick="exportPDF()">Export to PDF</button>
        </div>
    </main>
    
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll(".card").forEach(card => {
                card.addEventListener("click", function () {
                    let residentCode = this.getAttribute("data-resident");
                    let userType = this.getAttribute("data-user");
                    let checkin = this.getAttribute("data-checkin");
                    let checkout = this.getAttribute("data-checkout");
                    let days = this.getAttribute("data-days");
                    let unit = this.getAttribute("data-unit");
                    let guest = this.getAttribute("data-guest");
                    let id = this.getAttribute("data-id");
                    let vaccine = this.getAttribute("data-vaccine");
                    let status = this.getAttribute("data-status");
                    let created = this.getAttribute("data-created");
                    
                    showForm(residentCode, userType, checkin, checkout, days, unit, guest, id, vaccine, status, created);
                });
            });
        });
        
        
        function closeForm() {
            document.getElementById('form-container').style.display = 'none';
        }
        
        function exportPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            let content = `
                Guest Check-In Details
                ----------------------
                Resident Code: ${document.getElementById('form-resident-code').textContent}
                User Type: ${document.getElementById('form-user-type').textContent}
                Check-in Date: ${document.getElementById('form-checkin').textContent}
                Check-out Date: ${document.getElementById('form-checkout').textContent}
                Days of Stay: ${document.getElementById('form-days').textContent}
                Unit Type: ${document.getElementById('form-unit').textContent}
                Guest Info: ${document.getElementById('form-guest').textContent}
                Valid ID: ${document.getElementById('form-id').textContent}
                Vaccine Card: ${document.getElementById('form-vaccine').textContent}
                Status: ${document.getElementById('form-status').textContent}
                Created At: ${document.getElementById('form-created').textContent}
            `;
            
            doc.setFont("times", "normal");
            doc.text(content, 10, 10);
            doc.save("guest-details.pdf");
        }
        
        function showForm(residentCode, userType, checkin, checkout, days, unit, guest, id, vaccine, status, created) {
    document.getElementById('form-resident-code').textContent = residentCode;
    document.getElementById('form-user-type').textContent = userType;
    document.getElementById('form-checkin').textContent = checkin;
    document.getElementById('form-checkout').textContent = checkout;
    document.getElementById('form-days').textContent = days;
    document.getElementById('form-unit').textContent = unit;
    document.getElementById('form-guest').textContent = guest;
    document.getElementById('form-status').textContent = status;
    document.getElementById('form-created').textContent = created;

    // Display images if available
    let idImage = document.getElementById('form-id-image');
    let vaccineImage = document.getElementById('form-vaccine-image');

    if (id) {
        idImage.src = id;
        idImage.style.display = "block";
    } else {
        idImage.style.display = "none";
    }

    if (vaccine) {
        vaccineImage.src = vaccine;
        vaccineImage.style.display = "block";
    } else {
        vaccineImage.style.display = "none";
    }

    document.getElementById('form-container').style.display = 'block';
}

    </script>
</body>
</html>
<?php $conn->close(); ?>
