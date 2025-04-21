<?php
// Start session
session_start();

include('dbconn.php');

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$success = false; 
$error = "";

// Retrieve Owner_ID from session and set it as Resident_Code
$ownerId = $_SESSION['ownerId'];
$residentCode = $ownerId; // Set Resident_Code to the same value as Owner_ID

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['addOccupant'])) {
    // Sanitize user input
    $lastName = htmlspecialchars(trim($_POST['lastName'])); 
    $firstName = htmlspecialchars(trim($_POST['firstName'])); 
    $middleName = htmlspecialchars(trim($_POST['middleName']));
    $position = htmlspecialchars(trim($_POST['position']));

    // Check if restDay is set and capture its value
    if (isset($_POST['restDay'])) {
        $restDay = htmlspecialchars(trim($_POST['restDay']));
    } else {
        $restDay = null; // Handle the case where no rest day is selected
    }

    $mobileNumber = htmlspecialchars(trim($_POST['mobileNumber']));
    $address = htmlspecialchars(trim($_POST['address']));
    $email = htmlspecialchars(trim($_POST['email']));

    // Ensure Resident_Code (Owner_ID) exists
    if ($residentCode) {
        $sql = "INSERT INTO helper (Resident_Code, Last_Name, First_Name, Middle_Name, Position, Rest_Day, Mobile_Number, Address, Email)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssssssss", $residentCode, $lastName, $firstName, $middleName, $position, $restDay, $mobileNumber, $address, $email);
            if ($stmt->execute()) {
                $success = true; // Set success to true if helper added successfully
                // Redirect to OwnerContact.php
                header("Location: OwnerCreateAccount.php");
                exit(); // Make sure to exit after redirecting
            } else {
                $error = "Error adding helper: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    } else {
        $error = "No matching owner found with the provided Owner_ID.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swarm | Registration For New Resident</title>
    <link rel="stylesheet" href="RegStyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .form-group {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 10px 20px;
            align-items: center;
            margin-bottom: 15px;
        }
      .form-group span {
        color: red;
      }

        .form-container {
            flex: 1;
            padding: 40px;
            max-width: 1200px;
            margin: 0 auto;
            max-height: 600px;
            overflow-y: auto;
        }

        .btn-container {
            display: flex;
            justify-content: space-between;
            gap: 10px; /* Space between buttons */
    margin-top: 20px;
    align-items: center; /* Vertically aligns buttons */
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }

        .sidebar ul li.completed .circle {
            background-color: green;
            color: white;
            padding: 5px;
            border-radius: 50%;
        }
      
      .sidebar ul li .circle {
    transition: background-color 0.3s ease, transform 0.3s ease;
}

.sidebar ul li.active .circle {
    background-color: #FF6F00;
    color: #fff;
    transform: scale(1.2); /* Slightly larger */
}

.sidebar ul li.completed .circle {
    transform: scale(1.2); /* Slightly larger */
}
      
       /* Checkbox Group Styling */
.checkbox-group {
    display: flex;
    align-items: center;
}

/* Label styling for the toggle */
.toggle-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-size: 14px;
    color: #333;
}

/* Hide the default checkbox */
.toggle-label input[type="checkbox"] {
    display: none;
}

/* Toggle switch design */
.toggle-switch {
    width: 40px;
    height: 20px;
    background-color: #ccc;
    border-radius: 20px;
    position: relative;
    margin-right: 10px;
    transition: background-color 0.3s ease;
}

.toggle-switch::before {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    background-color: white;
    border-radius: 50%;
    top: 2px;
    left: 2px;
    transition: transform 0.3s ease;
}

/* Checked state styling */
.toggle-label input[type="checkbox"]:checked + .toggle-switch {
    background-color: #FF6F00;
}

.toggle-label input[type="checkbox"]:checked + .toggle-switch::before {
    transform: translateX(20px);
}

/* Text styling */
.toggle-text {
    font-size: 14px;
    color: #333;
}
    </style>
</head>
<body>
    <div class="main-container">
        <div class="sidebar">
            <ul>
                <li class="completed"><span class="circle"><i class="fas fa-check"></i></span>Unit Owner Information</li>
                <li class="completed"><span class="circle"><i class="fas fa-check"></i></span>Proof of Residency</li>
                <li class="completed"><span class="circle"><i class="fas fa-highlighter"></i></span>Owner Contact</li>
                <li class="completed"><span class="circle"><i class="fas fa-highlighter"></i></span>Owner Occupants</li>
                <li class="active"><span class="circle"><i class="fas fa-highlighter"></i></span>Owner Helper</li>
                <li><span class="circle"></span>Create an Account</li>
            </ul>
            <div class="help" title="Click here for assistance">
    <i class="fas fa-question-circle"></i>
    <span><a href="#" onclick="openChatbotPopup()">Need help?</a></span>
</div>
        </div>
        <div class="form-container">
           <div class="form-title">Add Helper</div>
          <div class="form-message">Please fill out the following details. Fields marked with an asterisk (*) are required.</div>
          
            <?php if (!empty($error)): ?>
                <p style="color: red;"><?php echo $error; ?></p>
            <?php endif; ?>
            <form method="POST">
              
              <div class="form-group checkbox-group">
    <label for="lockFields" class="toggle-label">
        <input type="checkbox" id="lockFields" name="lockFields" onclick="toggleLockAllFields()">
        <span class="toggle-switch"></span>
        <span class="toggle-text">If you don't have an helper, toggle the button and next.</span>
    </label>
</div>
              
                <div class="form-group">
                     
                   
                </div>
                <div class="form-group">
                    <label for="lastName">Last Name <span>*</span></label>
                    <input type="text" id="lastName" name="lastName" placeholder="e.g., Reyes" required>
                </div>
                <div class="form-group">
                    <label for="firstName">First Name</label>
                    <input type="text" id="firstName" name="firstName" placeholder="e.g., Jose" required>
                </div>
                <div class="form-group">
                    <label for="middleName">Middle Name</label>
                    <input type="text" id="middleName" name="middleName" placeholder="e.g., P">
                </div>
                <div class="form-group">
                    <label for="position">Position <span>*</span></label>
                    <input type="text" id="position" name="position" placeholder="e.g., Driver" required>
                </div>
                <div class="form-group">
                    <label for="restDay">Rest Day <span>*</span></label>
                    <input type="text" id="restDay" name="restDay" placeholder="e.g., S & S">
                </div>
                <div class="form-group">
                    <label for="mobileNumber">Mobile Number <span>*</span></label>
                    <input type="tel" id="mobileNumber" name="mobileNumber" pattern="\d{11}" placeholder="e.g., 09123456789" required>
                </div>
                <div class="form-group">
                    <label for="address">Address </label>
                    <input type="text" id="address" name="address" placeholder="e.g., San Isidro, Taytay Rizal">
                </div>
                <div class="form-group">
                    <label for="email">Email <span>*</span></label>
                    <input type="email" id="email" name="email" placeholder="e.g., josereyes@example.com" required>
                </div>
                <div class="btn-container">
                  <button type="button" class="btn previous-page" onclick="goBack()">Previous</button>
                    <button type="submit" name="addOccupant">Next</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function openChatbotPopup() {
        // Open a popup window for the chatbot page
        window.open("OwnerTenantChatbot2.php", "chatbotPopup", "width=500,height=500,top=300,left=300");
    }
      
       function toggleLockAllFields() {
            const formElements = document.querySelectorAll('input[type="text"], input[type="tel"], input[type="email"]');
            formElements.forEach(element => {
                element.disabled = document.getElementById('lockFields').checked;
            });
        }
    </script>
</body>
</html>
