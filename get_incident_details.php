<?php
include('dbconn.php');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $conn = new mysqli($servername, $db_username, $db_password, $dbname);
    
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
    }
    
    // Get incident details and responses
    $sql = "SELECT c.*, 
            r.response_text, r.response_status, r.response_date
            FROM ownertenantconcerns c
            LEFT JOIN concern_responses r ON c.ID = r.concern_id
            WHERE c.ID = ?
            ORDER BY r.response_date DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $incident = null;
    $responses = [];
    
    while ($row = $result->fetch_assoc()) {
        if (!$incident) {
            $incident = [
                'ID' => $row['ID'],
                'concern_type' => $row['concern_type'],
                'concern_details' => $row['concern_details'],
                'status' => $row['status'],
                'concern_status' => $row['concern_status'],
                'submitted_at' => $row['submitted_at'],
                'media_path' => $row['media_path']
            ];
        }
        
        if ($row['response_text']) {
            $responses[] = [
                'response_text' => $row['response_text'],
                'response_status' => $row['response_status'],
                'response_date' => $row['response_date']
            ];
        }
    }
    
    echo json_encode([
        'incident' => $incident,
        'response_history' => $responses
    ]);
    
    $conn->close();
} else {
    echo json_encode(['error' => 'No incident ID provided']);
}
?>
