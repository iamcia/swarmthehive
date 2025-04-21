<?php
session_start();
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $resident_code = $_SESSION['Resident_Code'] ?? '';
    $user_type = $_SESSION['User_Type'] ?? '';
    $unit_type = $_POST['Unit_Type'] ?? '';
    $date = $_POST['Date'] ?? '';
    $time = $_POST['Time'] ?? '';
    $visit_purpose = $_POST['Visit_Purpose'] ?? '';
    $status = 'Pending';

    // Handling guest info
    $guest_info = [];
    $valid_ids = [];  // Track valid ID files for each guest
    $guest_names = [];  // Track guest names for duplicate checking
    $guest_count = 0;
    
    if (!empty($_POST['Guest_Name'])) {
        for ($i = 0; $i < count($_POST['Guest_Name']); $i++) {
            $guest_name = $_POST['Guest_Name'][$i] ?? '';
            $guest_contact = $_POST['Guest_Contact'][$i] ?? '';
            $valid_id = $_FILES['Valid_ID']['name'][$i] ?? ''; // Valid ID for each guest
            
            // Check if the guest name is already an occupant (Last_Name + First_Name)
            $guest_names[] = $guest_name;
            
            if ($guest_name) {
                // Check if the guest name exists in the occupants table
                $sql = "SELECT COUNT(*) FROM occupants WHERE CONCAT(First_Name, ' ', Last_Name) = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $guest_name);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                if ($count > 0) {
                    echo "<script>
                            alert('The name you entered is already an occupant, not a guest.');
                            window.location.href='GuestInOutForm.php';
                          </script>";
                    exit();
                }
            }

            // Ensure that each guest uploads a valid ID
            if (empty($_FILES['Valid_ID']['name'][$i])) {
                echo "<script>
                        alert('Each guest must upload a valid ID.');
                        window.location.href='GuestInOutForm.php';
                      </script>";
                exit();
            }

            $guest_info[] = [
                'Guest_Name' => $guest_name,
                'Guest_Contact' => $guest_contact
            ];

            $valid_ids[] = $_FILES['Valid_ID']['name'][$i];
            $guest_count++;
        }
    }

    // Check the number of guests based on unit type
    if ($unit_type == '1' && $guest_count > 5) {
        echo "<script>
                alert('For a 1 Bedroom unit, you can add a maximum of 5 guests.');
                window.location.href='GuestInOutForm.php';
              </script>";
        exit();
    }
    if ($unit_type == '2' && $guest_count > 7) {
        echo "<script>
                alert('For a 2 Bedroom unit, you can add a maximum of 7 guests.');
                window.location.href='GuestInOutForm.php';
              </script>";
        exit();
    }

    $guest_info_json = json_encode($guest_info);

    // File Upload Directory
    $uploadDir = "ValidID/";

    // Handling Valid ID Uploads for each guest
    $valid_id_paths = [];
    foreach ($valid_ids as $key => $valid_id) {
        if (!empty($valid_id)) {
            $validIdFile = $uploadDir . basename($valid_id);
            if (move_uploaded_file($_FILES['Valid_ID']['tmp_name'][$key], $validIdFile)) {
                $valid_id_paths[] = $validIdFile;
            }
        }
    }

    // Handling Vaccine Card Upload
    $vaccine_card_path = "";
    if (!empty($_FILES['Vaccine_Card']['name'])) {
        $vaccineCardFile = $uploadDir . basename($_FILES['Vaccine_Card']['name']);
        if (move_uploaded_file($_FILES['Vaccine_Card']['tmp_name'], $vaccineCardFile)) {
            $vaccine_card_path = $vaccineCardFile;
        }
    }

    // Insert into database
    $sql = "INSERT INTO guestcheckinout (Resident_Code, User_Type, Guest_Info, Unit_Type, Date, Time, Visit_Purpose, Valid_ID, Vaccine_Card, Status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param(
            "ssssssssss", 
            $resident_code, 
            $user_type, 
            $guest_info_json, 
            $unit_type, 
            $date, 
            $time, 
            $visit_purpose, 
            json_encode($valid_id_paths), 
            $vaccine_card_path, 
            $status
        );

        if ($stmt->execute()) {
            echo "<script>
                    alert('Guest check-in request submitted successfully.');
                    window.location.href='GuestInOutForm.php';
                  </script>";
        } else {
            echo "<script>
                    alert('Error: " . addslashes($stmt->error) . "');
                    window.location.href='GuestInOutForm.php';
                  </script>";
        }

        $stmt->close();
    } else {
        echo "<script>
                alert('Database error: " . addslashes($conn->error) . "');
                window.location.href='GuestInOutForm.php';
              </script>";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Swarm | Guest Check-In/Out</title>
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
                        primaryLight: '#fef3c7',
                        secondary: '#333333',
                        light: '#f4f4f4',
                    },
                    boxShadow: {
                        'custom': '0 4px 20px rgba(0, 0, 0, 0.05)',
                        'hover': '0 10px 30px rgba(0, 0, 0, 0.1)',
                    },
                    animation: {
                        'bounce-slow': 'bounce 2s infinite',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-5xl mx-auto px-4 py-10">
        <!-- Header Section -->
        <div class="mb-10">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center">
                    <button onclick="history.back()" class="px-4 py-2 bg-white hover:bg-gray-100 text-gray-700 rounded-full shadow-custom flex items-center gap-2 transition duration-300 mr-5">
                        <i class="fas fa-arrow-left text-sm"></i>
                        <span>Back</span>
                    </button>
                    
                    <h1 class="text-3xl md:text-4xl font-extrabold text-gray-800">
                        <span class="text-primary">Guest</span> Check-In/Out Form
                    </h1>
                </div>
                
                <div class="hidden md:block">
                    <div class="bg-amber-100 text-amber-800 font-medium rounded-full px-4 py-1 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i>
                        <span>Form #GC-<?php echo date('Ymd'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Requirements Box -->
            <div class="bg-white rounded-2xl p-6 shadow-custom mb-8 transition-all duration-300 hover:shadow-hover border-l-4 border-primary">
                <div class="flex items-start">
                    <div class="bg-primaryLight p-3 rounded-full mr-4">
                        <i class="fas fa-clipboard-list text-primary text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold mb-4 flex items-center text-gray-800">
                            Required Documents
                            <span class="bg-amber-100 text-amber-800 text-xs uppercase tracking-wide font-bold rounded-full px-3 py-1 ml-3">Important</span>
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="flex items-start mb-3">
                                    <div class="bg-gray-100 p-1 rounded-full mr-2 mt-0.5">
                                        <i class="fas fa-check-circle text-green-500 text-sm"></i>
                                    </div>
                                    <div class="text-sm text-gray-700">
                                        <span class="font-medium">Lease Agreement</span> or 
                                        <span class="font-medium">Online Booking Confirmation</span>
                                    </div>
                                </div>
                                
                                <div class="flex items-start mb-3">
                                    <div class="bg-gray-100 p-1 rounded-full mr-2 mt-0.5">
                                        <i class="fas fa-check-circle text-green-500 text-sm"></i>
                                    </div>
                                    <div class="text-sm text-gray-700">
                                        <span class="font-medium">Pool Reservation Form</span>
                                        <span class="text-gray-500">(if pool access is needed)</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <div class="flex items-start mb-3">
                                    <div class="bg-gray-100 p-1 rounded-full mr-2 mt-0.5">
                                        <i class="fas fa-check-circle text-green-500 text-sm"></i>
                                    </div>
                                    <div class="text-sm text-gray-700">
                                        <span class="font-medium">Valid ID</span> of all guests (18+ years old)
                                    </div>
                                </div>
                                
                                <div class="flex items-start">
                                    <div class="bg-gray-100 p-1 rounded-full mr-2 mt-0.5">
                                        <i class="fas fa-check-circle text-green-500 text-sm"></i>
                                    </div>
                                    <div class="text-sm text-gray-700">
                                        <span class="font-medium">Vaccination Card/Certificate</span>
                                        <span class="text-gray-500 italic">(Only during COVID Alert)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mb-8">
                <div class="inline-block bg-blue-50 text-blue-700 rounded-lg px-5 py-3 shadow-sm">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-blue-500 mr-2 text-lg"></i>
                        <p class="text-sm font-medium">
                            Please complete all fields marked with <span class="text-red-500 font-bold">*</span> to submit your request
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Section -->
        <form method="POST" enctype="multipart/form-data" action="Insert-GuestInOutForm.php" class="space-y-8">
            <!-- Date Fields Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                    <div class="flex items-center mb-4">
                        <div class="bg-blue-50 p-2 rounded-lg mr-3">
                            <i class="fas fa-calendar-check text-blue-500"></i>
                        </div>
                        <label for="checkin-date" class="block text-gray-800 font-bold">
                            Check-in Date <span class="text-red-500">*</span>
                        </label>
                    </div>
                    <input 
                        type="date" 
                        id="checkin-date" 
                        name="checkin_date" 
                        required
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-300"
                    >
                    <p class="text-xs text-gray-500 mt-2 flex items-center">
                        <i class="fas fa-info-circle mr-1"></i>
                        Check-in time is between 2:00 PM and 6:00 PM
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                    <div class="flex items-center mb-4">
                        <div class="bg-red-50 p-2 rounded-lg mr-3">
                            <i class="fas fa-calendar-minus text-red-500"></i>
                        </div>
                        <label for="checkout-date" class="block text-gray-800 font-bold">
                            Check-out Date <span class="text-red-500">*</span>
                        </label>
                    </div>
                    <input 
                        type="date" 
                        id="checkout-date" 
                        name="checkout_date" 
                        required
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-300"
                    >
                    <p class="text-xs text-gray-500 mt-2 flex items-center">
                        <i class="fas fa-info-circle mr-1"></i>
                        Check-out time is no later than 12:00 PM
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                    <div class="flex items-center mb-4">
                        <div class="bg-green-50 p-2 rounded-lg mr-3">
                            <i class="fas fa-clock text-green-500"></i>
                        </div>
                        <label for="days-of-stay" class="block text-gray-800 font-bold">
                            Days of Stay <span class="text-red-500">*</span>
                        </label>
                    </div>
                    <input 
                        type="number" 
                        id="days-of-stay" 
                        name="days_of_stay" 
                        required
                        min="1"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-300"
                    >
                    <p class="text-xs text-gray-500 mt-2 flex items-center">
                        <i class="fas fa-info-circle mr-1"></i> 
                        Total number of nights at the property
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                    <div class="flex items-center mb-4">
                        <div class="bg-purple-50 p-2 rounded-lg mr-3">
                            <i class="fas fa-home text-purple-500"></i>
                        </div>
                        <label for="unit-type" class="block text-gray-800 font-bold">
                            Unit Type <span class="text-red-500">*</span>
                        </label>
                    </div>
                    <div class="relative">
                        <select 
                            id="unit-type" 
                            name="unit_type" 
                            required
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent appearance-none transition-all duration-300"
                        >
                            <option value="" disabled selected>Select unit type</option>
                            <option value="1">1 Bedroom</option>
                            <option value="2">2 Bedroom</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                            <i class="fas fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2 flex items-center">
                        <i class="fas fa-info-circle mr-1"></i>
                        Select the appropriate unit size
                    </p>
                </div>
            </div>

            <!-- Guest Information Table -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="bg-amber-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-users text-amber-500"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Guest Information</h3>
                    <span class="bg-blue-100 text-blue-800 text-xs uppercase tracking-wide font-bold rounded-full px-3 py-1 ml-3">Required</span>
                </div>
                
                <div class="overflow-x-auto mb-4 rounded-lg border border-gray-100">
                    <table id="guestTable" class="min-w-full">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 bg-gray-50 text-gray-700 font-bold text-left rounded-tl-lg border-b border-gray-100">Guest No.</th>
                                <th class="px-4 py-3 bg-gray-50 text-gray-700 font-bold text-left border-b border-gray-100">Name of Guest</th>
                                <th class="px-4 py-3 bg-gray-50 text-gray-700 font-bold text-left border-b border-gray-100">Contact Number</th>
                                <th class="px-4 py-3 bg-gray-50 text-gray-700 font-bold text-left border-b border-gray-100">Relationship</th>
                                <th class="px-4 py-3 bg-gray-50 text-gray-700 font-bold text-left border-b border-gray-100">Valid ID</th>
                                <th class="px-4 py-3 bg-gray-50 text-gray-700 font-bold text-left border-b border-gray-100">Vaccine Card</th>
                                <th class="px-4 py-3 bg-gray-50 text-gray-700 font-bold text-left rounded-tr-lg border-b border-gray-100">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="group hover:bg-gray-50 transition-colors">
                                <td class="border-b border-gray-100 px-4 py-3">
                                    <input 
                                        type="text" 
                                        name="guest_info[0][guest_no]" 
                                        placeholder="Guest #" 
                                        required
                                        class="w-full px-3 py-2 bg-white border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent group-hover:bg-white transition-all duration-200"
                                    >
                                </td>
                                <td class="border-b border-gray-100 px-4 py-3">
                                    <input 
                                        type="text" 
                                        name="guest_info[0][name]" 
                                        placeholder="Full Name" 
                                        required
                                        class="w-full px-3 py-2 bg-white border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent group-hover:bg-white transition-all duration-200"
                                    >
                                </td>
                                <td class="border-b border-gray-100 px-4 py-3">
                                    <input 
                                        type="text" 
                                        name="guest_info[0][contact]" 
                                        placeholder="Phone Number" 
                                        required
                                        class="w-full px-3 py-2 bg-white border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent group-hover:bg-white transition-all duration-200"
                                    >
                                </td>
                                <td class="border-b border-gray-100 px-4 py-3">
                                    <input 
                                        type="text" 
                                        name="guest_info[0][relationship]" 
                                        placeholder="Relationship" 
                                        required
                                        class="w-full px-3 py-2 bg-white border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent group-hover:bg-white transition-all duration-200"
                                    >
                                </td>
                                <td class="border-b border-gray-100 px-4 py-3">
                                    <input 
                                        type="file" 
                                        name="guest_info[0][valid_id]" 
                                        accept="image/*" 
                                        required 
                                        class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-primary"
                                    >
                                </td>
                                <td class="border-b border-gray-100 px-4 py-3">
                                    <input 
                                        type="file" 
                                        name="guest_info[0][vaccine_card]" 
                                        accept="image/*" 
                                        class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-primary"
                                    >
                                </td>
                                <td class="border-b border-gray-100 px-4 py-3">
                                    <button 
                                        type="button" 
                                        class="removeRowBtn text-red-500 hover:text-red-700 transition-colors"
                                    >
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-500 flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        Add all guests who will be staying at the property
                    </p>
                    
                    <button 
                        type="button" 
                        id="addRowBtn" 
                        class="flex items-center px-5 py-2.5 bg-primary hover:bg-primaryDark text-gray-800 font-medium rounded-lg transition duration-300 shadow-sm hover:shadow"
                    >
                        <i class="fas fa-plus mr-2"></i> Add Guest
                    </button>
                </div>
            </div>

            <!-- Form Buttons -->
            <div class="flex justify-end space-x-4 mt-8">
                <button 
                    type="button" 
                    id="clearFormBtn" 
                    class="px-6 py-3 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-lg shadow-sm border border-gray-200 transition duration-300 flex items-center"
                >
                    <i class="fas fa-eraser mr-2"></i> Clear Form
                </button>
                <button 
                    type="submit" 
                    class="px-8 py-3 bg-primary hover:bg-primaryDark text-gray-800 font-medium rounded-lg shadow-md transition duration-300 flex items-center group"
                >
                    <i class="fas fa-check mr-2 group-hover:animate-bounce-slow"></i> Submit Form
                </button>
            </div>
        </form>
    </div>

<script>
    // Dynamic Row Functions
    document.addEventListener('DOMContentLoaded', function() {
        const addRowBtn = document.getElementById('addRowBtn');
        const guestTable = document.getElementById('guestTable').getElementsByTagName('tbody')[0];
        let rowCount = 1;
        const maxGuests = document.getElementById('unit-type').value === '1' ? 5 : 7;
        
        // Add row function
        addRowBtn.addEventListener('click', function() {
            if (rowCount < maxGuests) {
                const row = guestTable.insertRow();
                row.innerHTML = `
                    <td class="border px-4 py-2">
                        <input type="text" name="guest_info[${rowCount}][guest_no]" placeholder="Guest #" required 
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-primary">
                    </td>
                    <td class="border px-4 py-2">
                        <input type="text" name="guest_info[${rowCount}][name]" placeholder="Full Name" required 
                            class="w-full px-3 py-2 bg-white border border-gray-200 rounded-md focus:outline-none focus:ring-1 focus:ring-primary">
                    </td>
                    <td class="border px-4 py-2">
                        <input type="text" name="guest_info[${rowCount}][contact]" placeholder="Phone Number" required 
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-primary">
                    </td>
                    <td class="border px-4 py-2">
                        <input type="text" name="guest_info[${rowCount}][relationship]" placeholder="Relationship" required 
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-primary">
                    </td>
                    <!-- Valid ID upload for each guest -->
                    <td class="border px-4 py-2">
                        <input type="file" name="guest_info[${rowCount}][valid_id]" accept="image/*" required 
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-primary">
                    </td>
                    <!-- Vaccine Card upload for each guest -->
                    <td class="border px-4 py-2">
                        <input type="file" name="guest_info[${rowCount}][vaccine_card]" accept="image/*" 
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-primary">
                    </td>
                    <td class="border px-4 py-2">
                        <button type="button" class="removeRowBtn text-red-500 hover:text-red-700 transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                `;
                rowCount++;
            } else {
                alert(`You can only add a maximum of ${maxGuests} guests.`);
            }
        });

        // Remove row function using event delegation
        guestTable.addEventListener('click', function(e) {
            if (e.target.closest('.removeRowBtn')) {
                // Don't remove the last row
                if (guestTable.rows.length > 1) {
                    e.target.closest('tr').remove();
                } else {
                    alert('At least one guest is required.');
                }
            }
        });
    });
    
    // Preview Image Function
    function previewImage(input, previewContainerId) {
        const previewContainer = document.getElementById(previewContainerId);
        previewContainer.innerHTML = ''; 
        
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const imgContainer = document.createElement('div');
                imgContainer.classList.add('relative', 'group', 'transition-all', 'duration-300', 'transform', 'hover:scale-105');
                
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = "Document Preview";
                img.classList.add('h-32', 'object-contain', 'rounded-lg', 'border', 'border-gray-200', 'shadow-sm', 'cursor-pointer');
                img.onclick = function() { showImage(e.target.result); };
                imgContainer.appendChild(img);
                
                const caption = document.createElement('div');
                caption.textContent = file.name;
                caption.classList.add('absolute', 'bottom-0', 'left-0', 'right-0', 'bg-black', 'bg-opacity-70',
                                     'text-white', 'text-xs', 'py-1', 'px-2', 'text-center', 'truncate');
                imgContainer.appendChild(caption);
                
                previewContainer.appendChild(imgContainer);
            };
            
            reader.readAsDataURL(file);
        }
    }
    
    // Show image in modal
    function showImage(src) {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById("modalImage");
        modal.classList.remove('hidden');
        modalImg.src = src;
    }
    
    // Close image modal
    function closeImageModal() {
        document.getElementById('imageModal').classList.add('hidden');
    }
</script>
</body>
</html>