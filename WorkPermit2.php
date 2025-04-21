<?php
include("dbconn.php");
session_start();

$message = '';
$userType = '';
$userEmail = '';
$signature = '';
$userNumber = '';
$unitNumber = '';
$residentCode = '';
$status = '';
$user_id = null; // Added user_id variable

// Fetch user details if the user is logged in
if (isset($_SESSION['username'])) {
    $ownerUsername = $_SESSION['username'];
    
    // First, attempt to fetch information from the OwnerInformation table
    $sql = "SELECT ID, Owner_ID, First_Name, Last_Name, Email, Mobile_Number, Unit_Number, Signature, Status FROM ownerinformation WHERE Username = ?"; // Added ID to select
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ownerUsername);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userType = 'Owner';
        $residentCode = $row['Owner_ID'];
        $ownerName = $row['First_Name'] . " " . $row['Last_Name'];
        $userEmail = $row['Email'];
        $userNumber = $row['Mobile_Number'];
        $unitNumber = $row['Unit_Number'];
        $signature = !empty($row['Signature']) ? base64_encode($row['Signature']) : '';
        $status = $row['Status'];
        $user_id = $row['ID']; // Store the user ID
    } else {
        // Check TenantInformation if no match found in OwnerInformation
        $sql = "SELECT Tenant_ID, First_Name, Last_Name, Email, Mobile_Number, Unit_Number, Signature, Status FROM tenantinformation WHERE Username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $ownerUsername);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $userType = 'Tenant';
            $residentCode = $row['Tenant_ID'];
            $ownerName = $row['First_Name'] . " " . $row['Last_Name'];
            $userEmail = $row['Email'];
            $userNumber = $row['Mobile_Number'];
            $unitNumber = $row['Unit_Number'];
            $signature = !empty($row['Signature']) ? base64_encode($row['Signature']) : '';
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
    $stmt->close();
}

