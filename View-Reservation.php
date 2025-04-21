<?php
include 'dbconn.php';
session_start();

// Check if an ID is provided
$reservation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($reservation_id <= 0) {
    echo "<script>alert('Invalid reservation ID.'); window.location.href='adm-servicerequest.php';</script>";
    exit;
}

// Fetch reservation details
$reservation = null;

$sql = "SELECT r.*, sr.id as sr_id 
        FROM ownertenantreservation r 
        LEFT JOIN servicerequests sr ON r.id = sr.service_id AND sr.service_type = 'AmenityReservation'
        WHERE r.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $reservation = $result->fetch_assoc();
} else {
    echo "<script>alert('Reservation not found.'); window.location.href='adm-servicerequest.php';</script>";
    exit;
}

// Handle status update if form submitted (for admin)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status']) && isset($_SESSION['admin_id'])) {
    $newStatus = $_POST['status'];
    $updateSql = "UPDATE ownertenantreservation SET status = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("si", $newStatus, $reservation_id);
    
    if ($updateStmt->execute()) {
        // Log the action (audit trail)
        $admin_id = $_SESSION['admin_id'];
        $log_query = "INSERT INTO audit_logs (admin_id, action, details, timestamp) 
                     VALUES (?, 'Update Reservation Status', ?, NOW())";
        $logStmt = $conn->prepare($log_query);
        $logDetails = "Updated Amenity Reservation #" . $reservation_id . " status to " . $newStatus;
        $logStmt->bind_param("ss", $admin_id, $logDetails);
        $logStmt->execute();
        
        echo "<script>alert('Status updated successfully.'); window.location.href='View-Reservation.php?id=" . $reservation_id . "';</script>";
    } else {
        echo "<script>alert('Failed to update status.');</script>";
    }
}

// Format date nicely
function formatDate($dateString) {
    return date('F j, Y', strtotime($dateString));
}

