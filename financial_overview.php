<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Overview Dashboard</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="./css/sidebar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/financial_overview.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
              <a href="./market_overview.php">
                  <span class="bx bx-store"></span>
                  <h3>Market Overview</h3>
              </a>
              <a href="./service_request.php">
                  <span class="bx bx-file"></span>
                  <h3>Service Requests</h3>
              </a>
              <a href="./financial_overview.php" class="active">
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

       <main>
           <h1>Financial Overview</h1>

           <div class="filter-container">
               <input type="text" placeholder="Search by Name or Unit">
               <select>
                   <option value="">Filter by Status</option>
                   <option value="paid">Paid</option>
                   <option value="unpaid">Unpaid</option>
               </select>
               <select>
                   <option value="">Filter by Payment Date</option>
                   <option value="last_week">Last Week</option>
                   <option value="last_month">Last Month</option>
               </select>
           </div>

           <div class="table-container">
               <table>
                   <thead>
                       <tr>
                           <th>Name</th>
                           <th>Unit</th>
                           <th>Status</th>
                           <th>Payment Date</th>
                           <th>Proof of Payment</th>
                           <th>View SOA</th>
                       </tr>
                   </thead>
                   <tbody>
                       <!-- Example rows -->
                       <tr>
                           <td>John Doe</td>
                           <td>101</td>
                           <td>Paid</td>
                           <td>2024-10-20</td>
                           <td><a href="#">View</a></td>
                           <td><a href="#">Download PDF</a></td>
                       </tr>
                       <tr>
                           <td>Jane Smith</td>
                           <td>102</td>
                           <td>Unpaid</td>
                           <td>N/A</td>
                           <td><a href="#">N/A</a></td>
                           <td><a href="#">Download PDF</a></td>
                       </tr>
                   </tbody>
               </table>
           </div>

           <div class="chart-container">
               <h2 style="color: var(--clr-dark);">Payment Trends Over Time</h2>
               <canvas id="submissionChart"></canvas>
           </div>
      </main>
   </div>

   <script>
       const sideMenu = document.querySelector('aside');
       const closeBtn = document.querySelector('#close_btn');

       closeBtn.addEventListener('click', () => {
           sideMenu.style.display = "none";
       });

       const ctx = document.getElementById('submissionChart').getContext('2d');
       const submissionChart = new Chart(ctx, {
           type: 'line',
           data: {
               labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
               datasets: [{
                   label: 'Payments',
                   data: [50, 30, 40, 55, 60, 70, 80],
                   borderColor: 'rgba(46, 204, 113, 1)',
                   backgroundColor: 'rgba(46, 204, 113, 0.2)',
                   borderWidth: 2,
                   fill: true,
               }]
           },
           options: {
               responsive: true,
               scales: {
                   y: {
                       beginAtZero: true,
                       grid: {
                           color: 'rgba(255, 255, 255, 0.1)',
                       },
                       ticks: {
                           color: '#ecf0f1'
                       }
                   },
                   x: {
                       grid: {
                           color: 'rgba(255, 255, 255, 0.1)',
                       },
                       ticks: {
                           color: '#ecf0f1'
                       }
                   }
               },
               plugins: {
                   legend: {
                       labels: {
                           color: '#ecf0f1'
                       }
                   },
               }
           }
       });
   </script>
</body>
</html>