$conn->close();
?>  

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Swarm | Work Permit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Add Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'swarm-yellow': '#f1c40f',
                        'swarm-dark-yellow': '#e1b00f',
                        'swarm-light-yellow': '#fef9c3',
                        'swarm-blue': '#3498db',
                        'swarm-green': '#2ecc71',
                        'swarm-orange': '#e67e22',
                        'swarm-red': '#e74c3c',
                    },
                    boxShadow: {
                        'custom': '0 4px 20px rgba(0, 0, 0, 0.05)',
                        'hover': '0 10px 30px rgba(0, 0, 0, 0.1)',
                    },
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 py-10">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <button onclick="history.back()" class="px-4 py-2 bg-white hover:bg-gray-100 text-gray-700 rounded-full shadow-custom flex items-center gap-2 transition duration-300 mr-5">
                        <i class="fas fa-arrow-left text-sm"></i>
                        <span>Back</span>
                    </button>
                    
                    <h1 class="text-3xl md:text-4xl font-extrabold text-gray-800">
                        <span class="text-swarm-yellow">Work</span> Permit
                    </h1>
                </div>
                
                <div class="hidden md:block">
                    <div class="bg-swarm-light-yellow text-swarm-dark-yellow font-medium rounded-full px-4 py-1 flex items-center">
                        <i class="fas fa-tools mr-2"></i>
                        <span>Form #WP-<?php echo date('Ymd'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl p-6 shadow-custom mb-8 transition-all duration-300 hover:shadow-hover border-l-4 border-swarm-yellow">
                <div class="flex items-start">
                    <div class="bg-swarm-light-yellow p-3 rounded-full mr-4">
                        <i class="fas fa-hard-hat text-swarm-yellow text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold mb-4 flex items-center text-gray-800">
                            Important Reminders
                            <span class="bg-swarm-light-yellow text-swarm-dark-yellow text-xs uppercase tracking-wide font-bold rounded-full px-3 py-1 ml-3">Read Carefully</span>
                        </h3>
                        
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-swarm-yellow mt-1 mr-2"></i>
                                <span>Work can only be done between <strong>8:00 AM to 5:00 PM</strong> from Monday to Saturday.</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-swarm-yellow mt-1 mr-2"></i>
                                <span>All building materials and construction debris must be properly disposed.</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-swarm-yellow mt-1 mr-2"></i>
                                <span>All workers must wear proper identification and follow building protocols.</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-swarm-yellow mt-1 mr-2"></i>
                                <span>Work permits are subject to approval by property management.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Container - Updated action to point to the new insert file -->
        <form action="Insert-WorkPermit.php" method="POST" class="space-y-8">
            <!-- Hidden Resident Code and User fields -->
            <input type="hidden" name="Resident_Code" value="<?php echo htmlspecialchars($residentCode); ?>" readonly>
            <input type="hidden" name="user_type" value="<?php echo htmlspecialchars($userType); ?>" readonly>
            <input type="hidden" name="user_email" value="<?php echo htmlspecialchars($userEmail); ?>" readonly>
            <input type="hidden" name="signature" value="<?php echo htmlspecialchars($signature); ?>" readonly>
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>" readonly>
            
            <!-- Work Type Section -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-5">
                    <div class="bg-blue-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-clipboard-list text-swarm-blue"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Type of Work</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                        <input type="checkbox" id="type1" name="type[]" value="Maintenance / Repair" class="w-5 h-5 accent-swarm-yellow focus:ring-swarm-yellow">
                        <label for="type1" class="font-medium cursor-pointer flex items-center">
                            <i class="fas fa-wrench text-swarm-blue mr-2"></i> Maintenance / Repair
                        </label>
                    </div>
                    <div class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                        <input type="checkbox" id="type2" name="type[]" value="Renovation" class="w-5 h-5 accent-swarm-yellow focus:ring-swarm-yellow">
                        <label for="type2" class="font-medium cursor-pointer flex items-center">
                            <i class="fas fa-paint-roller text-swarm-orange mr-2"></i> Renovation
                        </label>
                    </div>
                    <div class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                        <input type="checkbox" id="type3" name="type[]" value="Installation" class="w-5 h-5 accent-swarm-yellow focus:ring-swarm-yellow">
                        <label for="type3" class="font-medium cursor-pointer flex items-center">
                            <i class="fas fa-plug text-swarm-green mr-2"></i> Installation
                        </label>
                    </div>
                </div>
            </div>

            <!-- Owner Information Section -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-5">
                    <div class="bg-green-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-user text-swarm-green"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Owner Information</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="owner_name" class="block text-gray-700 font-medium mb-2">
                            Name of Owner
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user-circle text-gray-400"></i>
                            </div>
                            <input 
                                type="text" 
                                id="owner_name" 
                                name="owner_name" 
                                value="<?php echo htmlspecialchars($ownerName); ?>" 
                                readonly
                                class="w-full pl-10 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700"
                            >
                        </div>
                    </div>
                    
                    <div>
                        <label for="authorize" class="block text-gray-700 font-medium mb-2">
                            Authorized Representative <span class="text-swarm-red">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user-tie text-gray-400"></i>
                            </div>
                            <input 
                                type="text" 
                                id="authorize" 
                                name="authorize" 
                                required
                                placeholder="Enter name of representative"
                                class="w-full pl-10 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-swarm-yellow focus:border-transparent transition-all duration-300"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contractor and Period Section -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-5">
                    <div class="bg-orange-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-calendar-alt text-swarm-orange"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Contractor & Period Details</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="contractor" class="block text-gray-700 font-medium mb-2">
                            Contractor (if any)
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-hard-hat text-gray-400"></i>
                            </div>
                            <input 
                                type="text" 
                                id="contractor" 
                                name="contractor" 
                                placeholder="Company name (optional)"
                                class="w-full pl-10 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-swarm-yellow focus:border-transparent transition-all duration-300"
                            >
                        </div>
                    </div>
                    
                    <div>
                        <label for="period_from" class="block text-gray-700 font-medium mb-2">
                            Period From <span class="text-swarm-red">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar-day text-gray-400"></i>
                            </div>
                            <input 
                                type="date" 
                                id="period_from" 
                                name="period_from" 
                                required
                                class="w-full pl-10 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-swarm-yellow focus:border-transparent transition-all duration-300"
                            >
                        </div>
                    </div>
                    
                    <div>
                        <label for="period_to" class="block text-gray-700 font-medium mb-2">
                            Period To <span class="text-swarm-red">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar-check text-gray-400"></i>
                            </div>
                            <input 
                                type="date" 
                                id="period_to" 
                                name="period_to" 
                                required
                                class="w-full pl-10 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-swarm-yellow focus:border-transparent transition-all duration-300"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scope of Work Table Section -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-5">
                    <div class="bg-purple-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-tasks text-purple-600"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Scope of Work</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table id="scopeOfWorkTable" class="min-w-full bg-white">
                        <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider">
                            <tr>
                                <th class="py-3 px-4 text-left rounded-tl-lg">Task</th>
                                <th class="py-3 px-4 text-left">Personnel</th>
                                <th class="py-3 px-4 text-center rounded-tr-lg w-24">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-2 px-4">
                                    <input 
                                        type="text" 
                                        name="task[]" 
                                        placeholder="Describe the task"
                                        required 
                                        class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-swarm-yellow focus:border-transparent"
                                    >
                                </td>
                                <td class="py-2 px-4">
                                    <input 
                                        type="text" 
                                        name="personnel[]" 
                                        placeholder="Name of worker(s)"
                                        required 
                                        class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-swarm-yellow focus:border-transparent"
                                    >
                                </td>
                                <td class="py-2 px-4 text-center">
                                    <button 
                                        type="button" 
                                        onclick="removeRow(this)" 
                                        class="text-swarm-red hover:text-red-700 hover:bg-red-50 p-2 rounded-full transition-colors"
                                    >
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4 text-right">
                    <button 
                        type="button" 
                        id="addRowBtn" 
                        onclick="addRow()" 
                        class="px-4 py-2 bg-swarm-yellow hover:bg-swarm-dark-yellow text-white rounded-lg transition-colors flex items-center gap-2 ml-auto"
                    >
                        <i class="fas fa-plus"></i> Add More Tasks
                    </button>
                </div>
            </div>

            <!-- Signature Section -->
            <?php if (!empty($signature)): ?>
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-5">
                    <div class="bg-blue-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-signature text-swarm-blue"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Signature</h3>
                </div>
                
                <div class="flex justify-center p-4 bg-gray-50 rounded-lg border border-gray-100">
                    <img 
                        src="data:image/png;base64,<?php echo $signature; ?>" 
                        alt="Signature" 
                        class="h-24 object-contain"
                    >
                </div>
            </div>
            <?php endif; ?>

            <!-- Terms and Agreement Section -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-4">
                    <div class="bg-red-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-file-contract text-swarm-red"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Terms and Agreement</h3>
                </div>
                
                <div class="p-4 bg-gray-50 rounded-lg mb-4 text-sm text-gray-700 border border-gray-200">
                    <p>By submitting this form, I understand and agree:</p>
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        <li>All work will comply with building codes and regulations</li>
                        <li>Workers are my responsibility and must follow building protocols</li>
                        <li>I am responsible for any damages resulting from the work</li>
                        <li>Permission may be revoked if terms are violated</li>
                    </ul>
                </div>
                
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        id="terms_agree" 
                        required
                        class="w-5 h-5 accent-swarm-yellow focus:ring-swarm-yellow rounded"
                    >
                    <label for="terms_agree" class="ml-2 text-gray-700">
                        I agree to the terms and conditions
                    </label>
                </div>
            </div>

            <!-- Form Buttons -->
            <div class="flex justify-end gap-4 mt-8">
                <button 
                    type="button" 
                    onclick="window.location.href='dashboard.php'" 
                    class="px-6 py-3 bg-white text-gray-700 border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 transition-colors flex items-center"
                >
                    <i class="fas fa-times mr-2"></i> Cancel
                </button>
                <button 
                    type="submit" 
                    class="px-8 py-3 bg-swarm-yellow hover:bg-swarm-dark-yellow text-gray-800 rounded-lg shadow-md transition-colors flex items-center"
                >
                    <i class="fas fa-paper-plane mr-2"></i> Submit Request
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
                <p class="text-blue-700 text-sm mb-3">If you have questions about filing work permits:</p>
                <div class="flex flex-wrap gap-4 text-sm">
                    <a href="tel:+1234567890" class="text-blue-600 hover:text-blue-800 font-medium flex items-center">
                        <i class="fas fa-phone-alt mr-2"></i> Building Admin: (123) 456-7890
                    </a>
                    <a href="mailto:admin@example.com" class="text-blue-600 hover:text-blue-800 font-medium flex items-center">
                        <i class="fas fa-envelope mr-2"></i> Email Support
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add a new row to the scope of work table
        function addRow() {
            const table = document.getElementById("scopeOfWorkTable").getElementsByTagName('tbody')[0];
            const row = table.insertRow();
            row.className = "hover:bg-gray-50 transition-colors";
            row.innerHTML = `
                <td class="py-2 px-4">
                    <input 
                        type="text" 
                        name="task[]" 
                        placeholder="Describe the task"
                        required 
                        class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-swarm-yellow focus:border-transparent"
                    >
                </td>
                <td class="py-2 px-4">
                    <input 
                        type="text" 
                        name="personnel[]" 
                        placeholder="Name of worker(s)"
                        required 
                        class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-swarm-yellow focus:border-transparent"
                    >
                </td>
                <td class="py-2 px-4 text-center">
                    <button 
                        type="button" 
                        onclick="removeRow(this)" 
                        class="text-swarm-red hover:text-red-700 hover:bg-red-50 p-2 rounded-full transition-colors"
                    >
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            `;
        }

        // Remove a row from the table
        function removeRow(button) {
            // Get the table row containing the button and remove it
            const row = button.closest("tr");
            // Make sure we keep at least one row
            const table = document.getElementById("scopeOfWorkTable").getElementsByTagName('tbody')[0];
            if (table.rows.length > 1) {
                row.remove();
            } else {
                alert("You must have at least one task in the scope of work.");
            }
        }
        
        // Date validation to ensure period_to is after period_from
        document.addEventListener("DOMContentLoaded", function() {
            const periodFrom = document.getElementById("period_from");
            const periodTo = document.getElementById("period_to");
            
            // Set minimum date to today for both date inputs
            const today = new Date().toISOString().split('T')[0];
            periodFrom.min = today;
            
            // Update period_to min when period_from changes
            periodFrom.addEventListener("change", function() {
                periodTo.min = periodFrom.value;
                if (periodTo.value && new Date(periodTo.value) < new Date(periodFrom.value)) {
                    periodTo.value = periodFrom.value;
                }
            });
            
            <?php if (isset($_SESSION['success_message'])): ?>
                alert("<?php echo $_SESSION['success_message']; ?>");
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                alert("<?php echo $_SESSION['error_message']; ?>");
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>
