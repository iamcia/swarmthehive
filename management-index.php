<?php
include('dbconn.php');
session_start();

// Process login when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['user']);  
    $password = trim($_POST['password']);

    // Validate input
    if (empty($user) || empty($password)) {
        $_SESSION['login_error'] = "Please enter both Management Code and Password.";
        header("Location: management-index.php");
        exit();
    }

    // Convert Management_Code to uppercase to match database format
    $user = strtoupper($user);

    // Prepare SQL statement
    $sql = "SELECT Management_ID, Management_Code, firstname, lastname, position, password FROM managementaccount WHERE Management_Code = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $_SESSION['login_error'] = "Database error: " . $conn->error;
        header("Location: management-index.php");
        exit();
    }

    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Password verification with proper hashing
        if (password_verify($password, $row['password'])) {  
            // Store session variables
            $_SESSION['Management_ID'] = $row['Management_ID'];
            $_SESSION['Management_Code'] = $row['Management_Code'];
            $_SESSION['firstname'] = $row['firstname'];
            $_SESSION['lastname'] = $row['lastname'];
            $_SESSION['position'] = $row['position'];

            // Redirect based on position
            switch ($row['position']) {
                case "Finance":
                    header("Location: finance-dashboard.php");
                    break;
                case "Security":
                    header("Location: security-dashboard.php");
                    break;
                case "Maintenance":
                    header("Location: maintenance-dashboard.php");
                    break;
                case "Admin": // Redirect to Admin Dashboard
                    header("Location: adm-dashboard.php");
                    break;
                case "Property Manager": // Redirect to PM Scorecard
                    header("Location: PM-scorecard.php");
                    break;
                default:
                    $_SESSION['login_error'] = "Invalid position assigned.";
                    header("Location: management-index.php");
                    break;
            }
            exit();
        } else {
            $_SESSION['login_error'] = "Invalid Management Code or Password!";
        }
    } else {
        $_SESSION['login_error'] = "Management account not found!";
    }

    $stmt->close();
    $conn->close();

    // Redirect to prevent form resubmission
    header("Location: management-index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swarm | Management Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#e9be3a',
                        secondary: '#2c3e50',
                        accent: '#3498db',
                        danger: '#e74c3c',
                        success: '#2ecc71',
                        neutral: '#f8f9fa',
                        'neutral-focus': '#e9ecef'
                    },
                    fontFamily: {
                        'sans': ['Inter', 'sans-serif']
                    },
                    boxShadow: {
                        'card': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
                        'button': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)'
                    }
                }
            }
        }
    </script>
    <style>
        /* Additional custom styles */
        .form-input-focus:focus {
            box-shadow: 0 0 0 3px rgba(233, 190, 58, 0.2);
        }
    </style>
</head>
<body class="bg-neutral min-h-screen flex items-center justify-center p-6 font-sans">
    <!-- Background pattern -->
    <div class="fixed inset-0 bg-gradient-to-br from-neutral to-white z-0 opacity-70"></div>
    
    <!-- Main content -->
    <div class="relative z-10 w-full max-w-md">
        <div class="bg-white rounded-xl shadow-card p-8 border border-gray-100">
            <!-- Header and logo -->
            <div class="text-center mb-8">
                <div class="flex justify-center mb-4">
                    <img src="./img/logo swarm.png" class="h-14" alt="Swarm Logo">
                </div>
                <h1 class="text-2xl font-semibold text-secondary mb-1">Management Portal</h1>
                <p class="text-gray-500 text-sm">Secure access for authorized personnel</p>
            </div>
            
            <!-- Error message -->
            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="bg-red-50 border-l-4 border-danger text-danger px-4 py-3 rounded mb-6">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <span><?php echo $_SESSION['login_error']; unset($_SESSION['login_error']); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Login form -->
            <form id="loginForm" method="POST" action="management-index.php" class="space-y-5">
                <div>
                    <label for="user" class="block text-sm font-medium text-gray-700 mb-1">Management Code</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            name="user" 
                            id="user" 
                            placeholder="Enter your management code" 
                            required
                            class="form-input-focus pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors bg-white text-gray-800"
                        >
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            placeholder="Enter your password" 
                            required
                            class="form-input-focus pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors bg-white text-gray-800"
                        >
                    </div>
                </div>
                
                <div>
                    <button 
                        type="submit" 
                        class="w-full py-2.5 px-4 bg-primary hover:bg-opacity-90 text-white font-medium rounded-lg transition-all duration-200 shadow-button flex items-center justify-center"
                    >
                        <span>Access Portal</span>
                        <svg class="w-5 h-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </form>
            
            <!-- Divider -->
            <div class="flex items-center my-6">
                <div class="flex-grow border-t border-gray-200"></div>
                <span class="flex-shrink mx-4 text-gray-400 text-sm">or</span>
                <div class="flex-grow border-t border-gray-200"></div>
            </div>
            
            <!-- Register button -->
            <form action="ManagementRegistration.php" method="GET">
                <button 
                    type="submit" 
                    class="w-full py-2.5 px-4 bg-white border border-gray-300 text-secondary font-medium rounded-lg transition-all duration-200 hover:bg-gray-50 flex items-center justify-center"
                >
                    <svg class="w-5 h-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
                    </svg>
                    <span>Create New Account</span>
                </button>
            </form>
            
            <!-- Footer -->
            <div class="mt-8 text-center text-xs text-gray-500">
                <p>© 2025 Swarm</p>
            </div>
            
            <!-- Spinner -->
            <div id="spinner" class="hidden fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50">
                <div class="bg-white p-5 rounded-lg flex items-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mr-3"></div>
                    <span class="text-gray-700">Authenticating...</span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            document.getElementById('spinner').classList.remove('hidden');
        });
    </script>
</body>
</html>