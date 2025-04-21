<?php
include('dbconn.php');
session_start();

/**
if (!isset($_SESSION['Management_Code']) || $_SESSION['position'] != 'Admin') {
    header("Location: management-index.php");
    exit();
} 
*/

// Function to check if a unit already exists
function checkUnitExists($ownerId, $tenantId, $unitNumber, $conn) {
    $sql = "SELECT * FROM unitinformation WHERE Owner_ID = ? AND Tenant_ID = ? AND Unit_Number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $ownerId, $tenantId, $unitNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return true;
    }
    
    return false;
}

// Handle insert or update for unit information
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['saveUnit'])) {
        $ownerId = htmlspecialchars($_POST['owner_id']);
        $tenantId = htmlspecialchars($_POST['tenant_id']);
        $lastName = htmlspecialchars($_POST['last_name']);
        $firstName = htmlspecialchars($_POST['first_name']);
        $tower = htmlspecialchars($_POST['tower']);
        $unitNumber = htmlspecialchars($_POST['unit_number']);


        // Check if the unit already exists
        if (checkUnitExists($ownerId, $tenantId, $unitNumber, $conn)) {
            $message = "This unit is already assigned to the owner and tenant.";
        } else {
            // Insert the unit information into the table
            $sql = "INSERT INTO unitinformation (Owner_ID, Tenant_ID, Last_Name, First_Name, Tower, Unit_Number, Status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $ownerId, $tenantId, $lastName, $firstName, $tower, $unitNumber, $status);
            $stmt->execute();
            $stmt->close();
            
            $message = "Unit information saved successfully.";
        }
    }

    // Handle unit deletion
    if (isset($_POST['deleteUnit'])) {
        $ownerId = $_POST['owner_id'];
        $tenantId = $_POST['tenant_id'];
        $unitNumber = $_POST['unit_number'];
        
        // Delete unit information from the table
        $sql = "DELETE FROM unitinformation WHERE Owner_ID = ? AND Tenant_ID = ? AND Unit_Number = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $ownerId, $tenantId, $unitNumber);
        $stmt->execute();
        $stmt->close();
        
        $message = "Unit information deleted successfully.";
    }
}

// Build the query to retrieve unit information
// Modify the query to only select units where either Owner_ID or Tenant_ID is not NULL
$sql = "SELECT * FROM unitinformation WHERE Owner_ID IS NOT NULL AND Tenant_ID IS NOT NULL";
$stmt = $conn->prepare($sql);
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
    <title>SWARM | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./css/admin_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/adm-dashboard-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/user-management.css?v=<?php echo time(); ?>">
</head>
<body class="bg-gray-50 font-sans">
    <div class="container">
        <!-- Sidebar and header code as in your provided ADM-USERMANAGE.PHP code -->
        
        <main class="p-6 md:p-8 transition-all">
            <div class="flex flex-wrap items-center justify-between mb-6">
                
        <!-- Unit Structure Explanation Section -->
<div class="bg-white p-6 rounded-lg shadow-md mb-6">
    <h3 class="text-lg font-semibold text-gray-800">Unit Information</h3>
    <p class="mt-4 text-sm text-gray-600">
        The condo units are organized based on the tower structure (From A-D only).
    </p>
</div>

                            <!-- Unit Information List -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">Unit List</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Owner ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tenant ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Last Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">First Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tower</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Unit Number</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-yellow-50 transition-colors">
                                <td class="px-4 py-3"><?php echo htmlspecialchars($row['Owner_ID']); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($row['Tenant_ID']); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($row['Last_Name']); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($row['First_Name']); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($row['Tower']); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($row['Unit_Number']); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($row['Status']); ?></td>
                                <td class="px-4 py-3">
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="owner_id" value="<?php echo htmlspecialchars($row['Owner_ID']); ?>">
                                        <input type="hidden" name="tenant_id" value="<?php echo htmlspecialchars($row['Tenant_ID']); ?>">
                                        <input type="hidden" name="unit_number" value="<?php echo htmlspecialchars($row['Unit_Number']); ?>">
                                        <button type="submit" name="deleteUnit" class="px-3 py-1 bg-red-100 text-red-700 rounded-md hover:bg-red-200">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (!empty($message)): ?>
            <script>
                alert("<?php echo $message; ?>");
            </script>
            <?php endif; ?>
                
            </div>
        </main>
    </div>
</body>
</html>