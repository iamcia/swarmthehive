<?php
include 'dbconn.php';
session_start();

// Check if the user is logged in and has the correct position (Finance)
if (!isset($_SESSION['Management_Code']) || $_SESSION['position'] != 'Admin') {
    header("Location: management-index.php");
    exit();
}

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];

    // Posting an announcement
    if ($action == "post") {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $body = mysqli_real_escape_string($conn, $_POST['message']); // Form field still called 'message'
        $status = 'Approved'; // Default status for new announcements
        
        // Get end_date if provided (add to your form)
        $end_date = !empty($_POST['end_date']) ? mysqli_real_escape_string($conn, $_POST['end_date']) : null;

        // Handle file upload (now called media)
        $media = null;
        if (isset($_FILES['picture']) && $_FILES['picture']['error'] == 0) {
            $targetDir = "announcement_media/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true); // Create directory if it doesn't exist
            }
            $targetFile = $targetDir . basename($_FILES['picture']['name']);
            $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

            // Check if the file is an image
            if (getimagesize($_FILES['picture']['tmp_name'])) {
                if ($_FILES['picture']['size'] <= 5000000) { // Max 5MB
                    if (move_uploaded_file($_FILES['picture']['tmp_name'], $targetFile)) {
                        $media = $targetFile;
                    } else {
                        $_SESSION['message'] = "Sorry, there was an error uploading your file.";
                    }
                } else {
                    $_SESSION['message'] = "Sorry, your file is too large.";
                }
            } else {
                $_SESSION['message'] = "File is not an image.";
            }
        }

        // Updated SQL with new column names
        $sql = "INSERT INTO announcements (title, body, created_at, end_date, status, media) 
                VALUES ('$title', '$body', NOW(), " . ($end_date ? "'$end_date'" : "NULL") . ", '$status', '$media')";
        
        if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "Announcement posted successfully.";
        } else {
            $_SESSION['message'] = "Error posting announcement: " . $conn->error;
        }
    }

    // Handle Update Status
    if ($action == "update_status") {
        $announcement_id = $_POST['announcement_id'];
        $status = $_POST['status'];

        $announcement_id = mysqli_real_escape_string($conn, $announcement_id);
        $status = mysqli_real_escape_string($conn, $status);

        $sql = "UPDATE announcements SET status = '$status' WHERE id = $announcement_id";
        if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "Announcement status updated successfully.";
        } else {
            $_SESSION['message'] = "Error updating status: " . $conn->error;
        }
    }

    // Handle Update Announcement (new code for edit functionality)
    elseif ($action == "update") {
        $announcement_id = mysqli_real_escape_string($conn, $_POST['announcement_id']);
        $title = mysqli_real_escape_string($conn, $_POST['edit_title']);
        $body = mysqli_real_escape_string($conn, $_POST['edit_message']);
        $status = mysqli_real_escape_string($conn, $_POST['edit_status']);
        $end_date = !empty($_POST['edit_end_date']) ? mysqli_real_escape_string($conn, $_POST['edit_end_date']) : null;

        // Update the announcement
        $sql = "UPDATE announcements SET 
                title = '$title', 
                body = '$body', 
                status = '$status'" .
                ($end_date ? ", end_date = '$end_date'" : ", end_date = NULL") .
                " WHERE id = $announcement_id";

        if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "Announcement updated successfully.";
        } else {
            $_SESSION['message'] = "Error updating announcement: " . $conn->error;
        }
    }

    // Handle Delete Announcement
    elseif ($action == "delete") {
        $announcement_id = $_POST['announcement_id'];

        $announcement_id = mysqli_real_escape_string($conn, $announcement_id);

        $sql = "DELETE FROM announcements WHERE id = $announcement_id";
        if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "Announcement deleted successfully.";
        } else {
            $_SESSION['message'] = "Error deleting announcement: " . $conn->error;
        }
    }

    header("Location: adm-manageannounce.php");
    exit();
}

