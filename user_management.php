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
  <link rel="stylesheet" href="./css/sidebar.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="./css/user_management.css?v=<?php echo time(); ?>">
</head>
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

            <a href="./dashboard.php">
              <span class="bx bx-grid-alt"></span>
              <h3>Dashboard</h3>
           </a>
           <a href="./performance_overview.php">
              <span class="bx bx-pie-chart-alt-2"></span>
              <h3>Performance Overview</h3>
           </a>
           <a href="./user_management.php" class="active">
              <span class="bx bx-user"></span>
              <h3>User Management</h3>
           </a>
           <a href="./announcement.php">
              <span class="bx bx-bell"></span>
              <h3>Announcements</h3>
           </a>
           <a href="./market_overview.php">
              <span class="bx bx-store"></span>
              <h3>Market Overview</h3>
           </a>
           <a href="./service_request.php">
              <span class="bx bx-file"></span>
              <h3>Service Requests</h3>
           </a>
           <a href="./financial_overview.php">
              <span class="bx bx-wallet"></span>
              <h3>Financial Overview</h3>
           </a>
           <a href="./community_insights.php">
              <span class="bx bx-chat"></span>
              <h3>Community Insights</h3>
           </a>
            <a href="./audit_logs.php">
            <span class="bx bx-file-blank"></span>
            <h3>Audit Logs</h3>
         </a>
           <a href="./settings.php">
            <span class="bx bx-cog"></span>
            <h3>Settings</h3>
         </a>
           <a href="./index.php">
              <span class="bx bx-log-out"></span>
              <h3>Logout</h3>
           </a>
             

          </div>

      </aside>
      <!-- --------------
        end sidebar
      -------------------- -->

       <main>
      <div class="user-management">
        <h2>User Management</h2>

        <div class="search-bar">
          <input type="text" id="searchInput" placeholder="Search for users..." onkeyup="searchUsers()">
        </div>

        <table id="userTable">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>John Doe</td>
              <td>john.doe@example.com</td>
              <td>Admin</td>
              <td class="actions">
                <button class="btn edit-btn" onclick="editUser('John Doe')">Edit</button>
                <button class="btn delete-btn" onclick="deleteUser('John Doe')">Delete</button>
              </td>
            </tr>
            <tr>
              <td>Jane Smith</td>
              <td>jane.smith@example.com</td>
              <td>Finance</td>
              <td class="actions">
                <button class="btn edit-btn" onclick="editUser('Jane Smith')">Edit</button>
                <button class="btn delete-btn" onclick="deleteUser('Jane Smith')">Delete</button>
              </td>
            </tr>
            <!-- Add more users as needed -->
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <script>
    function searchUsers() {
      const input = document.getElementById('searchInput');
      const filter = input.value.toLowerCase();
      const table = document.getElementById('userTable');
      const tr = table.getElementsByTagName('tr');

      for (let i = 1; i < tr.length; i++) {
        const tdName = tr[i].getElementsByTagName('td')[0];
        const tdEmail = tr[i].getElementsByTagName('td')[1];
        if (tdName || tdEmail) {
          const txtValueName = tdName.textContent || tdName.innerText;
          const txtValueEmail = tdEmail.textContent || tdEmail.innerText;
          if (txtValueName.toLowerCase().indexOf(filter) > -1 || txtValueEmail.toLowerCase().indexOf(filter) > -1) {
            tr[i].style.display = '';
          } else {
            tr[i].style.display = 'none';
          }
        }
      }
    }

    function editUser(userName) {
      alert('Edit user: ' + userName);
      // Implement editing functionality here
    }

    function deleteUser(userName) {
      if (confirm('Are you sure you want to delete user: ' + userName + '?')) {
        // Implement deletion functionality here
        alert('User ' + userName + ' deleted.');
      }
    }
  </script>

    
</body>
</html>