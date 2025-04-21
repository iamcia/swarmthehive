<?php
// Database connection
include('dbconn.php');
session_start();



// Handle delete request
if (isset($_POST['delete_id'])) {
    $resident_code = $_POST['delete_id'];
    $delete_sql = "DELETE FROM ownertenantconcerns WHERE Resident_Code = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("s", $resident_code);
    if ($stmt->execute()) {
        echo "<script>alert('Concern deleted successfully!');</script>";
    }
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']); // Refresh page to see changes
    exit();
}

// Handle approval/disapproval request
if (isset($_POST['status']) && isset($_POST['update_id'])) {
    $resident_code = $_POST['update_id'];
    $status = $_POST['status'];
    $update_sql = "UPDATE ownerinformation SET Status = ? WHERE Resident_Code = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ss", $status, $resident_code);
    if ($stmt->execute()) {
        echo "<script>alert('Status updated to $status successfully!');</script>";
    }
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']); // Refresh page to see changes
    exit();
}

// Fetch records from ownertenantconcerns table
$sql = "SELECT * FROM ownertenantconcerns";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Admin.css">
    <title>Owner Tenant Concerns</title>
    <style>
        /* Dropdown Button Styles */
        .dropdown {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #d2b48c;
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
        }
        .dropdown-content a {
            color: white;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
            font-size: 1rem;
        }
        .dropdown-content a:hover {
            background-color: #ff9800;
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }
        img.signature {
            width: 100px;
            height: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
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

    <div class="container">
        <h1>Owner Tenant Concerns</h1>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Resident Code</th>
                        <th>User Type</th>
                        <th>User Email</th>
                        <th>User Number</th>
                        <th>Unit Number</th>
                        <th>Concern Type</th>
                        <th>Concern Details</th>
                        <th>Available Schedule</th>
                        <th>Signature</th>
                        <th>Submitted At</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['Resident_Code']); ?></td>
                                <td><?= htmlspecialchars($row['user_type']); ?></td>
                                <td><?= htmlspecialchars($row['user_email']); ?></td>
                                <td><?= htmlspecialchars($row['user_number']); ?></td>
                                <td><?= htmlspecialchars($row['unit_number']); ?></td>
                                <td><?= htmlspecialchars($row['concern_type']); ?></td>
                                <td><?= htmlspecialchars($row['concern_details']); ?></td>
                                <td><?= htmlspecialchars($row['available_sched']); ?></td>
                                <td>
                                    <?php if (!empty($row['signature'])): ?>
                                        <img src="data:image/jpeg;base64,<?= base64_encode($row['signature']); ?>" class="signature" alt="Signature">
                                    <?php else: ?>
                                        No Signature
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['submitted_at']); ?></td>
                                <td><?= htmlspecialchars($row['status']); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="update_id" value="<?= $row['Resident_Code']; ?>">
                                        <button type="submit" name="status" value="Approved">Approve</button>
                                        <button type="submit" name="status" value="Disapproved">Disapprove</button>
                                        <button type="submit" name="status" value="Completed">Complete</button>
                                    </form>
                                    <button class="delete-btn" onclick="confirmDelete('<?= $row['Resident_Code']; ?>')">Delete</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12">No concerns found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Hidden form for deleting records -->
        <form id="deleteForm" method="POST" style="display: none;">
            <input type="hidden" name="delete_id" id="delete_id">
        </form>
    </div>

    <script>
        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this concern?")) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>
