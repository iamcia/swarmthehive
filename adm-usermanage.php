<?php
include 'dbconn.php';
session_start();

// Check if the user is logged in and has the correct position (Admin)
if (!isset($_SESSION['Management_Code']) || $_SESSION['position'] != 'Admin') {
    header("Location: management-index.php");
    exit();
}

// Function to check if email exists
function checkEmailExists($email, $conn) {
    // Check both owner and tenant tables for the email
    $sql = "SELECT 'Owner' as type FROM ownerinformation WHERE Email = ? 
            UNION ALL 
            SELECT 'Tenant' as type FROM tenantinformation WHERE Email = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return ['exists' => true, 'type' => $row['type']];
    }
    
    return ['exists' => false, 'type' => null];
}

// Include PHPMailer
require 'PHPMAILER/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function generateAccessCode() {
    return rand(1000, 9999);
}

function sendAccessCodeEmail($email, $accessCode, $userType) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'TheHiveResidences@swarmthehive.online';
        $mail->Password = 'G#pdFHa7i';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom('TheHiveResidences@swarmthehive.online', 'SWARM - The Hive Residences');

        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to The Hive Residences - Your Access Code';

        // Create HTML email template
        $welcomeUrl = "https://swarmthehive.online/WelcomeReg.php";
        
        $emailBody = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <img src="https://swarmthehive.online/img/logo%20swarm.png" alt="SWARM Logo" style="max-width: 150px;">
                <h1 style="color: #F6BE00;">Welcome to The Hive Residences</h1>
            </div>
            
            <div style="margin-bottom: 25px;">
                <p>Dear ' . ($userType == 'Owner' ? 'Property Owner' : 'Resident') . ',</p>
                <p>Welcome to The Hive Residences community! We\'re excited to have you join us.</p>
                <p>To complete your registration, please use the access code below:</p>
                
                <div style="text-align: center; margin: 25px 0;">
                    <div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; font-size: 24px; font-weight: bold; letter-spacing: 5px;">
                        ' . $accessCode . '
                    </div>
                </div>
                
                <p>Please click the button below to complete your registration:</p>
                
                <div style="text-align: center; margin: 25px 0;">
                    <a href="' . $welcomeUrl . '" style="background-color: #F6BE00; color: #000; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">Complete Registration</a>
                </div>
                
                <p>If the button above doesn\'t work, you can copy and paste the following link into your browser:</p>
                <p><a href="' . $welcomeUrl . '">' . $welcomeUrl . '</a></p>
            </div>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #777; font-size: 12px;">
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>&copy; ' . date('Y') . ' The Hive Residences. All rights reserved.</p>
            </div>
        </div>
        ';

        $mail->Body = $emailBody;
        $mail->AltBody = "Welcome to The Hive Residences! Your access code is: $accessCode. Please visit $welcomeUrl to complete your registration.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

$message = '';
$email = isset($_POST['email']) ? $_POST['email'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['saveOwner']) || isset($_POST['saveTenant'])) {
        $accessCode = generateAccessCode();
        $email = htmlspecialchars($_POST['email']);
        $userType = isset($_POST['saveOwner']) ? 'Owner' : 'Tenant';
        
        // Check if email already exists
        $emailCheck = checkEmailExists($email, $conn);
        if ($emailCheck['exists']) {
            $message = "This email is already registered as a " . $emailCheck['type'] . ". Please use a different email.";
        } else {
            // SQL query and bind parameters based on user type
            if ($userType == 'Owner') {
                $sql = "INSERT INTO ownerinformation (Access_Code, Email) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $accessCode, $email);  // Binding two parameters: Access_Code and Email
            } else {
                $sql = "INSERT INTO tenantinformation (Email) VALUES (?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $email);  // Binding only one parameter: Email
            }
            
            // Execute and close the statement
            $stmt->execute(); // This should work as Owner_ID is not inserted now
            $stmt->close();
            
            // Send the email with the access code
            if (sendAccessCodeEmail($email, $accessCode, $userType)) {
                $message = "Access code generated and email sent successfully to $email as $userType.";
            } else {
                $message = "User saved, but failed to send email. Please try again.";
            }
        }
    }
}

// Handle actions
// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete'])) {
        $id = $_POST['delete'];
        $type = $_POST['type'];
        
        $table = ($type == 'Owner') ? 'ownerinformation' : 'tenantinformation';
        $id_field = ($type == 'Owner') ? 'Owner_ID' : 'Tenant_ID';
        
        // First check if the record exists
        $check_sql = "SELECT * FROM $table WHERE $id_field = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Record exists, proceed with deletion
            $sql = "DELETE FROM $table WHERE $id_field = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $id);
            $execution_result = $stmt->execute();
            
            if ($execution_result) {
                $message = "User successfully deleted.";
            } else {
                $message = "Error deleting user: " . $conn->error;
            }
            $stmt->close();
        } else {
            $message = "Record not found. It may have been already deleted.";
        }
        $check_stmt->close();
    }
}

// Fetch unit information for the owner
$sql = "SELECT * FROM unitinformation WHERE Owner_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$units = [];
while ($row = $result->fetch_assoc()) {
    $units[] = $row;
}