// Set filters for date, time, and status
$dateFilter = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';
$timeFilter = isset($_GET['filter_time']) ? $_GET['filter_time'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT * FROM announcements WHERE 1";
if (!empty($dateFilter)) {
    $sql .= " AND DATE(created_at) = '$dateFilter'";
}
if (!empty($timeFilter)) {
    $sql .= " AND TIME(created_at) = '$timeFilter'";
}
if (!empty($statusFilter)) {
    $sql .= " AND status = '$statusFilter'";
}
$sql .= " ORDER BY created_at DESC";
$result = $conn->query($sql);
$conn->close();
?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swarm | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/admin_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/adm-dashboard-style.css?v=<?php echo time(); ?>">
    <!-- Add Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#4CAF50',
                        'primary-dark': '#3d8b40',
                        'primary-light': '#e8f5e9',
                    },
                },
                fontFamily: {
                    sans: ['Google Sans', 'sans-serif'],
                }
            }
        }
    </script>
    <style>
        /* Keep existing styles for other elements */
        .content {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .content h1 {
            color: #333;
            margin-bottom: 2rem;
            font-size: 2rem;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 0.5rem;
        }

        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
            background-color: #4CAF50;
            color: white;
        }

        .filter-section {
            background: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .filter-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-weight: 500;
            color: #555;
        }

        .table-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem;
            margin-bottom: 2rem;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th {
            background: #f5f5f5;
            color: #333;
            font-weight: 600;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #ddd;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            color: #666;
        }

        .announcement-form {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
        }

        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-primary {
            background: #4CAF50;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-disapproved {
            background: #f8d7da;
            color: #721c24;
        }

        /* Enhanced Filter Styles */
        .filter-wrapper {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 30px;
        }

        .filter-header {
            margin-bottom: 15px;
            color: #333;
            font-size: 1.1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-header i {
            color: #4CAF50;
        }

        .filter-controls {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: border-color 0.3s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            border-color: #4CAF50;
            outline: none;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
        }

        .btn-filter {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-apply {
            background: #4CAF50;
            color: white;
        }

        .btn-apply:hover {
            background: #45a049;
        }

        .btn-reset {
            background: #f0f0f0;
            color: #666;
        }

        .btn-reset:hover {
            background: #e4e4e4;
        }

        /* Remove status-related styles */
        .status-badge,
        .status-pending,
        .status-approved,
        .status-disapproved {
            display: none;
        }

        /* Enhanced Announcement Form Styles */
        .announcement-form {
            background: #fff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .announcement-form h2 {
            color: #2e7d32;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            position: relative;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e8f5e9;
        }
        
        .announcement-form h2::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 80px;
            height: 2px;
            background-color: #4CAF50;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        @media (min-width: 768px) {
            .form-grid {
                grid-template-columns: 2fr 1fr;
            }
        }

        .form-left, .form-right {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #444;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group textarea {
            width: 100%;
            padding: 0.9rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05) inset;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="date"]:focus,
        .form-group textarea:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.15);
            outline: none;
        }
        
        .form-group textarea {
            min-height: 180px;
            resize: vertical;
        }
        
        .char-counter {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #777;
            text-align: right;
        }

        /* File Upload Styles */
        .file-upload-container {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: #f9f9f9;
        }

        .file-upload-container:hover {
            border-color: #4CAF50;
            background-color: #f0f8f0;
        }

        .file-upload-container i {
            font-size: 2.5rem;
            color: #4CAF50;
            margin-bottom: 0.75rem;
            display: block;
        }

        .file-upload-text {
            font-size: 0.95rem;
            color: #555;
            margin-bottom: 0.5rem;
        }

        .file-upload-info {
            font-size: 0.75rem;
            color: #888;
        }

        .file-upload-container input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        #media-preview {
            max-width: 100%;
            margin-top: 1rem;
            border-radius: 8px;
            display: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .submit-container {
            margin-top: 1.5rem;
            display: flex;
            justify-content: flex-end;
        }

        .btn-primary {
            background: #4CAF50;
            color: white;
            padding: 0.9rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background: #3d8b40;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
    </style>
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
                    <li class="active">
                        <a href="#">
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
                    <li>
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

      <!-- --------------
        end sidebar
      -------------------- -->

<!-- -------------- 
      start main part 
 --------------- -->

<main>
  
<div class="content">
        <h1>Manage Announcements</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message"><?= $_SESSION['message'] ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <!-- Enhanced Filter Section -->
        <div class="filter-wrapper">
            <div class="filter-header">
                <i class='bx bx-filter'></i>
                <span>Filter Announcements</span>
            </div>
            <form method="GET" class="filter-controls">
                <div class="filter-group">
                    <label for="filter_date">Filter by Date</label>
                    <input type="date" id="filter_date" name="filter_date" 
                           value="<?= htmlspecialchars($dateFilter ?? ''); ?>">
                </div>
                <div class="filter-group">
                    <label for="filter_time">Filter by Time</label>
                    <input type="time" id="filter_time" name="filter_time"
                           value="<?= htmlspecialchars($timeFilter ?? ''); ?>">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-filter btn-apply">Apply Filters</button>
                    <button type="reset" class="btn-filter btn-reset" 
                            onclick="window.location.href='adm-manageannounce.php'">Reset</button>
                </div>
            </form>
        </div>

        <!-- Updated Table Structure with Status column and Edit button -->
        <div class="table-container">
            <?php if ($result && $result->num_rows > 0): ?>
                <table>
                    <tr>
                        <th>Title</th>
                        <th>Message</th>
                        <th>Posted On</th>
                        <th>End Date</th>
                        <th>Media</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['title']); ?></td>
                        <td><?= htmlspecialchars(substr($row['body'], 0, 100)) . (strlen($row['body']) > 100 ? '...' : ''); ?></td>
                        <td><?= (new DateTime($row['created_at']))
                                ->setTimezone(new DateTimeZone('Asia/Manila'))
                                ->format('Y-m-d h:i A'); ?></td>
                        <td><?= !empty($row['end_date']) ? 
                                (new DateTime($row['end_date']))
                                ->setTimezone(new DateTimeZone('Asia/Manila'))
                                ->format('Y-m-d') : 'No end date'; ?></td>
                        <td>
                            <?php if (!empty($row['media'])): 
                                $fileExt = strtolower(pathinfo($row['media'], PATHINFO_EXTENSION));
                                if ($fileExt == 'pdf'): ?>
                                    <span class="flex items-center">
                                        <i class="bx bxs-file-pdf text-red-600 text-xl mr-2"></i>
                                        <span class="text-xs">PDF</span>
                                    </span>
                                <?php else: ?>
                                    <img src="<?= htmlspecialchars($row['media']); ?>" 
                                        alt="Announcement Media" 
                                        style="width: 50px; height: 50px; object-fit: cover;">
                                <?php endif; ?>
                            <?php else: ?>
                                <p>No Media</p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="<?= $row['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?> px-2 py-1 rounded-full text-xs font-medium">
                                <?= ucfirst(htmlspecialchars($row['status'])); ?>
                            </span>
                        </td>
                        <td class="flex gap-2">
                            <!-- Edit Button -->
                            <button onclick="openEditModal(<?= $row['id']; ?>, '<?= addslashes($row['title']); ?>', '<?= addslashes($row['body']); ?>', '<?= addslashes($row['status']); ?>', '<?= !empty($row['end_date']) ? $row['end_date'] : ''; ?>')" 
                                    class="btn bg-blue-500 hover:bg-blue-600 text-white py-1 px-3 rounded text-sm">
                                Edit
                            </button>
                            
                            <!-- Delete Button -->
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="announcement_id" value="<?= $row['id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-danger py-1 px-3 rounded text-sm" onclick="return confirm('Are you sure you want to delete this announcement?')">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <p>No announcements found.</p>
            <?php endif; ?>
        </div>

        <!-- Replace the announcement form section with this Tailwind CSS version -->
        <section class="bg-white rounded-xl shadow-lg p-6 md:p-8">
            <h2 class="text-primary text-2xl font-medium mb-6 pb-3 border-b border-gray-100 relative">
                Create New Announcement
                <span class="absolute bottom-0 left-0 w-20 h-0.5 bg-primary"></span>
            </h2>
            
            <form method="POST" action="insert-adm-manageannouce.php" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2 space-y-6">
                        <!-- Title Input -->
                        <div>
                            <label for="title" class="block text-gray-700 font-medium mb-2">Announcement Title</label>
                            <input type="text" id="title" name="title" required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primary focus:ring focus:ring-primary/20 focus:outline-none transition"
                                placeholder="Enter a descriptive title">
                        </div>
                        
                        <!-- Content Textarea -->
                        <div>
                            <label for="message" class="block text-gray-700 font-medium mb-2">Announcement Content</label>
                            <textarea id="message" name="message" rows="6" required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primary focus:ring focus:ring-primary/20 focus:outline-none transition resize-y"
                                placeholder="Write the announcement content here..." maxlength="2000"></textarea>
                            <div class="text-right text-sm text-gray-500 mt-2">
                                <span id="char-count">0</span>/2000 characters
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-6">
                        <!-- End Date -->
                        <div>
                            <label for="end_date" class="block text-gray-700 font-medium mb-2">End Date (Optional)</label>
                            <input type="date" id="end_date" name="end_date"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primary focus:ring focus:ring-primary/20 focus:outline-none transition">
                        </div>
                        
                        <!-- Media Upload -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Media Attachment</label>
                            <div id="upload-container" class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-primary hover:bg-green-50/30 transition-all bg-gray-50 relative">
                                <i class='bx bx-image-add text-4xl text-primary mb-3'></i>
                                <div class="text-gray-600 mb-1 upload-text">Click to upload an image or PDF</div>
                                <div class="text-gray-400 text-xs">Max size: 5MB (JPG, PNG, GIF, PDF)</div>
                                <input type="file" id="picture" name="picture" accept="image/*,.pdf" 
                                    class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewMedia(this)">
                            </div>
                            <!-- Preview container that can show either an image or PDF icon -->
                            <div id="preview-container" class="hidden mt-4">
                                <!-- Image preview -->
                                <img id="media-preview" class="rounded-lg max-h-48 mx-auto shadow-md hidden" src="#" alt="Preview">
                                
                                <!-- PDF preview -->
                                <div id="pdf-preview" class="hidden rounded-lg p-4 bg-gray-50 border border-gray-200 shadow-md text-center">
                                    <i class='bx bxs-file-pdf text-5xl text-red-500 mb-2'></i>
                                    <div class="text-gray-700 font-medium pdf-filename">document.pdf</div>
                                    <div class="text-gray-500 text-xs mt-1">PDF Document</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="flex justify-end mt-8">
                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-primary hover:bg-primary-dark text-white font-medium rounded-lg transition transform hover:-translate-y-0.5 hover:shadow-lg active:translate-y-0">
                        <i class='bx bx-send mr-2'></i>
                        Post Announcement
                    </button>
                </div>
            </form>
        </section>
    </div>
    </main>
