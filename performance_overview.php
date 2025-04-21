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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="./css/sidebar.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="./css/performance_overview.css?v=<?php echo time(); ?>">
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
           <a href="./performance_overview.php" class="active">
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

      <!-- --------------
        start main part
      --------------- -->

      <main>
           <h1>Performance Overview</h1>

           <div class="insights">
               <div class="approval">
                  <span class="material-symbols-sharp">trending_up</span>
                  <div class="middle">
                     <div class="left">
                        <h3>Forms Submitted Today</h3>
                        <h1>50</h1> <!-- Example number of forms submitted -->
                     </div>
                     <div class="progress">
                        <svg>
                           <circle r="30" cy="40" cx="40"></circle>
                        </svg>
                        <div class="number"><p>70%</p></div>
                     </div>
                  </div>
                  <small>Last 24 Hours</small>
               </div>

               <div class="pending">
                  <span class="material-symbols-sharp">hourglass_bottom</span>
                  <div class="middle">
                     <div class="left">
                        <h3>Pending Forms</h3>
                        <h1>15</h1> <!-- Example number of pending forms -->
                     </div>
                     <div class="progress">
                        <svg>
                           <circle r="30" cy="40" cx="40"></circle>
                        </svg>
                        <div class="number"><p>30%</p></div>
                     </div>
                  </div>
                  <small>Last 24 Hours</small>
               </div>

               <div class="completed">
                  <span class="material-symbols-sharp">check_circle</span>
                  <div class="middle">
                     <div class="left">
                        <h3>Completed Forms</h3>
                        <h1>35</h1> <!-- Example number of completed forms -->
                     </div>
                     <div class="progress">
                        <svg>
                           <circle r="30" cy="40" cx="40"></circle>
                        </svg>
                        <div class="number"><p>60%</p></div>
                     </div>
                  </div>
                  <small>Last 24 Hours</small>
               </div>
           </div>
           <!-- end insights -->

           <!-- Chart Section -->
           <div class="chart-container">
               <h2 style="color: #ecf0f1;">Submission Trends Over the Week</h2>
               <canvas id="submissionChart"></canvas>
           </div>
           <!-- end chart section -->
      </main>
      <!------------------
         end main
        ------------------->

    <script>
    // Close sidebar functionality
    const sideMenu = document.querySelector('aside');
    const closeBtn = document.querySelector('#close_btn');

    closeBtn.addEventListener('click', () => {
        sideMenu.style.display = "none";
    });

    // Chart.js script
    const ctx = document.getElementById('submissionChart').getContext('2d');
    const submissionChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [
                {
                    label: 'Forms Submitted',
                    data: [50, 30, 40, 55, 60, 70, 80],
                    borderColor: 'rgba(46, 204, 113, 1)',
                    backgroundColor: 'rgba(46, 204, 113, 0.2)',
                    borderWidth: 2,
                    fill: true,
                },
                {
                    label: 'Pending Forms',
                    data: [15, 10, 5, 20, 25, 15, 10],
                    borderColor: 'rgba(241, 196, 15, 1)',
                    backgroundColor: 'rgba(241, 196, 15, 0.2)',
                    borderWidth: 2,
                    fill: true,
                },
                {
                    label: 'Completed Forms',
                    data: [35, 20, 25, 30, 40, 50, 60],
                    borderColor: 'rgba(52, 152, 219, 1)',
                    backgroundColor: 'rgba(52, 152, 219, 0.2)',
                    borderWidth: 2,
                    fill: true,
                }
            ]
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
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return tooltipItem.dataset.label + ': ' + tooltipItem.raw;
                        }
                    }
                }
            }
        }
    });
</script>

</body>
</html>