// Build the query for combined results
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'Pending'; // Default to Pending status

// Create an empty array to store the parameters
$params = [];
$paramTypes = "";

// Get the status filter value, defaulting to 'All' if not provided
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'All';

// Modify the query to show records that match the selected status
$sql = "SELECT 'Owner' as UserType, Tower, Unit_Number, Owner_ID as ID, 
        First_Name, Last_Name, Status, Email 
        FROM ownerinformation 
        WHERE First_Name IS NOT NULL 
        AND Last_Name IS NOT NULL 
        AND Unit_Number IS NOT NULL 
        AND Tower IS NOT NULL 
        AND Email IS NOT NULL";

// Apply search condition if provided
if (!empty($search)) {
    $sql .= " AND (Last_Name LIKE ? OR First_Name LIKE ? OR Unit_Number LIKE ? OR Email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $paramTypes .= "ssss";
}

// If a specific status is selected (not 'All')
if ($status_filter !== 'All') {
    $sql .= " AND Status = ?";
    $params[] = $status_filter;
    $paramTypes .= "s";
}

// Union with tenant query, same condition for tenant records
$sql .= " UNION ALL SELECT 'Tenant' as UserType, Tower, Unit_Number, 
        Tenant_ID as ID, First_Name, Last_Name, Status, Email 
        FROM tenantinformation 
        WHERE First_Name IS NOT NULL 
        AND Last_Name IS NOT NULL 
        AND Unit_Number IS NOT NULL 
        AND Tower IS NOT NULL 
        AND Email IS NOT NULL";

// Apply search condition for tenants as well
if (!empty($search)) {
    $sql .= " AND (Last_Name LIKE ? OR First_Name LIKE ? OR Unit_Number LIKE ? OR Email LIKE ?)";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $paramTypes .= "ssss";
}

// If a specific status is selected (not 'All') for tenants
if ($status_filter !== 'All') {
    $sql .= " AND Status = ?";
    $params[] = $status_filter;
    $paramTypes .= "s";
}

// Order by user type and last name
$sql .= " ORDER BY UserType, Last_Name";

// Prepare the statement
$stmt = $conn->prepare($sql);

// Check if there are any parameters to bind
if (!empty($params)) {
    // Ensure the correct number of parameters is bound
    $stmt->bind_param($paramTypes, ...$params);
} else {
    // If no parameters, execute the query without binding
    $stmt->execute();
}

$stmt->execute();
$result = $stmt->get_result();
$total_records = $result->num_rows;

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
    <!-- Add Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./css/admin_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/adm-dashboard-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/user-management.css?v=<?php echo time(); ?>">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#F6BE00',
                        'primary-dark': '#E5AE00',
                        secondary: '#333333',
                    },
                    animation: {
                        'spin-slow': 'spin 2s linear infinite',
                        'fade-in': 'fadeIn 0.5s ease-out forwards',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: 0, transform: 'translateY(10px)' },
                            '100%': { opacity: 1, transform: 'translateY(0)' }
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 font-sans">
    <div class="container">
        <!-- Keep the sidebar unchanged -->
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
                    <li class="active">
                        <a href="#">
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

        <!-- Redesigned Main Content Area using Tailwind -->
        <main class="p-6 md:p-8 transition-all">
            <div class="flex flex-wrap items-center justify-between mb-6">
                <h2 class="text-2xl font-bold mb-0 pb-2 border-b-2 border-primary inline-block">User Management</h2>
                
            </div>

            <!-- User Registration Card -->
            <div class="bg-white rounded-lg shadow-md mb-6 overflow-hidden">
                <div class="bg-primary px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-800">Register New User</h3>
                </div>
                <div class="p-6">
                    <form id="userForm" method="POST" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                <input type="email" id="email" name="email" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-primary focus:border-primary" 
                                       placeholder="Enter user's email" oninput="validateEmail(this.value)" required>
                                <p class="text-xs text-gray-500">This email will receive the access code</p>
                            </div>
                            <div class="flex items-end">
                                <div class="flex space-x-3 w-full">
                                    <button type="button" id="saveOwnerBtn" 
                                            class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium bg-primary text-gray-800 rounded-md shadow-sm hover:bg-primary-dark disabled:opacity-60 disabled:cursor-not-allowed transition-colors" 
                                            onclick="showConfirmation('Owner')" disabled>
                                        <i class='bx bx-building-house mr-1.5'></i>
                                        Register as Owner
                                    </button>
                                    <button type="button" id="saveTenantBtn" 
                                            class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium bg-gray-200 text-gray-700 rounded-md shadow-sm hover:bg-gray-300 disabled:opacity-60 disabled:cursor-not-allowed transition-colors" 
                                            onclick="showConfirmation('Tenant')" disabled>
                                        <i class='bx bx-home mr-1.5'></i>
                                        Register as Tenant
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Search and Filter Card -->
            <div class="bg-white rounded-lg shadow-md p-5 mb-6">
                <form method="GET" action="" class="flex flex-wrap gap-3 items-center">
    <div class="flex-1 min-w-[200px]">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class='bx bx-search text-gray-400'></i>
            </div>
            <input type="text" name="search" 
                   class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-primary focus:border-primary" 
                   placeholder="Search by Name or Unit Number" 
                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        </div>
    </div>
    <div class="min-w-[150px]">
        <select name="status" 
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-primary focus:border-primary">
            <option value="All" <?php echo (!isset($_GET['status']) || $_GET['status'] == 'All') ? 'selected' : ''; ?>>All Statuses</option>
            <option value="Pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
            <option value="Approved" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
            <option value="Disapproved" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Disapproved') ? 'selected' : ''; ?>>Disapproved</option>
        </select>
    </div>
    <button type="submit" 
            class="flex items-center px-4 py-2 bg-primary text-gray-800 rounded-md shadow-sm hover:bg-primary-dark transition-colors">
        <i class='bx bx-filter-alt mr-1.5'></i>
        Apply Filters
    </button>
