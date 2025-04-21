<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection
include('dbconn.php');

// Check if username is set in the session
if (!isset($_SESSION['username'])) {
    die("Username not found in session. Please log in.");
}

$username = $_SESSION['username'];
$user_id = null;
$user_type = null; // Will be determined

// Check if the user is an owner
$sql = "SELECT Owner_ID, Status FROM ownerinformation WHERE Username = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Check if the user is a tenant if not found in ownerinformation
    $sql = "SELECT Tenant_ID, Status FROM tenantinformation WHERE Username = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("No tenant.");
    } else {
        // User is a tenant
        $row = $result->fetch_assoc();
        $user_id = $row['Tenant_ID'];
        $user_type = 'tenant';
    }
}

$stmt->close();

// Now fetch financial records based on user type (Owner_ID or Tenant_ID)
if ($user_type == 'owner') {
    $sql = "SELECT 
                sf.Billing_Number,
                sf.Unit_No,
                sf.Billing_Date,
                sf.Due_Date,
                sf.Status AS Payment_Status,
                sfre.Payment_Status AS Display_Payment_Status
            FROM soafinance sf
            JOIN soafinance_records sfre ON sf.Billing_Number = sfre.Billing_Number
            WHERE sf.Owner_ID = ?";
} else {
    $sql = "SELECT 
                sf.Billing_Number,
                sf.Unit_No,
                sf.Billing_Date,
                sf.Due_Date,
                sf.Status AS Payment_Status,
                sfre.Payment_Status AS Display_Payment_Status
            FROM soafinance sf
            JOIN soafinance_records sfre ON sf.Billing_Number = sfre.Billing_Number
            WHERE sf.Tenant_ID = ?";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Handle File Upload for Proof of Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['paymentProof'])) {
    $billingNumber = $_POST['billingNumber']; // Assuming billing number is passed via POST
    $file = $_FILES['paymentProof'];
    $uploadDir = 'uploads/proofs/';
    $fileName = basename($file['name']);
    $uploadFile = $uploadDir . $fileName;

    // Validate file type (PDF only)
    if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) === 'pdf') {
        if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
            // Insert the file info into the database
            $sql = "INSERT INTO soafinance_pdfs (Billing_Number, PDF_File) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $billingNumber, $uploadFile);
            $stmt->execute();
            $stmt->close();
            echo "Payment proof uploaded successfully!";
        } else {
            echo "Failed to upload the file.";
        }
    } else {
        echo "Invalid file type. Only PDF files are allowed.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SWARM | Payment Status</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans min-h-screen flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-white p-6 shadow-lg">
        <div class="flex items-center mb-6">
            <img src="img/logo swarm.png" alt="SWARM Logo" class="w-8 h-8">
            <span class="ml-3 text-xl font-semibold text-yellow-500">SWARM Portal</span>
        </div>
        <div class="space-y-4">
            <a href="OwnerAnnouncement.php" class="block px-4 py-2 text-gray-700 hover:bg-yellow-500 hover:text-white rounded-md">Announcements</a>
            <a href="OwnerServices.php" class="block px-4 py-2 text-gray-700 hover:bg-yellow-500 hover:text-white rounded-md">Services</a>
            <a href="OwnerPaymentinfo.php" class="block px-4 py-2 text-gray-700 hover:bg-yellow-500 hover:text-white rounded-md">Payment Info</a>
            <a href="OwnerPaymentStatus.php" class="block px-4 py-2 bg-yellow-500 text-white rounded-md">Tenant Status</a>
            <a href="OwnerSafetyguidelines.php" class="block px-4 py-2 text-gray-700 hover:bg-yellow-500 hover:text-white rounded-md">Safety Guidelines</a>
            <a href="OwnerCommunityfeedback.php" class="block px-4 py-2 text-gray-700 hover:bg-yellow-500 hover:text-white rounded-md">Community Feedback</a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 p-6">
        <!-- Navbar -->
        <div class="flex space-x-6 mb-6">
            <a href="OwnerTenantFormStatus.php" class="px-6 py-2 bg-yellow-500 text-white rounded-md">Form Status</a>
            <a href="OwnerPaymentStatus.php" class="px-6 py-2 bg-yellow-500 text-white rounded-md">Payment Status</a>
            <a href="OwnerAddTenant.php" class="px-6 py-2 bg-yellow-500 text-white rounded-md">Add Tenant Request</a>
        </div>

        <!-- Financial Records Section -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-2xl font-semibold text-gray-900 mb-6">Financial Records</h2>

            <!-- Payment Records Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-6 py-3 text-left text-gray-700 font-medium">Billing Number</th>
                            <th class="px-6 py-3 text-left text-gray-700 font-medium">Payment Status</th>
                            <th class="px-6 py-3 text-left text-gray-700 font-medium">Billing Date</th>
                            <th class="px-6 py-3 text-left text-gray-700 font-medium">Due Date</th>
                            <th class="px-6 py-3 text-left text-gray-700 font-medium">Upload Proof</th>
                        </tr>
                    </thead>
                    <tbody id="paymentTable">
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-gray-800"><?php echo htmlspecialchars($row['Billing_Number']); ?></td>
                                <td class="px-6 py-3 text-gray-800"><?php echo htmlspecialchars($row['Display_Payment_Status']); ?></td>
                                <td class="px-6 py-3 text-gray-800"><?php echo htmlspecialchars($row['Billing_Date']); ?></td>
                                <td class="px-6 py-3 text-gray-800"><?php echo htmlspecialchars($row['Due_Date']); ?></td>
                                <td class="px-6 py-3 text-gray-800">
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="billingNumber" value="<?php echo htmlspecialchars($row['Billing_Number']); ?>">
                                        <input type="file" name="paymentProof" class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                        <button type="submit" class="ml-2 px-4 py-2 bg-yellow-500 text-white rounded-md">Upload</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
