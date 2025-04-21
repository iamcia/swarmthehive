<?php
include('dbconn.php');
session_start();

// Set PHP default timezone
date_default_timezone_set('Asia/Manila');

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="alert alert-danger">Invalid request: Missing feedback ID</div>';
    exit;
}

// Sanitize the input
$feedbackId = intval($_GET['id']);

try {
    // Connect to the database
    $conn = new mysqli($servername, $db_username, $db_password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Prepare SQL to get feedback details
    $sql = "SELECT f.*, 
            CONCAT(o.First_Name, ' ', o.Last_Name) as owner_name,
            o.Email as owner_email,
            o.Contact_Number as owner_contact,
            o.Unit_Number as unit_number,
            t.Tenant_ID as tenant_id,
            CONCAT(t.First_Name, ' ', t.Last_Name) as tenant_name
            FROM feedback f
            LEFT JOIN ownerinformation o ON f.user_id = o.ID
            LEFT JOIN tenantinformation t ON f.user_id = t.owner_id
            WHERE f.ID = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param("i", $feedbackId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $feedback = $result->fetch_assoc();
        ?>
        <div class="row">
            <div class="col-md-6">
                <h5>Incident Information</h5>
                <table class="table">
                    <tr>
                        <th>ID:</th>
                        <td><?php echo htmlspecialchars($feedback['ID']); ?></td>
                    </tr>
                    <tr>
                        <th>Category:</th>
                        <td><?php echo htmlspecialchars($feedback['concern_category']); ?></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <span class="badge <?php 
                                switch($feedback['status']) {
                                    case 'Open': echo 'bg-warning'; break;
                                    case 'In Progress': echo 'bg-info'; break;
                                    case 'Resolved': echo 'bg-success'; break;
                                    case 'Closed': echo 'bg-secondary'; break;
                                    default: echo 'bg-primary';
                                }
                            ?>">
                                <?php echo htmlspecialchars($feedback['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Concern Status:</th>
                        <td><?php echo htmlspecialchars($feedback['concern_status']); ?></td>
                    </tr>
                    <tr>
                        <th>Submitted On:</th>
                        <td><?php echo date('F d, Y g:i A', strtotime($feedback['Created_At'])); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5>Submitter Information</h5>
                <table class="table">
                    <tr>
                        <th>Unit Number:</th>
                        <td><?php echo htmlspecialchars($feedback['unit_number'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Owner:</th>
                        <td><?php echo htmlspecialchars($feedback['owner_name'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Owner Email:</th>
                        <td><?php echo htmlspecialchars($feedback['owner_email'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Owner Contact:</th>
                        <td><?php echo htmlspecialchars($feedback['owner_contact'] ?? 'N/A'); ?></td>
                    </tr>
                    <?php if (!empty($feedback['tenant_id'])) { ?>
                    <tr>
                        <th>Tenant ID:</th>
                        <td><?php echo htmlspecialchars($feedback['tenant_id']); ?></td>
                    </tr>
                    <tr>
                        <th>Tenant Name:</th>
                        <td><?php echo htmlspecialchars($feedback['tenant_name'] ?? 'N/A'); ?></td>
                    </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12">
                <h5>Incident Details</h5>
                <div class="card">
                    <div class="card-body">
                        <?php echo nl2br(htmlspecialchars($feedback['concern_details'])); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($feedback['concern_media'])) { ?>
        <div class="row mt-3">
            <div class="col-12">
                <h5>Attached Media</h5>
                <div class="card">
                    <div class="card-body text-center">
                        <img src="<?php echo htmlspecialchars($feedback['concern_media']); ?>" 
                             class="img-fluid" style="max-height: 300px;">
                        <div class="mt-2">
                            <a href="<?php echo htmlspecialchars($feedback['concern_media']); ?>" 
                               class="btn btn-sm btn-outline-primary" target="_blank">
                                View Full Size
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
        
        <?php
    } else {
        echo '<div class="alert alert-warning">Feedback record not found</div>';
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    
    if (isset($conn)) {
        $conn->close();
    }
}
?>
