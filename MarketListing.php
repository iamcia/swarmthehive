<?php
session_start();

$servername = "localhost";
$db_username = "u113232969_Hives"; 
$db_password = "theSwarm4"; 
$dbname = "u113232969_SWARM";
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle new listing submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['item_title'])) {
    $item_title = $_POST['item_title'];
    $description = $_POST['description'];
    $price_range = $_POST['price_range'];
    $category = $_POST['category'];
    
    // Handle image upload
    $image_filename = null;
    if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
        // Ensure the directory exists
        $target_dir = "MarketID/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Generate a unique filename to avoid conflicts
        $image_filename = uniqid() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_filename;

        // Move the uploaded file to the target directory
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            echo "<script>alert('Error: Could not upload image.');</script>";
            $image_filename = null; // Reset if upload fails
        }
    }

    // Insert new listing into the database
    $stmt = $conn->prepare("INSERT INTO listings (item_title, description, price_range, category, image, Status) VALUES (?, ?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("ssdss", $item_title, $description, $price_range, $category, $image_filename);
    
    if ($stmt->execute()) {
        echo "<script>alert('New listing added successfully!');</script>";
    } else {
        echo "<script>alert('Error: Could not add listing.');</script>";
    }
    
    $stmt->close();
}

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // Delete the associated image file
    $stmt = $conn->prepare("SELECT image FROM listings WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->bind_result($image_filename);
    $stmt->fetch();
    $stmt->close();
    if ($image_filename && file_exists("MarketID/" . $image_filename)) {
        unlink("MarketID/" . $image_filename);
    }

    // Delete the listing record
    $stmt = $conn->prepare("DELETE FROM listings WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: MarketListing.php");
    exit();
}

// Handle update request
if (isset($_POST['update_id']) && isset($_POST['status'])) {
    $update_id = $_POST['update_id'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE listings SET Status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $update_id);
    $stmt->execute();
    $stmt->close();
    header("Location: MarketListing.php");
    exit();
}

// Fetch all listings from the listings table
$sql = "SELECT id, item_title, description, price_range, category, image, Status, created_at FROM listings";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Market Listings</title>
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid black; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        form { margin-top: 20px; }
        input, select, textarea { width: 100%; padding: 8px; margin: 5px 0; }
        .submit-button { background-color: green; color: white; padding: 10px; border: none; cursor: pointer; }
        .submit-button:hover { background-color: darkgreen; }
        .image-preview { width: 50px; height: auto; }
    </style>
</head>
<body>

<h2>Market Listings</h2>

<!-- Display Listings Table -->
<?php
if ($result->num_rows > 0) {
    echo "<table>
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Item Title</th>
                <th>Description</th>
                <th>Price Range (₱)</th>
                <th>Category</th>
                <th>Created At</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>";
    
    // Output data of each row
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($row['id']) . "</td>
                <td>";
               if ($row['image']) {
    // Load the image file and encode it in base64
    $imagePath = $row['image'];
    if (file_exists($imagePath)) {
        $imageData = file_get_contents($imagePath);
        $base64Image = 'data:image/' . pathinfo($imagePath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($imageData);
        echo "<img src='$base64Image' class='image-preview'>";
    } else {
        echo "Image not found";
    }
} else {
    echo "No image";
}
                echo "</td>
                <td>" . htmlspecialchars($row['item_title']) . "</td>
                <td>" . htmlspecialchars($row['description']) . "</td>
                <td>₱" . htmlspecialchars($row['price_range']) . "</td>
                <td>" . htmlspecialchars($row['category']) . "</td>
                <td>" . htmlspecialchars($row['created_at']) . "</td>
                <td>" . htmlspecialchars($row['Status']) . "</td>
                <td>
                    <a href='MarketListing.php?delete_id=" . $row['id'] . "' onclick=\"return confirm('Are you sure you want to delete this listing?');\">Delete</a>
                    <form action='MarketListing.php' method='post' style='display:inline;'>
                        <input type='hidden' name='update_id' value='" . $row['id'] . "'>
                        <select name='status'>
                            <option value='Pending' " . ($row['Status'] == 'Pending' ? 'selected' : '') . ">Pending</option>
                            <option value='Approved' " . ($row['Status'] == 'Approved' ? 'selected' : '') . ">Approved</option>
                            <option value='Disapproved' " . ($row['Status'] == 'Disapproved' ? 'selected' : '') . ">Disapproved</option>
                        </select>
                        <button type='submit'>Update</button>
                    </form>
                </td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "No listings available.";
}
?>
</body>
</html>

<?php
$conn->close();
?>