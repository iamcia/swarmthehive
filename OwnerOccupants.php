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

$success = false; // Initialize success variable

// Retrieve Owner_ID from session and set it as Resident_Code
$ownerId = isset($_SESSION['ownerId']) ? $_SESSION['ownerId'] : null;
$residentCode = $ownerId; // Set Resident_Code to the same value as Owner_ID

// Determine User_Type based on Resident_Code presence in ownerinformation or tenantinformation tables
$userType = null;
if ($residentCode) {
    $checkOwnerSql = "SELECT OWNER_ID FROM ownerinformation WHERE OWNER_ID = ?";
    $checkTenantSql = "SELECT TENANT_ID FROM tenantinformation WHERE TENANT_ID = ?";

    if ($stmt = $conn->prepare($checkOwnerSql)) {
        $stmt->bind_param("s", $residentCode);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $userType = "Owner";
        }
        $stmt->close();
    }

    if (!$userType && $stmt = $conn->prepare($checkTenantSql)) {
        $stmt->bind_param("s", $residentCode);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $userType = "Tenant";
        }
        $stmt->close();
    }
}

if (!$userType) {
    die("User type could not be determined.");
}

// Handle adding occupant
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['addOccupant'])) {
    // Sanitize user input
    $lastName = htmlspecialchars(trim($_POST['lastName']));
    $firstName = htmlspecialchars(trim($_POST['firstName']));
    $middleName = htmlspecialchars(trim($_POST['middleName']));
    $gender = htmlspecialchars(trim($_POST['gender']));
    $age = htmlspecialchars(trim($_POST['age']));
    $relation = htmlspecialchars(trim($_POST['relation']));
    $mobileNumber = htmlspecialchars(trim($_POST['mobileNumber']));
    $email = htmlspecialchars(trim($_POST['email']));

    // Insert the occupant with the retrieved Resident_Code and User_Type
    $sql = "INSERT INTO occupants (Resident_Code, User_Type, Last_Name, First_Name, Middle_Name, Gender, Age, Relation, Mobile_Number, Email)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        // Bind Resident_Code and user inputs to the prepared statement
        $stmt->bind_param(
            "ssssssssss",
            $residentCode, $userType, $lastName, $firstName, $middleName, $gender, $age, $relation, $mobileNumber, $email
        );
        if ($stmt->execute()) {
            $success = true;
        }
        $stmt->close();
    }
}

// Close database connection
$conn->close();

// Redirect to the same page to prevent form resubmission
if ($success) {
    header("Location: OwnerOccupants.php?success=true");
    exit();
}
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
      
      select[id="gender"]{
    margin-left: 10px;
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
                <li class="completed"><span class="circle"><i class="fas fa-check"></i></span>Owner Contact</li>
                <li class="active"><span class="circle"><i class="fas fa-highlighter"></i></span>Owner Occupants</li>
                <li><span class="circle"></span>Owner Helper</li>
                <li><span class="circle"></span>Create an Account</li>
            </ul>
        </div>

        <div class="form-container">
            <h2>Add Occupant</h2>
            <?php if (isset($_GET['success'])): ?>
                <p class="success-message">Occupant added successfully!</p>
            <?php endif; ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="lastName">Last Name</label>
                    <input type="text" id="lastName" name="lastName" placeholder="Required" required>
                </div>
                <div class="form-group">
                    <label for="firstName">First Name</label>
                    <input type="text" id="firstName" name="firstName" placeholder="Required" required>
                </div>
                <div class="form-group">
                    <label for="middleName">Middle Name</label>
                    <input type="text" id="middleName" name="middleName">
                </div>
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="age">Age</label>
                    <input type="number" id="age" name="age" placeholder="Required" required>
                </div>
                <div class="form-group">
                    <label for="relation">Relation</label>
                    <input type="text" id="relation" name="relation" placeholder="Required" required>
                </div>
                <div class="form-group">
                    <label for="mobileNumber">Mobile Number</label>
                    <input type="tel" id="mobileNumber" name="mobileNumber" pattern="\d{11}" placeholder="Required" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Required" required>
                </div>
                <div class="btn-container">
                    <button type="submit" name="addOccupant">Submit</button>
                </div>
            </form>

            <?php if (isset($_GET['success'])): ?>
                <form action="OwnerHelper.php" method="get">
                    <button type="submit" class="next-button">Next</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function openChatbotPopup() {
        // Open a popup window for the chatbot page
        window.open("OwnerTenantChatbot2.php", "chatbotPopup", "width=500,height=500,top=300,left=300");
    }
      
      function toggleLockAllFields() {
            const formElements = document.querySelectorAll('input[type="text"], input[type="tel"], input[type="email"], input[type="number"], select[id="gender"]');
            formElements.forEach(element => {
                element.disabled = document.getElementById('lockFields').checked;
            });
        }
      
       function goBack() {
        window.history.back();
    }
    </script>
</body>
</html>