<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Announcement Hub</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" />
  <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="./css/sidebar.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="./css/announcement.css?v=<?php echo time(); ?>">
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
           <a href="./announcement.php" class="active">
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

       <main>
    <h1>Announcement Hub</h1>
    <div class="search-filter">
        <input type="text" placeholder="Search announcements..." class="search-input" id="search-input" />
        <select class="filter-select" id="filter-select">
            <option value="all">All</option>
            <option value="approved">Approved</option>
            <option value="pending">Pending</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>
    <div class="announcement-list" id="announcement-list">
        <div class="announcement-box" data-status="pending">
            <div class="announcement-details">
                <p><strong>Time:</strong> <span class="time">2024-10-23 10:30 AM</span></p>
                <p><strong>Subject:</strong> <span class="subject">New Community Event</span></p>
                <p><strong>From:</strong> <span class="from">Admin</span></p>
                <p><strong>Message:</strong> <span class="message">Join us for our upcoming community event this Saturday!</span></p>
                <p><strong>Attachment:</strong> <a href="#" class="attachment">event-details.pdf</a></p>
            </div>
            <div class="announcement-actions">
                <button class="approve">Approve</button>
                <button class="reject">Reject</button>
            </div>
        </div>

        <div class="announcement-box" data-status="approved">
            <div class="announcement-details">
                <p><strong>Time:</strong> <span class="time">2024-10-22 09:00 AM</span></p>
                <p><strong>Subject:</strong> <span class="subject">Winter Maintenance Schedule</span></p>
                <p><strong>From:</strong> <span class="from">Property Management</span></p>
                <p><strong>Message:</strong> <span class="message">The winter maintenance schedule is now available. Please review.</span></p>
                <p><strong>Attachment:</strong> <a href="#" class="attachment">maintenance-schedule.pdf</a></p>
            </div>
            <div class="announcement-actions">
                <button class="approve">Approve</button>
                <button class="reject">Reject</button>
            </div>
        </div>

        <div class="announcement-box" data-status="pending">
            <div class="announcement-details">
                <p><strong>Time:</strong> <span class="time">2024-10-21 03:15 PM</span></p>
                <p><strong>Subject:</strong> <span class="subject">New Gym Opening</span></p>
                <p><strong>From:</strong> <span class="from">Management</span></p>
                <p><strong>Message:</strong> <span class="message">We're excited to announce the opening of our new gym next month!</span></p>
                <p><strong>Attachment:</strong> <a href="#" class="attachment">gym-opening-flyer.pdf</a></p>
            </div>
            <div class="announcement-actions">
                <button class="approve">Approve</button>
                <button class="reject">Reject</button>
            </div>
        </div>

        <div class="announcement-box" data-status="rejected">
            <div class="announcement-details">
                <p><strong>Time:</strong> <span class="time">2024-10-20 12:45 PM</span></p>
                <p><strong>Subject:</strong> <span class="subject">Parking Lot Closure</span></p>
                <p><strong>From:</strong> <span class="from">Facilities</span></p>
                <p><strong>Message:</strong> <span class="message">The parking lot will be closed for repairs next week.</span></p>
                <p><strong>Attachment:</strong> <a href="#" class="attachment">closure-notice.pdf</a></p>
            </div>
            <div class="announcement-actions">
                <button class="approve">Approve</button>
                <button class="reject">Reject</button>
            </div>
        </div>

        <div class="announcement-box" data-status="approved">
            <div class="announcement-details">
                <p><strong>Time:</strong> <span class="time">2024-10-19 04:30 PM</span></p>
                <p><strong>Subject:</strong> <span class="subject">Holiday Celebration</span></p>
                <p><strong>From:</strong> <span class="from">Admin</span></p>
                <p><strong>Message:</strong> <span class="message">Join us for our annual holiday celebration on December 15th!</span></p>
                <p><strong>Attachment:</strong> <a href="#" class="attachment">holiday-party-invite.pdf</a></p>
            </div>
            <div class="announcement-actions">
                <button class="approve">Approve</button>
                <button class="reject">Reject</button>
            </div>
        </div>
    </div>

    <!-- Modal for Announcement Details -->
    <div id="announcement-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3>Announcement Details</h3>
            <div class="modal-details"></div>
            <p><strong>Status:</strong> <span class="modal-status"></span></p>
            <div class="modal-attachment"></div>
        </div>
    </div>
</main>


<script>
    // Script for filtering announcements
const filterSelect = document.getElementById('filter-select');
const searchInput = document.getElementById('search-input');
const announcementList = document.getElementById('announcement-list');
const announcements = document.querySelectorAll('.announcement-box');

function filterAnnouncements() {
    const filterValue = filterSelect.value;
    const searchValue = searchInput.value.toLowerCase();

    announcements.forEach(box => {
        const subject = box.querySelector('.subject').textContent.toLowerCase();
        const status = box.dataset.status;

        const matchesSearch = subject.includes(searchValue);
        const matchesFilter = filterValue === 'all' || status === filterValue;

        if (matchesSearch && matchesFilter) {
            box.style.display = '';
        } else {
            box.style.display = 'none';
        }
    });
}

searchInput.addEventListener('input', filterAnnouncements);
filterSelect.addEventListener('change', filterAnnouncements);

// Script for modal
const modal = document.getElementById('announcement-modal');
const modalClose = document.querySelector('.close-modal');

announcementList.addEventListener('click', (e) => {
    if (e.target.classList.contains('approve') || e.target.classList.contains('reject')) {
        const announcementBox = e.target.closest('.announcement-box');
        const details = announcementBox.querySelector('.announcement-details').innerHTML;
        const status = announcementBox.dataset.status;
        const attachment = announcementBox.querySelector('.attachment').textContent;

        modal.querySelector('.modal-details').innerHTML = details;
        modal.querySelector('.modal-status').textContent = status.charAt(0).toUpperCase() + status.slice(1);
        modal.querySelector('.modal-attachment').innerHTML = `<strong>Download Attachment:</strong> <a href="#" class="attachment-download">${attachment}</a>`;

        modal.style.display = 'block';
    }
});

modalClose.addEventListener('click', () => {
    modal.style.display = 'none';
});

window.addEventListener('click', (event) => {
    if (event.target === modal) {
        modal.style.display = 'none';
    }
});

</script>

</body>
</html>
