<?php
include 'dbconn.php';
session_start();

// Handle requests for getting user data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']) && isset($_GET['type'])) {
    $userId = $_GET['id'];
    $userType = $_GET['type'];
    
    $table = ($userType === 'Owner') ? 'ownerinformation' : 'tenantinformation';
    $idField = ($userType === 'Owner') ? 'Owner_ID' : 'Tenant_ID';
    
    // Query to get user details
    $sql = "SELECT *, ID as primary_key_id FROM $table WHERE $idField = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // For owners, get their units from the unitinformation table
        if ($userType === 'Owner') {
            $unitsQuery = "
                SELECT ui.Owner_ID, ui.Tenant_ID, ui.Last_Name, ui.First_Name, ui.Tower, ui.Unit_Number, ui.Status, 
                    t.First_Name as tenant_first_name, t.Last_Name as tenant_last_name
                FROM unitinformation ui
                LEFT JOIN tenantinformation t ON ui.Tenant_ID = t.Tenant_ID
                WHERE ui.Owner_ID = ? 
                ORDER BY ui.Tower, ui.Unit_Number
            ";
            $unitsStmt = $conn->prepare($unitsQuery);
            $unitsStmt->bind_param("s", $row['ID']);
            $unitsStmt->execute();
            $unitsResult = $unitsStmt->get_result();
            
            $units = [];
            while ($unitRow = $unitsResult->fetch_assoc()) {
                // Convert null values to empty strings
                foreach ($unitRow as $key => &$value) {
                    if ($value === null) {
                        $value = "";
                    }
                }
                
                // Add tenant full name for easier access
                $unitRow['tenant_name'] = ($unitRow['Tenant_ID']) ? trim($unitRow['tenant_first_name'] . ' ' . $unitRow['tenant_last_name']) : '';
                $unitRow['status'] = !empty($unitRow['Tenant_ID']) ? 'Taken' : 'Vacant';
                
                $units[] = $unitRow;
            }
            
            $row['owned_units'] = $units;
            $row['unit_count'] = count($units);
            $unitsStmt->close();
        }
        
        // Return the data
        header('Content-Type: application/json');
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'User not found']);
    }
    $stmt->close();
    exit;
}

// Invalid request
$response = ['error' => 'Invalid request - missing required parameters'];
header('Content-Type: application/json');
echo json_encode($response);
?>
