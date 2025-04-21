<?php
// Include database connection
include 'dbconn.php';

// Start session for admin authentication check
session_start();

// Check if admin is logged in
// Commenting out admin login check as requested
/*
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
*/

// Check if concern ID is provided
if (!isset($_GET['id'])) {
    header("Location: adm-comminsights.php?error=no_id");
    exit;
}

$concern_id = $_GET['id'];

// Fetch concern details
$sql = "SELECT * FROM ownertenantconcerns WHERE ID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $concern_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: adm-comminsights.php?error=not_found");
    exit;
}

$concern = mysqli_fetch_assoc($result);

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment = $_POST['comment'];
    $admin_id = $_SESSION['admin_id'];
    
    $comment_sql = "INSERT INTO concern_comments (concern_id, admin_id, comment, created_at) 
                   VALUES (?, ?, ?, NOW())";
    $comment_stmt = mysqli_prepare($conn, $comment_sql);
    mysqli_stmt_bind_param($comment_stmt, "iis", $concern_id, $admin_id, $comment);
    
    if (mysqli_stmt_execute($comment_stmt)) {
        // Redirect to refresh the page and show the new comment
        header("Location: view-comminsights.php?id=$concern_id&success=comment_added");
        exit;
    }
}

// Fetch comments for this concern
$comments_sql = "SELECT cc.*, a.admin_name 
                FROM concern_comments cc
                LEFT JOIN admins a ON cc.admin_id = a.id
                WHERE cc.concern_id = ?
                ORDER BY cc.created_at DESC";
$comments_stmt = mysqli_prepare($conn, $comments_sql);
mysqli_stmt_bind_param($comments_stmt, "i", $concern_id);
mysqli_stmt_execute($comments_stmt);
$comments_result = mysqli_stmt_get_result($comments_stmt);

