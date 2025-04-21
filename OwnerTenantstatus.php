<?php
include('dbconn.php');
session_start();

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Notifications
$notification = '';
if (isset($_SESSION['notification'])) {
    $notification = $_SESSION['notification'];
    unset($_SESSION['notification']);
}

$ownerId = $_SESSION['user_id']; 
$status = '';

// Fetch status from the ownerinformation table
$sql = "SELECT Status FROM ownerinformation WHERE Owner_ID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $ownerId); 
    $stmt->execute();
    $stmt->bind_result($status);
    $stmt->fetch();
    $stmt->close();

}

// Check if username is set in the session
if (!isset($_SESSION['username'])) {
    die("Username not found in session. Please log in.");
}

$username = $_SESSION['username'];

// Retrieve Owner_ID and Tower/Unit_Number from ownerinformation
$sql = "SELECT Owner_ID, Tower, Unit_Number FROM ownerinformation WHERE Username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Owner information not found for the logged-in user.");
}

$row = $result->fetch_assoc();
$ownerID = $row['Owner_ID'];
$tower = $row['Tower'];
$unitNumber = $row['Unit_Number'];

$stmt->close();

// Fetch tenant information
$tenantInfo = [];
$sql = "SELECT Tower, Unit_Number, Last_Name, First_Name, Middle_Name, Email, Status FROM tenantinformation WHERE Owner_ID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $ownerID);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $tenantInfo[] = $row;
    }

    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $middleName = $_POST['middle_name'];
    $email = $_POST['email'];

    $checkSql = "SELECT Access_Code FROM tenantinformation WHERE Access_Code IS NOT NULL LIMIT 1";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $row = $checkResult->fetch_assoc();
        $accessCode = $row['Access_Code'];

        $insertSql = "INSERT INTO tenantinformation (Owner_ID, Tower, Unit_Number, Last_Name, First_Name, Middle_Name, Email, Status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("sssssss", $ownerID, $tower, $unitNumber, $lastName, $firstName, $middleName, $email);

        if ($insertStmt->execute()) {
            $_SESSION['notification'] = "Tenant added successfully!";
        } else {
            $_SESSION['notification'] = "Error adding tenant: " . $insertStmt->error;
        }

        $insertStmt->close();
    }

    $checkStmt->close();
    header("Location: " . $_SERVER['PHP_SELF'] . "#add-tenant-request");
    exit();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Swarm Portal</title>
    <link rel="stylesheet" href="tenantstatus-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet"/>
<style>
    .displayname {
    font-weight: bold;
    margin-bottom: 30px;
    color: white;
}

.edit-profile-icon {
      font-size: 18px;
      color: white;
      margin-left: 8px;
      cursor: pointer;
      transition: color 0.3s ease;
    }

    .edit-profile-icon:hover {
      color: #FFD700;
    }

.modalprof {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
      z-index: 1;
    }

