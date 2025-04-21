<?php
include 'dbconn.php';

// Get guest ID from request
if(isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare(
        "SELECT g.*, o.Tower, o.Unit_Number, o.Last_Name, o.First_Name, o.Middle_Name, o.Mobile_Number, o.Email 
         FROM guestcheckinout g
         JOIN ownerinformation o ON g.user_id = o.ID
         WHERE g.id = ?"
    );
    
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Format dates
            $checkin_date = date("M d, Y", strtotime($row['Checkin_Date']));
            $checkout_date = !empty($row['Checkout_Date']) ? date("M d, Y", strtotime($row['Checkout_Date'])) : null;
            $created_at = date("M d, Y h:i A", strtotime($row['Created_At']));
            
            // Parse the Guest_Info JSON
            $guest_info = json_decode($row['Guest_Info'], true);
            
            // Handle BLOB data for Valid ID and Vaccine Card
            $valid_id_data = null;
            if (!empty($row['Valid_ID'])) {
                // Attempt to detect if it's a file path or BLOB data
                if (is_string($row['Valid_ID']) && (strpos($row['Valid_ID'], '.jpg') !== false || 
                    strpos($row['Valid_ID'], '.png') !== false || 
                    strpos($row['Valid_ID'], '.pdf') !== false)) {
                    // It's a file path
                    $valid_id_data = $row['Valid_ID'];
                } else {
                    // It's a BLOB - encode as base64 with data URI
                    $valid_id_data = 'data:image/jpeg;base64,' . base64_encode($row['Valid_ID']);
                }
            }
            
            $vaccine_card_data = null;
            if (!empty($row['Vaccine_Card'])) {
                // Attempt to detect if it's a file path or BLOB data
                if (is_string($row['Vaccine_Card']) && (strpos($row['Vaccine_Card'], '.jpg') !== false || 
                    strpos($row['Vaccine_Card'], '.png') !== false || 
                    strpos($row['Vaccine_Card'], '.pdf') !== false)) {
                    // It's a file path
                    $vaccine_card_data = $row['Vaccine_Card'];
                } else {
                    // It's a BLOB - encode as base64 with data URI
                    $vaccine_card_data = 'data:image/jpeg;base64,' . base64_encode($row['Vaccine_Card']);
                }
            }
            
            // Prepare response data
            $response = [
                'guest_name' => $row['First_Name'] . ' ' . $row['Last_Name'],
                'resident_code' => $row['Resident_Code'],
                'user_type' => $row['User_Type'],
                'checkin_date' => $checkin_date,
                'checkout_date' => $checkout_date,
                'days_of_stay' => $row['Days_Of_Stay'],
                'unit_type' => $row['Unit_Type'],
                'guest_info' => $guest_info,
                'valid_id' => $valid_id_data,
                'vaccine_card' => $vaccine_card_data,
                'is_valid_id_blob' => !is_string($row['Valid_ID']) || (strpos($row['Valid_ID'], '.jpg') === false && 
                                    strpos($row['Valid_ID'], '.png') === false && 
                                    strpos($row['Valid_ID'], '.pdf') === false),
                'is_vaccine_card_blob' => !is_string($row['Vaccine_Card']) || (strpos($row['Vaccine_Card'], '.jpg') === false && 
                                        strpos($row['Vaccine_Card'], '.png') === false && 
                                        strpos($row['Vaccine_Card'], '.pdf') === false),
                'status' => $row['Status'],
                'created_at' => $created_at,
                'owner_name' => $row['First_Name'] . ' ' . $row['Middle_Name'] . ' ' . $row['Last_Name'],
                'tower' => $row['Tower'],
                'unit_number' => $row['Unit_Number'],
                'mobile_number' => $row['Mobile_Number'],
                'email' => $row['Email']
            ];
            
            echo json_encode($response);
        } else {
            echo json_encode(['error' => 'Guest record not found']);
        }
        
        $stmt->close();
    } else {
        echo json_encode(['error' => 'Failed to prepare statement: ' . $conn->error]);
    }
} else {
    echo json_encode(['error' => 'Invalid request. Guest ID is required.']);
}

$conn->close();
?>
