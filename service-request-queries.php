<?php
/**
 * Service Request Queries
 * This file contains all database queries related to service requests
 */

/**
 * Get all service requests with optional filtering
 * 
 * @param mysqli $conn Database connection
 * @param string $serviceType Filter by service type
 * @param string $status Filter by status
 * @param string $dateFrom Filter by date from
 * @param string $dateTo Filter by date to
 * @param string $search Search term for name, unit, id
 * @return array Array of service requests with details
 */
function getServiceRequests($conn, $serviceType = '', $status = '', $dateFrom = '', $dateTo = '', $search = '') {
    // Initialize arrays for service requests and statistics
    $serviceRequests = [];
    $statistics = [
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'completed' => 0,
        'rejected' => 0
    ];
    
    // Build the base query
    $query = "
        SELECT 
            sr.id AS request_id,
            sr.service_id,
            sr.service_type,
            sr.user_id,
            sr.reject_reason,
            oi.First_Name,
            oi.Last_Name,
            oi.Tower,
            oi.Unit_Number,
            oi.Mobile_Number,
            oi.Email
        FROM 
            servicerequests sr
        JOIN 
            ownerinformation oi ON sr.user_id = oi.ID
        WHERE 1=1
    ";
     
    // Add filters (except status, which will be filtered after)
    if (!empty($serviceType)) {
        $query .= " AND sr.service_type = '$serviceType'";
    }
    
    if (!empty($search)) {
        $query .= " AND (oi.First_Name LIKE '%$search%' OR oi.Last_Name LIKE '%$search%' OR 
                   oi.Unit_Number LIKE '%$search%' OR sr.id LIKE '%$search%')";
    }
    
    // Execute the main query
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        while($row = mysqli_fetch_assoc($result)) {
            // Get detailed information based on service type
            $row = getServiceDetails($conn, $row);
            
            // Apply status filtering if specified
            if (!empty($status) && (
                !isset($row['normalized_status']) || 
                strtolower($row['normalized_status']) != strtolower($status)
            )) {
                // Skip this record if status doesn't match
                continue;
            }
            
            // Apply date filtering if specified
            if (!empty($dateFrom) && !empty($dateTo) && isset($row['created_at'])) {
                $createdDate = new DateTime($row['created_at']);
                $fromDate = new DateTime($dateFrom);
                $toDate = new DateTime($dateTo);
                $toDate->modify('+1 day'); // Include the end date
                
                if ($createdDate < $fromDate || $createdDate > $toDate) {
                    // Skip this record if outside date range
                    continue;
                }
            }
            
            // Update status statistics
            $statistics['total']++;
            if (isset($row['status'])) {
                $rowStatus = strtolower($row['status']);
                if ($rowStatus == 'pending') {
                    $statistics['pending']++;
                } else if ($rowStatus == 'approval' || $rowStatus == 'approved' || $rowStatus == 'approve') {
                    $statistics['approved']++;
                } else if ($rowStatus == 'completed' || $rowStatus == 'complete') {
                    $statistics['completed']++;
                } else if ($rowStatus == 'rejected' || $rowStatus == 'reject') {
                    $statistics['rejected']++;
                }
            }
            
            $serviceRequests[] = $row;
        }
    }
    
    return ['requests' => $serviceRequests, 'stats' => $statistics];
}

/**
 * Get detailed information for a specific service request
 * 
 * @param mysqli $conn Database connection
 * @param array $row Base service request data
 * @return array Enhanced service request data with details
 */
