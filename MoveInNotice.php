<?php
include 'dbconn.php';

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Manila');

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$message = '';
$userType = '';
$signature = '';
$ownerName = '';
$user_id = null;
$residentCode = '';
$stats = 'Approval';
$moveInDate = '';
$lastMoveInDate = null;
$lastLeaseContract = null;

if (isset($_SESSION['username'])) {
    $ownerUsername = $_SESSION['username'];

    // Retrieve signature from owner or tenant
    $sql = "SELECT ID, Owner_ID, First_Name, Last_Name, Signature FROM ownerinformation WHERE Username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ownerUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userType = 'Owner';
        $residentCode = $row['Owner_ID'];
        $ownerName = $row['First_Name'] . " " . $row['Last_Name'];
        $user_id = $row['ID'];
        $signature = $row['Signature'];

        // Add query to check existing move-in records
        $moveInQuery = "SELECT MoveInDate, lease_contract 
                       FROM ownertenantmovein 
                       WHERE Resident_Code = ? 
                       ORDER BY MoveInDate DESC 
                       LIMIT 1";
        $moveInStmt = $conn->prepare($moveInQuery);
        $moveInStmt->bind_param("s", $residentCode);
        $moveInStmt->execute();
        $moveInResult = $moveInStmt->get_result();
        
        if ($moveInResult->num_rows > 0) {
            $moveInRow = $moveInResult->fetch_assoc();
            $lastMoveInDate = $moveInRow['MoveInDate'];
            $lastLeaseContract = $moveInRow['lease_contract'];
        }
        $moveInStmt->close();
    } else { 
        $sql = "SELECT Tenant_ID, First_Name, Last_Name, Owner_ID, Signature FROM tenantinformation WHERE Username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $ownerUsername);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $userType = 'Tenant';
            $residentCode = $row['Tenant_ID'];
            $ownerName = $row['First_Name'] . " " . $row['Last_Name'];
            $signature = $row['Signature'];

            $ownerQuery = "SELECT ID FROM ownerinformation WHERE Owner_ID = ?";
            $stmt_owner = $conn->prepare($ownerQuery);
            $stmt_owner->bind_param("s", $row['Owner_ID']);
            $stmt_owner->execute();
            $owner_result = $stmt_owner->get_result();

            if ($owner_result->num_rows > 0) {
                $owner_row = $owner_result->fetch_assoc();
                $user_id = $owner_row['ID'];
            }
            $stmt_owner->close();
        } else {
            echo "No matching user found.";
            exit;
        }
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate that required fields are not empty
    if (empty($_POST['MoveInDate']) || empty($_POST['date']) || empty($_POST['repName']) || empty($_POST['repContact'])) {
        echo "<script>alert('Please fill in all required fields.');</script>";
        exit;
    }

    // Sanitize inputs
    $moveInDate = mysqli_real_escape_string($conn, $_POST['MoveInDate']);
    $parkingSlotNumber = mysqli_real_escape_string($conn, $_POST['number']);
    $leaseExpiryDate = mysqli_real_escape_string($conn, $_POST['date']);
    $representativeName = mysqli_real_escape_string($conn, $_POST['repName']);
    $residentContact = mysqli_real_escape_string($conn, $_POST['repContact']);
    
    // Check if move-in date is valid
    $currentDate = date('Y-m-d');
    if ($moveInDate < $currentDate) {
        echo "<script>alert('Move-in date cannot be in the past.');</script>";
        exit;
    }

    // Check if lease expiry date is after move-in date
    if ($leaseExpiryDate <= $moveInDate) {
        echo "<script>alert('Lease expiry date must be after move-in date.');</script>";
        exit;
    }

    // Process lease contract file
    $leaseContractFile = null;
    if (!empty($_FILES["lease-contract"]["name"])) {
        $leaseContractFile = uploadFile($_FILES["lease-contract"], "Contracts/");
        if ($leaseContractFile === null) {
            exit; // File upload failed
        }
    }

    // Insert into ownertenantmovein table
    $sql = "INSERT INTO ownertenantmovein (
        Resident_Code, 
        MoveInDate, 
        Resident_Name, 
        parkingSlotNumber, 
        leaseExpiryDate, 
        representativeName, 
        Resident_Contact, 
        Signature, 
        lease_contract, 
        Status, 
        user_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssssi",
        $residentCode,
        $moveInDate,
        $ownerName,
        $parkingSlotNumber,
        $leaseExpiryDate,
        $representativeName,
        $residentContact,
        $signature,
        $leaseContractFile,
        $stats,
        $user_id
    );

    if ($stmt->execute()) {
        $moveInId = $conn->insert_id;
        
        // Insert into servicerequests table
        $serviceType = "MoveIn";

        $serviceRequestSql = "INSERT INTO servicerequests (            service_id,             service_type,             user_id        ) VALUES (?, ?, ?)";
                $serviceStmt = $conn->prepare($serviceRequestSql);
        $serviceStmt->bind_param("isi", $moveInId, $serviceType, $user_id);
        
        if ($serviceStmt->execute()) {
            echo "<script>
                alert('Move-In Notice submitted successfully!');
                window.location.href = 'OwnerServices.php';
            </script>";
        } else {
            echo "<script>alert('Error adding service request: " . $serviceStmt->error . "');</script>";
        }
        $serviceStmt->close();
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

function uploadFile($file, $uploadDir) {
    $targetDir = __DIR__ . "/" . $uploadDir;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = basename($file["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    $allowedTypes = array("jpg", "jpeg", "png", "pdf");
    if (in_array($fileType, $allowedTypes)) {
        if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
            return $uploadDir . $fileName;
        } else {
            echo "<script>alert('Error uploading file.');</script>";
            return null;
        }
    } else {
        echo "<script>alert('Invalid file type. Only JPG, JPEG, PNG, and PDF are allowed.');</script>";
        return null;
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Swarm | Move-In Notice</title>
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
                        <span class="text-primary">Move-In</span> Notice Form
                    </h1>
                </div>
            </div>
        </div>

        <!-- Form Section -->
        <form method="POST" enctype="multipart/form-data" action="MoveInNotice.php" class="space-y-8">
            <!-- Important Notice -->
            <div class="bg-yellow-50 rounded-xl shadow-sm p-6 border-l-4 border-yellow-400">
                <div class="flex items-start">
                    <div class="bg-yellow-100 p-3 rounded-full mr-4">
                        <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-yellow-800 mb-2">Important Notice</h3>
                        <p class="text-yellow-800">
                            The accomplished clearance form will be forwarded to the Property Administration Office seven (7) days prior the move-in date of my tenant.
                        </p>
                    </div>
                </div>
            </div>

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
                        <input type="text" id="ownerName" name="ownerName" 
                               value="<?php echo htmlspecialchars($ownerName); ?>" 
                               readonly
                               class="w-full px-4 py-2 bg-gray-100 border border-gray-200 rounded-lg">
                    </div>

                    <div class="form-group">
    <label class="block text-gray-700 font-medium mb-2">Move-In Date
        <span class="text-red-500">*</span>
    </label>
    <input type="date" id="MoveInDate" name="MoveInDate"  
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
                        <label class="block text-gray-700 font-medium mb-2">Lease Expiry Date
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="date" name="date" 
                               required
                               class="w-full px-4 py-2 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 mt-6">
                    <div class="form-group">
                        <label class="block text-gray-700 font-medium mb-2">Lease Contract
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="border-2 border-dashed border-gray-200 rounded-lg p-4 text-center hover:border-primary transition-colors duration-300 bg-gray-50 relative">
                            <input type="file" id="lease-contract" name="lease-contract" 
                                   required accept=".pdf,.jpg,.jpeg,.png"
                                   class="hidden"
                                   onchange="handleFileSelect(this)">
                            <label for="lease-contract" class="cursor-pointer flex flex-col items-center justify-center">
                                <div class="w-12 h-12 bg-yellow-50 rounded-full flex items-center justify-center mb-2">
                                    <i class="fas fa-file-upload text-primary text-xl"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-700">Click to upload lease contract</span>
                                <span class="text-xs text-gray-500">PDF, JPG, JPEG, PNG</span>
                            </label>
                            <div id="file-preview" class="mt-4 hidden">
                                <!-- Image preview container -->
                                <div id="image-preview-container" class="hidden">
                                    <img id="image-preview" src="#" alt="Contract Preview" class="mx-auto max-h-48 rounded-lg border border-gray-200" />
                                </div>
                                <!-- PDF preview container -->
                                <div id="pdf-preview-container" class="hidden">
                                    <iframe id="pdf-preview" src="" class="w-full h-96 rounded-lg border border-gray-200"></iframe>
                                </div>
                                <!-- File info -->
                                <div class="flex items-center justify-between mt-3 p-3 bg-white rounded-lg border border-gray-200">
                                    <div class="flex items-center space-x-3">
                                        <i id="file-icon" class="fas fa-file-alt text-gray-400 text-xl"></i>
                                        <div>
                                            <p id="file-name" class="font-medium text-gray-700"></p>
                                            <p id="file-size" class="text-sm text-gray-500"></p>
                                        </div>
                                    </div>
                                    <button type="button" onclick="removeFile()" 
                                            class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
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
        // Existing date handling
        document.getElementById('currentDate').valueAsDate = new Date();

        // Clear form function
        function clearForm() {
            if(confirm('Are you sure you want to clear the form?')) {
                document.querySelector('form').reset();
                document.getElementById('currentDate').valueAsDate = new Date();
                removeFile();
            }
        }

        function handleFileSelect(input) {
            const file = input.files[0];
            if (!file) return;

            const filePreview = document.getElementById('file-preview');
            const fileName = document.getElementById('file-name');
            const fileSize = document.getElementById('file-size');
            const fileIcon = document.getElementById('file-icon');
            const imagePreviewContainer = document.getElementById('image-preview-container');
            const pdfPreviewContainer = document.getElementById('pdf-preview-container');
            
            // Update file info
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            filePreview.classList.remove('hidden');

            // Reset previews
            imagePreviewContainer.classList.add('hidden');
            pdfPreviewContainer.classList.add('hidden');

            // Handle preview based on file type
            if (file.type.startsWith('image/')) {
                fileIcon.className = 'fas fa-file-image text-blue-500 text-xl';
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('image-preview').src = e.target.result;
                    imagePreviewContainer.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            } else if (file.type === 'application/pdf') {
                fileIcon.className = 'fas fa-file-pdf text-red-500 text-xl';
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('pdf-preview').src = URL.createObjectURL(file);
                    pdfPreviewContainer.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            }
        }

        function removeFile() {
            const input = document.getElementById('lease-contract');
            const filePreview = document.getElementById('file-preview');
            const imagePreviewContainer = document.getElementById('image-preview-container');
            const pdfPreviewContainer = document.getElementById('pdf-preview-container');
            
            input.value = '';
            filePreview.classList.add('hidden');
            imagePreviewContainer.classList.add('hidden');
            pdfPreviewContainer.classList.add('hidden');
            
            // Clear iframe source to prevent lingering PDF
            document.getElementById('pdf-preview').src = '';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
</body>
</html>