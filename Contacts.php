<?php
include('dbconn.php');
session_start();

// Handle deleting contact
if (isset($_GET['delete'])) {
    $residentCode = htmlspecialchars($_GET['delete']);  // Ensure Resident_Code is sanitized
    $deleteQuery = "DELETE FROM contacts WHERE Resident_Code = ?";

    if ($stmt = $conn->prepare($deleteQuery)) {
        $stmt->bind_param("s", $residentCode); // Bind Resident_Code as string
        if ($stmt->execute()) {
            $_SESSION['message'] = "Contact deleted successfully!";
            echo "<script>alert('Contact deleted successfully!');</script>";
        } else {
            $_SESSION['error'] = "Error deleting contact. Please try again later.";
            error_log("Database error: " . $stmt->error);
            echo "<script>alert('Error deleting contact. Please try again later.');</script>";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Database error. Please try again later.";
        error_log("Prepare failed: " . $conn->error);
        echo "<script>alert('Database error. Please try again later.');</script>";
    }

    // Redirect to prevent resubmission
    header("Location: Contacts.php");
    exit;
}

// Fetch all data from the contacts table
$result = $conn->query("SELECT * FROM contacts");
if (!$result) {
    $_SESSION['error'] = "Error fetching data: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .form-container, .table-container {
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 10px;
        }

        .form-group label {
            display: inline-block;
            width: 120px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #000;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .delete-btn {
            color: red;
            text-decoration: none;
        }

        .delete-btn:hover {
            text-decoration: underline;
        }

        h2 {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<?php
// Display messages
if (isset($_SESSION['message'])) {
    echo "<div class='message success'>{$_SESSION['message']}</div>";
    unset($_SESSION['message']);
}
if (isset($_SESSION['error'])) {
    echo "<div class='message error'>{$_SESSION['error']}</div>";
    unset($_SESSION['error']);
}
?>

<div class="table-container">
    <h2>Contact List</h2>
    <table>
        <tr>
            <th>Resident Code</th>
            <th>Last Name</th>
            <th>First Name</th>
            <th>Middle Name</th>
            <th>Mobile Number</th>
            <th>Address</th>
            <th>Email</th>
            <th>Action</th>
        </tr>
        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['Resident_Code']}</td>
                        <td>{$row['Last_Name']}</td>
                        <td>{$row['First_Name']}</td>
                        <td>{$row['Middle_Name']}</td>
                        <td>{$row['Mobile_Number']}</td>
                        <td>{$row['Address']}</td>
                        <td>{$row['Email']}</td>
                        <td><a class='delete-btn' href='Contacts.php?delete={$row['Resident_Code']}' onclick='return confirm(\"Are you sure you want to delete this contact?\")'>Delete</a></td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='8'>No contacts found</td></tr>";
        }
        ?>
    </table>
</div>

</body>
</html>

<?php
$conn->close();
?>
