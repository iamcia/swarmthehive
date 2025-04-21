<?php
include 'db_connection.php';

session_start();

// Initialize variables
$residentCode = '';
$userType = '';
$towerunit = '';
$showSuccessNotification = false;
$successMessage = "Your pool reservation has been submitted successfully.";
$showErrorNotification = false;
$errorMessage = "";

// Check if we have a success message in the session
if (isset($_SESSION['form_submitted']) && $_SESSION['form_submitted'] === true) {
    $showSuccessNotification = true;
    if (isset($_SESSION['form_success_message'])) {
        $successMessage = $_SESSION['form_success_message'];
        unset($_SESSION['form_success_message']);
    }
    unset($_SESSION['form_submitted']);
}

// Check if we have an error message in the session
if (isset($_SESSION['form_error']) && $_SESSION['form_error'] === true) {
    $showErrorNotification = true;
    if (isset($_SESSION['form_error_message'])) {
        $errorMessage = $_SESSION['form_error_message'];
        unset($_SESSION['form_error_message']);
    }
    unset($_SESSION['form_error']);
}

// Fetch user details if logged in
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];

    // Check in the OwnerInformation table
    $sql = "SELECT Owner_ID, Tower, Unit_Number FROM ownerinformation WHERE Username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userType = 'Owner';
        $residentCode = $row['Owner_ID'];
        $towerunit = $row['Tower'] . " " . $row['Unit_Number'];
    } else {
        // Check TenantInformation table if no owner record is found
        $sql = "SELECT Tenant_ID, Tower, Unit_Number FROM tenantinformation WHERE Username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $userType = 'Tenant';
            $residentCode = $row['Tenant_ID'];
            $towerunit = $row['Tower'] . " " . $row['Unit_Number'];
        }
    }
    $stmt->close();
}

// Handle form submission for pool reservation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $names = $_POST['names'];  // Array of guest names, each with first_name, last_name, and valid_id
    $schedule = $_POST['schedule'];
    $towerunitnum = $_POST['tower_unit'];
    
    // Check if the guest names exist in the occupants table
    foreach ($names as $guest) {
        $first_name = $guest['first_name'];
        $last_name = $guest['last_name'];
        
        $sql = "SELECT COUNT(*) FROM occupants WHERE First_Name = ? AND Last_Name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $first_name, $last_name);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        
        if ($count > 0) {
            // If a guest name matches an occupant, show error message and stop form submission
            $errorMessage = "One of the names you entered is already an occupant, not a guest.";
            echo "<script>
                    alert('$errorMessage');
                    window.location.href='ReservePool2.php';
                  </script>";
            exit();
        }
    }

    // Calculate the total fee (if required, depending on your fee structure)
    $totalFee = calculateTotalFee(count($names));  // Implement this function if needed
    
    // Insert reservation into the poolreserve table
    $sql = "INSERT INTO poolreserve (Resident_Code, User_Type, names, towerunitnum, schedule, Status) 
            VALUES (?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $names_json = json_encode($names);  // Convert names array to JSON for storage
        $status = 'Pending';
        $stmt->bind_param("ssssss", $residentCode, $userType, $names_json, $towerunitnum, $schedule, $status);

        if ($stmt->execute()) {
            echo "<script>
                    alert('$successMessage');
                    window.location.href='ReservePool2.php';
                  </script>";
        } else {
            echo "<script>
                    alert('Error: " . addslashes($stmt->error) . "');
                    window.location.href='ReservePool2.php';
                  </script>";
        }
        $stmt->close();
    } else {
        echo "<script>
                alert('Database error: " . addslashes($conn->error) . "');
                window.location.href='ReservePool2.php';
              </script>";
    }

    $conn->close();
}