</form>


            <!-- Table Card -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-2 sm:space-y-0">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class='bx bx-list-ul mr-2'></i>
                        User List
                    </h3>
                    <div class="text-sm text-gray-500 flex items-center">
                        <i class='bx bx-info-circle mr-1'></i>
                        Showing <?php echo $result->num_rows; ?> users with current filters
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">User Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tower</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Unit</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-yellow-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $row['UserType'] === 'Owner' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                        <?php echo $row['UserType'] ? htmlspecialchars($row['UserType']) : ''; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <?php echo $row['Tower'] ? htmlspecialchars($row['Tower']) : '-'; ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <?php echo $row['Unit_Number'] ? htmlspecialchars($row['Unit_Number']) : '-'; ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-xs font-mono bg-gray-100 px-2 py-1 rounded">
                                        <?php echo $row['ID'] ? htmlspecialchars($row['ID']) : ''; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-medium text-gray-900">
                                        <?php echo $row['First_Name'] ? htmlspecialchars($row['First_Name']) : ''; ?>
                                        <?php echo $row['Last_Name'] ? htmlspecialchars($row['Last_Name']) : ''; ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo $row['Email'] ? htmlspecialchars($row['Email']) : ''; ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php 
                                        if ($row['Status'] === 'Approved') echo 'bg-green-100 text-green-800';
                                        elseif ($row['Status'] === 'Disapproved') echo 'bg-red-100 text-red-800';
                                        else echo 'bg-yellow-100 text-yellow-800';
                                    ?>">
                                        <span class="h-2 w-2 rounded-full mr-1.5 inline-block <?php 
                                            if ($row['Status'] === 'Approved') echo 'bg-green-500';
                                            elseif ($row['Status'] === 'Disapproved') echo 'bg-red-500';
                                            else echo 'bg-yellow-500';
                                        ?>"></span>
                                        <?php echo $row['Status'] ? htmlspecialchars($row['Status']) : 'Pending'; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" 
                                                class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition-colors" 
                                                onclick="editUser('<?php echo htmlspecialchars($row['ID']); ?>', '<?php echo htmlspecialchars($row['UserType']); ?>')">
                                            <i class='bx bx-edit-alt mr-1'></i> Edit
                                        </button>
                                        <form method="POST" class="inline-block">
                                            <input type="hidden" name="type" value="<?php echo $row['UserType']; ?>">
                                            <button type="submit" name="delete" value="<?php echo htmlspecialchars($row['ID']); ?>" 
                                                    class="inline-flex items-center px-3 py-1 bg-red-100 text-red-700 rounded-md hover:bg-red-200 transition-colors" 
                                                    onclick="return confirm('Are you sure you want to delete this user?')">
                                                <i class='bx bx-trash mr-1'></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            
                            <?php if ($result->num_rows === 0): ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <i class='bx bx-search text-4xl mb-2'></i>
                                        <p>No users found with the current filters</p>
                                        <a href="?status=All" class="text-blue-500 hover:underline mt-2">Clear filters</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination UI -->
                <div class="px-4 py-3 bg-white border-t border-gray-200 flex justify-end">
                    <nav class="flex space-x-1" aria-label="Pagination">
                        <button class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 cursor-not-allowed" disabled>
                            <i class='bx bx-chevron-left'></i>
                            <span class="sr-only">Previous</span>
                        </button>
                        <button class="w-8 h-8 flex items-center justify-center rounded-md bg-primary text-gray-800 font-medium">
                            1
                        </button>
                        <button class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 cursor-not-allowed" disabled>
                            <i class='bx bx-chevron-right'></i>
                            <span class="sr-only">Next</span>
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Confirmation Modal -->
            <div id="confirmationModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
                <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
                <div class="relative bg-white rounded-lg shadow-lg max-w-md w-full transform scale-95 opacity-0 transition-all duration-300">
                    <div class="flex items-center justify-between p-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Confirm Registration</h3>
                        <button type="button" id="modalCloseBtn" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <i class='bx bx-x text-xl'></i>
                        </button>
                    </div>
                    <div class="p-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 bg-yellow-100 rounded-full p-2 mr-3">
                                <i class='bx bx-envelope text-yellow-600 text-xl'></i>
                            </div>
                            <div>
                            <p id="confirmationMessage" class="text-gray-700">
                                    Are you sure you want to register this user and send the access code via email?
                                </p>
                                <p class="text-sm text-gray-500 mt-2">
                                    An email with login instructions will be sent to the user's email address.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-lg">
                        <button id="cancelBtn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
                            <i class='bx bx-x mr-1.5'></i>
                            Cancel
                        </button>
                        <button id="confirmBtn" class="px-4 py-2 bg-primary text-gray-800 rounded-md hover:bg-primary-dark transition-colors">
                            <i class='bx bx-check mr-1.5'></i>
                            Yes, Proceed
                        </button>
                    </div>
                </div>
            </div>

            <!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black bg-opacity-60 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-3xl transform scale-95 opacity-0 transition-all duration-300">
        <div class="bg-gradient-to-r from-yellow-400 to-yellow-500 py-4 px-6 flex justify-between items-center rounded-t-lg">
            <h2 class="text-xl font-bold text-gray-900">Edit User Information</h2>
            <button id="editModalCloseBtn" class="text-gray-800 hover:text-gray-600 focus:outline-none">
                <i class='bx bx-x text-xl'></i>
            </button>
        </div>
        
        <form id="editUserForm" class="p-6">
            <div id="editFormLoading" class="flex justify-center py-10">
                <svg class="animate-spin h-10 w-10 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <!-- Form will be dynamically loaded here via JavaScript -->
        </form>

            <?php if (!empty($message)): ?>
            <script>
                alert("<?php echo $message; ?>");
            </script>
            <?php endif; ?>

