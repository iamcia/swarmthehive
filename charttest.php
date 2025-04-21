<?php
session_start();

// Enable error reporting for troubleshooting (only use in development, not production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection details
$servername = "localhost";
$db_username = "u113232969_Hives"; 
$db_password = "theSwarm4"; 
$dbname = "u113232969_SWARM";

// Connect to the database
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Count transactions in each table
$ownertenantreservationCount = $conn->query("SELECT COUNT(*) as count FROM ownertenantreservation")->fetch_assoc()['count'];
$poolreserveCount = $conn->query("SELECT COUNT(*) as count FROM poolreserve")->fetch_assoc()['count'];
$ownertenantconcernsCount = $conn->query("SELECT COUNT(*) as count FROM ownertenantconcerns")->fetch_assoc()['count'];
$gatepassCount = $conn->query("SELECT COUNT(*) as count FROM gatepass")->fetch_assoc()['count'];

// Prepare data points for chart
$dataPoints = array(
    array("label" => "Owner/Tenant Reservation", "y" => $ownertenantreservationCount),
    array("label" => "Pool Reserve", "y" => $poolreserveCount),
    array("label" => "Owner/Tenant Concerns", "y" => $ownertenantconcernsCount),
    array("label" => "Gate Pass", "y" => $gatepassCount)
);

// Close the connection
$conn->close();
?>

<!DOCTYPE HTML>
<html>
<head>
<script>
window.onload = function () {
    var chart = new CanvasJS.Chart("chartContainer", {
        animationEnabled: true,
        theme: "light2",
        title: {
            text: "Transactions"
        },
        axisY: {
            includeZero: true,
            title: "Number of Transactions"
        },
        data: [{
            type: "column",
            dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
        }]
    });
    chart.render();
}
</script>
</head>
<body>
<div id="chartContainer" style="height: 370px; width: 100%;"></div>
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
</body>
</html>
