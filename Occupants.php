<?php
include('dbconn.php');
session_start();

/**
if (!isset($_SESSION['Management_Code']) || $_SESSION['position'] != 'Admin') {
    header("Location: management-index.php");
    exit();
} 
*/

// Prepare SQL query with filters
$whereClauses = [];
$params = [];
$filterSQL = "SELECT o.*, 
              ow.Owner_ID, ow.Last_Name AS Owner_Last_Name, ow.First_Name AS Owner_First_Name,
              tn.Tenant_ID, tn.Last_Name AS Tenant_Last_Name, tn.First_Name AS Tenant_First_Name,
              tn.Unit_Number AS Tenant_Unit_Number, tn.Tower AS Tenant_Tower,
              ow.Unit_Number AS Owner_Unit_Number, ow.Tower AS Owner_Tower
              FROM occupants o
              LEFT JOIN ownerinformation ow ON o.Resident_Code = ow.Owner_ID
              LEFT JOIN tenantinformation tn ON o.Resident_Code = tn.Tenant_ID";

if (isset($_POST['filter'])) {
    // Capture filter input from the single search box
    $searchTerm = htmlspecialchars($_POST['search_term']);

    // If the search term is not empty, apply it to the relevant fields
    if (!empty($searchTerm)) {
        // Searching for the term across the Last Name, First Name, Unit Number, and Tower
        $whereClauses[] = "(o.Last_Name LIKE ? OR o.First_Name LIKE ? OR tn.Unit_Number LIKE ? OR tn.Tower LIKE ? OR ow.Unit_Number LIKE ? OR ow.Tower LIKE ?)";
        $params[] = "%" . $searchTerm . "%";
        $params[] = "%" . $searchTerm . "%";
        $params[] = "%" . $searchTerm . "%";
        $params[] = "%" . $searchTerm . "%";
        $params[] = "%" . $searchTerm . "%";
        $params[] = "%" . $searchTerm . "%";
    }

    if (count($whereClauses) > 0) {
        $filterSQL .= " WHERE " . implode(" AND ", $whereClauses);
    }
}

// Fetch filtered data
$stmt = $conn->prepare($filterSQL);
if (count($params) > 0) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    echo "<p>Error fetching occupants: " . $conn->error . "</p>";
}

// Handle deleting occupant
if (isset($_GET['delete'])) {
    $residentCode = htmlspecialchars($_GET['delete']); // Ensure Resident_Code is sanitized
    $deleteQuery = "DELETE FROM occupants WHERE Resident_Code = ?";
    if ($stmt = $conn->prepare($deleteQuery)) {
        $stmt->bind_param("s", $residentCode); // Bind Resident_Code as string
        if ($stmt->execute()) {
            echo "<script>alert('Occupant deleted successfully!'); window.location.href='Occupants.php';</script>";
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
    <title>Resident Occupants</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./css/admin_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/adm-dashboard-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/user-management.css?v=<?php echo time(); ?>">
    <style>
        /* Additional Custom Styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f7fafc;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 20px;
            text-align: left;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #4a5568;
        }
        td {
            background-color: #ffffff;
            color: #4a5568;
        }
        .btn {
            padding: 8px 16px;
            background-color: #48bb78;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            text-align: center;
        }
        .btn-danger {
            background-color: #f44336;
        }
        .btn:hover {
            background-color: #38a169;
        }
        .btn-danger:hover {
            background-color: #e41f2a;
        }
        .card {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card-header {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2d3748;
        }
    </style>
</head>
<body>
    
    <!-- Occupants Table Description Section -->
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>All Occupants</h2>
            </div>
            <p class="text-gray-600">
                This table lists all occupants in the system. The data includes both owners and tenants, identified by their <strong>Last Name</strong>, <strong>First Name</strong>, <strong>Unit Number</strong>, and <strong>Tower</strong>.
            </p>
            
           <!-- Filter Form (Single Textbox for Search) -->
            <form method="POST" class="mb-4">
                <div class="flex gap-4">
                    <input type="text" name="search_term" placeholder="Search by Last Name, First Name, Unit Number, or Tower" class="border p-2 rounded-md w-full">
                </div>
                <div class="flex gap-4 mt-4">
                    <button type="submit" name="filter" class="btn bg-blue-500 hover:bg-blue-600 mt-4">Search</button>
                </div>
            </form>
            
            <!-- Occupants Table -->
            <table>
                <thead>
                    <tr>
                        <th>Resident Code</th>
                        <th>User Type</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Relation</th>
                        <th>Mobile Number</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
<?php
// Check if there are occupants to display
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Resident_Code']) . "</td>";
        echo "<td>" . htmlspecialchars($row['User_Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Last_Name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['First_Name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Middle_Name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Gender']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Age']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Relation']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Mobile_Number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Email']) . "</td>";
        echo "<td><a href='Occupants.php?delete=" . htmlspecialchars($row['Resident_Code']) . "' class='btn btn-danger'>Delete</a></td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='11'>No occupants found.</td></tr>";
}
?>
                </tbody>
            </table>
        </div>
    </div>

<?php
$conn->close(); // Close connection after all operations are complete
?>

</body>
</html>