function getServiceDetails($conn, $row) {
    switch ($row['service_type']) {
        case 'MoveIn':
            $detailsQuery = "SELECT * FROM ownertenantmovein WHERE id = " . $row['service_id'];
            $detailsResult = mysqli_query($conn, $detailsQuery);
            
            if ($detailsResult && mysqli_num_rows($detailsResult) > 0) {
                $detailsData = mysqli_fetch_assoc($detailsResult);
                $row['status'] = $detailsData['Status'];
                $row['created_at'] = $detailsData['Created_At'];
                // Add more fields specific to MoveIn
                $row['move_in_date'] = $detailsData['MoveInDate'];
                $row['resident_name'] = $detailsData['Resident_Name'];
                $row['parking_slot'] = $detailsData['parkingSlotNumber'];
                $row['lease_expiry'] = $detailsData['leaseExpiryDate'];
                $row['lease_contract'] = $detailsData['lease_contract'];
            }
            break;
            
        case 'MoveOut':
            $detailsQuery = "SELECT * FROM ownertenantmoveout WHERE moveoutID = " . $row['service_id'];
            $detailsResult = mysqli_query($conn, $detailsQuery);
            
            if ($detailsResult && mysqli_num_rows($detailsResult) > 0) {
                $detailsData = mysqli_fetch_assoc($detailsResult);
                $row['status'] = $detailsData['Status'];
                $row['created_at'] = $detailsData['Created_At'];
                $row['resident_name'] = $detailsData['Resident_Name'];
                $row['days_prior_moveout'] = $detailsData['days_prior_moveout'];
            }
            break;
            
        case 'GuestCheckIn':
            $detailsQuery = "SELECT * FROM guestcheckinout WHERE id = " . $row['service_id'];
            $detailsResult = mysqli_query($conn, $detailsQuery);
            
            if ($detailsResult && mysqli_num_rows($detailsResult) > 0) {
                $detailsData = mysqli_fetch_assoc($detailsResult);
                $row['status'] = $detailsData['Status'];
                $row['created_at'] = $detailsData['Created_At'];
                $row['checkin_date'] = $detailsData['Checkin_Date'];
                $row['checkout_date'] = $detailsData['Checkout_Date'];
                $row['guest_info'] = $detailsData['Guest_Info'];
            }
            break;
            
        case 'PetRegistration':
            $detailsQuery = "SELECT * FROM pets WHERE id = " . $row['service_id'];
            $detailsResult = mysqli_query($conn, $detailsQuery);
            
            if ($detailsResult && mysqli_num_rows($detailsResult) > 0) {
                $detailsData = mysqli_fetch_assoc($detailsResult);
                $row['status'] = $detailsData['Status'];
                $row['created_at'] = $detailsData['created_at'];
                $row['pet_name'] = $detailsData['pet_name'];
                $row['breed'] = $detailsData['breed'];
            }
            break;
            
        case 'VisitorPass':
            $detailsQuery = "SELECT * FROM ownertenantvisitor WHERE id = " . $row['service_id'];
            $detailsResult = mysqli_query($conn, $detailsQuery);
            
            if ($detailsResult && mysqli_num_rows($detailsResult) > 0) {
                $detailsData = mysqli_fetch_assoc($detailsResult);
                $row['status'] = $detailsData['Status'];
                $row['created_at'] = $detailsData['submitted_at'];
                $row['start_date'] = $detailsData['start_date'];
                $row['end_date'] = $detailsData['end_date'];
                $row['guest_info'] = $detailsData['guest_info'];
            }
            break;
            
        case 'WorkPermit':
            $detailsQuery = "SELECT * FROM workpermit WHERE id = " . $row['service_id'];
            $detailsResult = mysqli_query($conn, $detailsQuery);
            
            if ($detailsResult && mysqli_num_rows($detailsResult) > 0) {
                $detailsData = mysqli_fetch_assoc($detailsResult);
                $row['status'] = $detailsData['status'];
                $row['created_at'] = $detailsData['submitted_at'];
                $row['work_type'] = $detailsData['work_type'];
                $row['period_from'] = $detailsData['period_from'];
                $row['period_to'] = $detailsData['period_to'];
            }
            break;
            
        case 'AmenityReservation':
            $detailsQuery = "SELECT * FROM ownertenantreservation WHERE id = " . $row['service_id'];
            $detailsResult = mysqli_query($conn, $detailsQuery);
            
            if ($detailsResult && mysqli_num_rows($detailsResult) > 0) {
                $detailsData = mysqli_fetch_assoc($detailsResult);
                $row['status'] = $detailsData['status'];
                $row['created_at'] = $detailsData['reservation_created_at'];
                $row['amenity'] = $detailsData['amenity'];
                $row['reservation_date'] = $detailsData['reservation_date'];
                $row['reservation_time'] = $detailsData['reservation_time'];
            }
            break;
            
        case 'poolreserve':
            $detailsQuery = "SELECT * FROM poolreserve WHERE id = " . $row['service_id'];
            $detailsResult = mysqli_query($conn, $detailsQuery);
            
            if ($detailsResult && mysqli_num_rows($detailsResult) > 0) {
                $detailsData = mysqli_fetch_assoc($detailsResult);
                $row['status'] = $detailsData['Status'];
                $row['created_at'] = $detailsData['Created_At'];
                $row['names'] = $detailsData['names'];
                $row['schedule'] = $detailsData['schedule'];
            }
            break;
            
        case 'gatepass':
            $detailsQuery = "SELECT * FROM gatepass WHERE Ticket_No = " . $row['service_id'];
            $detailsResult = mysqli_query($conn, $detailsQuery);
            
            if ($detailsResult && mysqli_num_rows($detailsResult) > 0) {
                $detailsData = mysqli_fetch_assoc($detailsResult);
                $row['status'] = $detailsData['Status'];
                $row['created_at'] = $detailsData['Created_At'];
                $row['date'] = $detailsData['Date'];
                $row['time'] = $detailsData['Time'];
                $row['bearer'] = $detailsData['Bearer'];
                $row['items'] = $detailsData['Items'];
                $row['completed_at'] = $detailsData['completed_at'];
            }
            break;
    }
    
    // Add normalized status field for consistent display
    if (isset($row['status'])) {
        $status = strtolower($row['status']);
        if ($status == 'pending') {
            $row['normalized_status'] = 'Pending';
        } else if (in_array($status, ['approval', 'approved', 'approve'])) {
            $row['normalized_status'] = 'Approved';
        } else if (in_array($status, ['completed', 'complete'])) {
            $row['normalized_status'] = 'Completed';
        } else if (in_array($status, ['rejected', 'reject'])) {
            $row['normalized_status'] = 'Rejected';
        } else {
            $row['normalized_status'] = ucfirst($status);
        }
    } else {
        $row['normalized_status'] = 'Pending';
    }
    
    return $row;
}

