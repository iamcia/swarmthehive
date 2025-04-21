<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Swarm | Maintenance Settings</title>
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/maintenance_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/maint-installation-style.css?v=<?php echo time(); ?>">
</head>
<body>
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
                    <li>
                        <a href="maintenance-installation.php">
                            <i class='bx bx-package'></i>
                            <span>Installation</span>
                        </a>
                    </li>
                    <li>
                        <a href="maintenance-renovation.php">
                            <i class='bx bx-building-house'></i>
                            <span>Renovation</span>
                        </a>
                    </li>
                    <li>
                        <a href="maintenance-repair.php">
                            <i class='bx bx-wrench'></i>
                            <span>Repair</span>
                        </a>
                    </li>
                    <li>
                        <a href="maintenance-history.php">
                            <i class='bx bx-history'></i>
                            <span>Logs</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="maintenance-settings.php">
                            <i class='bx bx-cog'></i>
                            <span>Settings</span>
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

      <main>



           


      </main>
      <!------------------
         end main
        ------------------->

    
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