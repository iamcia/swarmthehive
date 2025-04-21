<?php
include('dbconn.php');
session_start();

$message = '';
$userType = '';
$userEmail = '';
$signature = '';
$userNumber = '';
$unitNumber = '';
$residentCode = '';
$status = '';
$owner_name = '';
$user_id = null; // Added user_id variable

// Fetch user details if the user is logged in
if (isset($_SESSION['username'])) {
    $ownerUsername = $_SESSION['username'];

    // First, attempt to fetch information from the OwnerInformation table
    $sql = "SELECT ID, First_Name, Last_Name, Owner_ID, Email, Mobile_Number, Unit_Number, Signature, Status FROM ownerinformation WHERE Username = ?"; // Added ID
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ownerUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userType = 'Owner';
        $residentCode = $row['Owner_ID'];
        $owner_name = $row['First_Name'] . " " . $row['Last_Name'];
        $userEmail = $row['Email'];
        $userNumber = $row['Mobile_Number'];
        $unitNumber = $row['Unit_Number'];
        $signature = $row['Signature'];
        $status = $row['Status'];
        $user_id = $row['ID']; // Store the user ID
    } else {
        // If no match found in OwnerInformation, check TenantInformation
        $sql = "SELECT First_Name, Last_Name, Tenant_ID, Email, Mobile_Number, Unit_Number, Signature, Status FROM tenantinformation WHERE Username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $ownerUsername);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $userType = 'Tenant';
            $residentCode = $row['Tenant_ID'];
            $owner_name = $row['First_Name'] . " " . $row['Last_Name'];
            $userEmail = $row['Email'];
            $userNumber = $row['Mobile_Number'];
            $unitNumber = $row['Unit_Number'];
            $signature = $row['Signature'];
            $status = $row['Status'];
            
            // For tenants, we need to find their associated owner's ID
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
    }
}

