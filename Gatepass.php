<?php
include 'dbconn.php';
session_start();

// Fetch user details if logged in
$residentCode = '';
$userType = '';
$userEmail = '';
$status = 'Approval';

if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];

    // Fetch user information from ownerinformation or tenantinformation tables
    $sql = "SELECT Owner_ID, Email, Status FROM ownerinformation WHERE Username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $residentCode = $row['Owner_ID'];
        $userType = 'Owner';
        $userEmail = $row['Email'];
        $status = $row['Status'];
    } else {
        // Check tenantinformation if no owner match found
        $sql = "SELECT Tenant_ID, Email, Status FROM tenantinformation WHERE Username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $residentCode = $row['Tenant_ID'];
            $userType = 'Tenant';
            $userEmail = $row['Email'];
            $status = $row['Status'];
        }
    }
    $stmt->close();
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bearer = $_POST['bearer'];
    $authorization = $_POST['authorization'];
    $date_effective = $_POST['date_effective'];
    $time_effective = $_POST['time_effective'];
    $itemsArray = [];

    // Process each item row
    foreach ($_POST['items'] as $index => $item) {
        $itemNo = htmlspecialchars($item['item_no'], ENT_QUOTES, 'UTF-8');
        $quantity = (int) $item['quantity'];
        $unit = htmlspecialchars($item['unit'], ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8');

        $itemImages = [];
        if (isset($_FILES['items']['name'][$index]['item_pics'])) {
            foreach ($_FILES['items']['name'][$index]['item_pics'] as $fileIndex => $fileName) {
                if ($_FILES['items']['size'][$index]['item_pics'][$fileIndex] > 0) {
                    $targetDir = "GateItem/";
                    $newFileName = uniqid() . "_" . basename($fileName);
                    $targetFilePath = $targetDir . $newFileName;

                    if (move_uploaded_file($_FILES['items']['tmp_name'][$index]['item_pics'][$fileIndex], $targetFilePath)) {
                        $itemImages[] = $newFileName; // Store only the filename, not the full path
                    }
                }
            }
        }

        $itemsArray[] = [
            "item_no" => $itemNo,
            "quantity" => $quantity,
            "unit" => $unit,
            "description" => $description,
            "item_pics" => $itemImages
        ];
    }

    // Convert items to JSON
    $itemsJson = json_encode($itemsArray);
    
    // Get the actual user ID (primary key) from ownerinformation table
    $user_id = null;
    if ($userType == 'Owner') {
        // For owners, get ID directly
        $ownerQuery = "SELECT ID FROM ownerinformation WHERE Owner_ID = ?";
        $stmt_owner = $conn->prepare($ownerQuery);
        $stmt_owner->bind_param("s", $residentCode);
        $stmt_owner->execute();
        $owner_result = $stmt_owner->get_result();
        
        if ($owner_result->num_rows > 0) {
            $owner_row = $owner_result->fetch_assoc();
            $user_id = $owner_row['ID'];
        }
        $stmt_owner->close();
    } else if ($userType == 'Tenant') {
        // For tenants, get the owner ID associated with them
        $ownerQuery = "SELECT o.ID FROM ownerinformation o 
                       INNER JOIN tenantinformation t ON o.Owner_ID = t.Owner_ID 
                       WHERE t.Tenant_ID = ?";
        $stmt_owner = $conn->prepare($ownerQuery);
        $stmt_owner->bind_param("s", $residentCode);
        $stmt_owner->execute();
        $owner_result = $stmt_owner->get_result();
        
        if ($owner_result->num_rows > 0) {
            $owner_row = $owner_result->fetch_assoc();
            $user_id = $owner_row['ID'];
        }
        $stmt_owner->close();
    }
    
    // If no user_id found, try direct lookup by username
    if ($user_id === null) {
        $directQuery = "SELECT ID FROM ownerinformation WHERE Username = ?";
        $stmt_direct = $conn->prepare($directQuery);
        $stmt_direct->bind_param("s", $username);
        $stmt_direct->execute();
        $direct_result = $stmt_direct->get_result();
        
        if ($direct_result->num_rows > 0) {
            $direct_row = $direct_result->fetch_assoc();
            $user_id = $direct_row['ID'];
        }
        $stmt_direct->close();
    }
    
    // Only proceed if we have a valid user_id
    if ($user_id !== null) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert into gatepass table first
            $stmt = $conn->prepare("INSERT INTO gatepass (Resident_Code, User_Type, Date, Time, Bearer, Authorization, Items, Status, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, 'Approval', ?)");
            $stmt->bind_param("sssssssi", $residentCode, $userType, $date_effective, $time_effective, $bearer, $authorization, $itemsJson, $user_id);
            
            $result1 = $stmt->execute();
            
            // Get the auto-generated ticket number
            $ticket_no = $conn->insert_id;
            
            // Insert into servicerequests table with service_id, service_type and user_id
            $service_type = "gatepass";
            $stmt_service = $conn->prepare("INSERT INTO servicerequests (service_id, service_type, user_id) VALUES (?, ?, ?)");
            $stmt_service->bind_param("isi", $ticket_no, $service_type, $user_id);
            
            $result2 = $stmt_service->execute();
            
            if ($result1 && $result2) {
                $conn->commit();
                
                echo "<script>
                    alert('Gate Pass submitted successfully!');
                    window.location.href = 'Gatepass.php';
                </script>";
                exit;
            } else {
                throw new Exception("One of the inserts failed");
            }
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>
                alert('Error: " . $e->getMessage() . "');
            </script>";
        }

        // Close statements
        if (isset($stmt)) $stmt->close();
        if (isset($stmt_service)) $stmt_service->close();
    } else {
        echo "<script>
            alert('Error: Could not find a valid user ID. Please contact support.');
        </script>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Swarm | Gate Pass Form</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet"/>
    <!-- Add Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#f1c40f',
                        primaryDark: '#e1b00f',
                        primaryLight: '#fef9c3',
                        secondary: '#333333',
                        light: '#f4f4f4',
                        'vis-blue': '#0ea5e9', // Changed to sky blue
                        'vis-teal': '#0ea5e9', // Changed to sky blue
                        'vis-indigo': '#0ea5e9', // Changed to sky blue
                        'vis-orange': '#0ea5e9', // Changed to sky blue
                    },
                    boxShadow: {
                        'custom': '0 4px 20px rgba(0, 0, 0, 0.05)',
                        'hover': '0 10px 30px rgba(0, 0, 0, 0.1)',
                    },
                    animation: {
                        'bounce-slow': 'bounce 2s infinite',
                        'pulse-slow': 'pulse 3s infinite',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 py-10">
        <!-- Header Section -->
        <div class="mb-10">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center">
                    <button onclick="history.back()" class="px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 rounded-full shadow-custom flex items-center gap-2 transition duration-300 mr-5">
                        <i class="fas fa-arrow-left text-sm"></i>
                        <span>Back</span>
                    </button>
                    
                    <h1 class="text-3xl md:text-4xl font-extrabold text-gray-800">
                        <span class="text-vis-orange">Gate</span> Pass
                    </h1>
                </div>
            </div>
            
            <!-- Information Box -->
            <div class="bg-white rounded-xl p-6 shadow-custom mb-8 transition-all duration-300 hover:shadow-hover border-l-4 border-sky-500">
                <div class="flex items-start">
                    <div class="bg-sky-100 p-3 rounded-full mr-4">
                        <i class="fas fa-info-circle text-sky-500 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold mb-4 flex items-center text-gray-800">
                            Gate Pass Guidelines
                            <span class="bg-sky-100 text-sky-800 text-xs uppercase tracking-wide font-bold rounded-full px-3 py-1 ml-3">Important</span>
                        </h3>
                        
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-sky-500 mt-1 mr-2"></i>
                                <span>All items must be declared properly with accurate descriptions.</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-sky-500 mt-1 mr-2"></i>
                                <span>Photos of items are required for verification purposes.</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-sky-500 mt-1 mr-2"></i>
                                <span>Gate passes are valid only for the specified date and time.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Section -->
        <form class="space-y-8" method="POST" action="Gatepass.php" enctype="multipart/form-data">
            <!-- Date and Time Section -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="bg-indigo-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-calendar-alt text-vis-indigo"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Schedule Details</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="date_effective" class="block text-gray-700 font-medium mb-2">
                            Date Effective <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar-day text-gray-400"></i>
                            </div>
                            <input type="date" id="date_effective" name="date_effective" required
                                class="w-full pl-10 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-vis-indigo focus:border-transparent transition-all duration-300">
                        </div>
                    </div>
                    
                    <div>
                        <label for="time_effective" class="block text-gray-700 font-medium mb-2">
                            Time Effective <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-clock text-gray-400"></i>
                            </div>
                            <input type="time" id="time_effective" name="time_effective" required
                                class="w-full pl-10 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-vis-indigo focus:border-transparent transition-all duration-300">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bearer and Authorization Section -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="bg-teal-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-user-shield text-vis-teal"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Authorization Details</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="bearer" class="block text-gray-700 font-medium mb-2">
                            Bearer <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input type="text" id="bearer" name="bearer" required placeholder="Name of Bearer"
                                class="w-full pl-10 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-vis-teal focus:border-transparent transition-all duration-300">
                        </div>
                    </div>

                    <div>
                        <label for="authorization" class="block text-gray-700 font-medium mb-2">
                            Authorization Type <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-shield-alt text-gray-400"></i>
                            </div>
                            <select id="authorization" name="authorization" required
                                class="w-full pl-10 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-vis-teal focus:border-transparent transition-all duration-300">
                                <option value="1">Bring In</option>
                                <option value="2">Bring Out</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Table Section -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="bg-sky-50 p-2 rounded-lg mr-3">
                            <i class="fas fa-box text-sky-500"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Items List</h3>
                    </div>
                    
                    <button type="button" id="addRowBtn" 
                        class="px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white font-medium rounded-lg shadow-sm transition duration-300 flex items-center">
                        <i class="fas fa-plus mr-2"></i> Add Item
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table id="itemTable" class="min-w-full bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <thead>
                            <tr class="bg-sky-50 text-sky-800">
                                <th class="py-3 px-4 text-left font-semibold">Item No.</th>
                                <th class="py-3 px-4 text-left font-semibold">Quantity</th>
                                <th class="py-3 px-4 text-left font-semibold">Unit</th>
                                <th class="py-3 px-4 text-left font-semibold">Description</th>
                                <th class="py-3 px-4 text-left font-semibold">Item Pics</th>
                                <th class="py-3 px-4 text-left font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b hover:bg-gray-50 transition-colors">
                                <td class="p-2"><input type="text" name="items[0][item_no]" required placeholder="001" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all duration-300"></td>
                                <td class="p-2"><input type="number" name="items[0][quantity]" required placeholder="1" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all duration-300"></td>
                                <td class="p-2"><input type="text" name="items[0][unit]" required placeholder="pc/s" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all duration-300"></td>
                                <td class="p-2"><input type="text" name="items[0][description]" required placeholder="Item description" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all duration-300"></td>
                                <td class="p-2">
                                    <div class="flex items-center space-x-2">
                                        <div class="image-upload-group flex-1">
                                            <div class="flex flex-wrap gap-2">
                                                <div class="preview-container flex flex-wrap gap-2"></div>
                                                <input type="file" name="items[0][item_pics][]" accept="image/*" multiple class="hidden" onchange="handleImageUpload(event, this)">
                                                <button type="button" onclick="this.previousElementSibling.click()" 
                                                    class="px-3 py-2 bg-sky-500 hover:bg-sky-600 text-white text-sm font-medium rounded-lg shadow-sm transition duration-300 flex items-center">
                                                    <i class="fas fa-camera mr-1"></i> Upload Photos
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-2">
                                    <button type="button" class="removeRowBtn w-full px-3 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg shadow-sm transition duration-300">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Form Buttons -->
            <div class="flex justify-end space-x-4">
                <button type="button" id="clearFormBtn" 
                    class="px-6 py-3 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-lg shadow-sm border border-gray-200 transition duration-300 flex items-center">
                    <i class="fas fa-eraser mr-2"></i> Clear Form
                </button>
                <button type="submit" 
                    class="px-8 py-3 bg-vis-orange hover:bg-orange-600 text-white font-medium rounded-lg shadow-md transition duration-300 flex items-center group">
                    <i class="fas fa-check-circle mr-2 group-hover:animate-bounce-slow"></i> Submit Request
                </button>
            </div>
        </form>

        <!-- Help Card -->
        <div class="mt-10 bg-blue-50 rounded-xl p-6 border border-blue-100 flex items-start">
            <div class="bg-blue-100 p-3 rounded-full text-blue-500 mr-4">
                <i class="fas fa-question-circle text-xl"></i>
            </div>
            <div>
                <h4 class="font-bold text-blue-800 mb-1">Need Help?</h4>
                <p class="text-blue-700 text-sm mb-3">If you have questions about gate passes or building policies:</p>
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    <a href="#" class="text-blue-600 hover:text-blue-800 font-medium flex items-center">
                        <i class="fas fa-phone-alt mr-2"></i> Contact Security
                    </a>
                    <a href="#" class="text-blue-600 hover:text-blue-800 font-medium flex items-center">
                        <i class="fas fa-envelope mr-2"></i> Email Admin
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function handleImageUpload(event, inputElement) {
            const previewContainer = inputElement.closest('.image-upload-group').querySelector('.preview-container');
            const files = event.target.files;
            
            for(let i = 0; i < files.length; i++) {
                const file = files[i];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const imgWrapper = document.createElement('div');
                        imgWrapper.className = 'relative inline-block';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'w-12 h-12 object-cover rounded-lg border border-gray-200';
                        
                        const removeBtn = document.createElement('button');
                        removeBtn.className = 'absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600';
                        removeBtn.innerHTML = '×';
                        removeBtn.onclick = function() {
                            imgWrapper.remove();
                        };
                        
                        imgWrapper.appendChild(img);
                        imgWrapper.appendChild(removeBtn);
                        previewContainer.appendChild(imgWrapper);
                    };
                    reader.readAsDataURL(file);
                }
            }
        }

        document.getElementById('addRowBtn').addEventListener('click', function() {
            const tableBody = document.querySelector('#itemTable tbody');
            const rowCount = tableBody.rows.length;
            
            const newRow = document.createElement('tr');
            newRow.className = 'border-b hover:bg-gray-50 transition-colors';
            newRow.innerHTML = `
                <td class="p-2"><input type="text" name="items[${rowCount}][item_no]" required placeholder="001" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all duration-300"></td>
                <td class="p-2"><input type="number" name="items[${rowCount}][quantity]" required placeholder="1" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all duration-300"></td>
                <td class="p-2"><input type="text" name="items[${rowCount}][unit]" required placeholder="pc/s" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all duration-300"></td>
                <td class="p-2"><input type="text" name="items[${rowCount}][description]" required placeholder="Item description" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all duration-300"></td>
                <td class="p-2">
                    <div class="flex items-center space-x-2">
                        <div class="image-upload-group flex-1">
                            <div class="flex flex-wrap gap-2">
                                <div class="preview-container flex flex-wrap gap-2"></div>
                                <input type="file" name="items[${rowCount}][item_pics][]" accept="image/*" multiple class="hidden" onchange="handleImageUpload(event, this)">
                                <button type="button" onclick="this.previousElementSibling.click()" 
                                    class="px-3 py-2 bg-sky-500 hover:bg-sky-600 text-white text-sm font-medium rounded-lg shadow-sm transition duration-300 flex items-center">
                                    <i class="fas fa-camera mr-1"></i> Upload Photos
                                </button>
                            </div>
                        </div>
                    </div>
                </td>
                <td class="p-2">
                    <button type="button" class="removeRowBtn w-full px-3 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg shadow-sm transition duration-300">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            `;
            
            tableBody.appendChild(newRow);
            
            // Attach remove button event listener
            newRow.querySelector('.removeRowBtn').addEventListener('click', function() {
                if(tableBody.rows.length > 1) {
                    newRow.remove();
                }
            });
        });

        // Remove duplicate event listener and keep only this one
        document.querySelector('.removeRowBtn').addEventListener('click', function() {
            const tableBody = document.querySelector('#itemTable tbody');
            if(tableBody.rows.length > 1) {
                this.closest('tr').remove();
            }
        });
    </script>
</body>
</html>