// Format time nicely
function formatTime($timeString) {
    return date('g:i A', strtotime($timeString));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amenity Reservation Details | Swarm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Tailwind CSS -->
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
                    },
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen max-w-5xl mx-auto px-4 py-8">
        <!-- Header Section -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <a href="adm-servicerequest.php" class="px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 rounded-full shadow flex items-center gap-2 transition duration-300">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Requests</span>
                </a>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Amenity Reservation Details</h1>
            </div>
            
            <div class="bg-white py-1 px-4 rounded-full shadow-md border-l-4 border-swarm-yellow flex items-center">
                <i class="fas fa-calendar-alt text-swarm-yellow mr-2"></i>
                <span class="font-bold">AR-<?php echo $reservation['id']; ?></span>
            </div>
        </div>
        
        <!-- Status Badge -->
        <div class="mb-6">
            <?php
            $statusClass = '';
            $statusIcon = '';
            switch(strtolower($reservation['status'])) {
                case 'pending':
                    $statusClass = 'bg-amber-100 text-amber-800';
                    $statusIcon = 'clock';
                    break;
                case 'approved':
                    $statusClass = 'bg-blue-100 text-blue-800';
                    $statusIcon = 'thumbs-up';
                    break;
                case 'completed':
                    $statusClass = 'bg-green-100 text-green-800';
                    $statusIcon = 'check-circle';
                    break;
                case 'rejected':
                    $statusClass = 'bg-red-100 text-red-800';
                    $statusIcon = 'times-circle';
                    break;
                default:
                    $statusClass = 'bg-gray-100 text-gray-800';
                    $statusIcon = 'question-circle';
            }
            ?>
            <div class="inline-flex items-center <?php echo $statusClass; ?> px-4 py-2 rounded-full font-medium">
                <i class="fas fa-<?php echo $statusIcon; ?> mr-2"></i>
                <span>Status: <?php echo $reservation['status']; ?></span>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Left Column - Reservation Details -->
            <div class="md:col-span-2 space-y-6">
                <!-- Amenity Information -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-5">
                        <div class="bg-swarm-light-yellow p-2 rounded-full mr-3">
                            <i class="fas fa-building text-swarm-yellow"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Amenity Information</h2>
                    </div>
                    
                    <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-100 mb-4">
                        <div class="flex items-center">
                            <?php if ($reservation['amenity'] == 'Function Hall'): ?>
                                <i class="fas fa-utensils text-3xl text-yellow-500 mr-3"></i>
                                <div>
                                    <h3 class="font-bold text-gray-800">Function Hall</h3>
                                    <p class="text-sm text-gray-600">Perfect for parties and gatherings</p>
                                </div>
                            <?php else: ?>
                                <i class="fas fa-archway text-3xl text-orange-500 mr-3"></i>
                                <div>
                                    <h3 class="font-bold text-gray-800">Podium</h3>
                                    <p class="text-sm text-gray-600">Ideal for larger events and ceremonies</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex flex-col sm:flex-row">
                            <div class="font-medium text-gray-600 w-full sm:w-1/3">Reservation Date:</div>
                            <div class="text-gray-800 w-full sm:w-2/3">
                                <?php echo formatDate($reservation['reservation_date']); ?>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row">
                            <div class="font-medium text-gray-600 w-full sm:w-1/3">Reservation Time:</div>
                            <div class="text-gray-800 w-full sm:w-2/3">
                                <?php echo formatTime($reservation['reservation_time']); ?>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row">
                            <div class="font-medium text-gray-600 w-full sm:w-1/3">Number of People:</div>
                            <div class="text-gray-800 w-full sm:w-2/3">
                                <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded-lg font-medium">
                                    <?php echo $reservation['number_of_people']; ?> guests
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row">
                            <div class="font-medium text-gray-600 w-full sm:w-1/3">Submitted On:</div>
                            <div class="text-gray-800 w-full sm:w-2/3">
                                <?php echo date('F j, Y \a\t g:i A', strtotime($reservation['reservation_created_at'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Requests -->
                <?php if (!empty($reservation['additional_request'])): ?>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-purple-50 p-2 rounded-full mr-3">
                            <i class="fas fa-clipboard-list text-purple-500"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Additional Requests</h2>
                    </div>
                    
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($reservation['additional_request'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Status Update Form (For Admin) -->
                <?php if (isset($_SESSION['admin_id'])): ?>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-blue-50 p-2 rounded-full mr-3">
                            <i class="fas fa-tasks text-blue-500"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Update Status</h2>
                    </div>
                    
                    <form method="POST" class="flex items-center space-x-4">
                        <select name="status" class="flex-grow px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-swarm-yellow">
                            <option value="Pending" <?php echo ($reservation['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Approved" <?php echo ($reservation['status'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                            <option value="Completed" <?php echo ($reservation['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="Rejected" <?php echo ($reservation['status'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                        <button type="submit" name="update_status" class="px-6 py-2 bg-swarm-yellow hover:bg-swarm-dark-yellow text-gray-800 font-medium rounded-lg transition duration-300">
                            Update Status
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Right Column - Resident Info -->
            <div class="space-y-6">
                <!-- Resident Information -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-green-50 p-2 rounded-full mr-3">
                            <i class="fas fa-user text-green-600"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Resident Info</h2>
                    </div>
                    
                    <div class="space-y-3">
                        <div>
                            <div class="font-medium text-gray-600 mb-1">Resident Code:</div>
                            <div class="text-gray-800"><?php echo htmlspecialchars($reservation['Resident_Code']); ?></div>
                        </div>
                        
                        <div>
                            <div class="font-medium text-gray-600 mb-1">Type:</div>
                            <div class="text-gray-800"><?php echo htmlspecialchars($reservation['user_type']); ?></div>
                        </div>
                        
                        <div>
                            <div class="font-medium text-gray-600 mb-1">Email:</div>
                            <div class="text-gray-800"><?php echo htmlspecialchars($reservation['user_email']); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Amenity Rules & Guidelines -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-red-50 p-2 rounded-full mr-3">
                            <i class="fas fa-exclamation-triangle text-red-500"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Amenity Rules</h2>
                    </div>
                    
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-0.5 mr-2"></i>
                            <span>Reservations must be made at least 48 hours in advance</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-0.5 mr-2"></i>
                            <span>Function hall capacity: 50 people, Podium capacity: 100 people</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-0.5 mr-2"></i>
                            <span>Cancelations must be made 24 hours before reservation</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-0.5 mr-2"></i>
                            <span>All amenities must be cleaned after use</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Actions Box -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-blue-50 p-2 rounded-full mr-3">
                            <i class="fas fa-tools text-blue-500"></i>
                        </div>
                        <h2 class="text-lg font-bold text-gray-800">Quick Actions</h2>
                    </div>
                    
                    <div class="space-y-3">
                        <a href="mailto:<?php echo htmlspecialchars($reservation['user_email']); ?>" class="flex items-center p-3 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors">
                            <i class="fas fa-envelope mr-2"></i>
                            <span>Email Resident</span>
                        </a>
                        
                        <button onclick="printReservationDetails()" class="w-full flex items-center p-3 bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="fas fa-print mr-2"></i>
                            <span>Print Details</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function printReservationDetails() {
            window.print();
        }
    </script>
</body>
</html>
