<?php
include('dbconn.php');
session_start();

/**
if (!isset($_SESSION['Management_Code']) || $_SESSION['position'] != 'Admin') {
    header("Location: management-index.php");
    exit();
} 
*/

// Fetch all data from the helper and contacts tables
$helperResult = $conn->query("SELECT * FROM helper");
$contactsResult = $conn->query("SELECT * FROM contacts");

// Check for errors fetching data
if (!$helperResult || !$contactsResult) {
    echo "<p>Error fetching data: " . $conn->error . "</p>";
}

// Handle deleting an entry
if (isset($_GET['delete'])) {
    $residentCode = htmlspecialchars($_GET['delete']);  // Ensure Resident_Code is sanitized
    $deleteQuery = "DELETE FROM helper WHERE Resident_Code = ?";
    if ($stmt = $conn->prepare($deleteQuery)) {
        $stmt->bind_param("s", $residentCode); // Bind Resident_Code as string
        if ($stmt->execute()) {
            echo "<script>alert('Occupant deleted successfully!'); window.location.href='adm-others.php';</script>";
        } else {
            echo "<p>Error deleting occupant. Please try again later.</p>";
            error_log("Database error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        echo "<p>Database error. Please try again later.</p>";
        error_log("Prepare failed: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Helper & Contacts</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./css/admin_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/adm-dashboard-style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-gray-50 font-sans">
    <div class="container">
        <!-- Sidebar and header code as in your provided ADM-USERMANAGE.PHP code -->
        
        <main class="p-6 md:p-8 transition-all">
            <div class="flex flex-wrap items-center justify-between mb-6">
                
                <!-- Table for Helper Data -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">Helper Data</h3>
                    <table class="w-full mt-4">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Resident Code</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Last Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">First Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Mobile Number</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if ($helperResult->num_rows > 0): ?>
                                <?php while ($row = $helperResult->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-4 py-3"><?php echo $row['Resident_Code']; ?></td>
                                        <td class="px-4 py-3"><?php echo $row['Last_Name']; ?></td>
                                        <td class="px-4 py-3"><?php echo $row['First_Name']; ?></td>
                                        <td class="px-4 py-3"><?php echo $row['Mobile_Number']; ?></td>
                                        <td class="px-4 py-3"><?php echo $row['Email']; ?></td>
                                        <td class="px-4 py-3">
                                            <a href="adm-others.php?delete=<?php echo $row['Resident_Code']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to delete this helper?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="px-4 py-3 text-center">No helpers found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Table for Contacts Data -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">Contacts Data</h3>
                    <table class="w-full mt-4">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Resident Code</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Last Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">First Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Mobile Number</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if ($contactsResult->num_rows > 0): ?>
                                <?php while ($row = $contactsResult->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-4 py-3"><?php echo $row['Resident_Code']; ?></td>
                                        <td class="px-4 py-3"><?php echo $row['Last_Name']; ?></td>
                                        <td class="px-4 py-3"><?php echo $row['First_Name']; ?></td>
                                        <td class="px-4 py-3"><?php echo $row['Mobile_Number']; ?></td>
                                        <td class="px-4 py-3"><?php echo $row['Email']; ?></td>
                                        <td class="px-4 py-3">
                                            <a href="adm-others.php?delete=<?php echo $row['Resident_Code']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to delete this contact?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="px-4 py-3 text-center">No contacts found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

<?php
$conn->close();
?>
