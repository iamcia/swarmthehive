<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Swarm | Property Manager Audit Logs</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
  <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="./css/sidebar.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="./css/audit_logs.css?v=<?php echo time(); ?>">
</head>
<body>
   <div class="container">
      <aside>
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
              <a href="./user_management.php">
                  <span class="bx bx-user"></span>
                  <h3>User Management</h3>
              </a>
              <a href="./announcement.php">
                  <span class="bx bx-bell"></span>
                  <h3>Announcements</h3>
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
              <a href="./audit_logs.php" class="active">
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

      <main>
          <div class="audit-logs">
              <h1>Audit Logs</h1>

              <div class="log-filter">
                  <div class="filter-container">
                      <input type="checkbox" id="adminFilter" class="toggle" value="Admin" onchange="filterLogs()">
                      <label for="adminFilter" class="toggle-label"></label>
                      <span>Admin</span>
                  </div>
                  <input type="text" id="logSearch" class="log-search" placeholder="Search logs..." onkeyup="filterLogs()" />
              </div>

              <div class="log-entry" data-log-user="User789" data-log-type="User">
                  <div class="log-header">
                      <span class="log-date">2024-10-22</span>
                      <span class="log-user">User789</span>
                  </div>
                  <div class="log-action">
                      <span class="log-message">Deleted a record</span>
                  </div>
              </div>

              <div class="log-entry" data-log-user="Admin123" data-log-type="Admin">
                  <div class="log-header">
                      <span class="log-date">2024-10-21</span>
                      <span class="log-user">Admin123</span>
                  </div>
                  <div class="log-action">
                      <span class="log-message">Updated user details</span>
                  </div>
              </div>

              <div class="log-entry" data-log-user="User456" data-log-type="User">
                  <div class="log-header">
                      <span class="log-date">2024-10-20</span>
                      <span class="log-user">User456</span>
                  </div>
                  <div class="log-action">
                      <span class="log-message">Created a new record</span>
                  </div>
              </div>

              <div class="log-entry" data-log-user="Admin789" data-log-type="Admin">
                  <div class="log-header">
                      <span class="log-date">2024-10-19</span>
                      <span class="log-user">Admin789</span>
                  </div>
                  <div class="log-action">
                      <span class="log-message">Generated a report</span>
                  </div>
              </div>

              <div class="log-entry" data-log-user="User321" data-log-type="User">
                  <div class="log-header">
                      <span class="log-date">2024-10-18</span>
                      <span class="log-user">User321</span>
                  </div>
                  <div class="log-action">
                      <span class="log-message">Logged in</span>
                  </div>
              </div>
          </div>
      </main>
    
      <script>
          function filterLogs() {
              const searchInput = document.getElementById('logSearch').value.toLowerCase();
              const logEntries = document.querySelectorAll('.log-entry');

              // Get checked filter types
              const checkedTypes = Array.from(document.querySelectorAll('.filter-container .toggle:checked'))
                                        .map(checkbox => checkbox.value.toLowerCase());

              logEntries.forEach(log => {
                  const logUser = log.getAttribute('data-log-user').toLowerCase();
                  const logMessage = log.querySelector('.log-message').textContent.toLowerCase();
                  const logType = log.getAttribute('data-log-type').toLowerCase();

                  const matchesSearch = logUser.includes(searchInput) || logMessage.includes(searchInput);
                  const matchesType = checkedTypes.length === 0 || checkedTypes.includes(logType);

                  if (matchesSearch && matchesType) {
                      log.style.display = 'flex'; // Show the log entry
                  } else {
                      log.style.display = 'none'; // Hide the log entry
                  }
              });
          }
      </script>
    
</body>
</html>
