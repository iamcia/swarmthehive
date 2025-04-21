<?php
include 'dbconn.php';
session_start();

// Check if the user is logged in and has the correct position (Finance)
if (!isset($_SESSION['Management_Code']) || $_SESSION['position'] != 'Finance') {
    header("Location: management-index.php");
    exit();
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle File Upload and Billing Record Insert
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['soa_id'])) {
    $billing_number = $_POST['soa_id'];
    $unit_number = $_POST['resident_id'];
    $billing_date = $_POST['billing_date'];
    $due_date = $_POST['due_date'];

    $owner_id = null;
    $tenant_id = null;
    $user_type = "";

    // Start transaction
    $conn->begin_transaction();
    try {
        // Check for Owner
        $stmt = $conn->prepare("SELECT Owner_ID FROM ownerinformation WHERE Unit_Number = ?");
        $stmt->bind_param("s", $unit_number);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $owner_id = $row['Owner_ID'];
            $user_type = "Owner";
        }
        $stmt->close();

        // Check for Tenant if Owner not found
        if (!$owner_id) {
            $stmt = $conn->prepare("SELECT Tenant_ID FROM tenantinformation WHERE Unit_Number = ?");
            $stmt->bind_param("s", $unit_number);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $tenant_id = $row['Tenant_ID'];
                $user_type = "Tenant";
            }
            $stmt->close();
        }

        if (!$owner_id && !$tenant_id) {
            throw new Exception("No Owner or Tenant found for Unit Number.");
        }

        // Insert billing record with proper timestamp
        $stmt = $conn->prepare("INSERT INTO soafinance (Owner_ID, Tenant_ID, User_Type, Billing_Number, Unit_No, Billing_Date, Due_Date, Status, Uploaded_At) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', CURRENT_TIMESTAMP) 
                ON DUPLICATE KEY UPDATE 
                    Billing_Date = VALUES(Billing_Date), 
                    Due_Date = VALUES(Due_Date),
                    Status = 'Pending',
                    Uploaded_At = CURRENT_TIMESTAMP");

        $stmt->bind_param("sssssss", $owner_id, $tenant_id, $user_type, $billing_number, $unit_number, $billing_date, $due_date);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create billing record: " . $stmt->error);
        }
        $stmt->close();

        // Also insert into soafinance_records
        $stmt = $conn->prepare("INSERT INTO soafinance_records (Owner_ID, Tenant_ID, User_Type, Billing_Number, Unit_No, Billing_Date, Due_Date, Status, Payment_Status, Uploaded_At)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', 'Unpaid', CURRENT_TIMESTAMP)");
        
        $stmt->bind_param("sssssss", $owner_id, $tenant_id, $user_type, $billing_number, $unit_number, $billing_date, $due_date);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create billing record history: " . $stmt->error);
        }
        $stmt->close();

        // Handle file uploads with proper foreign key handling
        $upload_dir = "Soa/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_FILES['pdf_files']['tmp_name'] as $key => $tmp_name) {
            if (!empty($tmp_name)) {
                $file_name = basename($_FILES['pdf_files']['name'][$key]);
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

                if (strtolower($file_extension) !== 'pdf') {
                    throw new Exception("Only PDF files allowed.");
                }

                $file_path = $upload_dir . time() . "_" . $file_name;
                if (!move_uploaded_file($tmp_name, $file_path)) {
                    throw new Exception("Failed to upload $file_name");
                }

                // Insert PDF record with proper timestamp
                $stmt = $conn->prepare("INSERT INTO soafinance_pdfs (Billing_Number, PDF_File, Uploaded_At) VALUES (?, ?, CURRENT_TIMESTAMP)");
                $stmt->bind_param("ss", $billing_number, $file_path);
                
                if (!$stmt->execute()) {
                    // If foreign key constraint fails, clean up the uploaded file
                    unlink($file_path);
                    throw new Exception("Failed to link PDF to billing record: " . $stmt->error);
                }
                $stmt->close();
            }
        }

        // Remove finance_logs section and directly commit
        $conn->commit();
        $_SESSION['success'] = "Upload successful!";
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle AJAX Search
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search'])) {
    $search = trim($_POST['search']);
    $response = [];

    if (!empty($search)) {
        $sql = "
            SELECT 'Owner' AS Type, Owner_ID AS UserID, First_Name, Last_Name, Unit_Number 
            FROM ownerinformation 
            WHERE First_Name LIKE ? OR Last_Name LIKE ? OR Unit_Number LIKE ?

            UNION

            SELECT 'Tenant' AS Type, Tenant_ID AS UserID, First_Name, Last_Name, Unit_Number 
            FROM tenantinformation 
            WHERE First_Name LIKE ? OR Last_Name LIKE ? OR Unit_Number LIKE ?
        ";

        $stmt = $conn->prepare($sql);
        $search_param = "%{$search}%";
        $stmt->bind_param("ssssss", $search_param, $search_param, $search_param, $search_param, $search_param, $search_param);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }
        echo json_encode($response);
    }
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swarm | Finance Upload</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <!-- Add Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Original CSS for sidebar -->
    <link rel="stylesheet" href="./css/finance_style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Keep the original sidebar -->
        <aside class="sidebar">
            <div class="logo-container">
                <img src="./img/logo swarm.png" alt="Logo" class="logo">
                <span class="logo-text">SWARM</span>
            </div>

            <div class="menu-title">
                <i class='bx bx-money'></i>
                <span>Finance</span>
            </div>

            <nav class="sidebar-menu">
                <ul>
                    <li>
                        <a href="finance-dashboard.php">
                            <i class='bx bxs-dashboard'></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="#">
                            <i class='bx bx-upload'></i>
                            <span>Upload SOA</span>
                        </a>
                    </li>
                    <li>
                        <a href="finance-status.php">
                            <i class='bx bx-check-circle'></i>
                            <span>Status</span>
                        </a>
                    </li>
                    <li>
                        <a href="finance-history.php">
                            <i class='bx bx-history'></i>
                            <span>Logs</span>
                        </a>
                    </li>
                    <li>
                        <a href="finance-settings.php">
                            <i class='bx bx-cog'></i>
                            <span>Settings</span>
                        </a>
                    </li>
                    <div class="divider"></div>
                    <li>
                        <a href="logout.php" class="nav-item logout">
                            <i class='bx bx-log-out'></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Redesigned main content using Tailwind -->
        <main class="flex-1 overflow-auto p-6">
            <div class="max-w-4xl mx-auto">
                <!-- Status Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow" role="alert">
                        <p><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow" role="alert">
                        <p><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Search Section -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Search Tenant or Owner</h2>
                    
                    <div class="flex flex-wrap items-center gap-4 mb-6">
                        <div class="flex-1">
                            <label for="tenant-search" class="block text-sm font-medium text-gray-700 mb-1">
                                Search by Name or Unit Number:
                            </label>
                            <input type="search" id="tenant-search" name="tenant_search" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Enter name or unit number">
                        </div>
                        <button type="button" id="search-btn" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                            <i class='bx bx-search mr-1'></i> Search
                        </button>
                    </div>

                    <div id="search-results" class="overflow-x-auto">
                        <table id="results-table" class="min-w-full bg-white rounded-lg overflow-hidden shadow hidden">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Owner ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Tenant ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">First Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Last Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Unit Number</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody id="results-body" class="divide-y divide-gray-200"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Upload Form Section -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Upload Statement of Account</h2>
                    
                    <form method="post" enctype="multipart/form-data" class="space-y-6">
                        <!-- Hidden Fields for Selected IDs -->
                        <input type="hidden" id="owner_id" name="owner_id">
                        <input type="hidden" id="tenant_id" name="tenant_id">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="resident_id" class="block text-sm font-medium text-gray-700 mb-1">Unit Number:</label>
                                <input type="text" id="resident_id" name="resident_id" readonly required
                                    class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label for="soa_id" class="block text-sm font-medium text-gray-700 mb-1">Billing Number:</label>
                                <input type="text" id="soa_id" name="soa_id" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label for="billing_date" class="block text-sm font-medium text-gray-700 mb-1">Billing Date:</label>
                                <input type="date" id="billing_date" name="billing_date" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">Due Date:</label>
                                <input type="date" id="due_date" name="due_date" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <label for="pdf_files" class="block text-sm font-medium text-gray-700 mb-1">Upload PDFs:</label>
                            <div class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="pdf_files" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                            <span>Upload files</span>
                                            <input id="pdf_files" name="pdf_files[]" type="file" class="sr-only" accept=".pdf" multiple required>
                                        </label>
                                        
                                    </div>
                                    <p class="text-xs text-gray-500">PDF files only</p>
                                </div>
                            </div>
                            
                            <!-- PDF Preview Section -->
                            <div id="pdf-preview" class="grid grid-cols-1 gap-4 mt-4"></div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                <i class='bx bx-upload mr-2'></i> Upload SOA
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Handle search button click event
    document.getElementById("search-btn").addEventListener("click", function() {
        let searchValue = document.getElementById("tenant-search").value.trim();
        
        if (searchValue === "") {
            alert("Please enter a search term.");
            return;
        }

        let xhr = new XMLHttpRequest();
        xhr.open("POST", window.location.pathname, true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onload = function() {
            let resultsTable = document.getElementById("results-table");
            let resultsBody = document.getElementById("results-body");
            resultsBody.innerHTML = ""; // Clear previous results

            if (xhr.status === 200) {
                let data = JSON.parse(xhr.responseText);

                if (data.length > 0) {
                    data.forEach(row => {
                        let tr = document.createElement("tr");
                        tr.innerHTML = `
                            <td class="px-4 py-3 whitespace-nowrap">${row.Type === 'Owner' ? row.UserID : ''}</td>
                            <td class="px-4 py-3 whitespace-nowrap">${row.Type === 'Tenant' ? row.UserID : ''}</td>
                            <td class="px-4 py-3 whitespace-nowrap">${row.First_Name}</td>
                            <td class="px-4 py-3 whitespace-nowrap">${row.Last_Name}</td>
                            <td class="px-4 py-3 whitespace-nowrap">${row.Unit_Number}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <button type='button' 
                                    onclick='selectUser("${row.UserID}", "${row.Unit_Number}", "${row.Type}")' 
                                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-blue-500">
                                    Select
                                </button>
                            </td>
                        `;
                        resultsBody.appendChild(tr);
                    });
                    resultsTable.classList.remove("hidden");
                } else {
                    resultsBody.innerHTML = `<tr><td colspan='6' class="px-4 py-4 text-center text-gray-500">No results found.</td></tr>`;
                    resultsTable.classList.remove("hidden");
                }
            } else {
                alert("Error fetching data.");
            }
        };

        xhr.send("search=" + encodeURIComponent(searchValue));
    });

    function selectUser(userId, unitNumber, userType) {
        document.getElementById("resident_id").value = unitNumber;
        
        // Scroll to upload form
        document.querySelector('form').scrollIntoView({ 
            behavior: 'smooth'
        });
        
        // Highlight the selected field briefly
        const field = document.getElementById("resident_id");
        field.classList.add("bg-yellow-100");
        setTimeout(() => {
            field.classList.remove("bg-yellow-100");
        }, 1500);
    }

    document.addEventListener("DOMContentLoaded", function () {
        let today = new Date();
        let year = today.getFullYear();
        let month = today.getMonth(); // 0-based (Jan = 0, Dec = 11)

        // Set Billing Date to the 10th of the Current Month
        let billingDate = new Date(year, month, 11); // Set to the 10th day of the current month
        document.getElementById("billing_date").value = billingDate.toISOString().split('T')[0];

        // Set Due Date to the 30th of the Current Month
        let dueDate = new Date(year, month, 31); // Set to the 30th day of the current month
        document.getElementById("due_date").value = dueDate.toISOString().split('T')[0];
    });

    // PDF Preview Functionality
    document.getElementById('pdf_files').addEventListener('change', function(event) {
        const files = event.target.files;
        const previewContainer = document.getElementById('pdf-preview');
        previewContainer.innerHTML = '';

        if (files.length > 0) {
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (file.type === 'application/pdf') {
                    // Create a card for each PDF
                    const card = document.createElement('div');
                    card.className = 'bg-gray-50 border rounded-lg overflow-hidden shadow-sm';
                    
                    // Create a unique ID for the iframe
                    const iframeId = `pdf-preview-${i}`;
                    
                    // PDF icon, filename and preview container
                    card.innerHTML = `
                        <div class="p-4">
                            <div class="flex items-center mb-3">
                                <div class="flex-shrink-0 bg-red-100 p-2 rounded-md">
                                    <svg class="h-8 w-8 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-sm font-medium text-gray-900 truncate" title="${file.name}">${file.name}</h3>
                                    <p class="text-xs text-gray-500">${(file.size / 1024).toFixed(2)} KB</p>
                                </div>
                            </div>
                            <div class="border rounded h-96 w-full bg-white mb-2">
                                <iframe id="${iframeId}" class="w-full h-full"></iframe>
                            </div>
                        </div>
                        <div class="px-4 py-3 bg-gray-100 text-right">
                            <button type="button" class="text-xs font-medium text-red-600 hover:text-red-500" onclick="removeFile(${i})">
                                Remove
                            </button>
                        </div>
                    `;
                    
                    previewContainer.appendChild(card);
                    
                    // Generate the PDF preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const iframe = document.getElementById(iframeId);
                        iframe.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            }
        }
    });

    // Function to remove a file from the input
    function removeFile(index) {
        const input = document.getElementById('pdf_files');
        const dt = new DataTransfer();
        
        for (let i = 0; i < input.files.length; i++) {
            if (i !== index) {
                dt.items.add(input.files[i]);
            }
        }
        
        input.files = dt.files;
        
        // Trigger the change event to update the preview
        const event = new Event('change');
        input.dispatchEvent(event);
    }
    </script>
</body>
</html>