/**
 * Get detailed information for a specific service request by ID
 * 
 * @param mysqli $conn Database connection
 * @param int $requestId The service request ID
 * @return array Service request details or empty array if not found
 */
function getServiceRequestById($conn, $requestId) {
    $query = "
        SELECT 
            sr.id AS request_id,
            sr.service_id,
            sr.service_type,
            sr.user_id,
            sr.reject_reason,
            oi.First_Name,
            oi.Last_Name,
            oi.Tower,
            oi.Unit_Number,
            oi.Mobile_Number,
            oi.Email
        FROM 
            servicerequests sr
        JOIN 
            ownerinformation oi ON sr.user_id = oi.ID
        WHERE 
            sr.id = $requestId
    ";
    
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return getServiceDetails($conn, $row);
    }
    
    return [];
}

/**
 * Update service request status
 * 
 * @param mysqli $conn Database connection
 * @param int $requestId Service request ID
 * @param string $status New status (Pending, Approved, Completed, Rejected)
 * @param string $rejectReason Reason for rejection (if applicable)
 * @param string $checkerName Name of the checker for gate pass (if applicable)
 * @return bool True if update successful, false otherwise
 */
function updateServiceRequestStatus($conn, $requestId, $status, $rejectReason = '', $checkerName = '') {
    // Normalize status to ensure consistent casing
    $normalizedStatus = '';
    
    switch(strtolower($status)) {
        case 'pending':
            $normalizedStatus = 'Pending';
            break;
        case 'approval':
        case 'approved': // Keep for backward compatibility
        case 'approve':  // Keep for backward compatibility
            $normalizedStatus = 'Approval';
            break;
        case 'complete':
        case 'completed':
            $normalizedStatus = 'Completed';
            break;
        case 'reject':
        case 'rejected':
            $normalizedStatus = 'Rejected';
            break;
        default:
            $normalizedStatus = $status; // Keep as is if not recognized
    }
    
    // First get the service request to determine the service type and ID
    $query = "SELECT service_type, service_id FROM servicerequests WHERE id = $requestId";
    $result = mysqli_query($conn, $query);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        return false;
    }
    
    $serviceRequest = mysqli_fetch_assoc($result);
    $serviceType = $serviceRequest['service_type'];
    $serviceId = $serviceRequest['service_id'];
    
    // Update the status in the corresponding service table
    $updateSuccess = false;
    
    switch ($serviceType) {
        case 'MoveIn':
            $updateQuery = "UPDATE ownertenantmovein SET Status = '$normalizedStatus' WHERE id = $serviceId";
            $updateSuccess = mysqli_query($conn, $updateQuery);
            break;
        case 'MoveOut':
            $updateQuery = "UPDATE ownertenantmoveout SET Status = '$normalizedStatus' WHERE moveoutID = $serviceId";
            $updateSuccess = mysqli_query($conn, $updateQuery);
            break;
        case 'GuestCheckIn':
            $updateQuery = "UPDATE guestcheckinout SET Status = '$normalizedStatus' WHERE id = $serviceId";
            $updateSuccess = mysqli_query($conn, $updateQuery);
            break;
        case 'PetRegistration':
            $updateQuery = "UPDATE pets SET Status = '$normalizedStatus' WHERE id = $serviceId";
            $updateSuccess = mysqli_query($conn, $updateQuery);
            break;
        case 'VisitorPass':
            $updateQuery = "UPDATE ownertenantvisitor SET Status = '$normalizedStatus' WHERE id = $serviceId";
            $updateSuccess = mysqli_query($conn, $updateQuery);
            break;
        case 'WorkPermit':
            $updateQuery = "UPDATE workpermit SET status = '$normalizedStatus' WHERE id = $serviceId";
            $updateSuccess = mysqli_query($conn, $updateQuery);
            break;
        case 'AmenityReservation':
            $updateQuery = "UPDATE ownertenantreservation SET status = '$normalizedStatus' WHERE id = $serviceId";
            $updateSuccess = mysqli_query($conn, $updateQuery);
            break;
        case 'poolreserve':
            $updateQuery = "UPDATE poolreserve SET Status = '$normalizedStatus' WHERE id = $serviceId";
            $updateSuccess = mysqli_query($conn, $updateQuery);
            break;
        case 'gatepass':
            // Special handling for gate pass to include checker name and completion date
            if ($normalizedStatus == 'Completed' && !empty($checkerName)) {
                $checkerNameEscaped = mysqli_real_escape_string($conn, $checkerName);
                $updateQuery = "UPDATE gatepass SET Status = '$normalizedStatus', 
                                checked_by = '$checkerNameEscaped', 
                                completed_at = NOW() 
                                WHERE Ticket_No = $serviceId";
            } else {
                $updateQuery = "UPDATE gatepass SET Status = '$normalizedStatus' WHERE Ticket_No = $serviceId";
            }
            $updateSuccess = mysqli_query($conn, $updateQuery);
            break;
    }
    
    // Update the reject reason if provided
    if ($updateSuccess && $normalizedStatus == 'Rejected' && !empty($rejectReason)) {
        $rejectReasonEscaped = mysqli_real_escape_string($conn, $rejectReason);
        $rejectQuery = "UPDATE servicerequests SET reject_reason = '$rejectReasonEscaped' WHERE id = $requestId";
        mysqli_query($conn, $rejectQuery);
    }
    
    return $updateSuccess;
}

/**
 * Get service type options for dropdown
 * 
 * @return array Array of service type options
 */
function getServiceTypeOptions() {
    return [
        'MoveIn' => 'Move In',
        'MoveOut' => 'Move Out',
        'GuestCheckIn' => 'Guest Check-In',
        'PetRegistration' => 'Pet Registration',
        'VisitorPass' => 'Visitor Pass',
        'WorkPermit' => 'Work Permit',
        'AmenityReservation' => 'Amenity Reservation',
        'poolreserve' => 'Pool Reservation',
        'gatepass' => 'Gate Pass'
    ];
}
?>