// Check for success or error messages from form processing
if (isset($_GET['success'])) {
    echo "<script>alert('" . htmlspecialchars($_GET['message'] ?? "Pet registered successfully!") . "');</script>";
}
if (isset($_GET['error'])) {
    echo "<script>alert('" . htmlspecialchars($_GET['message'] ?? "An error occurred.") . "');</script>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Swarm | Pet Registration</title>
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
                        'pet-blue': '#3498db',
                        'pet-green': '#2ecc71',
                        'pet-purple': '#9b59b6',
                        'pet-red': '#e74c3c',
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
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 py-10">
        <!-- Header Section -->
        <div class="mb-10">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center">
                    <button onclick="history.back()" class="px-4 py-2 bg-white hover:bg-gray-100 text-gray-700 rounded-full shadow-custom flex items-center gap-2 transition duration-300 mr-5">
                        <i class="fas fa-arrow-left text-sm"></i>
                        <span>Back</span>
                    </button>
                    
                    <h1 class="text-3xl md:text-4xl font-extrabold text-gray-800">
                        <span class="text-pet-purple">Pet</span> Registration
                    </h1>
                </div>
                
                <div class="hidden md:block">
                    <div class="bg-purple-100 text-purple-800 font-medium rounded-full px-4 py-1 flex items-center">
                        <i class="fas fa-paw mr-2"></i>
                        <span>Form #PR-<?php echo date('Ymd'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Requirements Box -->
            <div class="bg-white rounded-2xl p-6 shadow-custom mb-8 transition-all duration-300 hover:shadow-hover border-l-4 border-pet-purple">
                <div class="flex items-start">
                    <div class="bg-purple-100 p-3 rounded-full mr-4">
                        <i class="fas fa-dog text-pet-purple text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold mb-4 flex items-center text-gray-800">
                            Important Reminders
                            <span class="bg-purple-100 text-purple-800 text-xs uppercase tracking-wide font-bold rounded-full px-3 py-1 ml-3">Read Carefully</span>
                        </h3>
                        
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-pet-purple mt-1 mr-2"></i>
                                <span>Owners or tenants must apply for permission to keep pets with Property Management Office.</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-pet-purple mt-1 mr-2"></i>
                                <span>Pets must be limited to aquarium fishes, birds, small dogs, and other small tamed animals.</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-pet-purple mt-1 mr-2"></i>
                                <span><strong>Only one (1) pet is allowed per unit.</strong></span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-pet-purple mt-1 mr-2"></i>
                                <span>Pets must be kept in humane conditions within the unit.</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-pet-purple mt-1 mr-2"></i>
                                <span>Owners are responsible for cleaning up after their pets in common areas.</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-pet-purple mt-1 mr-2"></i>
                                <span>Pets must not disturb other tenants with noise.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="text-center mb-8">
                <div class="inline-block bg-blue-50 text-blue-700 rounded-lg px-5 py-3 shadow-sm">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-blue-500 mr-2 text-lg"></i>
                        <p class="text-sm font-medium">
                            Please complete all fields marked with <span class="text-red-500 font-bold">*</span> to submit your registration
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Section -->
        <form method="POST" enctype="multipart/form-data" action="Insert-PetRegistration.php" class="space-y-8">
            <!-- Hidden Fields -->
            <input type="hidden" id="resident_code" name="resident_code" value="<?php echo htmlspecialchars($residentCode); ?>">
            <input type="hidden" id="owner_name" name="owner_name" value="<?php echo htmlspecialchars($owner_name); ?>" readonly required>
            <input type="hidden" id="contact" name="contact" value="<?php echo htmlspecialchars($userNumber); ?>" readonly required>
            <input type="hidden" id="unit_no" name="unit_no" value="<?php echo htmlspecialchars($unitNumber); ?>" readonly required>
            <input type="hidden" id="email" name="email" value="<?php echo htmlspecialchars($userEmail); ?>" readonly required>
            <input type="hidden" id="user_type" name="user_type" value="<?php echo htmlspecialchars($userType); ?>">
            <input type="hidden" id="user_id" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>"> <!-- Added user_id field -->

            <!-- Owner Information -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-4">
                    <div class="bg-blue-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-user text-pet-blue"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Owner Information</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                    <div>
                        <label class="block text-gray-600 text-sm font-medium mb-1">Name</label>
                        <p class="px-3 py-2 bg-gray-50 rounded-md border border-gray-200 text-gray-700"><?php echo htmlspecialchars($owner_name); ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-600 text-sm font-medium mb-1">Contact Number</label>
                        <p class="px-3 py-2 bg-gray-50 rounded-md border border-gray-200 text-gray-700"><?php echo htmlspecialchars($userNumber); ?></p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-600 text-sm font-medium mb-1">Unit Number</label>
                        <p class="px-3 py-2 bg-gray-50 rounded-md border border-gray-200 text-gray-700"><?php echo htmlspecialchars($unitNumber); ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-600 text-sm font-medium mb-1">Email</label>
                        <p class="px-3 py-2 bg-gray-50 rounded-md border border-gray-200 text-gray-700"><?php echo htmlspecialchars($userEmail); ?></p>
                    </div>
                </div>
            </div>

            <!-- Pet Information -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="bg-purple-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-paw text-pet-purple"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Pet Information</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="pet_name" class="block text-gray-700 font-medium mb-2">
                            Pet's Name <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-heart text-gray-400"></i>
                            </div>
                            <input 
                                type="text" 
                                id="pet_name" 
                                name="pet_name" 
                                placeholder="Enter pet's name" 
                                required
                                class="w-full pl-10 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-pet-purple focus:border-transparent transition-all duration-300"
                            >
                        </div>
                    </div>
                    
                    <div>
                        <label for="breed" class="block text-gray-700 font-medium mb-2">
                            Breed <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-tag text-gray-400"></i>
                            </div>
                            <input 
                                type="text" 
                                id="breed" 
                                name="breed" 
                                placeholder="Enter breed" 
                                required
                                class="w-full pl-10 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-pet-purple focus:border-transparent transition-all duration-300"
                            >
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="dob" class="block text-gray-700 font-medium mb-2">
                            Date of Birth <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar-alt text-gray-400"></i>
                            </div>
                            <input 
                                type="date" 
                                id="dob" 
                                name="dob" 
                                required
                                class="w-full pl-10 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-pet-purple focus:border-transparent transition-all duration-300"
                            >
                        </div>
                        <p class="text-xs text-gray-500 mt-1 flex items-center">
                            <i class="fas fa-info-circle mr-1"></i>
                            Approximate date if exact is unknown
                        </p>
                    </div>
                    
                    <div>
                        <label for="pet_pic" class="block text-gray-700 font-medium mb-2">
                            Pet Picture <span class="text-red-500">*</span>
                        </label>
                        <div class="border-2 border-dashed border-gray-200 rounded-lg p-4 text-center hover:border-pet-purple transition-colors duration-300 bg-gray-50 relative">
                            <input 
                                type="file" 
                                id="pet_pic" 
                                name="pet_pic" 
                                accept="image/*" 
                                required 
                                class="hidden"
                                onchange="previewImage(event, 'pet_preview')"
                            >
                            <label for="pet_pic" class="cursor-pointer flex flex-col items-center justify-center">
                                <div class="w-12 h-12 bg-purple-50 rounded-full flex items-center justify-center mb-2">
                                    <i class="fas fa-camera text-pet-purple text-xl"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-700">Click to upload pet photo</span>
                                <span class="text-xs text-gray-500">JPG, PNG or GIF</span>
                            </label>
                            <div id="pet_preview_container" class="mt-4 hidden">
                                <img id="pet_preview" src="#" alt="Pet Preview" class="mx-auto max-h-32 rounded-lg border border-gray-200" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vaccination Information -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="bg-green-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-syringe text-pet-green"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Vaccination Information</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="vaccinated" class="block text-gray-700 font-medium mb-2">
                            Is your pet vaccinated? <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select 
                                id="vaccinated" 
                                name="vaccinated" 
                                required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-pet-purple focus:border-transparent appearance-none transition-all duration-300"
                            >
                                <option value="">Select</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1 flex items-center">
                            <i class="fas fa-info-circle mr-1"></i>
                            Pets must be vaccinated against rabies and distemper
                        </p>
                    </div>
                    
                    <div>
                        <label for="vaccine_duration" class="block text-gray-700 font-medium mb-2">
                            Vaccine Duration (days) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-clock text-gray-400"></i>
                            </div>
                            <input 
                                type="number" 
                                id="vaccine_duration" 
                                name="vaccine_duration" 
                                min="1" 
                                max="365" 
                                placeholder="Enter days (1-365)" 
                                required
                                class="w-full pl-10 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-pet-purple focus:border-transparent transition-all duration-300"
                            >
                        </div>
                    </div>
                </div>

                <div>
                    <label for="vaccine_image" class="block text-gray-700 font-medium mb-2">
                        Vaccine Certificate <span class="text-red-500">*</span>
                    </label>
                    <div class="border-2 border-dashed border-gray-200 rounded-lg p-4 text-center hover:border-pet-green transition-colors duration-300 bg-gray-50">
                        <input 
                            type="file" 
                            id="vaccine_image" 
                            name="vaccine_image" 
                            accept="image/*" 
                            required 
                            class="hidden"
                            onchange="previewImage(event, 'vaccine_preview')"
                        >
                        <label for="vaccine_image" class="cursor-pointer flex flex-col items-center justify-center">
                            <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center mb-2">
                                <i class="fas fa-file-medical text-pet-green text-xl"></i>
                            </div>
                            <span class="text-sm font-medium text-gray-700">Click to upload vaccination certificate</span>
                            <span class="text-xs text-gray-500">JPG, PNG, or PDF format</span>
                        </label>
                        <div id="vaccine_preview_container" class="mt-4 hidden">
                            <img id="vaccine_preview" src="#" alt="Vaccine Certificate Preview" class="mx-auto max-h-32 rounded-lg border border-gray-200" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Notes -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-4">
                    <div class="bg-amber-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-sticky-note text-amber-500"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Additional Notes</h3>
                </div>
                
                <div>
                    <label for="remarks" class="block text-gray-700 font-medium mb-2">
                        Remarks <span class="text-sm font-normal text-gray-500">(Optional)</span>
                    </label>
                    <textarea 
                        id="remarks" 
                        name="remarks" 
                        rows="3" 
                        placeholder="Any additional information about your pet (allergies, special needs, etc.)" 
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-pet-purple focus:border-transparent transition-all duration-300"
                    ></textarea>
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
                    name="add_pet" 
                    class="px-8 py-3 bg-pet-purple hover:bg-purple-700 text-white font-medium rounded-lg shadow-md transition duration-300 flex items-center group"
                >
                    <i class="fas fa-paw mr-2 group-hover:animate-bounce-slow"></i> Register Pet
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
                <p class="text-blue-700 text-sm mb-3">If you have questions about pet registration or the building's pet policy:</p>
                <div class="flex items-center space-x-4 text-sm">
                    <a href="#" class="text-blue-600 hover:text-blue-800 font-medium flex items-center">
                        <i class="fas fa-phone-alt mr-2"></i> Contact Property Management
                    </a>
                    <a href="#" class="text-blue-600 hover:text-blue-800 font-medium flex items-center">
                        <i class="fas fa-envelope mr-2"></i> Email Admin
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Preview Image Function
        function previewImage(event, previewId) {
            const file = event.target.files[0];
            const preview = document.getElementById(previewId);
            const previewContainer = document.getElementById(previewId + "_container");
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            } else {
                preview.src = "";
                previewContainer.classList.add('hidden');
            }
        }

        // Set today as the default date
        document.addEventListener('DOMContentLoaded', function() {
            // Set default date for DOB field to be a year ago (for puppies/kittens)
            const dobInput = document.getElementById('dob');
            const today = new Date();
            const lastYear = new Date(today);
            lastYear.setFullYear(lastYear.getFullYear() - 1);
            
            const yyyy = lastYear.getFullYear();
            const mm = String(lastYear.getMonth() + 1).padStart(2, '0');
            const dd = String(lastYear.getDate()).padStart(2, '0');
            
            const defaultDate = `${yyyy}-${mm}-${dd}`;
            dobInput.value = defaultDate;
            
            // Clear form functionality
            document.getElementById('clearFormBtn').addEventListener('click', function() {
                document.querySelector('form').reset();
                document.getElementById('pet_preview_container').classList.add('hidden');
                document.getElementById('vaccine_preview_container').classList.add('hidden');
            });
        });
    </script>
</body>
</html>
