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
  <link rel="stylesheet" href="./css/dashboard.css?v=<?php echo time(); ?>">
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

            <a href="./dashboard.php"  class="active">
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
    <h1>Dashboard</h1>

    <div class="insights">
        <!-- Start For Approval -->
        <div class="approval">
            <span class="material-symbols-sharp">trending_up</span>
            <div class="middle">
                <div class="left">
                    <h3>For Approval</h3>
                    <h1>35</h1>
                </div>
            </div>
            <small>Last 24 Hours</small>
        </div>
        <!-- End For Approval -->

        <!-- Start Pending -->
        <div class="pending">
            <span class="material-symbols-sharp">hourglass_bottom</span>
            <div class="middle">
                <div class="left">
                    <h3>Pending</h3>
                    <h1>12</h1>
                </div>
            </div>
            <small>Last 24 Hours</small>
        </div>
        <!-- End Pending -->

        <!-- Start Completed -->
        <div class="completed">
            <span class="material-symbols-sharp">check_circle</span>
            <div class="middle">
                <div class="left">
                    <h3>Completed</h3>
                    <h1>28</h1>
                </div>
            </div>
            <small>Last 24 Hours</small>
        </div>
        <!-- End Completed -->

        <!-- Start Performance Overview -->
        <div class="performance large">
            <span class="material-symbols-sharp">bar_chart</span>
            <div class="middle">
                <div class="left">
                    <h3>Performance Overview</h3>
                    <h1>87%</h1>
                </div>
            </div>
            <small>Last Week</small>
            <div class="statistic-graph">
                <div class="bar" style="height: 87%; background-color: #9b59b6;"></div>
            </div>
        </div>
        <!-- End Performance Overview -->

        <!-- Start User Management -->
        <div class="user-management large">
            <span class="material-symbols-sharp">group</span>
            <div class="middle">
                <div class="left">
                    <h3>User Management</h3>
                    <h1>1250</h1>
                </div>
            </div>
            <small>Active Users</small>
            <div class="statistic-graph">
                <div class="bar" style="height: 75%; background-color: #3498db;"></div>
            </div>
        </div>
        <!-- End User Management -->

        <!-- Start Announcements -->
        <div class="announcements">
            <span class="material-symbols-sharp">campaign</span>
            <div class="middle">
                <div class="left">
                    <h3>Announcements</h3>
                    <h1>4</h1>
                </div>
            </div>
            <small>New This Week</small>
        </div>
        <!-- End Announcements -->

        <!-- Start Market Overview -->
        <div class="market-overview large">
            <span class="material-symbols-sharp">show_chart</span>
            <div class="middle">
                <div class="left">
                    <h3>Market Overview</h3>
                    <h1>$5.2M</h1>
                </div>
            </div>
            <small>Last Quarter</small>
            <div class="statistic-graph">
                <div class="bar" style="height: 90%; background-color: #2c3e50;"></div>
            </div>
        </div>
        <!-- End Market Overview -->

        <!-- Start Service Request -->
        <div class="service-request">
            <span class="material-symbols-sharp">support</span>
            <div class="middle">
                <div class="left">
                    <h3>Service Request</h3>
                    <h1>18</h1>
                </div>
            </div>
            <small>Ongoing</small>
        </div>
        <!-- End Service Request -->

        <!-- Start Financial Overview -->
        <div class="financial-overview large">
            <span class="material-symbols-sharp">account_balance</span>
            <div class="middle">
                <div class="left">
                    <h3>Financial Overview</h3>
                    <h1>$3.8M</h1>
                </div>
            </div>
            <small>This Month</small>
            <div class="statistic-graph">
                <div class="bar" style="height: 80%; background-color: #c0392b;"></div>
            </div>
        </div>
        <!-- End Financial Overview -->

        <!-- Start Community Insights -->
        <div class="community-insights large">
            <span class="material-symbols-sharp">insights</span>
            <div class="middle">
                <div class="left">
                    <h3>Community Insights</h3>
                    <h1>58%</h1>
                </div>
            </div>
            <small>Engagement</small>
            <div class="statistic-graph">
                <div class="bar" style="height: 58%; background-color: #27ae60;"></div>
            </div>
        </div>
        <!-- End Community Insights -->

        <!-- Start Audit Logs -->
        <div class="audit-logs">
            <span class="material-symbols-sharp">assignment</span>
            <div class="middle">
                <div class="left">
                    <h3>Audit Logs</h3>
                    <h1>10</h1>
                </div>
            </div>
            <small>Last Audit</small>
        </div>
        <!-- End Audit Logs -->
    </div>
    <!-- end insights -->
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

          
        </script>
</body>
</html>