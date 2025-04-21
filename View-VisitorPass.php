<?php
include 'dbconn.php';
session_start();

// Check if an ID is provided
$pass_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($pass_id <= 0) {
    echo "<script>alert('Invalid visitor pass ID.'); window.location.href='adm-servicerequest.php';</script>";
    exit;
}

// Fetch visitor pass details
$visitorPass = null;
$guestInfo = [];

$sql = "SELECT vp.*, sr.id as sr_id 
        FROM ownertenantvisitor vp 
        LEFT JOIN servicerequests sr ON vp.id = sr.service_id AND sr.service_type = 'VisitorPass'
        WHERE vp.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pass_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $visitorPass = $result->fetch_assoc();
    
    // Parse the guest info JSON
    if (!empty($visitorPass['guest_info'])) {
        $guestInfo = json_decode($visitorPass['guest_info'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $guestInfo = [];
        }
    }
} else {
    echo "<script>alert('Visitor pass not found.'); window.location.href='adm-servicerequest.php';</script>";
    exit;
}

// Handle status update if form submitted (for admin)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status']) && isset($_SESSION['admin_id'])) {
    $newStatus = $_POST['status'];
    $updateSql = "UPDATE ownertenantvisitor SET Status = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("si", $newStatus, $pass_id);
    
    if ($updateStmt->execute()) {
        // Log the action (audit trail)
        $admin_id = $_SESSION['admin_id'];
        $log_query = "INSERT INTO audit_logs (admin_id, action, details, timestamp) 
                     VALUES (?, 'Update Visitor Pass Status', ?, NOW())";
        $logStmt = $conn->prepare($log_query);
        $logDetails = "Updated Visitor Pass #" . $pass_id . " status to " . $newStatus;
        $logStmt->bind_param("ss", $admin_id, $logDetails);
        $logStmt->execute();
        
        echo "<script>alert('Status updated successfully.'); window.location.href='View-VisitorPass.php?id=" . $pass_id . "';</script>";
    } else {
        echo "<script>alert('Failed to update status.');</script>";
    }
}

