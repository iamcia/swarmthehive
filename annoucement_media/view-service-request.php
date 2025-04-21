<?php
include 'dbconn.php';

// Check if ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: adm-servicerequest.php");
    exit;
}

$id = $_GET['id'];

// Get service request details
$query = "SELECT sr.id, sr.Ticket_No, gp.Resident_Code, gp.User_Type, 
          gp.Date, gp.Time, gp.Bearer, gp.Authorization, gp.Items, 
          gp.Status, gp.Created_At 
          FROM servicerequests sr
          JOIN gatepass gp ON sr.Ticket_No = gp.Ticket_No
          WHERE sr.id = '$id'";
$result = mysqli_query($conn, $query);

if(mysqli_num_rows($result) == 0) {
    header("Location: adm-servicerequest.php");
    exit;
}

$request = mysqli_fetch_assoc($result);

// Parse items JSON data
$items = [];
$hasValidItems = false;

if(!empty($request['Items'])) {
    try {
        $items = json_decode($request['Items'], true);
        $hasValidItems = (json_last_error() === JSON_ERROR_NONE) && is_array($items) && count($items) > 0;
    } catch (Exception $e) {
        // JSON parsing failed
        $hasValidItems = false;
    }
}

// Process status update if form submitted
if(isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $ticket_no = $request['Ticket_No'];
    
    $update_query = "UPDATE gatepass SET Status = '$new_status' WHERE Ticket_No = '$ticket_no'";
    
    if(mysqli_query($conn, $update_query)) {
        // Update successful
        $request['Status'] = $new_status;
        
        // Log the action (audit trail)
        $admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 'Unknown';
        $log_query = "INSERT INTO audit_logs (admin_id, action, details, timestamp) 
                     VALUES ('$admin_id', 'Update Request Status', 'Updated Ticket #$ticket_no status to $new_status', NOW())";
        mysqli_query($conn, $log_query);
        
        // Reload the page to show updated status
        header("Location: view-service-request.php?id=$id");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Service Request | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/admin_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/adm-servicerequest-style.css?v=<?php echo time(); ?>">
    <style>
        .request-details {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .request-details h3 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .detail-row {
            display: flex;
            margin-bottom: 15px;
        }
        .detail-label {
            font-weight: bold;
            width: 30%;
            color: #555;
        }
        .detail-value {
            width: 70%;
        }
        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 15px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-approve {
            background-color: #4CAF50;
            color: white;
        }
        .btn-reject {
            background-color: #f44336;
            color: white;
        }
        .btn-back {
            background-color: #607d8b;
            color: white;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: bold;
            color: white;
        }
        .status-pending {
            background-color: #ff9800;
        }
        .status-approval {
            background-color: #2196F3;
        }
        .status-completed {
            background-color: #4CAF50;
        }
        .status-rejected {
            background-color: #f44336;
        }
        
        /* New styles for items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .items-table th {
            background-color: #f5f5f5;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            border-bottom: 1px solid #ddd;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .item-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .item-image:hover {
            transform: scale(1.05);
        }
        .image-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }
        .image-modal-content {
            margin: auto;
            display: block;
            max-width: 80%;
            max-height: 80%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .image-modal-close {
            position: absolute;
            top: 20px;
            right: 25px;
            color: #f1f1f1;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
        }
        .no-items {
            text-align: center;
            padding: 20px;
            color: #777;
            font-style: italic;
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
                    <li>
                        <a href="adm-manageannounce.php">
                            <i class='bx bx-notification'></i>
                            <span>Announcement</span>
                        </a>
                    </li>
                    <li class="active">
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
            <header class="app-header">
                <div class="app-header-navigation">
                    <div class="tabs">
                        <a href="adm-servicerequest.php" class="active">
                            Back to Service Requests
                        </a>
                    </div>
                </div>
            </header>
            
            <div class="app-body-main-content">
                <section class="service-section">
                    <h2>Service Request Details</h2>
                    <div class="request-details">
                        <h3>
                            Ticket #<?php echo $request['Ticket_No']; ?> 
                            <span class="status-badge status-<?php echo strtolower($request['Status']); ?>">
                                <?php echo ucfirst($request['Status']); ?>
                            </span>
                        </h3>
                        
                        <div class="detail-row">
                            <div class="detail-label">Request Type:</div>
                            <div class="detail-value"><?php echo $request['Authorization']; ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Resident Code:</div>
                            <div class="detail-value"><?php echo $request['Resident_Code']; ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">User Type:</div>
                            <div class="detail-value"><?php echo $request['User_Type']; ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Bearer:</div>
                            <div class="detail-value"><?php echo $request['Bearer']; ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Date:</div>
                            <div class="detail-value"><?php echo $request['Date']; ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Time:</div>
                            <div class="detail-value"><?php echo $request['Time']; ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Items:</div>
                            <div class="detail-value">
                                <?php if ($hasValidItems): ?>
                                <table class="items-table">
                                    <thead>
                                        <tr>
                                            <th>Item #</th>
                                            <th>Quantity</th>
                                            <th>Unit</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['item_no']); ?></td>
                                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                                        </tr>
                                        <?php if(!empty($item['item_pics']) && is_array($item['item_pics'])): ?>
                                        <tr>
                                            <td colspan="4">
                                                <div class="item-images">
                                                    <?php foreach($item['item_pics'] as $pic): ?>
                                                    <img src="GateItem/<?php echo htmlspecialchars($pic); ?>" 
                                                         alt="Item Image" 
                                                         class="item-image" 
                                                         onclick="showImage('uploads/items/<?php echo htmlspecialchars($pic); ?>')">
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php else: ?>
                                <div class="no-items">No items data available or format is invalid.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Created At:</div>
                            <div class="detail-value"><?php echo $request['Created_At']; ?></div>
                        </div>
                        
                        <form method="POST" action="">
                            <div class="action-buttons">
                                <a href="adm-servicerequest.php" class="btn btn-back">Back to List</a>
                                <?php if($request['Status'] == 'pending'): ?>
                                <button type="submit" name="update_status" value="approval" class="btn btn-approve" onclick="document.getElementById('status-input').value='approval'">Approve Request</button>
                                <button type="submit" name="update_status" value="rejected" class="btn btn-reject" onclick="document.getElementById('status-input').value='rejected'">Reject Request</button>
                                <input type="hidden" name="status" id="status-input" value="">
                                <?php endif; ?>
                                <?php if($request['Status'] == 'approval'): ?>
                                <button type="submit" name="update_status" value="completed" class="btn btn-approve" onclick="document.getElementById('status-input').value='completed'">Mark as Completed</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </main>

        <!-- Image Modal -->
        <div id="imageModal" class="image-modal">
            <span class="image-modal-close" onclick="closeModal()">&times;</span>
            <img class="image-modal-content" id="modalImage">
        </div>

        <script>
            // Image modal functionality
            function showImage(src) {
                const modal = document.getElementById('imageModal');
                const modalImg = document.getElementById("modalImage");
                modal.style.display = "block";
                modalImg.src = src;
            }
            
            function closeModal() {
                document.getElementById('imageModal').style.display = "none";
            }
            
            // Close modal when clicking outside the image
            window.onclick = function(event) {
                const modal = document.getElementById('imageModal');
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
            
            document.querySelectorAll('[name="update_status"]').forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('status-input').value = this.value;
                });
            });
            
            const sideMenu = document.querySelector('aside');
            const menuBtn = document.querySelector('#menu_bar');
            const closeBtn = document.querySelector('#close_btn');

            menuBtn.addEventListener('click',()=>{
                sideMenu.style.display = "block"
            });
            
            closeBtn.addEventListener('click',()=>{
                sideMenu.style.display = "none"
            });
        </script>
    </body>
</html>
