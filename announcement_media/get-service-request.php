<?php
include 'dbconn.php';

// Get request parameters
$id = isset($_GET['id']) ? $_GET['id'] : null;
$type = isset($_GET['type']) ? $_GET['type'] : null;

// Validate input
if(empty($id) || empty($type)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Sanitize input to prevent SQL injection
$id = mysqli_real_escape_string($conn, $id);
$type = mysqli_real_escape_string($conn, $type);

// Prepare the response array
$response = ['success' => false, 'message' => '', 'request' => null];

// Get service ID from servicerequests table
$service_query = "SELECT sr.service_id, sr.service_type, sr.reject_reason FROM servicerequests sr WHERE sr.id = '$id'";
$service_result = mysqli_query($conn, $service_query);

if(!$service_result || mysqli_num_rows($service_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Service request not found']);
    exit;
}

$service_row = mysqli_fetch_assoc($service_result);
$service_id = $service_row['service_id'];
$service_type = $service_row['service_type'];
$reject_reason = $service_row['reject_reason'];

// Query based on service type
if($service_type == 'gatepass') {
    $query = "SELECT gp.*, sr.id as SR_ID, sr.reject_reason 
              FROM gatepass gp 
              JOIN servicerequests sr ON gp.Ticket_No = sr.service_id 
              WHERE sr.id = '$id' AND sr.service_type = 'gatepass'";
} 
else if($service_type == 'poolreserve') {
    $query = "SELECT pr.*, sr.id as SR_ID, sr.reject_reason 
              FROM poolreserve pr 
              JOIN servicerequests sr ON pr.id = sr.service_id 
              WHERE sr.id = '$id' AND sr.service_type = 'poolreserve'";
}
else if($service_type == 'MoveIn') {
    $query = "SELECT mi.id as Ticket_No, mi.Resident_Code, mi.currentDate as Date, mi.Resident_Name as Bearer, 
              'Resident' as User_Type, 'Move-In Notice' as Authorization, 
              mi.parkingSlotNumber, mi.leaseExpiryDate, mi.representativeName, mi.Resident_Contact, 
              mi.Signature, mi.Status, IFNULL(mi.created_at, NOW()) as Created_At, 
              CONCAT('{\"parkingSlot\":\"', mi.parkingSlotNumber, '\",\"leaseExpiry\":\"', mi.leaseExpiryDate, '\"}') as Items,
              sr.id as SR_ID, sr.service_type as ServiceType, sr.reject_reason
              FROM ownertenantmovein mi 
              JOIN servicerequests sr ON mi.id = sr.service_id 
              WHERE sr.id = '$id' AND sr.service_type = 'MoveIn'";
}
else if($service_type == 'MoveOut') {
    $query = "SELECT mo.moveoutID as Ticket_No, mo.Resident_Code, '' as Date, mo.Resident_Name as Bearer, 
              'Resident' as User_Type, 'Move-Out Notice' as Authorization, 
              mo.parkingSlotNumber, mo.days_prior_moveout, mo.representativeName, mo.Resident_Contact, 
              mo.Signature, mo.Status, IFNULL(mo.created_at, NOW()) as Created_At, 
              CONCAT('{\"parkingSlot\":\"', mo.parkingSlotNumber, '\",\"daysPriorMoveout\":\"', mo.days_prior_moveout, '\"}') as Items,
              sr.id as SR_ID, sr.service_type as ServiceType, sr.reject_reason
              FROM ownertenantmoveout mo 
              JOIN servicerequests sr ON mo.moveoutID = sr.service_id 
              WHERE sr.id = '$id' AND sr.service_type = 'MoveOut'";
}
else if($service_type == 'GuestCheckIn') {
    $query = "SELECT gc.id as Ticket_No, gc.Resident_Code, gc.Checkin_Date as Date, gc.Checkout_Date,
              gc.Days_Of_Stay, gc.Unit_Type, gc.Guest_Info, gc.Valid_ID, gc.Vaccine_Card,  
              'Resident' as User_Type, 'Guest Check In/Out' as Authorization, 
              CONCAT(JSON_UNQUOTE(JSON_EXTRACT(gc.Guest_Info, '$[0].name'))) as Bearer,
              CONCAT('{\"checkinDate\":\"', gc.Checkin_Date, '\",\"checkoutDate\":\"', gc.Checkout_Date, 
                '\",\"daysOfStay\":\"', gc.Days_Of_Stay, '\",\"unitType\":\"', gc.Unit_Type, 
                '\",\"guestInfo\":', gc.Guest_Info, '}') as Items,
              gc.Status, IFNULL(gc.Created_At, NOW()) as Created_At,
              sr.id as SR_ID, sr.service_type as ServiceType, sr.reject_reason
              FROM guestcheckinout gc
              JOIN servicerequests sr ON gc.id = sr.service_id 
              WHERE sr.id = '$id' AND sr.service_type = 'GuestCheckIn'";
}
else if($service_type == 'PetRegistration') {
    $query = "SELECT p.id as Ticket_No, p.Resident_Code, p.user_type as User_Type,
              p.pet_name as Bearer, p.dob as Date, p.breed, p.vaccinated, p.owner_name, p.unit_no,
              p.contact, p.email, p.pet_pic, p.vaccine_card, p.vaccine_duration,
              p.remarks, p.user_signature,
              'Pet Registration' as Authorization,
              CONCAT('{\"petName\":\"', p.pet_name, '\",\"breed\":\"', p.breed, 
                '\",\"vaccinated\":\"', p.vaccinated, '\",\"owner\":\"', p.owner_name, 
                '\",\"unitNo\":\"', p.unit_no, '\"}') as Items, 
              p.Status, IFNULL(p.created_at, NOW()) as Created_At,
              sr.id as SR_ID, sr.service_type as ServiceType, sr.reject_reason
              FROM pets p
              JOIN servicerequests sr ON p.id = sr.service_id 
              WHERE sr.id = '$id' AND sr.service_type = 'PetRegistration'";
}
else if($service_type == 'VisitorPass') {
    $query = "SELECT vp.id as Ticket_No, vp.Resident_Code, vp.user_type as User_Type,
              vp.start_date as Date, vp.end_date as End_Date, 
              CONCAT(JSON_UNQUOTE(JSON_EXTRACT(vp.guest_info, '$[0].name'))) as Bearer,
              vp.valid_id, vp.signature, vp.guest_info,
              'Visitor Pass' as Authorization,
              CONCAT('{\"startDate\":\"', vp.start_date, '\",\"endDate\":\"', vp.end_date, 
                '\",\"guestInfo\":', vp.guest_info, '}') as Items, 
              vp.Status, vp.submitted_at as Created_At,
              sr.id as SR_ID, sr.service_type as ServiceType, sr.reject_reason
              FROM ownertenantvisitor vp
              JOIN servicerequests sr ON vp.id = sr.service_id 
              WHERE sr.id = '$id' AND sr.service_type = 'VisitorPass'";
}
else if($service_type == 'WorkPermit') {
    $query = "SELECT wp.id as Ticket_No, wp.Resident_Code, wp.user_type as User_Type,
              wp.period_from as Date, wp.period_to as End_Date, 
              wp.owner_name as Bearer,
              wp.authorize_rep, wp.contractor, wp.work_type, 
              wp.task_details, wp.personnel_details, wp.signature,
              'Work Permit' as Authorization,
              CONCAT('{\"workType\":\"', wp.work_type, '\",\"periodFrom\":\"', wp.period_from, 
                '\",\"periodTo\":\"', wp.period_to, '\",\"taskDetails\":\"', 
                REPLACE(wp.task_details, '\"', '\\\\\"'), '\"}') as Items, 
              wp.status as Status, wp.submitted_at as Created_At,
              sr.id as SR_ID, sr.service_type as ServiceType, sr.reject_reason
              FROM workpermit wp
              JOIN servicerequests sr ON wp.id = sr.service_id 
              WHERE sr.id = '$id' AND sr.service_type = 'WorkPermit'";
}
else if($service_type == 'AmenityReservation') {
    $query = "SELECT ar.id as Ticket_No, ar.Resident_Code, ar.user_type as User_Type,
              ar.reservation_date as Date, ar.reservation_time as Time,
              ar.amenity as Bearer, '' as Authorization,
              CONCAT('{\"amenity\":\"', ar.amenity, '\",\"date\":\"', ar.reservation_date,
                '\",\"time\":\"', ar.reservation_time, '\",\"people\":', ar.number_of_people,
                ',\"additionalRequest\":\"', REPLACE(ar.additional_request, '\"', '\\\\\"'), '\"}') as Items,
              ar.status as Status, ar.reservation_created_at as Created_At,
              sr.id as SR_ID, sr.service_type as ServiceType, sr.reject_reason
              FROM ownertenantreservation ar
              JOIN servicerequests sr ON ar.id = sr.service_id
              WHERE sr.id = '$id' AND sr.service_type = 'AmenityReservation'";
}
else {
    echo json_encode(['success' => false, 'message' => 'Unsupported service type']);
    exit;
}

$result = mysqli_query($conn, $query);

if(!$result || mysqli_num_rows($result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Request details not found']);
    exit;
}

// Get the request details
$request = mysqli_fetch_assoc($result);
$request['reject_reason'] = $reject_reason;

// Format the response
$response['success'] = true;
$response['request'] = $request;

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>