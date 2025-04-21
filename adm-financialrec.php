<?php
include 'dbconn.php';
session_start();

// Check if the user is logged in and has the correct position (Admin)
if (!isset($_SESSION['Management_Code']) || $_SESSION['position'] != 'Admin') {
    header("Location: management-index.php");
    exit();
}

// Fetch finance records from the database
$sql = "SELECT f.*, 
               o.Last_Name as owner_last_name, 
               o.First_Name as owner_first_name, 
               o.Unit_Number as owner_unit
        FROM soafinance_records f
        LEFT JOIN ownerinformation o ON f.Owner_ID = o.Owner_ID
        ORDER BY f.Billing_Date DESC";
$result = $conn->query($sql);
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./css/admin_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/adm-financialrec-style.css?v=<?php echo time(); ?>">
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
                    <li class="active">
                        <a href="#">
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

        <!-- Main content -->
        <main class="w-full bg-gray-100">
            <div class="app-body-main-content p-6">
                <section class="mb-8">
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <h2 class="text-3xl font-bold text-gray-800">Finance Records</h2>
                        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                            <div class="relative flex-grow">
                                <input type="text" id="searchInput" placeholder="Search by Name or Unit" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 pl-10">
                                <i class="bx bx-search absolute left-3 top-3 text-gray-400"></i>
                            </div>
                            <select id="statusFilter" class="px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Filter by Status</option>
                                <option value="paid">Paid</option>
                                <option value="unpaid">Unpaid</option>
                            </select>
                            <input type="date" id="dateFilter" class="px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <!-- Finance Records Table -->
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Unit</th>
                                    <th>Status</th>
                                    <th>Billing Date</th>
                                    <th>Proof of Payment</th>
                                    <th>SOA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>
                                                <td>{$row['owner_first_name']} {$row['owner_last_name']}</td>
                                                <td>{$row['owner_unit']}</td>
                                                <td>{$row['Payment_Status']}</td>
                                                <td>{$row['Billing_Date']}</td>
                                                <td><a href='{$row['Uploaded_At']}'>View</a></td>
                                                <td><a href='soa/{$row['Billing_Number']}.pdf'>Download PDF</a></td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6'>No records found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        // Add your filter functionality here if needed (filter by status, date, search)
    </script>
</body>
</html>