// Function to calculate the total fee
function calculateTotalFee($guestCount) {
    if ($guestCount <= 3) {
        return $guestCount * 300;
    } else {
        return $guestCount * 500;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pool Reservation Form</title>
    <!-- Include Tailwind CSS from CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#f1c40f',
                        'primary-dark': '#e1b00f',
                    },
                    boxShadow: {
                        'custom': '0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1)',
                        'custom-lg': '0 10px 15px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05)',
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom notification styles */
        .notification-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 50;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .notification-overlay.active {
            opacity: 1;
        }
        
        .notification-box {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }
        
        .notification-overlay.active .notification-box {
            transform: translateY(0);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen font-sans flex flex-col items-center p-5 md:p-10">
    <!-- Main Container -->
    <div class="w-full max-w-2xl bg-white rounded-xl shadow-custom-lg overflow-hidden">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-primary to-primary-dark p-6 flex items-center">
            <div class="bg-white rounded-full p-3 mr-4 shadow-md">
                <i class="fas fa-swimming-pool text-primary text-2xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white">Pool Reservation Form</h1>
                <p class="text-white text-opacity-90 text-sm">Reserve your time at our facility</p>
            </div>
        </div>

        <!-- Form Content -->
        <form id="poolReservationForm" method="POST" action="Insert-ReservePool2.php" enctype="multipart/form-data" class="p-6 md:p-8 space-y-6">
            <input type="hidden" name="Resident_Code" value="<?php echo $residentCode;?>" readonly>
            <input type="hidden" name="user_type" value="<?php echo $userType;?>" readonly>

            <!-- Resident Information -->
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-700 mb-2 flex items-center">
                    <i class="fas fa-user mr-2 text-primary"></i>Resident Information
                </h3>
                <div class="mb-4">
                    <label for="tower_unit_number" class="block text-sm font-medium text-gray-700 mb-1">Tower and Unit Number:</label>
                    <input type="text" name="tower_unit" value="<?php echo $towerunit;?>" readonly
                        class="w-full p-3 bg-gray-100 rounded-lg border border-gray-200 text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
            </div>

            <!-- Names and Valid ID Section -->
            <div class="space-y-3">
                <h3 class="text-lg font-semibold text-gray-700 flex items-center">
                    <i class="fas fa-users mr-2 text-primary"></i>Guest Information
                </h3>
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <table id="names-container" class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700 border-b">First Name</th>
                                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700 border-b">Last Name</th>
                                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700 border-b">Valid ID</th>
                                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700 border-b">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="name-entry hover:bg-gray-50 transition-colors">
                                <td class="p-3">
                                    <div class="name-group">
                                        <input type="text" name="first_name[]" placeholder="First Name" required
                                            class="w-full p-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                                    </div>
                                </td>
                                <td class="p-3">
                                    <div class="name-group">
                                        <input type="text" name="last_name[]" placeholder="Last Name" required
                                            class="w-full p-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                                    </div>
                                </td>
                                <td class="p-3">
                                    <div class="name-group">
                                        <input type="file" name="valid_id[]" accept="image/*" required
                                            class="w-full p-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                                    </div>
                                </td>
                                <td class="p-3">
                                    <div class="flex space-x-1">
                                        <button type="button" onclick="addNameEntry(this)" 
                                            class="bg-primary text-white px-3 py-1.5 rounded-md hover:bg-primary-dark transition-colors duration-200 flex items-center justify-center">
                                            <i class="fas fa-plus mr-1 text-xs"></i>Add
                                        </button>
                                        <button type="button" onclick="removeNameGroup(this)"
                                            class="bg-red-500 text-white px-3 py-1.5 rounded-md hover:bg-red-600 transition-colors duration-200 flex items-center justify-center">
                                            <i class="fas fa-minus mr-1 text-xs"></i>Remove
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-500 italic">Please add all guests who will be using the pool facility.</p>
            </div>

            <!-- Schedule Section -->
            <div class="space-y-3">
                <h3 class="text-lg font-semibold text-gray-700 flex items-center">
                    <i class="far fa-calendar-alt mr-2 text-primary"></i>Schedule Details
                </h3>
                <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <label for="schedule" class="block text-sm font-medium text-gray-700 mb-2">Select your preferred date and time:</label>
                    <input type="datetime-local" id="schedule" name="schedule" required
                        class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                    <p class="text-xs text-gray-500 italic mt-2">Note: Pool is closed every Monday for maintenance.</p>
                </div>
            </div>

            <!-- Fee Section -->
            <div class="space-y-3">
                <h3 class="text-lg font-semibold text-gray-700 flex items-center">
                    <i class="fas fa-money-bill-wave mr-2 text-primary"></i>Fee Information
                </h3>
                <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <p class="text-sm text-gray-700 mb-2">Fee Structure:</p>
                    <ul class="list-disc list-inside text-sm text-gray-700 mb-4">
                        <li>1st - 3rd Guest:
                            <ul class="list-disc list-inside ml-4">
                                <li>Adult: 200 pesos per person</li>
                                <li>Children (3-12 years): 100 pesos per person</li>
                            </ul>
                        </li>
                        <li>4th Guest onwards:
                            <ul class="list-disc list-inside ml-4">
                                <li>Adult: 500 pesos per person</li>
                                <li>Children (3-12 years): 100 pesos per person</li>
                            </ul>
                        </li>
                    </ul>
                    <p class="text-sm text-gray-700 font-medium">Please pay the fee at the Property Management Office (PMO) for your reservation to be approved.</p>
                </div>
            </div>

            <!-- Agreement Section -->
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox" name="acknowledge" required 
                            class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                    </div>
                    <div class="ml-3">
                        <label for="acknowledge" class="text-sm text-gray-700">
                            I acknowledge the <span class="font-medium">COVID-19</span> related risks and safety measures, and agree to follow all pool facility rules and regulations.
                        </label>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4">
                <button id="submitBtn" type="button" 
                    class="w-full bg-primary text-white py-3 px-4 rounded-lg hover:bg-primary-dark transition-all duration-200 font-medium flex items-center justify-center shadow-md hover:shadow-lg">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Submit Reservation
                </button>
            </div>
        </form>
    </div>

    <!-- Confirmation Dialog -->
    <div id="confirmationDialog" class="notification-overlay">
        <div class="notification-box">
            <div class="text-center mb-4">
                <div class="bg-yellow-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-question-circle text-primary text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800">Confirm Submission</h3>
                <p class="text-gray-600 mt-2">Are you sure you want to submit this pool reservation?</p>
            </div>
            <div class="flex justify-center space-x-4">
                <button id="confirmYes" class="bg-primary text-white px-6 py-2 rounded-md hover:bg-primary-dark transition-colors">
                    Yes, Submit
                </button>
                <button id="confirmNo" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400 transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    </div>
    
    <!-- Success Notification -->
    <div id="successNotification" class="notification-overlay">
        <div class="notification-box">
            <div class="text-center mb-4">
                <div class="bg-green-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-green-500 text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800">Success!</h3>
                <p class="text-gray-600 mt-2"><?php echo $successMessage; ?></p>
            </div>
            <div class="flex justify-center">
                <button id="successOk" class="bg-green-500 text-white px-6 py-2 rounded-md hover:bg-green-600 transition-colors">
                    OK
                </button>
            </div>
        </div>
    </div>

    <!-- Error Notification -->
    <div id="errorNotification" class="notification-overlay">
        <div class="notification-box">
            <div class="text-center mb-4">
                <div class="bg-red-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-times-circle text-red-500 text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800">Error!</h3>
                <p class="text-gray-600 mt-2"><?php echo $errorMessage; ?></p>
            </div>
            <div class="flex justify-center">
                <button id="errorOk" class="bg-red-500 text-white px-6 py-2 rounded-md hover:bg-red-600 transition-colors">
                    OK
                </button>
            </div>
        </div>
    </div>

    <script>
        // JavaScript functionality for adding/removing guest name entries, and showing notifications
        function addNameEntry(button) {
            let row = button.closest('tr'); // Get the row containing the button
            let tds = row.querySelectorAll('td');
            let firstNameGroup = tds[0].querySelector('.name-group');
            let lastNameGroup = tds[1].querySelector('.name-group');
            let validIdGroup = tds[2].querySelector('.name-group');

            // Create new first name input
            let newFirstName = document.createElement('input');
            newFirstName.type = "text";
            newFirstName.name = "first_name[]";
            newFirstName.placeholder = "First Name";
            newFirstName.required = true;
            newFirstName.className = "w-full p-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all mt-2";

            // Create new last name input
            let newLastName = document.createElement('input');
            newLastName.type = "text";
            newLastName.name = "last_name[]";
            newLastName.placeholder = "Last Name";
            newLastName.required = true;
            newLastName.className = "w-full p-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all mt-2";

            // Create new valid ID input
            let newValidId = document.createElement('input');
            newValidId.type = "file";
            newValidId.name = "valid_id[]";
            newValidId.accept = "image/*";
            newValidId.required = true;
            newValidId.className = "w-full p-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all mt-2";

            // Append new inputs to their respective divs
            firstNameGroup.appendChild(newFirstName);
            lastNameGroup.appendChild(newLastName);
            validIdGroup.appendChild(newValidId);
        }

        function removeNameGroup(button) {
            let row = button.closest('tr'); // Get the row containing the button
            let tds = row.querySelectorAll('td');
            let firstNameGroup = tds[0].querySelector('.name-group');
            let lastNameGroup = tds[1].querySelector('.name-group');
            let validIdGroup = tds[2].querySelector('.name-group');

            let firstNames = firstNameGroup.querySelectorAll('input');
            let lastNames = lastNameGroup.querySelectorAll('input');
            let validIds = validIdGroup.querySelectorAll('input');

            if (firstNames.length > 1 && lastNames.length > 1 && validIds.length > 1) {
                firstNameGroup.removeChild(firstNames[firstNames.length - 1]);
                lastNameGroup.removeChild(lastNames[lastNames.length - 1]);
                validIdGroup.removeChild(validIds[validIds.length - 1]);
            } else {
                alert("At least one name and ID is required.");
            }
        }
    </script>
</body>
</html>