</body>
</html>

<!-- Add this JavaScript at the end of the file, before the closing body tag -->
<script>
    // Character counter for textarea
    document.getElementById('message').addEventListener('input', function() {
        const charCount = this.value.length;
        document.getElementById('char-count').textContent = charCount;
        
        // Change color when approaching limit
        const counter = document.getElementById('char-count');
        if (charCount > 1800) {
            counter.classList.add('text-orange-600');
            counter.classList.remove('text-amber-500', 'text-gray-500');
        } else if (charCount > 1500) {
            counter.classList.add('text-amber-500');
            counter.classList.remove('text-orange-600', 'text-gray-500');
        } else {
            counter.classList.add('text-gray-500');
            counter.classList.remove('text-orange-600', 'text-amber-500');
        }
    });
    
    // Updated media preview functionality to handle PDFs
    function previewMedia(input) {
        const previewContainer = document.getElementById('preview-container');
        const imagePreview = document.getElementById('media-preview');
        const pdfPreview = document.getElementById('pdf-preview');
        const pdfFilename = pdfPreview.querySelector('.pdf-filename');
        const uploadContainer = document.getElementById('upload-container');
        const uploadText = uploadContainer.querySelector('.upload-text');
        
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const fileType = file.type;
            
            // Show the preview container regardless of file type
            previewContainer.classList.remove('hidden');
            
            // Update the upload container
            uploadContainer.classList.add('border-primary', 'border-solid');
            uploadContainer.classList.remove('border-dashed', 'border-gray-300');
            uploadText.textContent = 'Change file';
            
            // Handle different file types
            if (fileType.startsWith('image/')) {
                // It's an image
                pdfPreview.classList.add('hidden');
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            } 
            else if (fileType === 'application/pdf') {
                // It's a PDF
                imagePreview.classList.add('hidden');
                
                // Set the filename in the preview
                pdfFilename.textContent = file.name;
                pdfPreview.classList.remove('hidden');
            }
        }
    }
    
    // Edit Modal Functions
    function openEditModal(id, title, body, status, endDate) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_title').value = title;
        document.getElementById('edit_message').value = body;
        document.getElementById('edit_status').value = status;
        document.getElementById('edit_end_date').value = endDate;
        
        document.getElementById('editModal').classList.remove('hidden');
        document.getElementById('editModal').classList.add('flex');
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }
    
    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editModal').classList.remove('flex');
        document.body.style.overflow = 'auto'; // Enable scrolling
    }
    
    // Close modal if user clicks outside of it
    document.getElementById('editModal').addEventListener('click', function(event) {
        if (event.target === this) {
            closeEditModal();
        }
    });
