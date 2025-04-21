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
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
  <link
      href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css"
      rel="stylesheet"
    />
  <link rel="stylesheet" href="sidebar.css">
  <link rel="stylesheet" href="dashboard.css">
</head>
<style>
       <style>
    body {
        background-color: #121212; /* Dark background */
        color: #E0E0E0; /* Light text color */
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        padding: 20px;
        margin: 0;
    }
    
    .table-container {
        max-height: 400px; /* Adjusted for better visibility */
        overflow-y: auto;
        margin-bottom: 20px;
        border: 1px solid #444;
        border-radius: 8px;
        background-color: #1E1E1E; /* Slightly darker background */
        color: #DDD;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    th, td {
        padding: 12px;
        border: 1px solid #444;
        text-align: left;
    }
    
    th {
        background-color: #333;
        color: #E0E0E0;
        position: sticky;
        top: 0;
        z-index: 1;
        font-weight: bold;
    }
    
    td {
        background-color: #222;
        color: #DDD;
    }

    td:hover {
        background-color: #333;
        cursor: pointer;
    }

    button, select {
        background-color: #4CAF50;
        color: #FFF;
        border: none;
        padding: 8px 16px;
        margin: 5px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
        transition: background-color 0.3s ease;
    }
    
    button:hover, select:hover {
        background-color: #45A049;
    }
    
    h2 {
        color: #F0F0F0;
        margin-top: 20px;
        font-size: 24px;
    }
    
    .filter-container {
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .filter-container label {
        font-weight: bold;
    }
    
    select {
        background-color: #333;
        color: #E0E0E0;
        border-radius: 4px;
        padding: 8px;
        border: 1px solid #444;
    }
    
    select:focus, button:focus {
        outline: none;
        box-shadow: 0 0 5px rgba(72, 163, 72, 0.8);
    }

    button {
        transition: background-color 0.3s ease;
    }
    
     /* Container for the table with scrollbar */
        .table-container {
            max-width: 70%; /* Adjust width as needed */
            max-height: 180px; /* Adjust height as needed */
            overflow-y: auto; /* Enables vertical scrollbar */
            overflow-x: auto; /* Enables horizontal scrollbar */
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Table styling */
        table {
            width: 100%; /* Ensures table fits container width */
            border-collapse: collapse;
        }

        th, td {
            padding: 2px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #a52a2a; /* Darker color for header */
            color: #ffffff;
        }

        /* Hover effect for table rows */
        tr:hover {
            background-color: #f5f5f5;
        }
</style>
<body>
   <div class="container">
      <aside>
           
         
         <!-- end top -->
          <div class="sidebar">

          <div class="top">
           <div class="close" id="close_btn">
            <span class="material-symbols-sharp">close</span>
           </div>
         </div>

           <a href="AdminHomepage.php" >
              <span class="bx bx-grid-alt"></span>
              <h3>Overview</h3></a>
           <a href="UserManagement.php" class="active">       <!-- Owner, Tenant, Occupants, Helper, Contacts, Proof of Residency tables-->
              <span class="bx bx-user"></span>
              <h3>User Management</h3>
           </a>
           <a href="OwnerTenantAnnouncement.php">       <!-- OwnerTenantAnnouncement table-->
              <span class="bx bx-grid-alt"></span>
              <h3>Announcements</h3></a>
           <a href="AuditTrail.php">                        <!-- Audit Trail table-->
              <span class="bx bx-user"></span>
              <h3>Audit Logs</h3>
           </a>
           <a href="OwnerTenantMoveIn.php">                        <!-- Move In Table-->
              <span class="bx bx-file"></span>
              <h3>Move In</h3>
           </a>
          <a href="OwnerTenantMoveOut.php">                         <!-- Move Out Table-->
              <span class="bx bx-wallet"></span>
              <h3>Move Out</h3>
           </a>
           <a href="#">                        <!-- OwnerTenantReservation and Pool Tables-->
              <span class="bx bx-chat"></span>
              <h3>Amenitie Reservations</h3>
           </a>
            <a href="#">                       <!-- Pets Tables-->
            <span class="bx bx-file-blank"></span>
            <h3>Pet Registration</h3>
         </a>
            <a href="#">                       <!-- Gatepass, Guest Check In Tables-->
            <span class="bx bx-file-blank"></span>
            <h3>Security Forms</h3>
         </a>
            <a href="#">                       <!-- Work Permit Table-->
            <span class="bx bx-file-blank"></span>
            <h3>Maintenance Forms</h3>
         </a>
           <a href="#">
            <span class="bx bx-cog"></span>
            <h3>Settings</h3>
         </a>
            
           <a href="#">
              <span class="bx bx-log-out"></span>
              <h3>Logout</h3>
           </a>
             

          </div>

      </aside>
      <!-- --------------
        end sidebar
      -------------------- -->

<!-- -------------- 
      start main part 
 --------------- -->

<main>
</main>
<!-- ------------------ 
      end main 
------------------- -->



     
        <script>
          const  sideMenu = document.querySelector('aside');
const menuBtn = document.querySelector('#menu_bar');
const closeBtn = document.querySelector('#close_btn');


menuBtn.addEventListener('click',()=>{
       sideMenu.style.display = "block"
})
closeBtn.addEventListener('click',()=>{
    sideMenu.style.display = "none"
})
function filterStatus() {
        var filterValue = document.getElementById("status-filter").value;
        var ownerTable = document.getElementById("owner-table");
        var tenantTable = document.getElementById("tenant-table");

        filterTable(ownerTable, filterValue);
        filterTable(tenantTable, filterValue);
    }

    function filterTable(table, filterValue) {
        var rows = table.getElementsByTagName("tr");
        
        // Start from 1 to skip the header row
        for (var i = 1; i < rows.length; i++) {
            var status = rows[i].getAttribute("data-status");
            
            if (filterValue === "All" || status === filterValue) {
                rows[i].style.display = ""; // Show row
            } else {
                rows[i].style.display = "none"; // Hide row
            }
        }
    }
          function toggleForm() {
        var form = document.getElementById("addForm");
        if (form.style.display === "none" || form.style.display === "") {
            form.style.display = "block"; // Show the form
        } else {
            form.style.display = "none"; // Hide the form
        }
    }
    function toggleForm(formId) {
    const form = document.getElementById(formId);
    form.style.display = form.style.display === "none" ? "block" : "none";
}

   function loadSection(page) {
        const contentDiv = document.getElementById('content');

        // Use fetch to dynamically load the content
        fetch(page)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(data => {
                contentDiv.innerHTML = data;
            })
            .catch(error => {
                contentDiv.innerHTML = `<p>Error loading section: ${error.message}</p>`;
            });
    }
        </script>
</body>
</html>