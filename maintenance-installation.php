<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Swarm | Maintenance Installation</title>
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
                    <li class="active">
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
                    <li>
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
      <div class="app">
	<header class="app-header">
		<div class="app-header-navigation">
			<div class="tabs">
				<a href="#" class="active">
					Overall
				</a>
				<a href="#">
					Completed
				</a>
				<a href="#">
					Rejected
				</a>
			</div>
		</div>
		<div class="app-header-mobile">
			<button class="icon-button large">
				<i class="ph-list"></i>
			</button>
		</div>

	</header>
	
		<div class="app-body-main-content">
			<section class="service-section">
				<br><br><h2>Installation Forms</h2>
				<div class="service-section-header">
					<div class="search-field">
						<i class="ph-magnifying-glass"></i>
						<input type="text" placeholder="Name / Unit Number">
					</div>
					<button class="flat-button">
						Search
					</button>
				</div>
				<div class="mobile-only">
					<button class="flat-button">
						Toggle search
					</button>
				</div>
				<div class="tiles">
            <article class="tile">
    <div class="tile-header">
      <i class="ph-lightning-light"></i>
      <h3><br>
        <span>Tricia Mae Zantua</span>
        <span>THC 318</span>
      </h3>
      <span class="status approval">Approval</span>
    </div>
    <a href="#">
      <span>View</span>
      <span class="icon-button">
        <i class="bx bx-chevron-right"></i>
      </span>
    </a>
  </article>
  <article class="tile">
    <div class="tile-header">
      <i class="ph-fire-simple-light"></i>
      <h3><br>
        <span>Noreen Leonico</span>
        <span>THA 912</span>
      </h3>
      <span class="status pending">Pending</span>
    </div>
    <a href="#">
      <span>View</span>
      <span class="icon-button">
        <i class="bx bx-chevron-right"></i>
      </span>
    </a>
  </article>
  <article class="tile">
    <div class="tile-header">
      <i class="ph-file-light"></i>
      <h3><br>
        <span>Jan Salas</span>
        <span>THD 1506</span>
      </h3>
      <span class="status completed">Completed</span>
    </div>
    <a href="#">
      <span>View</span>
      <span class="icon-button">
        <i class="bx bx-chevron-right"></i>
      </span>
    </a>
  </article>
				</div>
				
			</section>
           

       <!-- end insights -->
      </main>
      <!------------------
         end main
        ------------------->
</body>
</html>