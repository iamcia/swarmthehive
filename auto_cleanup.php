<?php
// This file should be executed via a cron job to perform automatic cleanup of old records
// Example cron setup: 0 0 * * * php /path/to/your/website/auto_cleanup.php

// Include database connection
include 'dbconn.php';

// Function to clean up old records
function cleanupOldRecords($conn) {
    // Calculate date 30 days ago
    $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
    
    // Delete old owner records that only have email (pending registrations)
    $ownerSql = "DELETE FROM ownerinformation 
                WHERE Status = 'Pending' 
                AND (First_Name IS NULL OR First_Name = '') 
                AND (Last_Name IS NULL OR Last_Name = '') 
                AND Created_At <= ?";
    
    // Delete old tenant records that only have email (pending registrations)
    $tenantSql = "DELETE FROM tenantinformation 
                 WHERE Status = 'Pending' 
                 AND (First_Name IS NULL OR First_Name = '') 
                 AND (Last_Name IS NULL OR Last_Name = '') 
                 AND Created_At <= ?";
    
    // Execute owner cleanup
    $ownerStmt = $conn->prepare($ownerSql);
    $ownerStmt->bind_param("s", $thirtyDaysAgo);
    $ownerStmt->execute();
    $ownersDeleted = $ownerStmt->affected_rows;
    $ownerStmt->close();
    
    // Execute tenant cleanup
    $tenantStmt = $conn->prepare($tenantSql);
    $tenantStmt->bind_param("s", $thirtyDaysAgo);
    $tenantStmt->execute();
    $tenantsDeleted = $tenantStmt->affected_rows;
    $tenantStmt->close();
    
    // Return details about deleted records
    return [
        'owners' => $ownersDeleted,
        'tenants' => $tenantsDeleted,
        'total' => $ownersDeleted + $tenantsDeleted,
        'date' => date('Y-m-d H:i:s')
    ];
}

// Run the cleanup
$result = cleanupOldRecords($conn);

// Log the results
file_put_contents('cleanup_log.txt', 
    "Cleanup on {$result['date']}: Deleted {$result['owners']} owner records and {$result['tenants']} tenant records (Total: {$result['total']})\n", 
    FILE_APPEND);

// Output for cron job logs
echo "Auto-cleanup completed: Deleted {$result['total']} old pending records.\n";

// Close the database connection
$conn->close();
?>