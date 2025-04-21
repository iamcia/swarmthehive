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

// Fetch data from the updated pets table
$sql = "SELECT Resident_Code, user_type, owner_name, contact, unit_no, email, pet_name, breed, dob, vaccinated, 
        vaccine_duration, remarks, user_signature, pet_pic, vaccine_card, Status, created_at 
        FROM pets WHERE Status = 'Pending'";
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
        <h2>Pet Registration Requests</h2>
        <button class="redirect-btn" onclick="window.location.href='PetInformation.php'">Go To Table</button>
    </div>

    <div class="card-container">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php 
                    // Use file paths directly from the database
                    $userSignature = !empty($row['user_signature']) ? $row['user_signature'] : ''; 
                    $petPic = !empty($row['pet_pic']) ? $row['pet_pic'] : ''; 
                    $vaccineCard = !empty($row['vaccine_card']) ? $row['vaccine_card'] : ''; 
                ?>

                <div class="card" onclick="showForm(
                    '<?= htmlspecialchars($row['Resident_Code']) ?>',
                    '<?= htmlspecialchars($row['user_type'] ?? 'N/A') ?>',
                    '<?= htmlspecialchars($row['owner_name']) ?>',
                    '<?= htmlspecialchars($row['contact']) ?>',
                    '<?= htmlspecialchars($row['unit_no']) ?>',
                    '<?= htmlspecialchars($row['email']) ?>',
                    '<?= htmlspecialchars($row['pet_name']) ?>',
                    '<?= htmlspecialchars($row['breed']) ?>',
                    '<?= htmlspecialchars($row['dob']) ?>',
                    '<?= htmlspecialchars($row['vaccinated']) ?>',
                    '<?= htmlspecialchars($row['vaccine_duration']) ?>',
                    '<?= htmlspecialchars($row['remarks']) ?>',
                    '<?= $userSignature ?>',
                    '<?= $petPic ?>',
                    '<?= $vaccineCard ?>',
                    '<?= htmlspecialchars($row['Status']) ?>',
                    '<?= htmlspecialchars($row['created_at']) ?>'
                )">
                    <h4>Resident Code: <?= htmlspecialchars($row['Resident_Code']) ?></h4>
                    <p>Owner: <?= htmlspecialchars($row['owner_name']) ?></p>
                    <p>Pet Name: <?= htmlspecialchars($row['pet_name']) ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No pet registration requests found</p>
        <?php endif; ?>
    </div>
</main>

<!-- Form Display -->
<div id="form-container" class="hidden-form">
    <button class="close-btn" onclick="closeForm()">Close</button>
    <h3>Pet Registration Details</h3>
    <div class="pet-details">
        <p><strong>Resident Code:</strong> <span id="form-resident-code"></span></p>
        <p><strong>User Type:</strong> <span id="form-user-type"></span></p>
        <p><strong>Owner Name:</strong> <span id="form-owner-name"></span></p>
        <p><strong>Contact:</strong> <span id="form-contact"></span></p>
        <p><strong>Unit Number:</strong> <span id="form-unit-no"></span></p>
        <p><strong>Email:</strong> <span id="form-email"></span></p>
        <p><strong>Pet Name:</strong> <span id="form-pet-name"></span></p>
        <p><strong>Breed:</strong> <span id="form-breed"></span></p>
        <p><strong>Date of Birth:</strong> <span id="form-dob"></span></p>
        <p><strong>Vaccinated:</strong> <span id="form-vaccinated"></span></p>
        <p><strong>Vaccine Duration (Days):</strong> <span id="form-vaccine-duration"></span></p>
        <p><strong>Remarks:</strong> <span id="form-remarks"></span></p>
        <p><strong>Signature:</strong> 
    <img id="form-user-signature" alt="Signature" style="display: none; width: 150px; height: 75px; object-fit: cover;" />
</p>
<p><strong>Pet Picture:</strong> 
    <img id="form-pet-pic" alt="Pet Picture" style="display: none; width: 150px; height: 75px; object-fit: cover;" />
