<?php
session_start();
include 'dbconn.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set PHP default timezone
date_default_timezone_set('Asia/Manila');

$message = '';
$reservationDate = '';
$reservationTime = '';
$amenity = '';
$numberOfPeople = '';
$additionalRequest = '';
$userEmail = '';
$residentCode = '';
$stats = 'Approval';
$userType = '';
$userId = null; // Add this line to store user ID

// Fetch user email and resident code if logged in
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];

    // Check if the user is an owner
    $sql_owner = "SELECT ID, First_Name, Last_Name, Email, Status, Owner_ID FROM ownerinformation WHERE Username = ?";
    $stmt_owner = $conn->prepare($sql_owner);
    $stmt_owner->bind_param("s", $username);
    $stmt_owner->execute();
    $result_owner = $stmt_owner->get_result();

    if ($result_owner->num_rows > 0) {
        $row_owner = $result_owner->fetch_assoc();
        $userName = $row_owner['First_Name'] . " " . $row_owner['Last_Name'];
        $userEmail = $row_owner['Email'];
        $residentCode = $row_owner['Owner_ID'];
        $status = $row_owner['Status'];
        $userType = 'Owner';
        $userId = $row_owner['ID']; // Store owner ID
    } else {
        // If not an owner, check if the user is a tenant
        $sql_tenant = "SELECT ID, First_Name, Last_Name, Email, Status, Tenant_ID FROM tenantinformation WHERE Username = ?";
        $stmt_tenant = $conn->prepare($sql_tenant);
        $stmt_tenant->bind_param("s", $username);
        $stmt_tenant->execute();
        $result_tenant = $stmt_tenant->get_result();

        if ($result_tenant->num_rows > 0) {
            $row_tenant = $result_tenant->fetch_assoc();
            $userName = $row_tenant['First_Name'] . " " . $row_tenant['Last_Name'];
            $userEmail = $row_tenant['Email'];
            $residentCode = $row_tenant['Tenant_ID'];
            $status = $row_tenant['Status'];
            $userType = 'Tenant';
            $userId = $row_tenant['ID']; // Store tenant ID
        }
        $stmt_tenant->close();
    }
    $stmt_owner->close();
} else {
    // If session username is not set, redirect or handle error
    header('Location: login.php');
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_reservation'])) {
    $amenity = htmlspecialchars($_POST['amenity']);
    $reservationDate = htmlspecialchars($_POST['reservation_date']);
    $reservationTime = htmlspecialchars($_POST['reservation_time']);
    $numberOfPeople = htmlspecialchars($_POST['number_of_people']);
    $additionalRequest = htmlspecialchars($_POST['additional_request']);
    $cateringType = isset($_POST['catering_type']) ? $_POST['catering_type'] : 'none';
    $catererName = isset($_POST['caterer_name']) ? htmlspecialchars($_POST['caterer_name']) : null;
    $cateringContact = isset($_POST['catering_contact']) ? htmlspecialchars($_POST['catering_contact']) : null;

    // Convert reservation time format
    $dateTime = DateTime::createFromFormat('H:i', $reservationTime);
    $reservationTimeFormatted = $dateTime ? $dateTime->format('H:i A') : $reservationTime;

    // Insert into main reservation table
    $sql = "INSERT INTO ownertenantreservation (
        Resident_Code, 
        user_type, 
        user_email, 
        amenity, 
        reservation_date, 
        reservation_time, 
        number_of_people, 
        additional_request,
        catering_type,
        caterer_name,
        catering_contact,
        status,
        reservation_created_at,
        user_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssssssisssssi", 
        $residentCode,
        $userType, 
        $userEmail,
        $amenity, 
        $reservationDate, 
        $reservationTimeFormatted, 
        $numberOfPeople, 
        $additionalRequest,
        $cateringType,
        $catererName,
        $cateringContact,
        $stats,
        $userId
    );

    if ($stmt->execute()) {
        echo "Main reservation inserted successfully.<br>";
        echo "<script>
                alert('Reservation submitted successfully.');
                window.location.href = '" . $_SERVER['PHP_SELF'] . "';
              </script>";
        exit;
    } else {
        die("Main Insert Error: " . $stmt->error);
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Swarm | Amenity Reservation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Add Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'swarm-yellow': '#f1c40f',
                        'swarm-dark-yellow': '#e1b00f',
                        'swarm-light-yellow': '#fef9c3',
                        'swarm-blue': '#3498db',
                        'swarm-green': '#2ecc71',
                        'swarm-orange': '#e67e22',
                        'swarm-red': '#e74c3c',
                    },
                    boxShadow: {
                        'custom': '0 4px 20px rgba(0, 0, 0, 0.05)',
                        'hover': '0 10px 30px rgba(0, 0, 0, 0.1)',
                    },
                    animation: {
                        'bounce-slow': 'bounce 2s infinite',
                    }
                }
            }
        }

        function showSuccessMessage() {
            if ("<?php echo isset($_SESSION['success_message']) ? $_SESSION['success_message'] : ''; ?>") {
                alert("<?php echo $_SESSION['success_message']; ?>");
                <?php unset($_SESSION['success_message']); ?> // Clear session message
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 py-10">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <button onclick="history.back()" class="px-4 py-2 bg-white hover:bg-gray-100 text-gray-700 rounded-full shadow-custom flex items-center gap-2 transition duration-300 mr-5">
                        <i class="fas fa-arrow-left text-sm"></i>
                        <span>Back</span>
                    </button>
                    
                    <h1 class="text-3xl md:text-4xl font-extrabold text-gray-800">
                        <span class="text-swarm-yellow">Amenity</span> Reservation
                    </h1>
                </div>
                
                <div class="hidden md:block">
                    
                </div>
            </div>
            
            <div class="bg-white rounded-2xl p-6 shadow-custom mb-8 transition-all duration-300 hover:shadow-hover border-l-4 border-swarm-yellow">
                <div class="flex items-start">
                    <div class="bg-swarm-light-yellow p-3 rounded-full mr-4">
                        <i class="fas fa-info-circle text-swarm-yellow text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold mb-4 flex items-center text-gray-800">
                            Reservation Policy
                            <span class="bg-swarm-light-yellow text-swarm-dark-yellow text-xs uppercase tracking-wide font-bold rounded-full px-3 py-1 ml-3">Important</span>
                        </h3>
                        
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-swarm-yellow mt-1 mr-2"></i>
                                <span>Reservations must be made at least <strong>48 hours</strong> in advance</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-swarm-yellow mt-1 mr-2"></i>
                                <span>Function Room capacity: <strong>100 people</strong>, Podium capacity: <strong>150 people</strong></span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-swarm-yellow mt-1 mr-2"></i>
                                <span>Reservation for the use of the function room and podium is on the first come-first serve basis</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-swarm-yellow mt-1 mr-2"></i>
                                <span>Cancellations must be made at least <strong>24 hours</strong> before the reservation</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-swarm-yellow mt-1 mr-2"></i>
                                <span>All amenities must be left clean and in the same condition as before use</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

       <!-- Form Container -->
    <form method="POST" action="OwnerReservation.php" class="space-y-8">
        <!-- Hidden Fields -->
        <input type="hidden" id="resident_code" name="resident_code" value="<?php echo htmlspecialchars($residentCode); ?>" readonly>
        <input type="hidden" id="user_email" name="user_email" value="<?php echo htmlspecialchars($userEmail); ?>" readonly>
        <input type="hidden" id="user_type" name="user_type" value="<?php echo htmlspecialchars($userType); ?>" readonly>

        <!-- User Information -->
        <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
            <div class="flex items-center mb-5">
                <div class="bg-blue-50 p-2 rounded-lg mr-3">
                    <i class="fas fa-user text-swarm-blue"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Resident Information</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-600 text-sm font-medium mb-1">Resident Name</label>
                    <p class="px-3 py-2 bg-gray-50 rounded-md border border-gray-200 text-gray-700"><?php echo htmlspecialchars($userName); ?></p>
                </div>
                
                <div>
                    <label class="block text-gray-600 text-sm font-medium mb-1">Resident Code</label>
                    <p class="px-3 py-2 bg-gray-50 rounded-md border border-gray-200 text-gray-700"><?php echo htmlspecialchars($residentCode); ?></p>
                </div>
            </div>
        </div>


            <!-- Amenity Selection -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-5">
                    <div class="bg-green-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-building text-swarm-green"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Amenity Selection</h3>
                </div>
                
                <div class="mb-6">
                    <label for="amenity" class="block text-gray-700 font-medium mb-2">
                        Choose an Amenity <span class="text-swarm-red">*</span>
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="relative">
                            <input type="radio" id="function_hall" name="amenity" value="Function Room" <?php echo ($amenity == "Function Room") ? 'checked' : ''; ?> required class="hidden peer">
                            <label for="function_hall" class="flex items-center p-4 bg-white border-2 border-gray-200 rounded-lg cursor-pointer transition-all duration-300 peer-checked:border-swarm-yellow peer-checked:bg-swarm-light-yellow hover:bg-gray-50">
                                <i class="fas fa-building text-2xl text-swarm-yellow mr-3"></i>
                                <div>
                                    <div class="font-medium">Function Room</div>
                                    <div class="text-xs text-gray-500">Perfect for parties and gatherings</div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="relative">
                            <input type="radio" id="podium" name="amenity" value="Podium" <?php echo ($amenity == "Podium") ? 'checked' : ''; ?> required class="hidden peer">
                            <label for="podium" class="flex items-center p-4 bg-white border-2 border-gray-200 rounded-lg cursor-pointer transition-all duration-300 peer-checked:border-swarm-yellow peer-checked:bg-swarm-light-yellow hover:bg-gray-50">
                                <i class="fas fa-archway text-2xl text-swarm-orange mr-3"></i>
                                <div>
                                    <div class="font-medium">Podium</div>
                                    <div class="text-xs text-gray-500">Ideal for larger events and ceremonies</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fee Information Container -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-5">
                    <div class="bg-yellow-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-money-bill-wave text-yellow-600"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Fee Information</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Function Room Fees -->
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-bold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-building text-swarm-yellow mr-2"></i>
                            Function Room
                        </h4>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li>PHP 12,000 for the first 4 hours</li>
                            <li>PHP 1,750 per succeeding hour</li>
                            <li>50% reservation deposit</li>
                            <li>PHP 3,000 security deposit</li>
                            <li class="text-xs text-gray-500 mt-2">Inclusive of:</li>
                            <li class="text-xs text-gray-500 pl-3">- Electricity</li>
                            <li class="text-xs text-gray-500 pl-3">- Six (6) 2.5 HP Aircon units</li>
                            <li class="text-xs text-gray-500 pl-3">- Two (2) 2.5 HP Aircon for Amenity Lobby</li>
                            <li class="text-xs text-gray-500 pl-3">- One (1) 1.5 HP Aircon for preparation area</li>
                        </ul>
                    </div>

                    <!-- Podium Fees -->
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-bold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-archway text-swarm-orange mr-2"></i>
                            Podium
                        </h4>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li>PHP 7,000 for the first 4 hours</li>
                            <li>PHP 1,000 per succeeding hour</li>
                            <li>PHP 3,000 security deposit</li>
                            <li class="mt-4 text-xs italic">Maximum capacity: 150 persons</li>
                        </ul>
                    </div>
                </div>

                <!-- Payment Notice -->
                <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                        <div>
                            <h5 class="font-medium text-blue-800 mb-2">Payment Information</h5>
                            <ul class="space-y-2 text-sm text-blue-700">
                                <li>• All payments must be made at the Property Management Office</li>
                                <li>• Reservation will only be confirmed after payment is received</li>
                                <li>• Please bring a valid ID when making the payment</li>
                                <li>• Payment must be made within 24 hours of request submission</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Date and Time Selection -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-5">
                    <div class="bg-purple-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-calendar-alt text-purple-600"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Date & Time</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="reservation_date" class="block text-gray-700 font-medium mb-2">
                            Reservation Date <span class="text-swarm-red">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar-day text-gray-400"></i>
                            </div>
                            <input 
                                type="date" 
                                id="reservation_date" 
                                name="reservation_date" 
                                value="<?php echo $reservationDate; ?>"
                                required
                                min="<?php echo date('Y-m-d', strtotime('+2 days')); ?>"
                                class="w-full pl-10 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-swarm-yellow focus:border-transparent transition-all duration-300"
                            >
                        </div>
                        <p class="text-xs text-gray-500 mt-1 flex items-center">
                            <i class="fas fa-info-circle mr-1"></i>
                            Must be at least 48 hours in advance
                        </p>
                    </div>
                    
                    <div>
                        <label for="reservation_time" class="block text-gray-700 font-medium mb-2">
                            Reservation Time <span class="text-swarm-red">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-clock text-gray-400"></i>
                            </div>
                            <input 
                                type="time" 
                                id="reservation_time" 
                                name="reservation_time" 
                                value="<?php echo $reservationTime; ?>"
                                required
                                class="w-full pl-10 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-swarm-yellow focus:border-transparent transition-all duration-300"
                            >
                        </div>
                        <p class="text-xs text-gray-500 mt-1 flex items-center">
                            <i class="fas fa-info-circle mr-1"></i>
                            Available from 8:00 AM to 10:00 PM
                        </p>
                    </div>
                </div>
            </div>

            <!-- Additional Details -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-5">
                    <div class="bg-amber-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-clipboard-list text-amber-500"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Additional Details</h3>
                </div>
                
                <!-- Number of People -->
                <div class="mb-6">
                    <label for="number_of_people" class="block text-gray-700 font-medium mb-2">
                        Number of People <span class="text-swarm-red">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-users text-gray-400"></i>
                        </div>
                        <input 
                            type="number" 
                            id="number_of_people" 
                            name="number_of_people" 
                            value="<?php echo $numberOfPeople; ?>"
                            min="1" 
                            max="100"
                            required
                            class="w-full pl-10 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-swarm-yellow focus:border-transparent transition-all duration-300"
                        >
                    </div>
                    <p class="text-xs text-gray-500 mt-1 flex items-center">
                        <i class="fas fa-info-circle mr-1"></i>
                        Maximum capacity varies by amenity
                    </p>
                </div>

                <!-- Catering Section -->
               <div class="mb-6">
    <label class="block text-gray-700 font-medium mb-2">
        Catering Arrangements <span class="text-sm font-normal text-gray-500">(Optional)</span>
    </label>
    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
        <div class="space-y-3">
            <div class="flex items-center">
                <input type="radio" id="own_catering" name="catering_type" value="own" class="w-4 h-4" onclick="toggleCateringDetails(true)">
                <label for="own_catering" class="ml-2 text-sm text-gray-700">Own Catering Service</label>
            </div>
            <div class="flex items-center">
                <input type="radio" id="accredited_catering" name="catering_type" value="accredited" class="w-4 h-4" onclick="toggleCateringDetails(true)">
                <label for="accredited_catering" class="ml-2 text-sm text-gray-700">Use Accredited Caterer</label>
            </div>
            <div class="flex items-center">
                <input type="radio" id="no_catering" name="catering_type" value="none" class="w-4 h-4" onclick="toggleCateringDetails(false)" checked>
                <label for="no_catering" class="ml-2 text-sm text-gray-700">No Catering</label>
            </div>

            <div id="catering_details" class="mt-4 space-y-4" style="display: none;">
                <div>
                    <label for="caterer_name">Caterer's Name</label>
                    <input type="text" id="caterer_name" name="caterer_name">
                </div>
                <div>
                    <label for="catering_contact">Contact Number</label>
                    <input type="tel" id="catering_contact" name="catering_contact">
                </div>
            </div>
        </div>
    </div>
</div>
                <!-- Additional Requests -->
                <div>
                    <label for="additional_request" class="block text-gray-700 font-medium mb-2">
                        Additional Requests <span class="text-sm font-normal text-gray-500">(Optional)</span>
                    </label>
                    <div class="relative">
                        <div class="absolute top-3 left-3 text-gray-400">
                            <i class="fas fa-comment-alt"></i>
                        </div>
                        <textarea 
                            id="additional_request" 
                            name="additional_request" 
                            rows="4" 
                            placeholder="Any specific requirements or arrangements needed..."
                            class="w-full pl-10 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-swarm-yellow focus:border-transparent transition-all duration-300 resize-none"
                        ><?php echo $additionalRequest; ?></textarea>
                    </div>
                </div>

                <!-- Terms and Agreement -->
                <div class="mt-6">
                    <div class="p-4 bg-gray-50 rounded-lg mb-4 text-sm text-gray-700 border border-gray-200">
                        <p>By submitting this form, I agree to the following terms:</p>
                        <ul class="list-disc pl-5 mt-2 space-y-1">
                            <li>I will be responsible for any damage to the facility during my reservation</li>
                            <li>I will adhere to the condo rules and regulations during the use of the facility</li>
                            <li>I understand that the reservation is subject to management approval</li>
                            <li>Cancellations must be made at least 24 hours in advance</li>
                        </ul>
                    </div>
                    
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="terms_agree" 
                            required
                            class="w-5 h-5 accent-swarm-yellow focus:ring-swarm-yellow rounded"
                        >
                        <label for="terms_agree" class="ml-2 text-gray-700">
                            I agree to the terms and conditions
                        </label>
                    </div>
                </div>
            </div>

            <!-- Form Buttons -->
            <div class="flex justify-end gap-4 mt-8">
                <button 
                    type="button" 
                    onclick="clearForm()" 
                    class="px-6 py-3 bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 rounded-lg shadow-sm transition-colors flex items-center"
                >
                    <i class="fas fa-eraser mr-2"></i> Clear Form
                </button>
                <button 
                    type="submit" 
                    name="submit_reservation"
                    class="px-8 py-3 bg-swarm-yellow hover:bg-swarm-dark-yellow text-gray-800 rounded-lg shadow-md transition-colors flex items-center"
                >
                    <i class="fas fa-calendar-check mr-2"></i> Submit Reservation
                </button>
            </div>
        </form>

        <!-- Help Card -->
        <div class="mt-10 bg-blue-50 rounded-xl p-6 border border-blue-100 flex items-start">
            <div class="bg-blue-100 p-3 rounded-full text-blue-500 mr-4">
                <i class="fas fa-question-circle text-xl"></i>
            </div>
            <div>
                <h4 class="font-bold text-blue-800 mb-1">Need Help?</h4>
                <p class="text-blue-700 text-sm mb-3">If you have questions about reserving amenities:</p>
                <div class="flex flex-wrap gap-4 text-sm">
                    <a href="tel:+639667154160" class="text-blue-600 hover:text-blue-800 font-medium flex items-center">
                        <i class="fas fa-phone-alt mr-2"></i> Contact Admin: +(63) 966 715 4160
                    </a>
                    <a href="mailto:thehive.propertymanagement@gmail.com" class="text-blue-600 hover:text-blue-800 font-medium flex items-center">
                        <i class="fas fa-envelope mr-2"></i> Email Support
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set minimum date (48 hours from now) for reservation date
        window.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            today.setDate(today.getDate() + 2); // Add 2 days (48 hours)
            
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            
            const minDate = `${yyyy}-${mm}-${dd}`;
            document.getElementById('reservation_date').setAttribute('min', minDate);
        });

        // Function to clear the form fields
        function clearForm() {
            if(confirm('Are you sure you want to clear all fields?')) {
                document.getElementById('function_hall').checked = false;
                document.getElementById('podium').checked = false;
                document.getElementById('reservation_date').value = '';
                document.getElementById('reservation_time').value = '';
                document.getElementById('number_of_people').value = '';
                document.getElementById('additional_request').value = '';
                document.getElementById('terms_agree').checked = false;
                document.getElementById('own_catering').checked = false;
                document.getElementById('accredited_catering').checked = false;
                document.getElementById('caterer_name').value = '';
                document.getElementById('catering_contact').value = '';
            }
        }

        // Validate number of people based on selected amenity
        document.addEventListener('DOMContentLoaded', function() {
            const functionHallRadio = document.getElementById('function_hall');
            const podiumRadio = document.getElementById('podium');
            const numberOfPeople = document.getElementById('number_of_people');
            
            function updateNumberOfPeopleMax() {
                if (functionHallRadio.checked) {
                    numberOfPeople.setAttribute('max', '100');
                    if (parseInt(numberOfPeople.value) > 100) {
                        numberOfPeople.value = 100;
                    }
                } else if (podiumRadio.checked) {
                    numberOfPeople.setAttribute('max', '150');
                    if (parseInt(numberOfPeople.value) > 150) {
                        numberOfPeople.value = 150;
                    }
                }
            }
            
            functionHallRadio.addEventListener('change', updateNumberOfPeopleMax);
            podiumRadio.addEventListener('change', updateNumberOfPeopleMax);
            
            // Initial call to set the proper max value
            if (functionHallRadio.checked || podiumRadio.checked) {
                updateNumberOfPeopleMax();
            }
        });

        // Catering form handling
        const ownCatering = document.getElementById('own_catering');
        const accreditedCatering = document.getElementById('accredited_catering');
        const cateringDetails = document.getElementById('catering_details');
        const catererName = document.getElementById('caterer_name');
        const cateringContact = document.getElementById('catering_contact');

        function toggleCateringDetails() {
            if (ownCatering.checked || accreditedCatering.checked) {
                cateringDetails.style.display = 'block';
            } else {
                cateringDetails.style.display = 'none';
                catererName.value = '';
                cateringContact.value = '';
            }
        }

        ownCatering.addEventListener('change', toggleCateringDetails);
        accreditedCatering.addEventListener('change', toggleCateringDetails);

        // Initial state
        toggleCateringDetails();
        
        
    </script>
</body>
</html>