// Get resident information if available
$resident_info = null;
if (!empty($concern['owner_id'])) {
    $resident_sql = "SELECT * FROM owners WHERE owner_id = ?";
    $resident_stmt = mysqli_prepare($conn, $resident_sql);
    mysqli_stmt_bind_param($resident_stmt, "i", $concern['owner_id']);
    mysqli_stmt_execute($resident_stmt);
    $resident_result = mysqli_stmt_get_result($resident_stmt);
    if (mysqli_num_rows($resident_result) > 0) {
        $resident_info = mysqli_fetch_assoc($resident_result);
    }
} elseif (!empty($concern['tenant_id'])) {
    $resident_sql = "SELECT * FROM tenants WHERE tenant_id = ?";
    $resident_stmt = mysqli_prepare($conn, $resident_sql);
    mysqli_stmt_bind_param($resident_stmt, "i", $concern['tenant_id']);
    mysqli_stmt_execute($resident_stmt);
    $resident_result = mysqli_stmt_get_result($resident_stmt);
    if (mysqli_num_rows($resident_result) > 0) {
        $resident_info = mysqli_fetch_assoc($resident_result);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Concern Details | Swarm Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/admin_style.css?v=<?php echo time(); ?>">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo-container">
                <img src="./img/logo swarm.png" alt="Logo" class="logo">
                <span class="logo-text">SWARM</span>
            </div>

            <div class="menu-title">
                <i class='bx bxs-group'></i>
                <span>Admin</span>
            </div>

            <nav class="sidebar-menu">
                <ul>
                    <li>
                        <a href="adm-dashboard.php">
                            <i class='bx bxs-dashboard'></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-usermanage.php">
                            <i class='bx bx-user'></i>
                            <span>User Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-manageannounce.php">
                            <i class='bx bx-notification'></i>
                            <span>Announcement</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-servicerequest.php">
                            <i class='bx bx-file'></i>
                            <span>Service Requests</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-financialrec.php">
                            <i class='bx bx-wallet'></i>
                            <span>Finance Records</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="adm-comminsights.php">
                            <i class='bx bx-chat'></i>
                            <span>Community Insights</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-auditlogs.php">
                            <i class='bx bx-file-blank'></i>
                            <span>Audit Logs</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-systemsettings.php">
                            <i class='bx bx-cog'></i>
                            <span>Settings</span>
                        </a>
                    </li>
                    <div class="divider"></div>
                    <li>
                        <a href="logout.php" class="nav-item logout">
                            <i class='bx bx-log-out'></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main>
            <div class="bg-white p-6 rounded-lg shadow-md w-full">
                <!-- Success Message -->
                <?php if (isset($_GET['success']) && $_GET['success'] === 'comment_added'): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">Your comment has been added successfully.</span>
                    <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                        <svg onclick="this.parentElement.parentElement.style.display='none'" class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <title>Close</title>
                            <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                        </svg>
                    </span>
                </div>
                <?php endif; ?>

                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Concern Details</h1>
                    <a href="adm-comminsights.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded">
                        <i class='bx bx-arrow-back mr-2'></i> Back to Concerns
                    </a>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <div class="flex flex-wrap justify-between mb-4">
                        <div class="mb-4 md:mb-0">
                            <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mb-2">ID: <?php echo $concern['ID']; ?></span>
                            <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($concern['concern_type']); ?></h2>
                            <p class="text-gray-600">Unit: <?php echo htmlspecialchars($concern['unit_number']); ?></p>
                        </div>
                        <?php 
                            $status_class = '';
                            switch ($concern['concern_status']) {
                                case 'Resolved':
                                    $status_class = 'bg-green-100 text-green-800';
                                    break;
                                case 'In Progress':
                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                    break;
                                default:
                                    $status_class = 'bg-red-100 text-red-800';
                                    break;
                            }
                        ?>
                        <span class="px-4 py-2 rounded-full text-sm font-medium <?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($concern['concern_status']); ?>
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 mb-1">Submitted By</h3>
                            <p class="text-gray-800"><?php echo $concern['user_type']; ?><?php echo !empty($concern['Resident_Code']) ? ' (Code: ' . htmlspecialchars($concern['Resident_Code']) . ')' : ''; ?></p>
                        </div>
                        
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 mb-1">Email</h3>
                            <p class="text-gray-800"><?php echo htmlspecialchars($concern['user_email']); ?></p>
                        </div>
                        
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 mb-1">Contact Number</h3>
                            <p class="text-gray-800"><?php echo htmlspecialchars($concern['user_number']); ?></p>
                        </div>
                        
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 mb-1">Submitted At</h3>
                            <p class="text-gray-800">
                                <?php 
                                    $date = new DateTime($concern['submitted_at']);
                                    echo $date->format('F j, Y - h:i A'); 
                                ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if ($resident_info): ?>
                    <div class="bg-blue-50 rounded-lg p-4 mb-6">
                        <h3 class="text-sm font-semibold text-blue-800 mb-2">Additional Resident Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($resident_info as $key => $value): ?>
                            <?php if ($key != 'password' && $key != 'id' && !empty($value)): ?>
                            <div>
                                <span class="block text-xs text-blue-600"><?php echo ucwords(str_replace('_', ' ', $key)); ?></span>
                                <span class="text-sm"><?php echo htmlspecialchars($value); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-500 mb-2">Concern Details</h3>
                        <div class="bg-white p-4 rounded border border-gray-200">
                            <p class="text-gray-800 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($concern['concern_details'])); ?></p>
                        </div>
                    </div>
                    
                    <?php if (!empty($concern['media_path'])): ?>
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-500 mb-2">Attachments</h3>
                        <div class="flex flex-wrap gap-3">
                            <?php 
                            $media_paths = explode(',', $concern['media_path']);
                            foreach ($media_paths as $path): 
                                $file_extension = pathinfo($path, PATHINFO_EXTENSION);
                                $is_image = in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif']);
                            ?>
                            <?php if ($is_image): ?>
                                <a href="<?php echo htmlspecialchars($path); ?>" target="_blank" class="block w-32 h-32 bg-gray-100 rounded overflow-hidden">
                                    <img src="<?php echo htmlspecialchars($path); ?>" alt="Concern media" class="w-full h-full object-cover">
                                </a>
                            <?php else: ?>
                                <a href="<?php echo htmlspecialchars($path); ?>" download class="flex items-center p-3 border border-gray-200 rounded bg-gray-50 hover:bg-gray-100">
                                    <i class='bx bx-file text-gray-500 text-2xl mr-2'></i>
                                    <span class="text-sm text-gray-700">Download File</span>
                                </a>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($concern['signature'])): ?>
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-500 mb-2">Signature</h3>
                        <div class="bg-white p-4 rounded border border-gray-200 max-w-xs">
                            <img src="<?php echo htmlspecialchars($concern['signature']); ?>" alt="Signature" class="w-full">
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex flex-wrap gap-3 mt-6">
                        <?php if ($concern['concern_status'] != 'Resolved'): ?>
                        <a href="update-concern.php?id=<?php echo $concern['ID']; ?>&action=resolve" class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded">
                            <i class='bx bx-check-circle mr-2'></i> Mark as Resolved
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($concern['concern_status'] == 'Unresolved'): ?>
                        <a href="update-concern.php?id=<?php echo $concern['ID']; ?>&action=progress" class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded">
                            <i class='bx bx-time mr-2'></i> Mark as In Progress
                        </a>
                        <?php endif; ?>
                        
                        <a href="#" onclick="printConcern()" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
                            <i class='bx bx-printer mr-2'></i> Print Details
                        </a>
                    </div>
                </div>
                
                <!-- Comments Section -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Admin Comments</h2>
                    
                    <form method="POST" action="" class="mb-6">
                        <div class="mb-3">
                            <label for="comment" class="block text-sm font-medium text-gray-700 mb-1">Add a Comment</label>
                            <textarea id="comment" name="comment" rows="3" required
                                class="w-full px-3 py-2 text-gray-700 border rounded-lg focus:outline-none focus:border-blue-400"
                                placeholder="Enter your comment here..."></textarea>
                        </div>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
                            <i class='bx bx-message-square-add mr-2'></i> Submit Comment
                        </button>
                    </form>
                    
                    <div class="space-y-4">
                        <?php if (mysqli_num_rows($comments_result) > 0): ?>
                            <?php while ($comment = mysqli_fetch_assoc($comments_result)): ?>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="flex justify-between items-start mb-2">
                                        <p class="font-medium text-gray-800">
                                            <?php echo !empty($comment['admin_name']) ? htmlspecialchars($comment['admin_name']) : 'Admin'; ?>
                                        </p>
                                        <span class="text-xs text-gray-500">
                                            <?php 
                                                $comment_date = new DateTime($comment['created_at']);
                                                echo $comment_date->format('M j, Y - h:i A'); 
                                            ?>
                                        </span>
                                    </div>
                                    <p class="text-gray-700 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-6 text-gray-500">
                                <i class='bx bx-message-detail text-gray-400 text-4xl mb-2'></i>
                                <p>No comments yet. Be the first to comment on this concern.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>

        <!-- Print-friendly version for JavaScript print function -->
        <div id="print-version" class="hidden">
            <style>
                @media print {
                    body { font-family: Arial, sans-serif; }
                    .header { text-align: center; margin-bottom: 20px; }
                    .section { margin-bottom: 15px; }
                    .section-title { font-weight: bold; margin-bottom: 5px; }
                    .concern-details { white-space: pre-line; }
                }
            </style>
            <div class="header">
                <h1>Concern Report #<?php echo $concern['ID']; ?></h1>
                <p>Generated on <?php echo date('F j, Y - h:i A'); ?></p>
            </div>
            
            <div class="section">
                <div class="section-title">Concern Type:</div>
                <div><?php echo htmlspecialchars($concern['concern_type']); ?></div>
            </div>
            
            <div class="section">
                <div class="section-title">Status:</div>
                <div><?php echo htmlspecialchars($concern['concern_status']); ?></div>
            </div>
            
            <div class="section">
                <div class="section-title">Submitted By:</div>
                <div><?php echo htmlspecialchars($concern['user_type']); ?> (<?php echo htmlspecialchars($concern['user_email']); ?>)</div>
                <div>Unit: <?php echo htmlspecialchars($concern['unit_number']); ?></div>
                <div>Contact: <?php echo htmlspecialchars($concern['user_number']); ?></div>
                <div>Date: <?php echo (new DateTime($concern['submitted_at']))->format('F j, Y - h:i A'); ?></div>
            </div>
            
            <div class="section">
                <div class="section-title">Concern Details:</div>
                <div class="concern-details"><?php echo htmlspecialchars($concern['concern_details']); ?></div>
            </div>
            
            <div class="section">
                <div class="section-title">Comments:</div>
                <?php 
                mysqli_data_seek($comments_result, 0); // Reset pointer to beginning of result set
                if (mysqli_num_rows($comments_result) > 0): 
                    while ($comment = mysqli_fetch_assoc($comments_result)): 
                ?>
                <div style="margin-bottom: 10px; padding-left: 10px; border-left: 2px solid #ddd;">
                    <div><strong><?php echo !empty($comment['admin_name']) ? htmlspecialchars($comment['admin_name']) : 'Admin'; ?></strong> - 
                    <?php echo (new DateTime($comment['created_at']))->format('M j, Y'); ?></div>
                    <div><?php echo htmlspecialchars($comment['comment']); ?></div>
                </div>
                <?php 
                    endwhile; 
                else: 
                ?>
                <div>No comments recorded.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function printConcern() {
            const printContents = document.getElementById('print-version').innerHTML;
            const originalContents = document.body.innerHTML;
            
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
        }
    </script>
</body>
</html>