</p>
<p><strong>Vaccine Certificate:</strong> 
    <img id="form-vaccine-cert" alt="Vaccine Certificate" style="display: none; width: 150px; height: 75px; object-fit: cover;" />
</p>
        <p><strong>Status:</strong> <span id="form-status"></span></p>
        <p><strong>Created At:</strong> <span id="form-created"></span></p>
            <button class="export-btn" onclick="exportToPDF()">Export to PDF</button>
    </div>
</div>

<script>
function showForm(residentCode, userType, ownerName, contact, unitNo, email, petName, breed, dob, vaccinated, vaccineDuration, remarks, userSignature, petPic, vaccineCert, status, createdAt) {
    const fields = {
        'form-resident-code': residentCode,
        'form-user-type': userType,
        'form-owner-name': ownerName,
        'form-contact': contact,
        'form-unit-no': unitNo,
        'form-email': email,
        'form-pet-name': petName,
        'form-breed': breed,
        'form-dob': dob,
        'form-vaccinated': vaccinated,
        'form-vaccine-duration': vaccineDuration,
        'form-remarks': remarks,
        'form-status': status,
        'form-created': createdAt
    };
    
    Object.entries(fields).forEach(([id, value]) => {
        document.getElementById(id).textContent = value || 'N/A';
    });

    setImage('form-user-signature', userSignature);
    setImage('form-pet-pic', petPic);
    setImage('form-vaccine-cert', vaccineCert);

    document.getElementById('form-container').style.display = 'block';
}

function setImage(elementId, filePath) {
    const imgElement = document.getElementById(elementId);
    if (filePath) {
        imgElement.src = filePath;
        imgElement.style.display = 'block';
        imgElement.style.width = '150px';
        imgElement.style.height = '75px';
        imgElement.style.objectFit = 'cover'; // Ensures the image fits without distortion
    } else {
        imgElement.src = "";
        imgElement.style.display = 'none';
    }
}

function closeForm() {
    document.getElementById('form-container').style.display = 'none';
}

function exportToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    doc.text("Pet Registration Form", 10, 10);

    let y = 20;

    function addText(label, value) {
        doc.text(`${label}: ${value}`, 10, y);
        y += 10;
    }

    addText("Resident Code", document.getElementById('form-resident-code').textContent);
    addText("User Type", document.getElementById('form-user-type').textContent);
    addText("Owner Name", document.getElementById('form-owner-name').textContent);
    addText("Contact", document.getElementById('form-contact').textContent);
    addText("Unit No", document.getElementById('form-unit-no').textContent);
    addText("Email", document.getElementById('form-email').textContent);
    addText("Pet Name", document.getElementById('form-pet-name').textContent);
    addText("Breed", document.getElementById('form-breed').textContent);
    addText("Date of Birth", document.getElementById('form-dob').textContent);
    addText("Vaccinated", document.getElementById('form-vaccinated').textContent);
    addText("Vaccine Duration", document.getElementById('form-vaccine-duration').textContent);
    addText("Remarks", document.getElementById('form-remarks').textContent);
    addText("Status", document.getElementById('form-status').textContent);
    addText("Created At", document.getElementById('form-created').textContent);

    function addImage(id, x, y) {
        const imgElement = document.getElementById(id);
        if (imgElement.src) {
            doc.addImage(imgElement.src, 'JPEG', x, y, 30, 30);
        }
    }

    addText("Signature", "");
    addImage("form-user-signature", 80, y - 10);
    y += 40;

    addText("Pet Picture", "");
    addImage("form-pet-pic", 80, y - 10);
    y += 40;

    addText("Vaccine Certificate", "");
    addImage("form-vaccine-cert", 80, y - 10);
    y += 40;

    doc.save("Pet_Registration.pdf");
}
</script>
</main>
</body>
</html>

<?php $conn->close(); ?>
