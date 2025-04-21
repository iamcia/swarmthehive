<?php
/**
 * Get counts of Pending, Approved, Completed, and Rejected statuses across all service tables
 * 
 * @param object $conn Database connection 
 * @return array Associative array with status counts
 */
function getStatusCounts($conn) {
    $sql = "
    -- For Pending status count (case insensitive)
    SELECT 'Pending' AS Status, (
        SELECT COUNT(*) FROM gatepass WHERE LOWER(Status) = 'pending'
    ) + (
        SELECT COUNT(*) FROM guestcheckinout WHERE LOWER(Status) = 'pending'
    ) + (
        SELECT COUNT(*) FROM ownertenantmoveout WHERE LOWER(Status) = 'pending'
    ) + (
        SELECT COUNT(*) FROM ownertenantreservation WHERE LOWER(Status) = 'pending'
    ) + (
        SELECT COUNT(*) FROM ownertenantvisitor WHERE LOWER(Status) = 'pending'
    ) + (
        SELECT COUNT(*) FROM pets WHERE LOWER(Status) = 'pending'
    ) + (
        SELECT COUNT(*) FROM poolreserve WHERE LOWER(Status) = 'pending'
    ) + (
        SELECT COUNT(*) FROM workpermit WHERE LOWER(Status) = 'pending'
    ) AS Count

    UNION ALL

    -- For approval/approved status count (case insensitive)
    SELECT 'Approval' AS Status, (
        SELECT COUNT(*) FROM gatepass WHERE LOWER(Status) IN ('approved', 'approval')
    ) + (
        SELECT COUNT(*) FROM guestcheckinout WHERE LOWER(Status) IN ('approved', 'approval')
    ) + (
        SELECT COUNT(*) FROM ownertenantmoveout WHERE LOWER(Status) IN ('approved', 'approval')
    ) + (
        SELECT COUNT(*) FROM ownertenantreservation WHERE LOWER(Status) IN ('approved', 'approval')
    ) + (
        SELECT COUNT(*) FROM ownertenantvisitor WHERE LOWER(Status) IN ('approved', 'approval')
    ) + (
        SELECT COUNT(*) FROM pets WHERE LOWER(Status) IN ('approved', 'approval')
    ) + (
        SELECT COUNT(*) FROM poolreserve WHERE LOWER(Status) IN ('approved', 'approval')
    ) + (
        SELECT COUNT(*) FROM workpermit WHERE LOWER(Status) IN ('approved', 'approval')
    ) AS Count

    UNION ALL

    -- For complete/completed status count (case insensitive)
    SELECT 'Complete' AS Status, (
        SELECT COUNT(*) FROM gatepass WHERE LOWER(Status) IN ('complete', 'completed')
    ) + (
        SELECT COUNT(*) FROM guestcheckinout WHERE LOWER(Status) IN ('complete', 'completed')
    ) + (
        SELECT COUNT(*) FROM ownertenantmoveout WHERE LOWER(Status) IN ('complete', 'completed')
    ) + (
        SELECT COUNT(*) FROM ownertenantreservation WHERE LOWER(Status) IN ('complete', 'completed')
    ) + (
        SELECT COUNT(*) FROM ownertenantvisitor WHERE LOWER(Status) IN ('complete', 'completed')
    ) + (
        SELECT COUNT(*) FROM pets WHERE LOWER(Status) IN ('complete', 'completed')
    ) + (
        SELECT COUNT(*) FROM poolreserve WHERE LOWER(Status) IN ('complete', 'completed')
    ) + (
        SELECT COUNT(*) FROM workpermit WHERE LOWER(Status) IN ('complete', 'completed')
    ) AS Count
    
    UNION ALL

    -- For rejected status count (case insensitive)
    SELECT 'Rejected' AS Status, (
        SELECT COUNT(*) FROM gatepass WHERE LOWER(Status) IN ('rejected', 'reject', 'declined', 'deny', 'denied')
    ) + (
        SELECT COUNT(*) FROM guestcheckinout WHERE LOWER(Status) IN ('rejected', 'reject', 'declined', 'deny', 'denied')
    ) + (
        SELECT COUNT(*) FROM ownertenantmoveout WHERE LOWER(Status) IN ('rejected', 'reject', 'declined', 'deny', 'denied')
    ) + (
        SELECT COUNT(*) FROM ownertenantreservation WHERE LOWER(Status) IN ('rejected', 'reject', 'declined', 'deny', 'denied')
    ) + (
        SELECT COUNT(*) FROM ownertenantvisitor WHERE LOWER(Status) IN ('rejected', 'reject', 'declined', 'deny', 'denied')
    ) + (
        SELECT COUNT(*) FROM pets WHERE LOWER(Status) IN ('rejected', 'reject', 'declined', 'deny', 'denied')
    ) + (
        SELECT COUNT(*) FROM poolreserve WHERE LOWER(Status) IN ('rejected', 'reject', 'declined', 'deny', 'denied')
    ) + (
        SELECT COUNT(*) FROM workpermit WHERE LOWER(Status) IN ('rejected', 'reject', 'declined', 'deny', 'denied')
    ) AS Count";

    $result = $conn->query($sql);
    $counts = [];

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $counts[$row['Status']] = $row['Count'];
        }
    }

    return $counts;
}

