<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
  <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="./css/sidebar.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="./css/settings.css?v=<?php echo time(); ?>">
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
              <a href="./dashboard.php"><span class="bx bx-grid-alt"></span><h3>Dashboard</h3></a>
              <a href="./performance_overview.php"><span class="bx bx-pie-chart-alt-2"></span><h3>Performance Overview</h3></a>
              <a href="./user_management.php"><span class="bx bx-user"></span><h3>User Management</h3></a>
              <a href="./announcement.php"><span class="bx bx-bell"></span><h3>Announcements</h3></a>
              <a href="./service_request.php"><span class="bx bx-file"></span><h3>Service Requests</h3></a>
              <a href="./financial_overview.php"><span class="bx bx-wallet"></span><h3>Financial Overview</h3></a>
              <a href="./community_insights.php"><span class="bx bx-chat"></span><h3>Community Insights</h3></a>
              <a href="./audit_logs.php"><span class="bx bx-file-blank"></span><h3>Audit Logs</h3></a>
              <a href="./settings.php" class="active"><span class="bx bx-cog"></span><h3>Settings</h3></a>
              <a href="./index.php"><span class="bx bx-log-out"></span><h3>Logout</h3></a>
          </div>
      </aside>

      <main>
          <h1>Settings</h1>

          <!-- General Settings -->
          <section id="general-settings">
              <h2>General Settings</h2>
              <div>
                  <label for="portal-name">Portal Name</label>
                  <input type="text" id="portal-name" placeholder="Enter portal name">
                  <label for="portal-logo">Upload Logo</label>
                  <input type="file" id="portal-logo">
              </div>
              <div>
                  <label for="theme">Theme Customization</label>
                  <select id="theme">
                      <option value="default">Default</option>
                      <option value="dark">Dark</option>
                      <option value="light">Light</option>
                  </select>
              </div>
              <div>
                  <label for="language">Language Preferences</label>
                  <select id="language">
                      <option value="en">English</option>
                      <option value="es">Spanish</option>
                  </select>
              </div>
          </section>

          <!-- User Management -->
          <section id="user-management">
              <h2>User Management</h2>
              <div>
                  <label for="roles">User Role Assignments</label>
                  <select id="roles">
                      <option value="admin">Admin</option>
                      <option value="employee">Employee</option>
                      <option value="resident">Resident</option>
                  </select>
              </div>
              <div>
                  <label for="account-creation">Account Creation</label>
                  <input type="text" id="account-creation" placeholder="Create new account">
              </div>
              <div>
                  <label for="bulk-account">Bulk Account Management</label>
                  <input type="file" id="bulk-account">
              </div>
              <div>
                  <label for="password-reset">Password Management</label>
                  <button id="password-reset">Reset Password</button>
              </div>
          </section>

          <!-- Notification Settings -->
          <section id="notification-settings">
              <h2>Notification Settings</h2>
              <div>
                  <label for="email-alerts">Email Alerts</label>
                  <label class="switch">
                      <input type="checkbox" id="email-alerts">
                      <span class="slider"></span>
                  </label>
              </div>
              <div>
                  <label for="push-notifications">Push Notifications</label>
                  <label class="switch">
                      <input type="checkbox" id="push-notifications">
                      <span class="slider"></span>
                  </label>
              </div>
              <div>
                  <label for="announcement-settings">Announcement Settings</label>
                  <label class="switch">
                      <input type="checkbox" id="announcement-settings">
                      <span class="slider"></span>
                  </label>
              </div>
          </section>

          <!-- Financial Settings -->
          <section id="financial-settings">
              <h2>Financial Settings</h2>
              <div>
                  <label for="payment-gateway">Payment Gateway Integration</label>
                  <input type="text" id="payment-gateway" placeholder="Enter gateway details">
              </div>
              <div>
                  <label for="billing-preferences">Billing Preferences</label>
                  <input type="text" id="billing-preferences" placeholder="Set billing cycles">
              </div>
              <div>
                  <label for="tax-settings">Tax Settings</label>
                  <input type="text" id="tax-settings" placeholder="Configure applicable taxes">
              </div>
          </section>

      </main>
   </div>
</body>
</html>
