<?php
// Simple diagnostic script to check pool reservation system

// Include database connection
include 'dbconn.php';

echo "<h1>Pool Reservation System Diagnostic</h1>";

// Check database connection
if ($conn->connect_error) {
    echo "<p style='color:red'>Database connection failed: " . $conn->connect_error . "</p>";
    exit;
} else {
    echo "<p style='color:green'>Database connection successful!</p>";
}

// Check if poolreserve table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'poolreserve'");
if ($tableCheck->num_rows == 0) {
    echo "<p style='color:red'>The poolreserve table does not exist!</p>";
    
    // Show table creation SQL to help fixing the issue
    echo "<p>You can create the table using the following SQL:</p>";
    echo "<pre>
CREATE TABLE poolreserve (
  id INT AUTO_INCREMENT PRIMARY KEY,
  Resident_Code VARCHAR(50) NOT NULL,
  User_Type VARCHAR(20) NOT NULL,
  names TEXT NOT NULL,
  towerunitnum VARCHAR(50) NOT NULL,
  schedule DATETIME NOT NULL,
  Status VARCHAR(20) DEFAULT 'Pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
</pre>";
} else {
    echo "<p style='color:green'>The poolreserve table exists!</p>";
    
    // Check table structure
    echo "<h2>Table Structure:</h2>";
    $result = $conn->query("DESCRIBE poolreserve");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show recent entries
    echo "<h2>Recent Reservations:</h2>";
    $result = $conn->query("SELECT * FROM poolreserve ORDER BY created_at DESC LIMIT 5");
    if ($result->num_rows > 0) {
        echo "<table border='1'><tr><th>ID</th><th>Resident Code</th><th>User Type</th><th>Names</th><th>Tower/Unit</th><th>Schedule</th><th>Status</th><th>Created At</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['Resident_Code'] . "</td>";
            echo "<td>" . $row['User_Type'] . "</td>";
            echo "<td>" . $row['names'] . "</td>";
            echo "<td>" . $row['towerunitnum'] . "</td>";
            echo "<td>" . $row['schedule'] . "</td>";
            echo "<td>" . $row['Status'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No reservations found in the database.</p>";
    }
}

// Show session information
echo "<h2>Current Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Close connection
$conn->close();
?>
