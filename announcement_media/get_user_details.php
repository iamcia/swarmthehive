<?php
include 'dbconn.php';
session_start();

// For debugging purposes - can be removed in production
error_log("get_user_details.php accessed with params: " . json_encode($_GET));

// Check if user is logged in as admin - temporarily bypassed for troubleshooting
// if (!isset($_SESSION['admin_id'])) {
//     $response = [
//         'error' => 'Unauthorized access.'
//     ];
//     header('Content-Type: application/json');
//     echo json_encode($response);
//     exit;
// }

// Handle requests for getting user data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']) && isset($_GET['type'])) {
    $userId = $_GET['id'];
    $userType = $_GET['type'];
    
    $table = ($userType === 'Owner') ? 'ownerinformation' : 'tenantinformation';
    $idField = ($userType === 'Owner') ? 'Owner_ID' : 'Tenant_ID';
    
    // Query to get user details - make sure to include the ID column
    $sql = "SELECT *, ID as primary_key_id FROM $table WHERE $idField = ?";
    error_log("Executing query: $sql with param: $userId");
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Add debugging info
        $row['_debug'] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'query' => "SELECT * FROM $table WHERE $idField = '$userId'"
        ];
        
        // Convert any NULL values to empty strings for better JSON encoding
        foreach ($row as $key => &$value) {
            if ($value === null) {
                $value = "";
            }
        }
        
        // For owners, get their units
        if ($userType === 'Owner') {
            // Modified query to join with tenantinformation to get tenant details
            $unitsQuery = "SELECT ou.id, ou.tower, ou.unit_num, ou.created_at, ou.status, ou.tenant_id,
                           t.First_Name as tenant_first_name, t.Last_Name as tenant_last_name
                           FROM ownerunits ou
                           LEFT JOIN tenantinformation t ON ou.tenant_id = t.ID
                           WHERE ou.owner_id = ? 
                           ORDER BY ou.tower, ou.unit_num";
                           
            $unitsStmt = $conn->prepare($unitsQuery);
            $unitsStmt->bind_param("i", $row['ID']); // Use numeric ID as foreign key
            $unitsStmt->execute();
            $unitsResult = $unitsStmt->get_result();
            
            $units = [];
            while ($unitRow = $unitsResult->fetch_assoc()) {
                // Convert null values to empty strings here as well
                foreach ($unitRow as $key => &$value) {
                    if ($value === null) {
                        $value = "";
                    }
                }
                
                // Add tenant full name for easier access
                if ($unitRow['tenant_id']) {
                    $unitRow['tenant_name'] = trim($unitRow['tenant_first_name'] . ' ' . $unitRow['tenant_last_name']);
                } else {
                    $unitRow['tenant_name'] = '';
                }
                
                // Status should be Vacant or Taken
                // If there's a tenant_id, the unit is Taken, otherwise it's Vacant
                if (!empty($unitRow['tenant_id'])) {
                    $unitRow['status'] = 'Taken';
                } else {
                    $unitRow['status'] = 'Vacant';
                }
                
                $units[] = $unitRow;
            }
            
            $row['owned_units'] = $units;
            $row['unit_count'] = count($units);
            
            $unitsStmt->close();
            error_log("Found " . count($units) . " units for owner ID: $userId");
        }
        
        header('Content-Type: application/json');
        echo json_encode($row);
        error_log("User data found and returned successfully.");
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'User not found',
            '_debug' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'query' => "SELECT * FROM $table WHERE $idField = '$userId'"
            ]
        ]);
        error_log("User not found for $idField = $userId");
    }
    
    $stmt->close();
    exit;
}

// If we reach here, there was an invalid request
$response = [
    'error' => 'Invalid request - missing required parameters'
];
header('Content-Type: application/json');
echo json_encode($response);
error_log("Invalid request to get_user_details.php: " . json_encode($_GET));
?>
