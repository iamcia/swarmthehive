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
  <link rel="stylesheet" href="./css/community_insights.css?v=<?php echo time(); ?>">
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
           <a href="./community_insights.php" class="active">
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
        
       <main>
    <div class="community-insights">
        <h1>Community Insights</h1>
        <div class="report-card">
            <div class="report-header">
                <h3>Report Title</h3>
                <span class="status unresolved">Unresolved</span>
            </div>
            <p class="report-description">Description of the report or concern goes here. It provides an overview of the issue reported by the resident.</p>
            <div class="report-images">
                <img src="https://via.placeholder.com/100.png?text=Report+Image+3" alt="Report Image 1">
                <img src="https://via.placeholder.com/100.png?text=Report+Image+3" alt="Report Image 2">
            </div>
            <div class="report-actions">
                <a href="#" class="view-details">View Details</a>
                <button class="dismiss-button">Dismiss</button>
            </div>
        </div>

        <div class="report-card">
            <div class="report-header">
                <h3>Another Report Title</h3>
                <span class="status resolved">Resolved</span>
            </div>
            <p class="report-description">Another description of the report or concern goes here.</p>
            <div class="report-images">
                <img src="https://via.placeholder.com/100.png?text=Report+Image+3" alt="Report Image 3">
            </div>
            <div class="report-actions">
                <a href="#" class="view-details">View Details</a>
                <button class="dismiss-button">Dismiss</button>
            </div>
        </div>
    </div>
</main>



      </main>
      <!------------------
         end main
        ------------------->

    
</body>
</html>