// Format dates nicely
function formatDate($dateString) {
    return date('F j, Y', strtotime($dateString));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Pass Details | Swarm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'vis-orange': '#f97316',
                        'vis-blue': '#3b82f6',
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
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Visitor Pass Details</h1>
            </div>
            
            <div class="bg-white py-1 px-4 rounded-full shadow-md border-l-4 border-vis-orange flex items-center">
                <i class="fas fa-id-card text-vis-orange mr-2"></i>
                <span class="font-bold">VP-<?php echo $visitorPass['id']; ?></span>
            </div>
        </div>
        
        <!-- Status Badge -->
        <div class="mb-6">
            <?php
            $statusClass = '';
            $statusIcon = '';
            switch(strtolower($visitorPass['Status'])) {
                case 'pending':
                    $statusClass = 'bg-amber-100 text-amber-800';
                    $statusIcon = 'clock';
                    break;
                case 'approval':
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
                <span>Status: <?php echo $visitorPass['Status']; ?></span>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Left Column - Visit Info -->
            <div class="md:col-span-2 space-y-6">
                <!-- Visit Information -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-vis-orange bg-opacity-10 p-2 rounded-full mr-3">
                            <i class="fas fa-calendar-day text-vis-orange"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Visit Information</h2>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex flex-col sm:flex-row">
                            <div class="font-medium text-gray-600 w-full sm:w-1/3">Date Range:</div>
                            <div class="text-gray-800 w-full sm:w-2/3">
                                <?php echo formatDate($visitorPass['start_date']); ?> - 
                                <?php echo formatDate($visitorPass['end_date']); ?>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row">
                            <div class="font-medium text-gray-600 w-full sm:w-1/3">Duration:</div>
                            <div class="text-gray-800 w-full sm:w-2/3">
                                <?php 
                                $start = new DateTime($visitorPass['start_date']);
                                $end = new DateTime($visitorPass['end_date']);
                                $days = $end->diff($start)->days + 1;
                                echo $days . ' ' . ($days > 1 ? 'days' : 'day');
                                ?>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row">
                            <div class="font-medium text-gray-600 w-full sm:w-1/3">Submitted:</div>
                            <div class="text-gray-800 w-full sm:w-2/3">
                                <?php echo date('F j, Y \a\t g:i A', strtotime($visitorPass['submitted_at'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Visitors Information -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-vis-blue bg-opacity-10 p-2 rounded-full mr-3">
                            <i class="fas fa-users text-vis-blue"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Visitors</h2>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200 rounded-lg overflow-hidden">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-2 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="py-2 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Number</th>
                                    <th class="py-2 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Relationship</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if (empty($guestInfo)): ?>
                                <tr>
                                    <td colspan="3" class="py-4 px-6 text-center text-gray-500 italic">No visitor information available</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($guestInfo as $guest): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 px-4"><?php echo htmlspecialchars($guest['name'] ?? 'N/A'); ?></td>
                                        <td class="py-3 px-4"><?php echo htmlspecialchars($guest['contact'] ?? 'N/A'); ?></td>
                                        <td class="py-3 px-4"><?php echo htmlspecialchars($guest['relationship'] ?? 'N/A'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Status Update Form (For Admin) -->
                <?php if (isset($_SESSION['admin_id'])): ?>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-purple-100 p-2 rounded-full mr-3">
                            <i class="fas fa-tasks text-purple-600"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Update Status</h2>
                    </div>
                    
                    <form method="POST" class="flex items-center space-x-4">
                        <select name="status" class="flex-grow px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-vis-orange">
                            <option value="Pending" <?php echo ($visitorPass['Status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Approval" <?php echo ($visitorPass['Status'] == 'Approval') ? 'selected' : ''; ?>>Approved</option>
                            <option value="Completed" <?php echo ($visitorPass['Status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="Rejected" <?php echo ($visitorPass['Status'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                        <button type="submit" name="update_status" class="px-6 py-2 bg-vis-orange hover:bg-orange-600 text-white font-medium rounded-lg transition duration-300">
                            Update Status
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Right Column - Resident Info & Documents -->
            <div class="space-y-6">
                <!-- Resident Information -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-green-100 p-2 rounded-full mr-3">
                            <i class="fas fa-user text-green-600"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Resident Info</h2>
                    </div>
                    
                    <div class="space-y-3">
                        <div>
                            <div class="font-medium text-gray-600 mb-1">Resident Code:</div>
                            <div class="text-gray-800"><?php echo htmlspecialchars($visitorPass['Resident_Code']); ?></div>
                        </div>
                        
                        <div>
                            <div class="font-medium text-gray-600 mb-1">Type:</div>
                            <div class="text-gray-800"><?php echo htmlspecialchars($visitorPass['user_type']); ?></div>
                        </div>
                        
                        <div>
                            <div class="font-medium text-gray-600 mb-1">Unit No:</div>
                            <div class="text-gray-800"><?php echo htmlspecialchars($visitorPass['unit_no'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Valid ID Document -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-indigo-100 p-2 rounded-full mr-3">
                            <i class="fas fa-id-card text-indigo-600"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Valid ID</h2>
                    </div>
                    
                    <div class="flex justify-center">
                        <?php if (!empty($visitorPass['valid_id'])): ?>
                        <img 
                            src="<?php echo htmlspecialchars($visitorPass['valid_id']); ?>" 
                            alt="Valid ID" 
                            class="max-h-64 object-contain border border-gray-200 rounded-lg cursor-pointer hover:opacity-90 transition-opacity"
                            onclick="showImageModal(this.src)"
                        >
                        <?php else: ?>
                        <div class="text-gray-500 italic text-center py-8">No ID document available</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Signature -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-amber-100 p-2 rounded-full mr-3">
                            <i class="fas fa-signature text-amber-600"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Signature</h2>
                    </div>
                    
                    <div class="flex justify-center">
                        <?php if (!empty($visitorPass['signature'])): ?>
                        <img 
                            src="<?php echo htmlspecialchars($visitorPass['signature']); ?>" 
                            alt="Signature" 
                            class="max-h-32 object-contain border border-gray-200 rounded-lg cursor-pointer hover:opacity-90 transition-opacity"
                            onclick="showImageModal(this.src)"
                        >
                        <?php else: ?>
                        <div class="text-gray-500 italic text-center py-8">No signature available</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Image Modal -->
    <div id="imageModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-90 flex items-center justify-center">
        <div class="absolute top-4 right-4">
            <button onclick="closeImageModal()" class="text-white hover:text-gray-300 text-4xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <img id="modalImage" class="max-w-[90vw] max-h-[90vh] object-contain" alt="Enlarged Image">
    </div>
    
    <script>
        function showImageModal(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('imageModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
        
        // Close modal when clicking outside the image
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeImageModal();
            }
        });
        
        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !document.getElementById('imageModal').classList.contains('hidden')) {
                closeImageModal();
            }
        });
    </script>
</body>
</html>