<script>
    // Function to validate email and enable buttons with live check
    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValid = emailRegex.test(email);
        
        document.getElementById('saveOwnerBtn').disabled = !isValid;
        document.getElementById('saveTenantBtn').disabled = !isValid;
        
        // Add visual feedback for validation
        const emailInput = document.getElementById('email');
        
        if (email.length > 0) {
            // Clear any existing messages
            const messageContainer = document.getElementById('emailFeedback');
            if (messageContainer) {
                messageContainer.remove();
            }
            
            if (isValid) {
                // Show checking state
                const feedbackDiv = document.createElement('div');
                feedbackDiv.id = 'emailFeedback';
                feedbackDiv.className = 'mt-1 text-sm text-yellow-600';
                feedbackDiv.innerHTML = `
                    <div class="flex items-center">
                        <svg class="animate-spin h-4 w-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Checking email availability...
                    </div>
                `;
                emailInput.parentNode.appendChild(feedbackDiv);
                
                // Check if email already exists in the database
                fetch(`check_email2.php?email=${encodeURIComponent(email)}`)
                    .then(response => response.json())
                    .then(data => {
                        // Remove any existing feedback
                        const oldFeedback = document.getElementById('emailFeedback');
                        if (oldFeedback) {
                            oldFeedback.remove();
                        }
                        
                        // Create feedback element
                        const feedbackDiv = document.createElement('div');
                        feedbackDiv.id = 'emailFeedback';
                        feedbackDiv.className = 'mt-1 text-sm';
                        
                        if (data.exists) {
                            // Email exists - show warning
                            feedbackDiv.className += ' text-red-600';
                            feedbackDiv.innerHTML = `<i class='bx bx-error-circle mr-1'></i> This email is already registered as a ${data.type}`;
                            
                            // Disable registration buttons
                            document.getElementById('saveOwnerBtn').disabled = true;
                            document.getElementById('saveTenantBtn').disabled = true;
                            
                            // Add red border
                            emailInput.classList.remove('border-green-300', 'focus:ring-green-500', 'focus:border-green-500');
                            emailInput.classList.add('border-red-300', 'focus:ring-red-500', 'focus:border-red-500');
                        } else {
                            // Email is available
                            feedbackDiv.className += ' text-green-600';
                            feedbackDiv.innerHTML = `<i class='bx bx-check-circle mr-1'></i> Email is available`;
                            
                            // Enable registration buttons
                            document.getElementById('saveOwnerBtn').disabled = false;
                            document.getElementById('saveTenantBtn').disabled = false;
                            
                            // Add green border
                            emailInput.classList.remove('border-red-300', 'focus:ring-red-500', 'focus:border-red-500');
                            emailInput.classList.add('border-green-300', 'focus:ring-green-500', 'focus:border-green-500');
                        }
                        
                        // Add feedback element after email input
                        emailInput.parentNode.appendChild(feedbackDiv);
                    })
                    .catch(error => {
                        console.error('Error checking email:', error);
                        
                        // Remove any existing feedback
                        const oldFeedback = document.getElementById('emailFeedback');
                        if (oldFeedback) {
                            oldFeedback.remove();
                        }
                        
                        // Show error message
                        const feedbackDiv = document.createElement('div');
                        feedbackDiv.id = 'emailFeedback';
                        feedbackDiv.className = 'mt-1 text-sm text-red-600';
                        feedbackDiv.innerHTML = `<i class='bx bx-error-circle mr-1'></i> Error checking email. Please try again.`;
                        emailInput.parentNode.appendChild(feedbackDiv);
                    });
            } else {
                emailInput.classList.remove('border-green-300', 'focus:ring-green-500', 'focus:border-green-500');
                emailInput.classList.add('border-red-300', 'focus:ring-red-500', 'focus:border-red-500');
                
                // Show invalid email message
                const feedbackDiv = document.createElement('div');
                feedbackDiv.id = 'emailFeedback';
                feedbackDiv.className = 'mt-1 text-sm text-red-600';
                feedbackDiv.innerHTML = `<i class='bx bx-x-circle mr-1'></i> Please enter a valid email address`;
                
                // Remove any existing feedback first
                const oldFeedback = document.getElementById('emailFeedback');
                if (oldFeedback) {
                    oldFeedback.remove();
                }
                
                // Add new feedback
                emailInput.parentNode.appendChild(feedbackDiv);
            }
        } else {
            emailInput.classList.remove('border-red-300', 'focus:ring-red-500', 'focus:border-red-500');
            emailInput.classList.remove('border-green-300', 'focus:ring-green-500', 'focus:border-green-500');
            
            // Remove any feedback messages
            const messageContainer = document.getElementById('emailFeedback');
            if (messageContainer) {
                messageContainer.remove();
            }
        }
    }
    
    // Variables to store the current selection
    let currentUserType = '';
    
    // Function to show confirmation modal
    function showConfirmation(userType) {
        currentUserType = userType;
        const email = document.getElementById('email').value;
        document.getElementById('confirmationMessage').innerHTML = 
            `Are you sure you want to register <strong>${email}</strong> as a <strong>${userType}</strong> and send the access code via email?`;
        
        const modal = document.getElementById('confirmationModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Animate modal appearance
        setTimeout(() => {
            const modalContent = modal.querySelector('.transform');
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        }, 10);
    }
    
    // Event listeners for modal buttons
    document.getElementById('confirmBtn').addEventListener('click', function() {
        const form = document.getElementById('userForm');
        
        // Create and append the hidden input
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = currentUserType === 'Owner' ? 'saveOwner' : 'saveTenant';
        hiddenInput.value = '1';
        form.appendChild(hiddenInput);
        
        // Submit the form
        form.submit();
    });
    
    document.getElementById('cancelBtn').addEventListener('click', closeConfirmationModal);
    document.getElementById('modalCloseBtn').addEventListener('click', closeConfirmationModal);
    
    function closeConfirmationModal() {
        const modal = document.getElementById('confirmationModal');
        const modalContent = modal.querySelector('.transform');
        
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }, 300);
    }
    
    // Close modal when clicking outside
    document.getElementById('confirmationModal').addEventListener('click', function(event) {
        if (event.target === this) {
            closeConfirmationModal();
        }
    });
    