</script>

<!-- Add the Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold text-gray-800">Edit Announcement</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="bx bx-x text-2xl"></i>
                </button>
            </div>
            
            <form id="editForm" method="POST" class="space-y-6">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="announcement_id" id="edit_id">
                
                <!-- Title Input -->
                <div>
                    <label for="edit_title" class="block text-gray-700 font-medium mb-2">Title</label>
                    <input type="text" id="edit_title" name="edit_title" required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primary focus:ring focus:ring-primary/20 focus:outline-none transition">
                </div>
                
                <!-- Content Textarea -->
                <div>
                    <label for="edit_message" class="block text-gray-700 font-medium mb-2">Message</label>
                    <textarea id="edit_message" name="edit_message" rows="6" required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primary focus:ring focus:ring-primary/20 focus:outline-none transition"></textarea>
                </div>
                
                <!-- Status Dropdown -->
                <div>
                    <label for="edit_status" class="block text-gray-700 font-medium mb-2">Status</label>
                    <select id="edit_status" name="edit_status" required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primary focus:ring focus:ring-primary/20 focus:outline-none transition">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <!-- End Date -->
                <div>
                    <label for="edit_end_date" class="block text-gray-700 font-medium mb-2">End Date (Optional)</label>
                    <input type="date" id="edit_end_date" name="edit_end_date"
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primary focus:ring focus:ring-primary/20 focus:outline-none transition">
                </div>
                
                <!-- Submit Button -->
                <div class="flex justify-end pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeEditModal()" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg mr-4 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2.5 bg-primary text-white rounded-lg hover:bg-primary-dark">
                        Update Announcement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>