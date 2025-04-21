<?php
include 'dbconn.php';
session_start();

// Check if an ID is provided
$permit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($permit_id <= 0) {
    echo "<script>alert('Invalid work permit ID.'); window.location.href='adm-servicerequest.php';</script>";
    exit;
}

// Fetch work permit details
$workPermit = null;
$taskDetails = [];
$personnelDetails = [];

$sql = "SELECT wp.*, sr.id as sr_id 
        FROM workpermit wp 
        LEFT JOIN servicerequests sr ON wp.id = sr.service_id AND sr.service_type = 'WorkPermit'
        WHERE wp.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $permit_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $workPermit = $result->fetch_assoc();
    
    // Parse task and personnel details
    $taskDetails = explode(", ", $workPermit['task_details']);
    $personnelDetails = explode(", ", $workPermit['personnel_details']);
} else {
    echo "<script>alert('Work permit not found.'); window.location.href='adm-servicerequest.php';</script>";
    exit;
}

// Handle status update if form submitted (for admin)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status']) && isset($_SESSION['admin_id'])) {
    $newStatus = $_POST['status'];
    $updateSql = "UPDATE workpermit SET Status = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("si", $newStatus, $permit_id);
    
    if ($updateStmt->execute()) {
        // Log the action (audit trail)
        $admin_id = $_SESSION['admin_id'];
        $log_query = "INSERT INTO audit_logs (admin_id, action, details, timestamp) 
                     VALUES (?, 'Update Work Permit Status', ?, NOW())";
        $logStmt = $conn->prepare($log_query);
        $logDetails = "Updated Work Permit #" . $permit_id . " status to " . $newStatus;
        $logStmt->bind_param("ss", $admin_id, $logDetails);
        $logStmt->execute();
        
        echo "<script>alert('Status updated successfully.'); window.location.href='View-WorkPermit.php?id=" . $permit_id . "';</script>";
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
    <title>Work Permit Details | Swarm</title>
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
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Work Permit Details</h1>
            </div>
            
            <div class="bg-white py-1 px-4 rounded-full shadow-md border-l-4 border-swarm-yellow flex items-center">
                <i class="fas fa-tools text-swarm-yellow mr-2"></i>
                <span class="font-bold">WP-<?php echo $workPermit['id']; ?></span>
            </div>
        </div>
        
        <!-- Status Badge -->
        <div class="mb-6">
            <?php
            $statusClass = '';
            $statusIcon = '';
            switch(strtolower($workPermit['status'])) {
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
                <span>Status: <?php echo $workPermit['status']; ?></span>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Left Column - Work Details -->
            <div class="md:col-span-2 space-y-6">
                <!-- Work Type Information -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-swarm-light-yellow p-2 rounded-full mr-3">
                            <i class="fas fa-clipboard-list text-swarm-yellow"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Work Type</h2>
                    </div>
                    
                    <div class="flex flex-wrap gap-2 mb-4">
                        <?php 
                        $workTypes = explode(", ", $workPermit['work_type']);
                        foreach ($workTypes as $type): 
                            $iconClass = 'fas fa-wrench'; // Default icon
                            if (stripos($type, 'renovation') !== false) {
                                $iconClass = 'fas fa-paint-roller';
                            } elseif (stripos($type, 'installation') !== false) {
                                $iconClass = 'fas fa-plug';
                            }
                        ?>
                        <div class="px-4 py-2 bg-gray-50 rounded-lg border border-gray-200 flex items-center">
                            <i class="<?php echo $iconClass; ?> text-swarm-yellow mr-2"></i>
                            <?php echo htmlspecialchars($type); ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex flex-col sm:flex-row">
                            <div class="font-medium text-gray-600 w-full sm:w-1/3">Period:</div>
                            <div class="text-gray-800 w-full sm:w-2/3">
                                <?php echo formatDate($workPermit['period_from']); ?> - 
                                <?php echo formatDate($workPermit['period_to']); ?>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row">
                            <div class="font-medium text-gray-600 w-full sm:w-1/3">Duration:</div>
                            <div class="text-gray-800 w-full sm:w-2/3">
                                <?php 
                                $start = new DateTime($workPermit['period_from']);
                                $end = new DateTime($workPermit['period_to']);
                                $days = $end->diff($start)->days + 1;
                                echo $days . ' ' . ($days > 1 ? 'days' : 'day');
                                ?>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row">
                            <div class="font-medium text-gray-600 w-full sm:w-1/3">Submitted:</div>
                            <div class="text-gray-800 w-full sm:w-2/3">
                                <?php echo date('F j, Y \a\t g:i A', strtotime($workPermit['submitted_at'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Scope of Work Information -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-blue-50 p-2 rounded-full mr-3">
                            <i class="fas fa-tasks text-swarm-blue"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Scope of Work</h2>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200 rounded-lg overflow-hidden">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-2 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Task</th>
                                    <th class="py-2 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Personnel</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php for ($i = 0; $i < count($taskDetails); $i++): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($taskDetails[$i]); ?></td>
                                    <td class="py-3 px-4"><?php echo isset($personnelDetails[$i]) ? htmlspecialchars($personnelDetails[$i]) : ''; ?></td>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Contractor Information (if provided) -->
                <?php if (!empty($workPermit['contractor'])): ?>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-green-50 p-2 rounded-full mr-3">
                            <i class="fas fa-hard-hat text-swarm-green"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Contractor Details</h2>
                    </div>
                    
                    <div class="p-4 bg-green-50 border border-green-100 rounded-lg">
                        <div class="font-medium text-gray-800 mb-1">Contractor Name/Company:</div>
                        <div class="text-gray-800"><?php echo htmlspecialchars($workPermit['contractor']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
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
                        <select name="status" class="flex-grow px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-swarm-yellow">
                            <option value="Pending" <?php echo ($workPermit['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Approval" <?php echo ($workPermit['status'] == 'Approval') ? 'selected' : ''; ?>>Approved</option>
                            <option value="Completed" <?php echo ($workPermit['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="Rejected" <?php echo ($workPermit['status'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                        <button type="submit" name="update_status" class="px-6 py-2 bg-swarm-yellow hover:bg-swarm-dark-yellow text-gray-800 font-medium rounded-lg transition duration-300">
                            Update Status
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Right Column - Resident Info & Signature -->
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
                            <div class="text-gray-800"><?php echo htmlspecialchars($workPermit['Resident_Code']); ?></div>
                        </div>
                        
                        <div>
                            <div class="font-medium text-gray-600 mb-1">Type:</div>
                            <div class="text-gray-800"><?php echo htmlspecialchars($workPermit['user_type']); ?></div>
                        </div>
                        
                        <div>
                            <div class="font-medium text-gray-600 mb-1">Owner Name:</div>
                            <div class="text-gray-800"><?php echo htmlspecialchars($workPermit['owner_name']); ?></div>
                        </div>
                        
                        <div>
                            <div class="font-medium text-gray-600 mb-1">Email:</div>
                            <div class="text-gray-800"><?php echo htmlspecialchars($workPermit['user_email']); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Representative Information -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-blue-100 p-2 rounded-full mr-3">
                            <i class="fas fa-user-tie text-blue-600"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Representative</h2>
                    </div>
                    
                    <div class="flex justify-center mb-4">
                        <div class="inline-block bg-blue-50 px-4 py-2 rounded-lg border border-blue-100">
                            <div class="font-medium text-blue-800"><?php echo htmlspecialchars($workPermit['authorize_rep']); ?></div>
                            <div class="text-sm text-blue-600">Authorized Representative</div>
                        </div>
                    </div>
                </div>
                
                <!-- Signature -->
                <?php if (!empty($workPermit['signature'])): ?>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-amber-100 p-2 rounded-full mr-3">
                            <i class="fas fa-signature text-amber-600"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Signature</h2>
                    </div>
                    
                    <div class="flex justify-center">
                        <img 
                            src="data:image/png;base64,<?php echo htmlspecialchars($workPermit['signature']); ?>" 
                            alt="Signature" 
                            class="max-h-32 object-contain border border-gray-200 rounded-lg cursor-pointer hover:opacity-90 transition-opacity"
                            onclick="showImageModal(this.src)"
                        >
                    </div>
                </div>
                <?php endif; ?>
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