// Function to handle edit user action
function editUser(id, userType) {
    // Show loading in the edit modal
    document.getElementById('editUserForm').innerHTML = `
        <div class="flex justify-center py-10">
            <svg class="animate-spin h-10 w-10 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    `;
    
    // Show the modal with animation
    const modal = document.getElementById('editModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    setTimeout(() => {
        const modalContent = modal.querySelector('.transform');
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
if (userType === 'Owner') {
    // Loop through the units and create table rows for each unit
    let unitRows = '';
    data.owned_units.forEach(unit => {
        unitRows += `
            <tr id="unit-row-${unit.Unit_Number}">
                <td>${unit.Tower}</td>
                <td>${unit.Unit_Number}</td>
                <td>${unit.Status}</td>
                <td>${unit.tenant_name || 'None'}</td>
                <td>${unit.created_at || 'N/A'}</td>
                <td>
                    <button type="button" onclick="editUnit(${unit.Unit_Number})">Edit</button>
                    <button type="button" onclick="deleteUnit(${unit.Unit_Number})">Delete</button>
                </td>
            </tr>
        `;
    });
    document.getElementById('unitTableBody').innerHTML = unitRows;
}
    // Fetch user data
    fetch(`get_user_details.php?id=${id}&type=${userType}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Network response was not ok: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            // Build the form with user data
            document.getElementById('editUserForm').innerHTML = `
                <input type="hidden" id="editUserId" name="userId" value="${id}">
                <input type="hidden" id="editUserType" name="userType" value="${userType}">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Personal Information -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-700 mb-2 pb-1 border-b">Personal Information</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="editFirstName" class="block text-sm font-medium text-gray-700">First Name</label>
                                <input type="text" id="editFirstName" name="First_Name" value="${data.First_Name || ''}" 
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            
                            <div>
                                <label for="editMiddleName" class="block text-sm font-medium text-gray-700">Middle Name</label>
                                <input type="text" id="editMiddleName" name="Middle_Name" value="${data.Middle_Name || ''}" 
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            
                            <div>
                                <label for="editLastName" class="block text-sm font-medium text-gray-700">Last Name</label>
                                <input type="text" id="editLastName" name="Last_Name" value="${data.Last_Name || ''}" 
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            
                            <div>
                                <label for="editNationality" class="block text-sm font-medium text-gray-700">Nationality</label>
                                <input type="text" id="editNationality" name="Nationality" value="${data.Nationality || ''}" 
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact Information -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-700 mb-2 pb-1 border-b">Contact Information</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="editEmail" class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" id="editEmail" name="Email" value="${data.Email || ''}" 
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            
                            <div>
                                <label for="editMobileNumber" class="block text-sm font-medium text-gray-700">Mobile Number</label>
                                <input type="text" id="editMobileNumber" name="Mobile_Number" value="${data.Mobile_Number || ''}" 
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            
                            <div>
                                <label for="editHomeNumber" class="block text-sm font-medium text-gray-700">Home Number</label>
                                <input type="text" id="editHomeNumber" name="Home_Number" value="${data.Home_Number || ''}" 
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Residence Information -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-700 mb-2 pb-1 border-b">Residence Details</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="editTower" class="block text-sm font-medium text-gray-700">Tower</label>
                                <select id="editTower" name="Tower" 
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="" ${!data.Tower ? 'selected' : ''}>Select Tower</option>
                                    <option value="A" ${data.Tower === 'A' ? 'selected' : ''}>Tower A</option>
                                    <option value="B" ${data.Tower === 'B' ? 'selected' : ''}>Tower B</option>
                                    <option value="C" ${data.Tower === 'C' ? 'selected' : ''}>Tower C</option>
                                    <option value="D" ${data.Tower === 'D' ? 'selected' : ''}>Tower D</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="editUnitNumber" class="block text-sm font-medium text-gray-700">Unit Number</label>
                                <input type="text" id="editUnitNumber" name="Unit_Number" value="${data.Unit_Number || ''}" 
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            
                            ${userType === 'Owner' ? `
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Issue Date</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <select name="Month_Issue" 
                                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-primary focus:border-primary">
                                        <option value="" ${!data.Month_Issue ? 'selected' : ''}>Month</option>
                                        <option value="January" ${data.Month_Issue === 'January' ? 'selected' : ''}>January</option>
                                        <option value="February" ${data.Month_Issue === 'February' ? 'selected' : ''}>February</option>
                                        <option value="March" ${data.Month_Issue === 'March' ? 'selected' : ''}>March</option>
                                        <option value="April" ${data.Month_Issue === 'April' ? 'selected' : ''}>April</option>
                                        <option value="May" ${data.Month_Issue === 'May' ? 'selected' : ''}>May</option>
                                        <option value="June" ${data.Month_Issue === 'June' ? 'selected' : ''}>June</option>
                                        <option value="July" ${data.Month_Issue === 'July' ? 'selected' : ''}>July</option>
                                        <option value="August" ${data.Month_Issue === 'August' ? 'selected' : ''}>August</option>
                                        <option value="September" ${data.Month_Issue === 'September' ? 'selected' : ''}>September</option>
                                        <option value="October" ${data.Month_Issue === 'October' ? 'selected' : ''}>October</option>
                                        <option value="November" ${data.Month_Issue === 'November' ? 'selected' : ''}>November</option>
                                        <option value="December" ${data.Month_Issue === 'December' ? 'selected' : ''}>December</option>
                                    </select>
                                    <input type="text" name="Year_Issue" value="${data.Year_Issue || ''}" placeholder="Year" 
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-primary focus:border-primary">
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <!-- Status Information -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-700 mb-2 pb-1 border-b">Account Status</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="editStatus" class="block text-sm font-medium text-gray-700">Status</label>
                                <select id="editStatus" name="Status" 
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="Pending" ${data.Status === 'Pending' ? 'selected' : ''}>Pending</option>
                                    <option value="Approved" ${data.Status === 'Approved' ? 'selected' : ''}>Approved</option>
                                    <option value="Disapproved" ${data.Status === 'Disapproved' ? 'selected' : ''}>Disapproved</option>
                                </select>
                            </div>
                            
                            ${userType === 'Tenant' ? `
                            <div>
                                <label for="editOwnerID" class="block text-sm font-medium text-gray-700">Owner ID</label>
                                <input type="text" id="editOwnerID" name="Owner_ID" value="${data.Owner_ID || ''}" 
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
                
                <!-- Owner Units Section - Only show for Owners -->
                ${userType === 'Owner' ? `
                <div class="mt-6 border rounded-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-yellow-50 to-amber-50 p-4 border-b">
                        <h3 class="text-md font-semibold text-gray-700">
                            <div class="flex justify-between items-center">
                                <span>Units Owned</span>
                                <span class="bg-primary text-xs font-medium px-2.5 py-0.5 rounded-full text-gray-900">
                                    Total: ${data.unit_count || 0}
                                </span>
                            </div>
                        </h3>
                    </div>
                    
                    <div class="overflow-auto max-h-48">
                        ${data.unitinformation && data.unitinformation.length > 0 ? `
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tower</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit #</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenant</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                ${data.unitinformation.map(unit => `
                                <tr class="hover:bg-gray-50" id="unit-row-${unit.id}">
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                        <span class="unit-display">Tower ${unit.tower || ''}</span>
                                        <div class="unit-edit hidden">
                                            <select class="edit-tower block w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                                <option value="A" ${unit.tower === 'A' ? 'selected' : ''}>Tower A</option>
                                                <option value="B" ${unit.tower === 'B' ? 'selected' : ''}>Tower B</option>
                                                <option value="C" ${unit.tower === 'C' ? 'selected' : ''}>Tower C</option>
                                                <option value="D" ${unit.tower === 'D' ? 'selected' : ''}>Tower D</option>
                                            </select>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                        <span class="unit-display">${unit.unit_num || ''}</span>
                                        <div class="unit-edit hidden">
                                            <input type="text" class="edit-unit-num block w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" 
                                                   value="${unit.unit_num || ''}">
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">
                                        <span class="unit-display px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            ${unit.status === 'Vacant' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                                            ${unit.status || 'N/A'}
                                        </span>
                                        <div class="unit-edit hidden">
                                            <select class="edit-status block w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                                <option value="Vacant" ${unit.status === 'Vacant' ? 'selected' : ''}>Vacant</option>
                                                <option value="Taken" ${unit.status === 'Taken' ? 'selected' : ''}>Taken</option>
                                            </select>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                        ${unit.tenant_name || 'None'}
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">${unit.created_at || 'N/A'}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex space-x-1">
                                            <button type="button" class="edit-unit-btn text-blue-600 hover:text-blue-800" 
                                                    data-unit-id="${unit.id}" onclick="toggleUnitEdit(${unit.id})">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button type="button" class="save-unit-btn hidden text-green-600 hover:text-green-800" 
                                                    data-unit-id="${unit.id}" onclick="saveUnitChanges(${unit.id}, '${data.Owner_ID}')">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                            <button type="button" class="cancel-unit-btn hidden text-red-600 hover:text-red-800" 
                                                    data-unit-id="${unit.id}" onclick="cancelUnitEdit(${unit.id})">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="edit-unit-response text-xs mt-1"></div>
                                    </td>
                                </tr>
                                `).join('')}
                            </tbody>
                        </table>
                        ` : `
                        <div class="text-center py-6 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <p>No units registered for this owner.</p>
                        </div>
                        `}
                    </div>
                    
                    <!-- Add New Unit Form -->
                    <div class="p-4 bg-gray-50 border-t">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Add New Unit</h4>
                        <div class="flex space-x-2">
                            <div class="w-1/3">
                                <select id="newUnitTower" 
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                                    <option value="">Select Tower</option>
                                    <option value="A">Tower A</option>
                                    <option value="B">Tower B</option>
                                    <option value="C">Tower C</option>
                                    <option value="D">Tower D</option>
                                </select>
                            </div>
                            <div class="w-1/3">
                                <input type="text" id="newUnitNumber" placeholder="Unit Number" 
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                            </div>
                            <div class="w-1/3">
                                <button type="button" id="addUnitBtn" onclick="addNewUnit('${id}')" 
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-gray-900 bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Add Unit
                                </button>
                            </div>
                        </div>
                        <div id="addUnitResponse" class="mt-2 text-sm"></div>
                    </div>
                </div>
                ` : ''}
                
                <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
                    <div id="editFormResponse" class="flex-grow text-left"></div>
                    <button type="button" id="cancelEditFormBtn" 
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" id="submitEditFormBtn" 
                            class="px-4 py-2 bg-primary text-gray-900 rounded-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary transition-colors">
                        Update User
                    </button>
                </div>
            `;
            
            // Re-attach event listeners
            document.getElementById('cancelEditFormBtn').addEventListener('click', closeEditModal);
            
            // Reattach form submission event listener
            document.getElementById('editUserForm').addEventListener('submit', submitEditForm);
        })
        .catch(error => {
            console.error('Error fetching user data:', error);
            document.getElementById('editUserForm').innerHTML = `
                <div class="bg-red-50 text-red-600 p-6 rounded-lg">
                    <h3 class="font-bold text-lg mb-2">Error Loading User Data</h3>
                    <p>There was a problem fetching the user information. Please try again.</p>
                    <p class="text-sm mt-2">${error.message}</p>
                    
                    <div class="mt-4 flex justify-end">
                        <button type="button" onclick="closeEditModal()" 
                                class="px-4 py-2 bg-red-100 text-red-700 rounded-md hover:bg-red-200 focus:outline-none transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            `;
        });
}

// Handle edit form submission
function submitEditForm(e) {
    e.preventDefault();
    
    // Show loading state
    const responseElement = document.getElementById('editFormResponse');
    responseElement.innerHTML = `
        <div class="flex items-center text-yellow-600">
            <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Updating user information...
        </div>
    `;
    
    // Collect form data
    const formData = new FormData(this);
    
    // Send the update request
    fetch('edit-adm-usermanage.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            responseElement.innerHTML = `
                <div class="flex items-center text-green-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    ${data.message}
                </div>
            `;
            
            // Close the modal after a delay and refresh the page
            setTimeout(() => {
                closeEditModal();
                window.location.reload(); // Refresh the page to show updated data
            }, 1500);
        } else {
            responseElement.innerHTML = `
                <div class="flex items-center text-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    ${data.message}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        responseElement.innerHTML = `
            <div class="flex items-center text-red-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                An error occurred. Please try again.
            </div>
        `;
    });
}

// Function to close edit modal
function closeEditModal() {
    const modal = document.getElementById('editModal');
    const modalContent = modal.querySelector('.transform');
    
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
        
        // Reset form
        document.getElementById('editUserForm').reset();
    }, 300);
}

    
   // Toggle the edit mode for a unit
function toggleUnitEdit(unitId) {
    const row = document.getElementById(`unit-row-${unitId}`);
    
    const displayElements = row.querySelectorAll('.unit-display');
    const editElements = row.querySelectorAll('.unit-edit');
    const editBtn = row.querySelector('.edit-unit-btn');
    const saveBtn = row.querySelector('.save-unit-btn');
    const cancelBtn = row.querySelector('.cancel-unit-btn');
    
    displayElements.forEach(el => el.classList.add('hidden'));
    editElements.forEach(el => el.classList.remove('hidden'));
    editBtn.classList.add('hidden');
    saveBtn.classList.remove('hidden');
    cancelBtn.classList.remove('hidden');
}

    function cancelUnitEdit(unitId) {
        const row = document.getElementById(`unit-row-${unitId}`);
        
        // Revert to display mode
        const displayElements = row.querySelectorAll('.unit-display');
        const editElements = row.querySelectorAll('.unit-edit');
        const editBtn = row.querySelector('.edit-unit-btn');
        const saveBtn = row.querySelector('.save-unit-btn');
        const cancelBtn = row.querySelector('.cancel-unit-btn');
        
        displayElements.forEach(el => el.classList.remove('hidden'));
        editElements.forEach(el => el.classList.add('hidden'));
        editBtn.classList.remove('hidden');
        saveBtn.classList.add('hidden');
        cancelBtn.classList.add('hidden');
        
        // Clear any response messages
        row.querySelector('.edit-unit-response').innerHTML = '';
    }

// Save the unit changes to the database
function saveUnitChanges(unitId) {
    const row = document.getElementById(`unit-row-${unitId}`);
    const responseElement = row.querySelector('.edit-unit-response');
    
    // Get the edited values
    const tower = row.querySelector('.edit-tower').value;
    const unitNum = row.querySelector('.edit-unit-num').value;
    const status = row.querySelector('.edit-status').value;
    
    // Validate inputs
    if (!tower || !unitNum) {
        responseElement.innerHTML = 'Please fill in all fields.';
        return;
    }
    
    // Send the updated data
    const formData = new FormData();
    formData.append('action', 'updateUnit');
    formData.append('unit_id', unitId);
    formData.append('tower', tower);
    formData.append('unit_num', unitNum);
    formData.append('status', status);
    
    fetch('edit-adm-usermanage.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            responseElement.innerHTML = 'Unit updated successfully!';
            // Update the display with the new values
            row.querySelector('.unit-display').textContent = `Tower ${tower}, Unit ${unitNum}`;
        } else {
            responseElement.innerHTML = `Error: ${data.message}`;
        }
    })
    .catch(error => {
        responseElement.innerHTML = `Error: ${error.message}`;
    });
}

    
    // Function to add a new unit
    function addNewUnit(ownerId) {
        // Get the values from the form
        const tower = document.getElementById('newUnitTower').value;
        const unitNumber = document.getElementById('newUnitNumber').value;
        const responseElement = document.getElementById('addUnitResponse');
        
        // Clear previous response
        responseElement.textContent = '';
        responseElement.className = '';
        
        // Validate input
        if (!tower) {
            responseElement.innerHTML = '<span class="text-red-600">Please select a tower.</span>';
            return;
        }
        
        if (!unitNumber) {
            responseElement.innerHTML = '<span class="text-red-600">Please enter a unit number.</span>';
            return;
        }
        
        // Show loading indicator
        responseElement.innerHTML = `
            <div class="flex items-center text-yellow-600">
                <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Adding unit...
            </div>
        `;
        
        // Create form data to send
        const formData = new FormData();
        formData.append('action', 'addUnit');
        formData.append('owner_id', ownerId);
        formData.append('tower', tower);
        formData.append('unit_num', unitNumber);
        
        // Send request to the server
        fetch('edit-adm-usermanage.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Check if the response is ok before trying to parse JSON
            if (!response.ok) {
                throw new Error(`Server returned ${response.status} ${response.statusText}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Error parsing response:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            if (data.success) {
                // Show success message
                responseElement.innerHTML = `
                    <div class="flex items-center text-green-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        ${data.message}
                    </div>
                `;
                
                // Clear the form
                document.getElementById('newUnitTower').value = '';
                document.getElementById('newUnitNumber').value = '';
                
                // Refresh the edit form after a short delay to show the new unit
                setTimeout(() => {
                    editUser(ownerId, 'Owner');
                }, 1500);
            } else {
                // Show error message
                responseElement.innerHTML = `
                    <div class="flex items-center text-red-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error adding unit:', error);
            responseElement.innerHTML = `
                <div class="flex items-center text-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    ${error.message || 'An error occurred. Please try again.'}
                </div>
            `;
        });
    }
</script>
        </main>
    </div>
</body>
</html>