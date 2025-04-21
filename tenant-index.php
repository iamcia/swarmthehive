<?php
include('dbconn.php');
session_start();

$tenantId = $_SESSION['user_id']; // Updated session variable to user_id based on login code
$status = '';

// Fetch status from the tenant information table
$sql = "SELECT Status FROM tenantinformation WHERE Tenant_ID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $tenantId); // Use 's' for string type (TENANT_ID is a VARCHAR)
    $stmt->execute();
    $stmt->bind_result($status);
    $stmt->fetch();
    $stmt->close();

}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Swarm Portal</title>
    <link rel="stylesheet" href="owner-index-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .displayname {
    font-weight: bold;
    margin-bottom: 30px;
    color: white;
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
                <div class="avatar"></div>
                <div class="username">BINI IRA</div> 
            </div>
            
            <?php if ($status == 'Approved'): ?>
            <div class="navigation-items home">
                <a href="tenant-index.php">
                <img src="/img/home.png" width="25px" height="25px" class="nav-icon">
                Home
                </a>
            </div>
            <div class="navigation-items1">
                <a href="ten-announcement.php">
			    <img src="/img/announcement.png" width="25px" height="25px" class="nav-icon"  >
				Announcements
                </a>
		    </div>
            <div class="navigation-items1">
                <a href="ten-communitymarket.php">
			    <img src="/img/comm market.png" width="25px" height="25px" class="nav-icon">
			    Community Market
                </a>
			</div>
            <div class="navigation-items1">
                <a href="ten-services.php">
			    <img src="/img/services.png" width="25px" height="25px" class="nav-icon">
			    Services
                </a>
			</div>
            <div class="navigation-items1">
                <a href="ten-paymentinfo.php">
			    <img src="/img/payment info.png" width="25px" height="25px" class="nav-icon">
			    Payment Info
                </a>
			</div>
            <div class="navigation-items1">
                <a href="ten-safetyguidelines.php">
			    <img src="/img/safe guidelines.png" width="25px" height="25px" class="nav-icon">
			    Safety Guidelines
                </a>
			</div>
            <div class="navigation-items1">
                <a href="ten-communityfeedback.php">
			    <img src="/img/comm feedback.png" width="25px" height="25px" class="nav-icon">
		        Community Feedback
                </a>
			</div>
			<?php endif; ?>
			<?php if ($status == 'Pending'): ?>
            <div class="navigation-items home">
                <a href="tenant-index.php">
                <img src="/img/home.png" width="25px" height="25px" class="nav-icon">
                Home
                </a>
            </div>
            <div class="navigation-items1">
                <a href="ten-announcement.php">
			    <img src="/img/announcement.png" width="25px" height="25px" class="nav-icon"  >
				Announcements
                </a>
		    </div>
            <div class="navigation-items1">
                <a href="#">
			    <img src="/img/comm market.png" width="25px" height="25px" class="nav-icon">
			    Community Market
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
            <div class="navigation-items1">
                <a href="ten-safetyguidelines.php">
			    <img src="/img/safe guidelines.png" width="25px" height="25px" class="nav-icon">
			    Safety Guidelines
                </a>
			</div>
            <div class="navigation-items1">
                <a href="ten-communityfeedback.php">
			    <img src="/img/comm feedback.png" width="25px" height="25px" class="nav-icon">
		        Community Feedback
                </a>
			</div>
			<?php endif; ?>
        </div>
        
                <div class="content">
            <div class="main-content">
			    <div class="container-images">
                     <img src="https://th.bing.com/th/id/OIP.mnVK_rghCfhGGTetvsatzwHaFj?rs=1&pid=ImgDetMain"> 
                     <img src="https://th.bing.com/th/id/OIP.MfdA_zpKsrAHmfwPMn5gSwHaEK?rs=1&pid=ImgDetMain">
                     <img src="https://static-ph.lamudi.com/static/media/bm9uZS9ub25l/2x2x6x1200x900/e5080d81b8f3f1.jpg">
                </div>
                <!-- Two-column layout for Marketplace and Service Requests -->
        <div class="columns">
            <!-- Marketplace Card -->
            <div class="card">
                <h3>Marketplace</h3>
                <div class="marketplace-slider">
                    <div class="marketplace-slides">
                        <div class="marketplace-slide"><img src="item1.jpg" alt="Item 1"></div>
                        <div class="marketplace-slide"><img src="item2.jpg" alt="Item 2"></div>
                        <div class="marketplace-slide"><img src="item3.jpg" alt="Item 3"></div>
                    </div>
                </div>
                <div class="slider-controls">
    <button class="slider-button" onclick="prevMarketSlide()">&#10094;</button> <!-- Left arrow icon -->
    <button class="slider-button" onclick="nextMarketSlide()">&#10095;</button> <!-- Right arrow icon -->
