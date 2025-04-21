<?php
include 'dbconn.php';
session_start();

// Handle adding new unit for owner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'addUnit') {
    header('Content-Type: application/json');
    
    try {
        // Get parameters
        $ownerUserID = isset($_POST['owner_id']) ? $_POST['owner_id'] : '';
        $tower = isset($_POST['tower']) ? $_POST['tower'] : '';
        $unitNum = isset($_POST['unit_num']) ? $_POST['unit_num'] : '';
        
        // Validate parameters
        if (empty($ownerUserID) || empty($tower) || empty($unitNum)) {
            throw new Exception('Missing required parameters');
        }
        
        // Get the actual primary key (ID) for the owner
        $getOwnerIdSql = "SELECT ID FROM ownerinformation WHERE Owner_ID = ?";
        $getOwnerIdStmt = $conn->prepare($getOwnerIdSql);
        $getOwnerIdStmt->bind_param("s", $ownerUserID);
        $getOwnerIdStmt->execute();
        $getOwnerIdResult = $getOwnerIdStmt->get_result();
        
        if ($getOwnerIdResult->num_rows === 0) {
            throw new Exception('Owner not found');
        }
        
        $ownerRow = $getOwnerIdResult->fetch_assoc();
        $ownerPrimaryKey = $ownerRow['ID'];
        $getOwnerIdStmt->close();
        
        // Check if the unit already exists
        $checkUnitSql = "SELECT id FROM unitinformation WHERE Owner_ID = ? AND Tower = ? AND Unit_Number = ?";
        $checkUnitStmt = $conn->prepare($checkUnitSql);
        $checkUnitStmt->bind_param("iss", $ownerPrimaryKey, $tower, $unitNum);
        $checkUnitStmt->execute();
        $checkUnitResult = $checkUnitStmt->get_result();
        
        if ($checkUnitResult->num_rows > 0) {
            throw new Exception('This unit is already registered for this owner');
        }
        
        // Insert the new unit into unitinformation table
        $status = 'Vacant'; // Default status for newly added units
        $currentDate = date('Y-m-d H:i:s');
        
        $insertSql = "INSERT INTO unitinformation (Owner_ID, Tower, Unit_Number, Status, Created_At) VALUES (?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("issss", $ownerPrimaryKey, $tower, $unitNum, $status, $currentDate);
        $insertStmt->execute();
        
        $unitId = $insertStmt->insert_id;
        $insertStmt->close();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Unit added successfully',
            'unit_id' => $unitId
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Handle updating unit information
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'updateUnit') {
    header('Content-Type: application/json');
    
    try {
        // Get parameters
        $unitId = isset($_POST['unit_id']) ? $_POST['unit_id'] : '';
        $tower = isset($_POST['tower']) ? $_POST['tower'] : '';
        $unitNum = isset($_POST['unit_num']) ? $_POST['unit_num'] : '';
        $status = isset($_POST['status']) ? $_POST['status'] : '';
        
        if (empty($unitId)) {
            throw new Exception('Missing unit ID');
        }
        
        if (empty($tower) || empty($unitNum) || empty($status)) {
            throw new Exception('Missing required fields');
        }
        
        // Update the unit information
        $updateSql = "UPDATE unitinformation SET Tower = ?, Unit_Number = ?, Status = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("sssi", $tower, $unitNum, $status, $unitId);
        $updateStmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Unit updated successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Handle deleting unit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'deleteUnit') {
    header('Content-Type: application/json');
    
    try {
        $unitId = isset($_POST['unit_id']) ? $_POST['unit_id'] : '';
        
        if (empty($unitId)) {
            throw new Exception('Unit ID is required');
        }
        
        $deleteSql = "DELETE FROM unitinformation WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("i", $unitId);
        $deleteStmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Unit deleted successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}