.modalprof-content {
      background: linear-gradient(to bottom right, #ffffff, #f0f0f0);
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      width: 320px; /* Adjust width */
      max-width: 90%; /* Ensure responsiveness */
      text-align: center;
      position: relative;
    }

.modalprof-content::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 10px;
      background: linear-gradient(to right, #F2C94C, #fe8415);
      border-radius: 10px 10px 0 0;
    }

.profile-picture {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      cursor: pointer;
      margin-bottom: 10px;
      border: 2px solid #F2C94C;
      transition: border-color 0.3s ease;
    }

.profile-picture:hover {
      border-color: #fe8415;
    }

.upload-btn {
      display: none;
    }

.name-input {
      width: calc(100% - 20px);
      padding: 10px;
      margin-top: 10px;
      border-radius: 5px;
      border: 1px solid #ccc;
      transition: border-color 0.3s ease;
      box-sizing: border-box;
    }

.name-input:focus {
      border-color: #F2C94C;
      outline: none;
    }

.save-btn {
      background-color: #4CAF50;
      color: white;
      padding: 10px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      width: 100%;
      margin-top: 10px;
      transition: background-color 0.3s ease;
      box-sizing: border-box;
    }

.save-btn:hover {
      background-color: #45a049;
    }

.close-btn {
    position: absolute;
    top: 10px;
    right: 15px;
    color: #333;
    font-size: 24px;
    cursor: pointer;
    transition: color 0.3s ease;
}

.close-btn:hover {
    color: #fe8415; /* Change color on hover */
}
</style>
</head>
<body>
<header class="header">
    <img src="/img/swarm logo.png" border='0'/>
    <div class="search-container">
    <div class="search-box">
        <input type="text" id="search" placeholder="Search?" />
        <button id="send-btn">
            <img src="/img/bee-search.png" class="bee-icon" />
        </button>
    </div>
</div>

    <div class="right-container">
      <button id="inboxButton" class="inbox-btn"></button>
    <button id="notifButton" class="notif-btn"></button>
    <button class="settings-btn" onclick="window.location.href='settings.php';"></button>
    <div class="separator" style="width: 1px; height: 24px; background-color: #ffffff; margin: 0 10px;"></div>
    <button class="logout-btn" onclick="window.location.href='index.php';"></button>
    
<!-- Hidden messages container SAMPLE BUT NEED TO UPDATE THIS ONE -->
<div class="hidden-inbox" id="hiddenInbox">
    <div class="message">Message 1</div>
    <div class="message">Message 2</div>
    <div class="message">Message 3</div>
    <!-- Add more messages as needed -->
</div>

    <!-- Hidden Notification Dropdown SAMPLE BUT NEED TO UPDATE THIS ONE-->
    <div id="hiddenNotif" class="hidden-notif">
        <!-- Example notifications -->
        <div class="notification">You have a new message.</div>
        <div class="notification">Reminder: Meeting at 3 PM.</div>
        <div class="notification">System update available.</div>
        <div class="notification">New friend request.</div>
    </div>
        
        </div>
    </div>
</header>
 <div class="container">
        <div class="sidebar">
             <div class="profile">
                      <img src="/img/default-profile-pic.png" alt="Profile Picture" class="avatar" id="mainProfilePicture">
                <div class="displayname" id="mainDisplayname">BINI IRA
              <i class="fas fa-edit edit-profile-icon" onclick="openEditProfile()"></i></div> 
            </div>
          <?php if ($status == 'Approved'): ?>
            <div class="navigation-items1">
                <a href="OwnerAnnouncement.php">
			    <img src="/img/announcement.png" width="25px" height="25px" class="nav-icon"  >
				Announcements
                </a>
		    </div>
            <div class="navigation-items1">
                <a href="OwnerServices.php">
			    <img src="/img/services.png" width="25px" height="25px" class="nav-icon">
			    Services
                </a>
			</div>
            <div class="navigation-items1">
                <a href="OwnerPaymentinfo.php">
			    <img src="/img/payment info.png" width="25px" height="25px" class="nav-icon">
			    Payment Info
                </a>
                
                </div>
                <div class="navigation-items highlight">
            <a href="#">
                <img src="/img/tenant status.png" width="25px" height="25px" class="nav-icon" alt="Tenant Status Icon">
                Tenant Status
            </a>
        </div>
                
            <div class="navigation-items1">
                <a href="OwnerSafetyguidelines.php">
			    <img src="/img/safe guidelines.png" width="25px" height="25px" class="nav-icon">
			    Safety Guidelines
                </a>
			</div>
            <div class="navigation-items1">
                <a href="OwnerCommunityfeedback.php">
			    <img src="/img/comm feedback.png" width="25px" height="25px" class="nav-icon">
		        Community Feedback
                </a>
			</div>
			<?php endif; ?>
			
			<?php if ($status == 'Pending'): ?>
            <div class="navigation-items1">
                <a href="OwnerAnnouncement.php">
			    <img src="/img/announcement.png" width="25px" height="25px" class="nav-icon"  >
				Announcements
                </a>
		    </div>
            <div class="navigation-items1">
                <a href="#">
			    <img src="/img/services.png" width="25px" height="25px" class="nav-icon">
			    Services
                </a>
			</div>
            <div class="navigation-items1">
                <a href="#">
			    <img src="/img/payment info.png" width="25px" height="25px" class="nav-icon">
			    Payment Info
                </a>
			</div>
			
			<div class="navigation-items highlight">
            <a href="#">
                <img src="/img/tenant status.png" width="25px" height="25px" class="nav-icon" alt="Tenant Status Icon">
                Tenant Status
            </a>
        </div>
			
            <div class="navigation-items1">
                <a href="OwnerSafetyguidelines.php">
			    <img src="/img/safe guidelines.png" width="25px" height="25px" class="nav-icon">
			    Safety Guidelines
                </a>
			</div>
            <div class="navigation-items1">
                <a href="OwnerCommunityfeedback.php">
			    <img src="/img/comm feedback.png" width="25px" height="25px" class="nav-icon">
		        Community Feedback
                </a>
			</div>
        </div>
			<?php endif; ?>
        </div>
        
        <div class="content">
    <div class="main-content">
        <!-- STARTS -->
        <nav id="navigation">
            <a href="#form-status" class="nav-link">FORM STATUS</a>
            <a href="#payment-status" class="nav-link">PAYMENT STATUS</a>
            <a href="#add-tenant-request" class="nav-link">ADD TENANT REQUEST</a>
            <div id="indicator"></div>
        </nav>

<section id="add-tenant-request">
    <div class="instruction-message">
        <h3>Tenant Addition Request Instructions</h3>
        <p>Please follow these instructions before submitting your request:</p>
        <ul>
            <li>Ensure that all fields are filled out correctly.</li>
            <li>Provide a valid email address for the tenant.</li>
            <li>Check that the Tower/Unit number is accurate.</li>
            <li>Once submitted, wait to receive a code and link via Email.</li>
            <li>All requests are subject to review and approval.</li>
        </ul>
        <p>Make sure all information is valid before submitting your request.</p>
    </div>

    <h1>Your Tenants</h1>

    <table border="1">
        <thead>
            <tr>
                <th>Tower</th>
                <th>Unit Number</th>
                <th>Last Name</th>
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tenantInfo as $tenant): ?>
                <tr>
                    <td><?= htmlspecialchars($tenant['Tower']) ?></td>
                    <td><?= htmlspecialchars($tenant['Unit_Number']) ?></td>
                    <td><?= htmlspecialchars($tenant['Last_Name']) ?></td>
                    <td><?= htmlspecialchars($tenant['First_Name']) ?></td>
                    <td><?= htmlspecialchars($tenant['Middle_Name']) ?></td>
                    <td><?= htmlspecialchars($tenant['Email']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <form id="addForm" method="POST" enctype="multipart/form-data" style="margin-top: 20px;" action="OwnerTenantstatus.php#add-tenant-request">
        <h2>Add Tenant</h2>
         <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
            <div class="form-group">
                <label for="middle_name">Middle Name</label>
                <input type="text" id="middle_name" name="middle_name">
            </div>
            <div class="form-group">
                <label for="tower">Tower</label>
                <input type="text" id="tower" name="tower" value="<?php echo htmlspecialchars($tower); ?>" readonly>
            </div>
            <div class="form-group">
                <label for="unit_number">Unit Number</label>
                <input type="text" id="unit_number" name="unit_number" value="<?php echo htmlspecialchars($unitNumber); ?>" readonly>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <button type="submit">Add Tenant</button>
            </div>
    </form>
</section>

                <!-- Main content ends here -->
            </div>
        </div>
    </div>
	
<!-- Edit Profile Modal -->
<div class="modalprof" id="editProfileModal">
  <div class="modalprof-content">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <input type="file" id="upload" class="upload-btn" accept="image/*" onchange="previewImage(event)">
    <img src="default-profile.png" alt="Profile Picture" class="profile-picture" id="previewProfilePicture" onclick="triggerUpload()">
    <input type="text" id="nameInput" class="name-input" placeholder="Enter your name" value="Bini Ira">
    <button class="save-btn" onclick="saveProfile()">Save</button>
  </div>
</div>

</body>
<script>

// Function to toggle the visibility of the add form
    function toggleForm(formId) {
        const form = document.getElementById(formId);
        if (form.style.display === "none" || form.style.display === "") {
            form.style.display = "block";
        } else {
            form.style.display = "none";
        }
    }

let isSpinning = false;
let currentOpenMenu = null; // To track the currently open menu (either 'inbox', 'notif', 'settings')

// Inbox Animation
document.getElementById('inboxButton').addEventListener('click', function() {
    const inbox = document.getElementById('hiddenInbox');
    const inboxButton = this;

    if (currentOpenMenu && currentOpenMenu !== 'inbox') {
        closeCurrentMenu(); // Close the currently open menu if it's not 'inbox'
    }

    // Toggle the inbox display
    if (!inbox.classList.contains('show-inbox')) {
        inbox.classList.add('show-inbox');
        inboxButton.classList.add('inbox-open'); // Add shake animation class

        // Remove the animation class after 0.5 seconds (duration of animation)
        setTimeout(function() {
            inboxButton.classList.remove('inbox-open');
        }, 500); // 500ms = 0.5s (length of shake animation)
    } else {
        inbox.classList.remove('show-inbox');
    }

    currentOpenMenu = inbox.classList.contains('show-inbox') ? 'inbox' : null; // Update the open menu state
});

// Notification Animation
document.getElementById('notifButton').addEventListener('click', function() {
    const notif = document.getElementById('hiddenNotif');
    const notifButton = this;

    if (currentOpenMenu && currentOpenMenu !== 'notif') {
        closeCurrentMenu(); // Close the currently open menu if it's not 'notif'
    }

    if (!notif.classList.contains('show-notif')) {
        notif.classList.add('show-notif');
        notifButton.classList.add('ringing');

        setTimeout(function() {
            notifButton.classList.remove('ringing');
        }, 500); // 500ms = 0.5s (length of ringing animation)
    } else {
        notif.classList.remove('show-notif');
    }

    currentOpenMenu = notif.classList.contains('show-notif') ? 'notif' : null; // Update the open menu state
});

// Helper function to close the current open menu
function closeCurrentMenu() {
    if (currentOpenMenu === 'inbox') {
        document.getElementById('hiddenInbox').classList.remove('show-inbox');
    } else if (currentOpenMenu === 'notif') {
        document.getElementById('hiddenNotif').classList.remove('show-notif');
    } else if (currentOpenMenu === 'settings') {
        document.getElementById('hiddenIcons').classList.remove('show-icons');
        document.querySelector('.settings-btn').style.transform = 'rotate(0deg)';
        isSpinning = false;
    }

    currentOpenMenu = null; // Reset the open menu state
}

// JavaScript to handle the navigation and indicator positioning
const navLinks = document.querySelectorAll('nav a');
const indicator = document.getElementById('indicator');

// Function to update the indicator position
function updateIndicator(index) {
    const linkWidth = navLinks[index].offsetWidth;
    const leftPosition = index * linkWidth;

    indicator.style.width = `${linkWidth}px`;
    indicator.style.left = `${leftPosition}px`;
}

// Function to show the selected section and hide others
function showSection(index) {
    const sections = document.querySelectorAll('section');
    sections.forEach((section, idx) => {
        section.style.display = idx === index ? 'block' : 'none';
    });
}

// Set initial indicator position to Form Status (first link)
updateIndicator(0);
showSection(0); // Show Form Status section by default

// Event listeners for navigation links
navLinks.forEach((link, index) => {
    link.addEventListener('click', () => {
        updateIndicator(index);
        showSection(index);
    });
});

window.onload = function() {
    const savedProfilePic = localStorage.getItem('profilePic');
    const defaultProfilePic = '/img/default-profile-pic.png'; // Path to your default profile picture

    // Check if there is a saved profile picture
    if (savedProfilePic) {
        document.getElementById('mainProfilePicture').src = savedProfilePic;
        document.getElementById('previewProfilePicture').src = savedProfilePic;
    } else {
        // Set the default profile picture if no saved picture is found
        document.getElementById('mainProfilePicture').src = defaultProfilePic;
        document.getElementById('previewProfilePicture').src = defaultProfilePic;
    }
};

function openEditProfile() {
    document.getElementById('editProfileModal').style.display = 'flex';
}

function triggerUpload() {
    document.getElementById('upload').click();
}

function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function() {
        document.getElementById('previewProfilePicture').src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
}

function saveProfile() {
    const name = document.getElementById('nameInput').value; // Get the updated name
    const profilePicSrc = document.getElementById('previewProfilePicture').src;

    // Update the main profile picture and username display
    document.getElementById('mainProfilePicture').src = profilePicSrc;
    const mainDisplaynameElement = document.getElementById('mainDisplayname');
    mainDisplaynameElement.childNodes[0].nodeValue = name; // Display the new name, but do not store it

    // Save only the profile picture to localStorage
    localStorage.setItem('profilePic', profilePicSrc);

    // Display success prompt
    alert("Profile successfully updated!");

    // Hide the modal
    document.getElementById('editProfileModal').style.display = 'none';
}

function closeModal() {
    document.getElementById('editProfileModal').style.display = 'none';
}

 function filterTable() {
        var searchInput = document.getElementById("searchInput").value.toLowerCase();
        var requestType = document.getElementById("requestType").value.toLowerCase();
        var status = document.getElementById("status").value.toLowerCase();
        var table = document.getElementById("dataTable");
        var rows = table.getElementsByTagName("tr");

        for (var i = 1; i < rows.length; i++) {
            var cells = rows[i].getElementsByTagName("td");
            if (cells.length > 0) {
                var userType = cells[0].innerText.toLowerCase();
                var userEmail = cells[1].innerText.toLowerCase();
                var formName = cells[2].innerText.toLowerCase();
                var rowStatus = cells[3].innerText.toLowerCase();

                var matchesSearch = searchInput === "" || userType.includes(searchInput) || userEmail.includes(searchInput) || formName.includes(searchInput);
                var matchesRequest = requestType === "" || formName.includes(requestType);
                var matchesStatus = status === "" || rowStatus.includes(status);

                rows[i].style.display = (matchesSearch && matchesRequest && matchesStatus) ? "" : "none";
            }
        }
    }
    
    document.addEventListener("DOMContentLoaded", function() {
        const links = document.querySelectorAll(".nav-link");

        links.forEach(link => {
            link.addEventListener("click", function(event) {
                event.preventDefault();
                const targetId = this.getAttribute("href").substring(1);
                const targetElement = document.getElementById(targetId);

                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 50, // Adjust for fixed navbar
                        behavior: "smooth"
                    });
                }
            });
        });
    });
</script>

</html>