</div>

            </div>

            <!-- Service Requests Card -->
            <div class="card">
    <h3>Service Requests</h3>
    <div class="form-request">
        <div class="form-item">
            <p>Work Permit</p>
            <span class="status pending">Pending Approval</span>
            <button class="view-button"><span>&#x279C;</span></button> <!-- Right arrow icon inside circle -->
        </div>
        <div class="form-item">
            <p>Guest Check-In</p>
            <span class="status approved">Approved</span>
            <button class="view-button"><span>&#x279C;</span></button>
        </div>
        <div class="form-item">
            <p>Installation Form</p>
            <span class="status rejected">Rejected</span>
            <button class="view-button"><span>&#x279C;</span></button>
        </div>
        <div class="form-item">
            <p>Renovation Permit</p>
            <span class="status approval">Approval Required</span>
            <button class="view-button"><span>&#x279C;</span></button>
        </div>
    </div>
</div>
        
        <!-- Single-column layout for Tenant Status -->
        <div class="tenant-status-section">
            <div class="card">
                <h3>Tenant Status</h3>
                <div class="chart-section">
                    <div class="bar-chart-container">
                        <h2>Payment Statistics (Current Month)</h2>
                        <canvas id="paymentChart"></canvas>
                    </div>
                    <div class="pie-chart-container">
    <h2>Form Statistics</h2>
    <canvas id="formChart" width="220" height="220"></canvas> <!-- Adjust width and height here -->
</div>

                </div>
            </div>
        </div>
              
            </div>
        </div>
    </div>
	
  <!-- Edit Profile Modal -->
<div class="modal" id="editProfileModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <input type="file" id="upload" class="upload-btn" accept="image/*" onchange="previewImage(event)">
    <img src="default-profile.png" alt="Profile Picture" class="profile-picture" id="previewProfilePicture" onclick="triggerUpload()">
    <input type="text" id="nameInput" class="name-input" placeholder="Enter your name" value="Bini Ira">
    <button class="save-btn" onclick="saveProfile()">Save</button>
  </div>
</div>
	
<script>
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


// Close the currently open menu
function closeCurrentMenu() {
    if (currentOpenMenu === 'inbox') {
        const inbox = document.getElementById('hiddenInbox');
        inbox.classList.remove('show-inbox');
    } else if (currentOpenMenu === 'notif') {
        const notif = document.getElementById('hiddenNotif');
        notif.classList.remove('show-notif');
    }

    currentOpenMenu = null; // Reset the open menu state
}

/* AUTOMATIC SLIDES */
let index = 0;
const slides = document.querySelectorAll('.container-images img');
const totalSlides = slides.length;
let autoSlideInterval;
let clickTimeout;
let isHovered = false;

function showSlide(i) {
    slides.forEach((slide, idx) => {
        if (idx === i) {
            slide.style.width = '100%';  // Widen the current slide
        } else {
            slide.style.width = '50px';  // Shrink all other slides
        }
    });
}

function startAutoSlide() {
    autoSlideInterval = setInterval(() => {
        if (!isHovered) {  // Only slide if no image is being hovered
            index = (index + 1) % totalSlides;
            showSlide(index);
        }
    }, 5000);  // Automatically slide every 5 seconds
}

function stopAutoSlide() {
    clearInterval(autoSlideInterval);  // Stop the automatic sliding
}

