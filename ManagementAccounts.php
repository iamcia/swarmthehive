<?php
session_start(); // Start session to store messages if needed

$servername = "localhost";
$db_username = "u113232969_Hives";
$db_password = "theSwarm4";
$dbname = "u113232969_SWARM";

// Establish database connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve records from managementaccount table
$sql = "SELECT firstname, lastname, contact_number, email, position, security_number, shift_schedule FROM managementaccount";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Accounts</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h2>Management Accounts</h2>
    <table>
        <thead>
            <tr>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Contact</th>
                <th>Email</th>
                <th>Position</th>
                <th>Security Number</th>
                <th>Shift Schedule</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row["firstname"]); ?></td>
                        <td><?php echo htmlspecialchars($row["lastname"]); ?></td>
                        <td><?php echo htmlspecialchars($row["contact_number"]); ?></td>
                        <td><?php echo htmlspecialchars($row["email"]); ?></td>
                        <td><?php echo htmlspecialchars($row["position"]); ?></td>
                        <td><?php echo htmlspecialchars($row["security_number"]); ?></td>
                        <td><?php echo htmlspecialchars($row["shift_schedule"]); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No management accounts found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

<?php
$conn->close();
?>
