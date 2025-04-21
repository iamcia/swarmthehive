<?php
include 'dbconn.php';
session_start();

$message = '';
$userType = '';
$userEmail = '';
$signature = '';
$userNumber = '';
$unitNumber = '';
$residentCode = '';
$stats = 'Approval';
$ownerName = '';
$user_id = null;

// Fetch user details if logged in
if (isset($_SESSION['username'])) {
    $ownerUsername = $_SESSION['username'];

    // Fetch from OwnerInformation table
    $sql = "SELECT ID, Owner_ID, First_Name, Last_Name, Status, Signature FROM ownerinformation WHERE Username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ownerUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userType = 'Owner';
        $residentCode = $row['Owner_ID'];
        $ownerName = $row['First_Name'] . " " . $row['Last_Name'];
        $status = $row['Status'];
        $user_id = $row['ID'];
        $signature = $row['Signature'];
    } else {
        // Fetch from TenantInformation table
        $sql = "SELECT Tenant_ID, First_Name, Last_Name, Status, Signature FROM tenantinformation WHERE Username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $ownerUsername);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $userType = 'Tenant';
            $residentCode = $row['Tenant_ID'];
            $ownerName = $row['First_Name'] . " " . $row['Last_Name'];
            $status = $row['Status'];
            $signature = $row['Signature'];

            // Fetch associated owner ID
            $ownerQuery = "SELECT o.ID FROM ownerinformation o INNER JOIN tenantinformation t ON o.Owner_ID = t.Owner_ID WHERE t.Tenant_ID = ?";
            $stmt_owner = $conn->prepare($ownerQuery);
            $stmt_owner->bind_param("s", $residentCode);
            $stmt_owner->execute();
            $owner_result = $stmt_owner->get_result();

            if ($owner_result->num_rows > 0) {
                $owner_row = $owner_result->fetch_assoc();
                $user_id = $owner_row['ID'];
            }
            $stmt_owner->close();
        }
    }
    $stmt->close();
}

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $parking_slot_number = $_POST['number'];
    $days_prior_moveout = $_POST['daysPriorMoveout'];
    $representative_name = $_POST['repName'];
    $resident_contact = $_POST['repContact'];
    $move_out_date = $_POST['moveOutDate'];

    if ($user_id !== null) {
        $conn->begin_transaction();
        try {
            // Insert into ownertenantmoveout table with moveOutDate
            $stmt = $conn->prepare("INSERT INTO ownertenantmoveout (Resident_Code, Resident_Name, parkingSlotNumber, days_prior_moveout, representativeName, Resident_Contact, moveOutDate, Signature, Status, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssisssssi", $residentCode, $ownerName, $parking_slot_number, $days_prior_moveout, $representative_name, $resident_contact, $move_out_date, $signature, $stats, $user_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error inserting into ownertenantmoveout: " . $stmt->error);
            }
            
            // Insert into servicerequests table
            $moveOutId = $conn->insert_id;
            $serviceType = "MoveOut";
            $serviceRequestSql = "INSERT INTO servicerequests (service_id, service_type, user_id) VALUES (?, ?, ?)";
            $serviceStmt = $conn->prepare($serviceRequestSql);
            $serviceStmt->bind_param("isi", $moveOutId, $serviceType, $user_id);
            
            if (!$serviceStmt->execute()) {
                throw new Exception("Error inserting into servicerequests: " . $serviceStmt->error);
            }
            
            $conn->commit();
            echo "<script>alert('Record added successfully!');</script>";
            
            $serviceStmt->close();
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
        }
    } else {
        echo "<script>alert('Error: Could not find a valid user ID. Please contact support.');</script>";
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Swarm | Move-Out Notice</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#f1c40f',
                        primaryDark: '#e1b00f',
                        primaryLight: '#fef3c7',
                        secondary: '#333333',
                        light: '#f4f4f4',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-5xl mx-auto px-4 py-10">
        <!-- Header Section -->
        <div class="mb-10">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center">
                    <button onclick="history.back()" class="px-4 py-2 bg-white hover:bg-gray-100 text-gray-700 rounded-full shadow-sm flex items-center gap-2 transition duration-300 mr-5">
                        <i class="fas fa-arrow-left text-sm"></i>
                        <span>Back</span>
                    </button>
                    <h1 class="text-3xl md:text-4xl font-extrabold text-gray-800">
                        <span class="text-primary">Move-Out</span> Notice Form
                    </h1>
                </div>
            </div>
        </div>

        <!-- Form Section -->
        <form method="POST" enctype="multipart/form-data" action="MoveOutNotice2.php" class="space-y-8">
            <!-- Resident Information -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="bg-blue-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-user text-blue-500"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Resident Information</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="block text-gray-700 font-medium mb-2">Resident Name
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" 
                               value="<?php echo htmlspecialchars($ownerName); ?>" 
                               readonly
                               class="w-full px-4 py-2 bg-gray-100 border border-gray-200 rounded-lg">
                    </div>

                    <div class="form-group">
                        <label class="block text-gray-700 font-medium mb-2">Move-Out Date
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="moveOutDate" name="moveOutDate" 
                               required
                               class="w-full px-4 py-2 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                </div>
            </div>

            <!-- Unit Details -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="bg-amber-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-home text-amber-500"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Unit Details</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="block text-gray-700 font-medium mb-2">Parking Slot Number
                            <span class="text-gray-500">(Optional)</span>
                        </label>
                        <input type="text" id="number" name="number" 
                               class="w-full px-4 py-2 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary">
                    </div>

                    <div class="form-group">
                        <label class="block text-gray-700 font-medium mb-2">Days Prior to Move-Out
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="daysPriorMoveout" name="daysPriorMoveout" 
                               required min="1"
                               class="w-full px-4 py-2 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                </div>
            </div>

            <!-- Representative Information -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="bg-green-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-id-card text-green-500"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Representative Information</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="block text-gray-700 font-medium mb-2">Representative Name
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="repName" name="repName" 
                               required placeholder="First MI. Last"
                               class="w-full px-4 py-2 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary">
                    </div>

                    <div class="form-group">
                        <label class="block text-gray-700 font-medium mb-2">Contact Number
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="repContact" name="repContact" 
                               required placeholder="09XX XXX XXXX"
                               class="w-full px-4 py-2 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                </div>
            </div>
            
          <!-- Hidden Signature Field -->
<input type="hidden" name="signature" value="<?php echo htmlspecialchars($signature); ?>">

            <!-- Submit Button -->
            <div class="flex justify-end space-x-4">
                <button type="button" onclick="clearForm()" 
                        class="px-6 py-3 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-lg shadow-sm border border-gray-200 transition duration-300 flex items-center">
                    <i class="fas fa-eraser mr-2"></i> Clear Form
                </button>
                <button type="submit" 
                        class="px-8 py-3 bg-primary hover:bg-primaryDark text-gray-800 font-medium rounded-lg shadow-md transition duration-300 flex items-center group">
                    <i class="fas fa-check mr-2 group-hover:animate-bounce-slow"></i> Submit Form
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
                <p class="text-blue-700 text-sm mb-3">If you encounter any issues or have questions about this form:</p>
                <div class="flex items-center space-x-4 text-sm">
                    <a href="#" class="text-blue-600 hover:text-blue-800 font-medium flex items-center">
                        <i class="fas fa-phone-alt mr-2"></i> Contact Support
                    </a>
                    <a href="#" class="text-blue-600 hover:text-blue-800 font-medium flex items-center">
                        <i class="fas fa-envelope mr-2"></i> Email Admin
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function clearForm() {
            if(confirm('Are you sure you want to clear the form?')) {
                document.querySelector('form').reset();
            }
        }
    </script>
</body>
</html>