<?php
include('dbconn.php');
session_start();

// Fetch all data from the helper table
$result = $conn->query("SELECT * FROM helper");

// Check if there is an error in fetching data
if (!$result) {
    echo "<p>Error fetching data: " . $conn->error . "</p>";
}

// Handle deleting occupant
if (isset($_GET['delete'])) {
    $residentCode = htmlspecialchars($_GET['delete']);  // Ensure Resident_Code is sanitized
    $deleteQuery = "DELETE FROM helper WHERE Resident_Code = ?";
    if ($stmt = $conn->prepare($deleteQuery)) {
        $stmt->bind_param("s", $residentCode); // Bind Resident_Code as string
        if ($stmt->execute()) {
            echo "<script>alert('Occupant deleted successfully!'); window.location.href='Helper.php';</script>";
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
    <title>Helper Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-section, .table-section {
            margin-bottom: 30px;
        }
        .form-section {
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }
        label {
            margin: 10px 0 5px;
        }
        input[type="text"], input[type="email"], input[type="tel"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
        }
        button:hover {
            background-color: #45a049;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 auto;
        }
        table, th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        td {
            text-align: center;
        }
        .table-section table {
            margin: 20px 0;
        }
    </style>
</head>
<body>
        <!-- Display the helper table -->
        <div class="table-section">
            <table>
                <tr>
                    <th>Resident Code</th>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Position</th>
                    <th>Rest Day</th>
                    <th>Mobile Number</th>
                    <th>Address</th>
                    <th>Email</th>
                    <th>Action</th>
                </tr>

                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['Resident_Code']; ?></td>
                        <td><?php echo $row['Last_Name']; ?></td>
                        <td><?php echo $row['First_Name']; ?></td>
                        <td><?php echo $row['Middle_Name']; ?></td>
                        <td><?php echo $row['Position']; ?></td>
                        <td><?php echo $row['Rest_Day']; ?></td>
                        <td><?php echo $row['Mobile_Number']; ?></td>
                        <td><?php echo $row['Address']; ?></td>
                        <td><?php echo $row['Email']; ?></td>
                        <td>
                            <a href="Helper.php?delete=<?php echo $row['Resident_Code']; ?>" onclick="return confirm('Are you sure you want to delete this helper?');">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10">No helpers found</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <?php $conn->close(); ?>
</body>
</html>