/**
 * Get detailed breakdown of status counts by service type
 * 
 * @param object $conn Database connection
 * @return array Multi-dimensional array with counts by service type
 */
function getDetailedStatusCounts($conn) {
    $sql = "
    WITH ServiceCounts AS (
        -- Gatepass counts
        SELECT 
            'Gatepass' AS ServiceType,
            COUNT(CASE WHEN LOWER(Status) = 'pending' THEN 1 END) AS Pending,
            COUNT(CASE WHEN LOWER(Status) IN ('approved', 'approval') THEN 1 END) AS Approved,
            COUNT(CASE WHEN LOWER(Status) IN ('complete', 'completed') THEN 1 END) AS Complete,
            COUNT(CASE WHEN LOWER(Status) IN ('rejected', 'reject', 'declined', 'deny', 'denied') THEN 1 END) AS Rejected
        FROM gatepass
        
        UNION ALL
        
        -- Guest Check In/Out counts
        SELECT 
            'Guest Check In/Out' AS ServiceType,
            COUNT(CASE WHEN LOWER(Status) = 'pending' THEN 1 END) AS Pending,
            COUNT(CASE WHEN LOWER(Status) IN ('approved', 'approval') THEN 1 END) AS Approved,
            COUNT(CASE WHEN LOWER(Status) IN ('complete', 'completed') THEN 1 END) AS Complete,
            COUNT(CASE WHEN LOWER(Status) IN ('rejected', 'reject', 'declined', 'deny', 'denied') THEN 1 END) AS Rejected
        FROM guestcheckinout
        
        UNION ALL
        
        -- Move Out counts
        SELECT 
            'Move Out' AS ServiceType,
            COUNT(CASE WHEN LOWER(Status) = 'pending' THEN 1 END) AS Pending,
            COUNT(CASE WHEN LOWER(Status) IN ('approved', 'approval') THEN 1 END) AS Approved,
            COUNT(CASE WHEN LOWER(Status) IN ('complete', 'completed') THEN 1 END) AS Complete,
            COUNT(CASE WHEN LOWER(Status) IN ('rejected', 'reject', 'declined', 'deny', 'denied') THEN 1 END) AS Rejected
        FROM ownertenantmoveout
        
        UNION ALL
        
        -- Move In counts
        SELECT 
            'Move In' AS ServiceType,
            COUNT(CASE WHEN LOWER(Status) = 'pending' THEN 1 END) AS Pending,
            COUNT(CASE WHEN LOWER(Status) IN ('approved', 'approval') THEN 1 END) AS Approved,
            COUNT(CASE WHEN LOWER(Status) IN ('complete', 'completed') THEN 1 END) AS Complete,
            COUNT(CASE WHEN LOWER(Status) IN ('rejected', 'reject', 'declined', 'deny', 'denied') THEN 1 END) AS Rejected
        FROM ownertenantmovein
        
        UNION ALL
        
        -- Reservation counts
        SELECT 
            'Reservation' AS ServiceType,
            COUNT(CASE WHEN LOWER(status) = 'pending' THEN 1 END) AS Pending,
            COUNT(CASE WHEN LOWER(status) IN ('approved', 'approval') THEN 1 END) AS Approved,
            COUNT(CASE WHEN LOWER(status) IN ('complete', 'completed') THEN 1 END) AS Complete,
            COUNT(CASE WHEN LOWER(status) IN ('rejected', 'reject', 'declined', 'deny', 'denied') THEN 1 END) AS Rejected
        FROM ownertenantreservation
        
        UNION ALL
        
        -- Visitor counts
        SELECT 
            'Visitor' AS ServiceType,
            COUNT(CASE WHEN LOWER(Status) = 'pending' THEN 1 END) AS Pending,
            COUNT(CASE WHEN LOWER(Status) IN ('approved', 'approval') THEN 1 END) AS Approved,
            COUNT(CASE WHEN LOWER(Status) IN ('complete', 'completed') THEN 1 END) AS Complete,
            COUNT(CASE WHEN LOWER(Status) IN ('rejected', 'reject', 'declined', 'deny', 'denied') THEN 1 END) AS Rejected
        FROM ownertenantvisitor
        
        UNION ALL
        
        -- Pets counts
        SELECT 
            'Pets' AS ServiceType,
            COUNT(CASE WHEN LOWER(Status) = 'pending' THEN 1 END) AS Pending,
            COUNT(CASE WHEN LOWER(Status) IN ('approved', 'approval') THEN 1 END) AS Approved,
            COUNT(CASE WHEN LOWER(Status) IN ('complete', 'completed') THEN 1 END) AS Complete,
            COUNT(CASE WHEN LOWER(Status) IN ('rejected', 'reject', 'declined', 'deny', 'denied') THEN 1 END) AS Rejected
        FROM pets
        
        UNION ALL
        
        -- Pool Reservation counts
        SELECT 
            'Pool Reservation' AS ServiceType,
            COUNT(CASE WHEN LOWER(Status) = 'pending' THEN 1 END) AS Pending,
            COUNT(CASE WHEN LOWER(Status) IN ('approved', 'approval') THEN 1 END) AS Approved,
            COUNT(CASE WHEN LOWER(Status) IN ('complete', 'completed') THEN 1 END) AS Complete,
            COUNT(CASE WHEN LOWER(Status) IN ('rejected', 'reject', 'declined', 'deny', 'denied') THEN 1 END) AS Rejected
        FROM poolreserve
        
        UNION ALL
        
        -- Work Permit counts
        SELECT 
            'Work Permit' AS ServiceType,
            COUNT(CASE WHEN LOWER(status) = 'pending' THEN 1 END) AS Pending,
            COUNT(CASE WHEN LOWER(status) IN ('approved', 'approval') THEN 1 END) AS Approved,
            COUNT(CASE WHEN LOWER(status) IN ('complete', 'completed') THEN 1 END) AS Complete,
            COUNT(CASE WHEN LOWER(status) IN ('rejected', 'reject', 'declined', 'deny', 'denied') THEN 1 END) AS Rejected
        FROM workpermit
    )
    
    -- Get detailed breakdown by service type
    SELECT 
        ServiceType,
        Pending,
        Approved,
        Complete,
        Rejected,
        (Pending + Approved + Complete + Rejected) AS Total
    FROM ServiceCounts
    
    UNION ALL
    
    -- Get overall totals
    SELECT 
        'TOTAL' AS ServiceType,
        SUM(Pending) AS Pending,
        SUM(Approved) AS Approved,
        SUM(Complete) AS Complete,
        SUM(Rejected) AS Rejected,
        SUM(Pending + Approved + Complete + Rejected) AS Total
    FROM ServiceCounts";

    $result = $conn->query($sql);
    $detailedCounts = [];

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $detailedCounts[] = $row;
        }
    }

    return $detailedCounts;
}
?>