function resumeAfterClick() {
    clearTimeout(clickTimeout);
    clickTimeout = setTimeout(() => {
        if (!isHovered) {  // Only resume auto-sliding if no image is hovered
            startAutoSlide();
        }
    }, 3000);  // Resume sliding 3 seconds after clicking
}

document.addEventListener('DOMContentLoaded', function () {
    startAutoSlide();
    showSlide(index);  // Initialize the slider position on load
    
    // Event listener for clicks on images
    slides.forEach((slide, idx) => {
        slide.addEventListener('click', function () {
            stopAutoSlide();  // Stop auto-sliding when clicked
            showSlide(idx);   // Immediately widen the clicked image
            resumeAfterClick();  // Resume automatic sliding after 3 seconds
        });
        
        // Event listener for hovering over the image
        slide.addEventListener('mouseenter', function () {
            isHovered = true;
            stopAutoSlide();  // Stop sliding when hovered
        });

        // Event listener for leaving hover state
        slide.addEventListener('mouseleave', function () {
            isHovered = false;
            resumeAfterClick();  // Resume automatic sliding 3 seconds after leaving hover
        });
    });
});

  // Example data for units and payment status for the current month
        const paymentData = {
            units: ['Unit 101', 'Unit 202', 'Unit 305', 'Unit 408'],  // List of units
            paid: [1, 0, 1, 1],      // Paid status for each unit (1 = Paid, 0 = Not Paid)
            notPaid: [0, 1, 0, 0],   // Not Paid status
            delayed: [0, 0, 0, 1]    // Delayed status for each unit (1 = Delayed, 0 = Not Delayed)
        };

        // Stacked bar chart for payment statistics in the current month
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        const paymentChart = new Chart(paymentCtx, {
            type: 'bar',
            data: {
                labels: paymentData.units,  // Unit numbers on the X-axis
                datasets: [
                    {
                        label: 'Paid',
                        data: paymentData.paid,  // Paid data
                        backgroundColor: '#4caf50'  // Green for Paid
                    },
                    {
                        label: 'Not Paid',
                        data: paymentData.notPaid,  // Not Paid data
                        backgroundColor: '#f44336'  // Red for Not Paid
                    },
                    {
                        label: 'Delayed',
                        data: paymentData.delayed,  // Delayed data
                        backgroundColor: '#ff9800'  // Orange for Delayed
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',  // Place the legend at the bottom
                    }
                },
                scales: {
                    x: {
                        stacked: true,  // Stack the bars on the X-axis
                    },
                    y: {
                        stacked: true,  // Stack the bars on the Y-axis
                        beginAtZero: true,  // Start the Y-axis at zero
                        ticks: {
                            precision: 0  // Ensure that the tick values are whole numbers
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 20,
                        bottom: 20
                    }
                }
            }
        });

        // Example form statuses for tenants (submitted, pending, etc.)
        const formStatusData = {
            'Submitted': 8, 
            'Pending': 3, 
            'Rejected': 1
        };

        // Doughnut chart for form submission status
        const formCtx = document.getElementById('formChart').getContext('2d');
        const formChart = new Chart(formCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(formStatusData),  // Form statuses
                datasets: [{
                    data: Object.values(formStatusData),  // Status counts
                    backgroundColor: ['#4caf50', '#ff9800', '#f44336'],  // Colors for each status
                    hoverOffset: 10  // Create a hover effect
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'  // Position the legend to the right
                    }
                }
            }
        });

        // Carousel for marketplace slider
        let currentMarketSlide = 0;
        const marketSlides = document.querySelector('.marketplace-slides');
        const totalMarketSlides = marketSlides.children.length;

        function showMarketSlide(index) {
            marketSlides.style.transform = `translateX(-${index * 100}%)`;
        }

        function nextMarketSlide() {
            currentMarketSlide = (currentMarketSlide + 1) % totalMarketSlides;
            showMarketSlide(currentMarketSlide);
        }

        function prevMarketSlide() {
            currentMarketSlide = (currentMarketSlide - 1 + totalMarketSlides) % totalMarketSlides;
            showMarketSlide(currentMarketSlide);
        }
  
  
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
</script>
</html>
</body>

