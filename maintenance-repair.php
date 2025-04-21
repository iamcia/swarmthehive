<?php
include 'dbconn.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Swarm | Maintenance Repair</title>
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="./css/maintenance_style.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="./css/maint-installation-style.css?v=<?php echo time(); ?>">
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Alpine.js for animations -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <!-- jQuery for AJAX -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    /* Custom animations */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .fade-in {
      animation: fadeIn 0.5s ease-out forwards;
    }
    .card-hover {
      transition: all 0.3s ease;
    }
    .card-hover:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    /* FIXED Modal styles - completely revised */
    #workPermitModal {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0, 0, 0, 0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      opacity: 1;
      visibility: visible;
    }
    
    #workPermitModal.hidden {
      display: none;
      opacity: 0;
      visibility: hidden;
    }
    
    .modal-content {
      background-color: white;
      border-radius: 0.75rem;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      width: 100%;
      max-width: 48rem;
      max-height: 90vh;
      overflow: auto;
      transform: scale(1);
      transition: transform 0.3s ease;
    }
    
    #workPermitModal.hidden .modal-content {
      transform: scale(0.9);
    }
  </style>
</head>
<body class="bg-gray-50">
    <div class="container">
        <aside class="sidebar">
            <div class="logo-container">
                <img src="./img/logo swarm.png" alt="Logo" class="logo">
                <span class="logo-text">SWARM</span>
            </div>

            <div class="menu-title">
                <i class='bx bx-wrench'></i>
                <span>Maintenance</span>
            </div>

            <nav class="sidebar-menu">
                <ul>
                    <li>
                        <a href="maintenance-dashboard.php">
                            <i class='bx bxs-dashboard'></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="#">
                            <i class='bx bx-wrench'></i>
                            <span>Operations</span>
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
      <!-- --------------
        end sidebar
      -------------------- -->

      <!-- --------------
        start main part
      --------------- -->

    <main class="p-6">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 fade-in">
            <h1 class="text-2xl font-bold text-gray-800 mb-4">Work Permit Management</h1>
            <p class="text-gray-600 mb-6">View and manage work permits for maintenance, repair, renovation, and installation requests.</p>
            
            <!-- Status filter tabs -->
            <div class="mb-6 border-b border-gray-200">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
                    <li class="mr-2">
                        <a href="#" class="status-tab inline-block p-4 border-b-2 border-blue-600 rounded-t-lg text-blue-600 active" data-status="all">
                            <i class='bx bx-list-ul mr-2'></i>All Permits
                        </a>
                    </li>
                    <li class="mr-2">
                        <a href="#" class="status-tab inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" data-status="Approval">
                            <i class='bx bx-check-circle mr-2'></i>Approval
                        </a>
                    </li>
                    <li class="mr-2">
                        <a href="#" class="status-tab inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" data-status="Pending">
                            <i class='bx bx-time-five mr-2'></i>Approved
                        </a>
                    </li>

                    <li class="mr-2">
                        <a href="#" class="status-tab inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" data-status="Complete">
                            <i class='bx bx-check-double mr-2'></i>Completed
                        </a>
                    </li>

                </ul>
            </div>
            
            <!-- Search bar -->
            <div class="relative mb-6">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i class='bx bx-search text-gray-500'></i>
                </div>
                <input type="text" id="search-input" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5" placeholder="Search by name, work type, etc.">
            </div>
            
            <!-- Work permits container -->
            <div id="work-permits-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Work permits will be loaded here dynamically -->
                <div class="flex justify-center items-center col-span-full py-10">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    <span class="ml-3 text-gray-600">Loading work permits...</span>
                </div>
            </div>
            
            <!-- Empty state -->
            <div id="empty-state" class="hidden text-center py-10">
                <i class='bx bx-folder-open text-gray-400 text-6xl'></i>
                <p class="mt-4 text-gray-500">No work permits found</p>
            </div>
        </div>
    </main>
    
    <!-- Work permit detail modal -->
    <div id="workPermitModal" class="hidden">
        <div class="modal-content">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Work Permit Details</h2>
                    <button id="closeModalBtn" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                        <i class='bx bx-x text-2xl'></i>
                    </button>
                </div>
                <div id="modal-content">
                    <!-- Modal content will be loaded here dynamically -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Current active status filter
        let currentStatus = 'all';
        let workPermits = [];
        
        // Function to load work permits
        function loadWorkPermits() {
            $('#work-permits-container').html(`
                <div class="flex justify-center items-center col-span-full py-10">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    <span class="ml-3 text-gray-600">Loading work permits...</span>
                </div>
            `);
            
            $.ajax({
                url: 'get-workpermit.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    workPermits = data;
                    displayWorkPermits();
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching work permits:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load work permits. Please try again.',
                    });
                    
                    $('#work-permits-container').html(`
                        <div class="col-span-full py-10 text-center">
                            <i class='bx bx-error-circle text-red-500 text-5xl'></i>
                            <p class="mt-4 text-gray-700">Failed to load work permits. Please try again.</p>
                            <button id="retry-btn" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                Retry
                            </button>
                        </div>
                    `);
                    
                    $('#retry-btn').on('click', loadWorkPermits);
                }
            });
        }
        
        // Function to display work permits based on current filter
        function displayWorkPermits() {
            const container = $('#work-permits-container');
            const searchTerm = $('#search-input').val().toLowerCase();
            
            // Filter permits based on status and search term
            const filteredPermits = workPermits.filter(permit => {
                // Add null checks to prevent errors if properties are missing
                const matchesStatus = currentStatus === 'all' || permit.status === currentStatus;
                const matchesSearch = 
                    (permit.owner_name && permit.owner_name.toLowerCase().includes(searchTerm)) ||
                    (permit.work_type && permit.work_type.toLowerCase().includes(searchTerm)) ||
                    (permit.task_details && permit.task_details.toLowerCase().includes(searchTerm));
                
                return matchesStatus && matchesSearch;
            });
            
            // Clear container
            container.empty();
            
            // Show empty state if no permits
            if (filteredPermits.length === 0) {
                $('#empty-state').removeClass('hidden');
                return;
            } else {
                $('#empty-state').addClass('hidden');
            }
            
            // Append each permit to the container
            filteredPermits.forEach(permit => {
                // Choose background and status colors based on status
                let statusClass, statusIcon, statusText, bgClass;
                
                switch(permit.status) {
                    case 'Pending':
                        statusClass = 'bg-yellow-100 text-yellow-800';
                        statusIcon = 'bx-time-five';
                        statusText = 'Pending';
                        bgClass = 'bg-yellow-50 border-yellow-200';
                        break;
                    case 'Approval':
                        statusClass = 'bg-green-100 text-green-800';
                        statusIcon = 'bx-check-circle';
                        statusText = 'Approved';
                        bgClass = 'bg-green-50 border-green-200';
                        break;
                    case 'Completed':
                        statusClass = 'bg-blue-100 text-blue-800';
                        statusIcon = 'bx-check-double';
                        statusText = 'Completed';
                        bgClass = 'bg-blue-50 border-blue-200s';
                        break;
                    case 'Reject':
                        statusClass = 'bg-red-100 text-red-800';
                        statusIcon = 'bx-x-circle';
                        statusText = 'Rejected';
                        bgClass = 'bg-red-50 border-red-200';
                        break;
                    default:
                        statusClass = 'bg-gray-100 text-gray-800';
                        statusIcon = 'bx-question-mark';
                        statusText = permit.status || 'Unknown';
                        bgClass = 'bg-gray-50 border-gray-200';
                }
                
                // Safely format dates with error handling
                let periodFrom = 'N/A';
                let periodTo = 'N/A';
                
                try {
                    if (permit.period_from) {
                        periodFrom = new Date(permit.period_from).toLocaleDateString();
                    }
                    if (permit.period_to) {
                        periodTo = new Date(permit.period_to).toLocaleDateString();
                    }
                } catch (e) {
                    console.error('Date formatting error:', e);
                }
                
                // Create permit card with null checks to prevent errors
                const card = `
                    <div class="card-hover border ${bgClass} rounded-lg overflow-hidden shadow-sm fade-in">
                        <div class="p-5">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">${permit.owner_name || 'Unknown'}</h3>
                                    <p class="text-sm text-gray-500">${permit.work_type || 'N/A'}</p>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClass}">
                                    <i class='bx ${statusIcon} mr-1'></i>${statusText}
                                </span>
                            </div>
                            
                            <div class="mb-4">
                                <p class="text-sm text-gray-600 mb-1">
                                    <i class='bx bx-calendar mr-1'></i>
                                    ${periodFrom} - ${periodTo}
                                </p>
                                <p class="text-sm text-gray-600">
                                    <i class='bx bx-user mr-1'></i>
                                    ${permit.contractor || 'No contractor specified'}
                                </p>
                            </div>
                            
                            <div class="text-sm text-gray-600 mb-4">
                                <p class="line-clamp-2">${permit.task_details || 'No details provided'}</p>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <button class="view-details-btn text-blue-600 hover:text-blue-800 text-sm font-medium" data-id="${permit.id}">
                                    <i class='bx bx-expand-alt mr-1'></i>View Details
                                </button>
                                
                                ${permit.status === 'Pending' ? 
                                    `<button class="complete-btn px-3 py-1.5 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700" data-id="${permit.id}">
                                        <i class='bx bx-check mr-1'></i>Mark as Complete
                                    </button>` : ''}
                            </div>
                        </div>
                    </div>
                `;
                
                container.append(card);
            });
            
            // Attach event listeners for the view details buttons
            $('.view-details-btn').on('click', function() {
                const permitId = $(this).data('id');
                viewWorkPermitDetails(permitId);
            });
            
            // Attach event listeners for the complete buttons
            $('.complete-btn').on('click', function() {
                const permitId = $(this).data('id');
                updateWorkPermitStatus(permitId, 'Completed'); // Changed from 'Complete' to 'Completed'
            });
        }
        
        // Function to view work permit details
        function viewWorkPermitDetails(permitId) {
            console.log("Opening modal for permit ID:", permitId);
            const permit = workPermits.find(p => p.id == permitId);
            
            if (!permit) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Work permit not found.',
                });
                return;
            }
            
            console.log("Permit data:", permit);
            
            // Status class for styling
            let statusClass, statusIcon, statusText;
            switch(permit.status) {
                case 'Pending':
                    statusClass = 'bg-yellow-100 text-yellow-800';
                    statusIcon = 'bx-time-five';
                    statusText = 'Approval';
                    break;
                case 'Approval':
                    statusClass = 'bg-green-100 text-green-800';
                    statusIcon = 'bx-check-circle';
                    statusText = 'Approved';
                    break;
                case 'Completed':  // Make sure this matches the database value
                    statusClass = 'bg-blue-100 text-blue-800';
                    statusIcon = 'bx-check-double';
                    statusText = 'Completed';
                    break;
                case 'Reject':
                    statusClass = 'bg-red-100 text-red-800';
                    statusIcon = 'bx-x-circle';
                    statusText = 'Rejected';
                    break;
                default:
                    statusClass = 'bg-gray-100 text-gray-800';
                    statusIcon = 'bx-question-mark';
                    statusText = permit.status || 'Unknown';
            }
            
            // Format dates with error handling
            let periodFrom = 'N/A';
            let periodTo = 'N/A';
            let submittedAt = 'N/A';
            
            try {
                if (permit.period_from) {
                    periodFrom = new Date(permit.period_from).toLocaleDateString();
                }
                if (permit.period_to) {
                    periodTo = new Date(permit.period_to).toLocaleDateString();
                }
                if (permit.submitted_at) {
                    submittedAt = new Date(permit.submitted_at).toLocaleString();
                }
            } catch (e) {
                console.error('Date formatting error:', e);
            }
            
            // Handle signature display
            let signatureHtml = '';
            if (permit.signature) {
                console.log("Signature data:", permit.signature);
                
                // Check if it's a Base64 string by looking for data URI pattern or Base64 characters
                if (permit.signature.startsWith('data:image') || 
                    /^[A-Za-z0-9+/=]+$/.test(permit.signature)) {
                    // It's likely Base64 data
                    try {
                        // Check if it's already a data URI
                        if (permit.signature.startsWith('data:image')) {
                            signatureHtml = `
                            <div>
                                <p class="text-sm font-medium text-gray-500">Signature</p>
                                <div class="mt-2">
                                    <img src="${permit.signature}" alt="Signature" class="h-20 border rounded p-2">
                                </div>
                            </div>`;
                        } else {
                            // Convert Base64 to data URI
                            signatureHtml = `
                            <div>
                                <p class="text-sm font-medium text-gray-500">Signature</p>
                                <div class="mt-2">
                                    <img src="data:image/png;base64,${permit.signature}" alt="Signature" class="h-20 border rounded p-2">
                                </div>
                            </div>`;
                        }
                    } catch (e) {
                        console.error('Error displaying base64 signature:', e);
                        signatureHtml = `
                        <div>
                            <p class="text-sm font-medium text-gray-500">Signature</p>
                            <div class="mt-2">
                                <p class="text-red-500">Error displaying signature</p>
                            </div>
                        </div>`;
                    }
                } else {
                    // It's likely a path - try both direct path and with folder prefix
                    let signaturePath = permit.signature;
                    if (!signaturePath.startsWith('Signature/')) {
                        signaturePath = 'Signature/' + signaturePath;
                    }
                    signatureHtml = `
                    <div>
                        <p class="text-sm font-medium text-gray-500">Signature</p>
                        <div class="mt-2">
                            <img src="${signaturePath}" alt="Signature" class="h-20 border rounded p-2" onerror="this.onerror=null; this.src='Signature/${permit.signature.split('/').pop()}'; this.onerror=function(){this.style.display='none'; this.parentNode.innerHTML += '<p class=\\'text-red-500\\'>Signature image not found</p>';}">
                        </div>
                    </div>`;
                }
            }
            
            // Prepare the details HTML with null checks
            const detailsHtml = `
                <div class="space-y-6">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-800">${permit.work_type || 'N/A'}</h3>
                        <span class="px-3 py-1 rounded-full text-sm font-medium ${statusClass}">
                            <i class='bx ${statusIcon} mr-1'></i>${statusText}
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Owner Name</p>
                            <p class="text-gray-800">${permit.owner_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Resident Code</p>
                            <p class="text-gray-800">${permit.Resident_Code || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Authorized Representative</p>
                            <p class="text-gray-800">${permit.authorize_rep || 'Not specified'}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Contractor</p>
                            <p class="text-gray-800">${permit.contractor || 'Not specified'}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Period</p>
                            <p class="text-gray-800">${periodFrom} - ${periodTo}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Submitted</p>
                            <p class="text-gray-800">${submittedAt}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">User Type</p>
                            <p class="text-gray-800">${permit.user_type || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Email</p>
                            <p class="text-gray-800">${permit.user_email || 'N/A'}</p>
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-sm font-medium text-gray-500">Task Details</p>
                        <p class="text-gray-800 bg-gray-50 p-3 rounded-lg mt-1">${permit.task_details || 'No details available'}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm font-medium text-gray-500">Personnel Details</p>
                        <p class="text-gray-800 bg-gray-50 p-3 rounded-lg mt-1">${permit.personnel_details || 'No details available'}</p>
                    </div>
                    
                    ${signatureHtml}
                    
                    ${(permit.status === 'Pending' || permit.status === 'Approval') ? `
                    <div class="flex justify-end">
                        <button id="modal-complete-btn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700" data-id="${permit.id}">
                            <i class='bx bx-check mr-1'></i>Mark as Complete
                        </button>
                    </div>
                    ` : ''}
                </div>
            `;
            
            // Set the modal content
            $('#modal-content').html(detailsHtml);
            
            // Show the modal using jQuery instead of Alpine.js
            $('#workPermitModal').removeClass('hidden');
            
            // Add event listener for the complete button in the modal
            $('#modal-complete-btn').on('click', function() {
                const permitId = $(this).data('id');
                updateWorkPermitStatus(permitId, 'Completed'); // Changed from 'Complete' to 'Completed'
                $('#workPermitModal').addClass('hidden');
            });
        }
        
        // Function to update work permit status
        function updateWorkPermitStatus(permitId, newStatus) {
            Swal.fire({
                title: 'Update Status',
                text: `Are you sure you want to mark this work permit as ${newStatus.toLowerCase()}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, update it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Updating...',
                        text: 'Please wait while we update the status.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Send AJAX request to update status
                    $.ajax({
                        url: 'update-workpermit-status.php',
                        type: 'POST',
                        data: {
                            id: permitId,
                            status: newStatus
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Success!',
                                    text: response.message,
                                    icon: 'success'
                                });
                                
                                // Update the local data and refresh the display
                                const index = workPermits.findIndex(p => p.id == permitId);
                                if (index !== -1) {
                                    workPermits[index].status = newStatus;
                                    displayWorkPermits();
                                }
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: response.message || 'Failed to update status.',
                                    icon: 'error'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire({
                                title: 'Error',
                                text: 'An error occurred while updating the status.',
                                icon: 'error'
                            });
                            console.error('Error updating status:', error);
                        }
                    });
                }
            });
        }
        
        // Event handlers for filter tabs
        $('.status-tab').on('click', function(e) {
            e.preventDefault();
            
            // Update active tab
            $('.status-tab').removeClass('border-blue-600 text-blue-600 active').addClass('border-transparent');
            $(this).addClass('border-blue-600 text-blue-600 active').removeClass('border-transparent');
            
            // Update current status filter
            currentStatus = $(this).data('status');
            
            // Refresh the display
            displayWorkPermits();
        });
        
        // Event handler for search input
        $('#search-input').on('input', debounce(function() {
            displayWorkPermits();
        }, 300));
        
        // Debounce function to prevent excessive filtering on search
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }
        
        // Add event listener for close button
        $(document).ready(function() {
            $('#closeModalBtn').on('click', function() {
                $('#workPermitModal').addClass('hidden');
            });
            
            // Close modal when clicking outside the content
            $('#workPermitModal').on('click', function(e) {
                if (e.target === this) {
                    $(this).addClass('hidden');
                }
            });
            
            // Load work permits on page load
            loadWorkPermits();
        });
    </script>
</body>
</html>