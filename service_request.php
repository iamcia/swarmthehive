<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
  <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="./css/service_request.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="./css/sidebar.css?v=<?php echo time(); ?>">
  <style>
    .hidden {
      display: none;
    }
  </style>
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
              <a href="./service_request.php" class="active">
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

      <main>
           <div class="app">
	<header class="app-header">
		<div class="app-header-navigation">
			<div class="tabs">
				<a href="#" class="active" data-status="overall">Overall</a>
				<a href="#" data-status="approval">Approval</a>
				<a href="#" data-status="pending">Pending</a>
				<a href="#" data-status="completed">Completed</a>
				<a href="#" data-status="rejected">Rejected</a>
			</div>
		</div>
		<div class="app-header-actions">
				<span>Property Manager</span>
			</button>
			<div class="app-header-actions-buttons">
				<button class="icon-button large">
					<i class="bx bx-bell"></i>
				</button>
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
				<br><br><h2>Services</h2>
				<div class="service-section-header">
					<div class="search-field">
                  <i class="ph-magnifying-glass"></i>
                  <input type="text" id="searchInput" placeholder="Name / Unit Number"> <!-- Added id here -->
               </div>
					<div class="dropdown-field">
						<select>
							<option>All</option>
							<option>Move-In Notice</option>
                            <option>Move-Out Notice</option>
                            <option>Guest Check In/Out</option>
                            <option>Gate Pass</option>
                            <option>Pet Registration</option>
                            <option>Visitor Pass</option>
                            <option>Work Permit</option>
                            <option>Amenities Reservation</option>
						</select>
						<i class="ph-caret-down"></i>
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
					<article class="tile" data-status="approval">
                        <div class="tile-header">
                            <i class="ph-lightning-light"></i>
                            <h3><br>
                                <span>Guest Check In/Out</span>
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
                    <article class="tile" data-status="pending">
                        <div class="tile-header">
                            <i class="ph-fire-simple-light"></i>
                            <h3><br>
                                <span>Gate Pass</span>
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
                    <article class="tile" data-status="completed">
                        <div class="tile-header">
                            <i class="ph-file-light"></i>
                            <h3><br>
                                <span>Installation</span>
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
                    <article class="tile" data-status="rejected">
                        <div class="tile-header">
                            <i class="ph-file-light"></i>
                            <h3><br>
                                <span>Installation</span>
                                <span>Mark Salas</span>
                                <span>THD 1511</span>
                            </h3>
                            <span class="status rejected">Rejected</span>
                        </div>
                        <a href="#">
                            <span>View</span>
                            <span class="icon-button">
                                <i class="bx bx-chevron-right"></i>
                            </span>
                        </a>
                    </article>
                    <article class="tile" data-status="rejected">
    <div class="tile-header">
        <i class="ph-file-light"></i>
        <h3><br>
            <span>Installation</span>
            <span>Mark Salas</span>
            <span>THD 1511</span>
        </h3>
        <span class="status rejected">Rejected</span>
    </div>
    <a href="#">
        <span>View</span>
        <span class="icon-button">
            <i class="bx bx-chevron-right"></i>
        </span>
    </a>
</article>

<article class="tile" data-status="approved">
    <div class="tile-header">
        <i class="ph-check-circle-light"></i>
        <h3><br>
            <span>Repair Request</span>
            <span>Alice Rodriguez</span>
            <span>THD 1503</span>
        </h3>
        <span class="status approved">Approved</span>
    </div>
    <a href="#">
        <span>View</span>
        <span class="icon-button">
            <i class="bx bx-chevron-right"></i>
        </span>
    </a>
</article>

<article class="tile" data-status="pending">
    <div class="tile-header">
        <i class="ph-hourglass-light"></i>
        <h3><br>
            <span>Maintenance Request</span>
            <span>John Doe</span>
            <span>THD 1407</span>
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

<article class="tile" data-status="completed">
    <div class="tile-header">
        <i class="ph-check-light"></i>
        <h3><br>
            <span>Cleaning Service</span>
            <span>Lisa Wang</span>
            <span>THD 1012</span>
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

<article class="tile" data-status="approval">
    <div class="tile-header">
        <i class="ph-file-light"></i>
        <h3><br>
            <span>Guest Check In</span>
            <span>Emma Stone</span>
            <span>THD 1301</span>
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

                </div>
                
			</section>

      </main>

      <script>
        // Filter functionality
        const tabs = document.querySelectorAll('.tabs a');
        const tiles = document.querySelectorAll('.tiles .tile');

        tabs.forEach(tab => {
          tab.addEventListener('click', (event) => {
            event.preventDefault();
            const status = tab.getAttribute('data-status');
            
            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            // Add active class to the clicked tab
            tab.classList.add('active');

            // Show or hide tiles based on the selected status
            tiles.forEach(tile => {
              if (status === 'overall' || tile.getAttribute('data-status') === status) {
                tile.classList.remove('hidden');
              } else {
                tile.classList.add('hidden');
              }
            });
          });
        });

        // Search functionality
		document.getElementById('searchInput').addEventListener('input', function () {
			const searchValue = this.value.toLowerCase();
			const tiles = document.querySelectorAll('.tile');

			tiles.forEach(tile => {
				const title = tile.querySelector('h3 span').textContent.toLowerCase();
				const unitNumber = tile.querySelector('h3 span:nth-child(3)').textContent.toLowerCase();
				if (title.includes(searchValue) || unitNumber.includes(searchValue)) {
					tile.classList.remove('hidden');
				} else {
					tile.classList.add('hidden');
				}
			});
		});
      </script>
</body>
</html>
