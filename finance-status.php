<?php
include 'dbconn.php';
session_start();

// Check if the user is logged in and has the correct position (Finance)
if (!isset($_SESSION['Management_Code']) || $_SESSION['position'] != 'Finance') {
    header("Location: management-index.php");
    exit();
}

// Handle Status Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['billing_number']) && isset($_POST['new_status'])) {
    $billing_number = $_POST['billing_number'];
    $new_status = $_POST['new_status'];

    // Update the status in the database
    $stmt = $conn->prepare("UPDATE soafinance SET Status = ? WHERE Billing_Number = ?");
    $stmt->bind_param("ss", $new_status, $billing_number);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Status updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update status.";
    }
    $stmt->close();
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch data from soafinance, soafinance_records, and soafinance_pdfs
$sql = "SELECT s.Billing_Number, s.Unit_No, s.Owner_ID, s.Tenant_ID, s.Status, s.Billing_Date, s.Due_Date, 
               r.Payment_Status, s.Uploaded_At, p.PDF_File
        FROM soafinance s
        LEFT JOIN soafinance_records r ON s.Billing_Number = r.Billing_Number
        LEFT JOIN soafinance_pdfs p ON s.Billing_Number = p.Billing_Number";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swarm | Finance Status</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/finance_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/finance-upload-style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo-container">
                <img src="./img/logo swarm.png" alt="Logo" class="logo">
                <span class="logo-text">SWARM</span>
            </div>

            <div class="menu-title">
                <i class='bx bx-money'></i>
                <span>Finance</span>
            </div>

            <nav class="sidebar-menu">
                <ul>
                    <li>
                        <a href="finance-dashboard.php">
                            <i class='bx bxs-dashboard'></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="finance-upload.php">
                            <i class='bx bx-upload'></i>
                            <span>Upload SOA</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="#">
                            <i class='bx bx-check-circle'></i>
                            <span>Status</span>
                        </a>
                    </li>
                    <li>
                        <a href="finance-history.php">
                            <i class='bx bx-history'></i>
                            <span>Logs</span>
                        </a>
                    </li>
                    <li>
                        <a href="finance-settings.php">
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

        <!-- Main Content Area -->
        <main>
            <header class="main-header">
                <div class="header-content">
                    <h1>Finance Status</h1>
                    <p class="header-subtitle">View the current billing status</p>
                </div>
            </header>

            <!-- Table Display in Modal Layout -->
            <div class="status-cards">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="status-card" onclick="openModal('<?php echo $row['Billing_Number']; ?>')">
                            <h3>Billing Number: <?php echo $row['Billing_Number']; ?></h3>
                            <p><strong>Unit:</strong> <?php echo $row['Unit_No']; ?></p>
                            <p><strong>Owner:</strong> <?php echo $row['Owner_ID']; ?></p>
                            <p><strong>Tenant:</strong> <?php echo $row['Tenant_ID']; ?></p>
                        </div>

                        <!-- Modal for Billing Details -->
                        <div id="modal-<?php echo $row['Billing_Number']; ?>" class="modal">
                            <div class="modal-content">
                                <span class="close-btn" onclick="closeModal('<?php echo $row['Billing_Number']; ?>')">&times;</span>
                                <h2>Billing Details</h2>
                                <p><strong>Billing Number:</strong> <?php echo $row['Billing_Number']; ?></p>
                                <p><strong>Unit:</strong> <?php echo $row['Unit_No']; ?></p>
                                <p><strong>Owner:</strong> <?php echo $row['Owner_ID']; ?></p>
                                <p><strong>Tenant:</strong> <?php echo $row['Tenant_ID']; ?></p>
                                <p><strong>Billing Date:</strong> <?php echo $row['Billing_Date']; ?></p>
                                <p><strong>Due Date:</strong> <?php echo $row['Due_Date']; ?></p>
                                <p><strong>Uploaded At:</strong> <?php echo $row['Uploaded_At']; ?></p>
                                <p><strong>Status:</strong>
                                    <form method="POST" action="">
                                        <select name="new_status" id="status-<?php echo $row['Billing_Number']; ?>">
                                            <option value="Approval" <?php echo ($row['Status'] == 'Approval') ? 'selected' : ''; ?>>Approval</option>
                                            <option value="Completed" <?php echo ($row['Status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                        <input type="hidden" name="billing_number" value="<?php echo $row['Billing_Number']; ?>" />
                                        <button type="submit" class="update-btn">Update Status</button>
                                    </form>
                                </p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No billing data available.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Styles for the Cards and Modal -->
    <style>
        .status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .status-card {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            cursor: pointer;
        }

        /* Modal Styles */
        .modal {
            display: none; 
            position: fixed;
            z-index: 1;
            left: 10%;
            top: 1%;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4); /* Transparent background */
            padding-top: 100px; /* Add some padding to the top for better centering */
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 70%; /* Set the width of the modal to a comfortable size */
            max-width: 800px; /* Ensure it doesn't get too wide */
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            overflow-y: auto; /* Allow vertical scroll if content overflows */
            max-height: 80%; /* Limit height to prevent modal from becoming too large */
        }

        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close-btn:hover,
        .close-btn:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .status-card {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .status-card:hover {
            transform: scale(1.05); /* Add a hover effect */
        }

        .status-card h3 {
            margin-top: 0;
        }

        .status-card p {
            margin: 5px 0;
            font-size: 14px;
        }

        .update-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
        }

        .update-btn:hover {
            background-color: #45a049;
        }
        
        
    </style>

    <!-- JavaScript for Modal -->
    <script>
        function openModal(billingNumber) {
            document.getElementById("modal-" + billingNumber).style.display = "block";
        }

        function closeModal(billingNumber) {
            document.getElementById("modal-" + billingNumber).style.display = "none";
        }

        // Close the modal if the user clicks outside of it
        window.onclick = function(event) {
            var modals = document.getElementsByClassName("modal");
            for (var i = 0; i < modals.length; i++) {
                if (event.target == modals[i]) {
                    modals[i].style.display = "none";
                }
            }
        }
    </script>

</body>
</html>

<?php
$conn->close();
?>
