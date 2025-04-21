<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$db_username = "u113232969_Hives";
$db_password = "theSwarm4";
$dbname = "u113232969_SWARM";
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$ownerId = $_SESSION['user_id'];
$status = '';

// Fetch status from tenant information table
$sql = "SELECT Status FROM ownerinformation WHERE Owner_ID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $ownerId);
    $stmt->execute();
    $stmt->bind_result($status);
    $stmt->fetch();
    $stmt->close();

    if ($status == 'Disapproved') {
        echo "<script>alert('Your account has been disapproved. Please update your RIS information.');
              window.location.href = 'Register1(Disapprove).php';</script>";
        exit();
    } elseif ($status == 'Pending') {
        echo "<script>alert('Your account is pending. Some features are currently locked.');
              window.location.href = 'OwnerHomepage.php';</script>";
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_title = $_POST['item_title'];
    $description = $_POST['description'];
    $price_range = $_POST['price_range'];
    $category = $_POST['category'];
    $image = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "MarketID/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $unique_filename = uniqid() . '.' . $file_extension;
        $image_path = $target_dir . $unique_filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            $image = $image_path;
        } else {
            echo "<script>alert('Failed to upload the image.');</script>";
        }
    }

    $sql = "INSERT INTO listings (item_title, description, price_range, category, image) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdss", $item_title, $description, $price_range, $category, $image);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$sql = "SELECT id, item_title, description, price_range, category, image, Status, created_at FROM listings";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Swarm Portal</title>
    <link rel="stylesheet" href="communitymarket-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        /* Listings container */
        .listing-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        /* Individual card style */
        .listing-card {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            width: 200px;
            padding: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        .listing-card:hover {
            transform: scale(1.05);
        }

        .listing-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }

        .listing-card h3, .listing-card p, .listing-card .price, .listing-card .category {
            margin: 8px 0;
            color: #333;
        }

        .price {
            font-size: 1.1em;
            color: #e60023;
            font-weight: bold;
        }

        .category {
            font-size: 0.9em;
            color: #666;
        }

        /* Modal styling */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        .modal-content h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .close {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 1.5em;
            cursor: pointer;
        }

        /* Form styling */
        form div {
            margin-bottom: 15px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        input[type="text"], input[type="number"], select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }

        input[type="submit"] {
            background-color: #e60023;
            color: #fff;
            border: none;
            padding: 12px 20px;
            font-size: 1em;
            cursor: pointer;
            border-radius: 5px;
        }

        input[type="submit"]:hover {
            background-color: #d40020;
        }
    </style>
<body>
<header class="header">
    <img src="/img/swarm logo.png" alt="Swarm Logo" />
    <div class="search-container">
        <div class="search-box">
            <input type="text" id="search" placeholder="Search?" />
            <button id="send-btn">
                <img src="/img/bee-search.png" class="bee-icon" alt="Search Icon" />
            </button>
        </div>
    </div>

    <div class="right-container">
        <button id="inboxButton" class="inbox-btn"></button>
        <button id="notifButton" class="notif-btn"></button>
        <button id="settingsButton" class="settings-btn"></button>
        
        <!-- Inbox and Notification Containers -->
        <div id="hiddenInbox" class="hidden-inbox">
            <div class="message">Message 1</div>
            <div class="message">Message 2</div>
            <div class="message">Message 3</div>
        </div>

        <div id="hiddenNotif" class="hidden-notif">
            <div class="notification">You have a new message.</div>
            <div class="notification">Reminder: Meeting at 3 PM.</div>
            <div class="notification">System update available.</div>
            <div class="notification">New friend request.</div>
        </div>

        <div id="hiddenIcons" class="hidden-icons">
            <div class="icon">
                <img src="/img/user-avatar.png" alt="User Icon">
            </div>
            <div class="toggle-switch">
                <input type="checkbox" id="darkModeToggle" style="display: none;">
                <label for="darkModeToggle">
                    <i class="sun-icon">&#x2600;</i>
                    <i class="moon-icon">&#x1F319;</i>
                </label>
            </div>
            <a href="index.php" class="icon">
                <img src="/img/logout.png" alt="Logout">
            </a>
        </div>
    </div>
</header>

<div class="container">
    <div class="sidebar">
        <div class="profile">
            <div class="avatar"></div>
            <div class="username">BINI IRA</div> 
        </div>
        <?php if ($status == 'Approved'): ?>
            <!-- Approved Navigation Links -->
            <div class="navigation-items1"><a href="OwnerHomepage.php"><img src="/img/home.png" class="nav-icon" alt="Home Icon">Home</a></div>
            <div class="navigation-items1"><a href="OwnerAnnouncement.php"><img src="/img/announcement.png" class="nav-icon" alt="Announcements Icon">Announcements</a></div>
            <div class="navigation-items highlight"><a href="OwnerCommunitymarket.php"><img src="/img/comm market.png" class="nav-icon" alt="Community Market Icon">Community Market</a></div>
            <div class="navigation-items1"><a href="OwnerServices.php"><img src="/img/services.png" class="nav-icon" alt="Services Icon">Services</a></div>
            <div class="navigation-items1"><a href="OwnerPaymentinfo.php"><img src="/img/payment info.png" class="nav-icon" alt="Payment Info Icon">Payment Info</a></div>
            <div class="navigation-items1"><a href="OwnerTenantstatus.php"><img src="/img/tenant status.png" class="nav-icon" alt="Tenant Status Icon">Tenant Status</a></div>
            <div class="navigation-items1"><a href="OwnerSafetyguidelines.php"><img src="/img/safe guidelines.png" class="nav-icon" alt="Safety Guidelines Icon">Safety Guidelines</a></div>
            <div class="navigation-items1"><a href="OwnerCommunityfeedback.php"><img src="/img/comm feedback.png" class="nav-icon" alt="Community Feedback Icon">Community Feedback</a></div>
        <?php elseif ($status == 'Pending'): ?>
            <!-- Pending Navigation Links -->
            <div class="navigation-items1"><a href="OwnerHomepage.php"><img src="/img/home.png" class="nav-icon" alt="Home Icon">Home</a></div>
            <div class="navigation-items1"><a href="OwnerAnnouncement.php"><img src="/img/announcement.png" class="nav-icon" alt="Announcements Icon">Announcements</a></div>
            <div class="navigation-items highlight"><a href="#"><img src="/img/comm market.png" class="nav-icon" alt="Community Market Icon">Community Market</a></div>
            <div class="navigation-items1"><a href="#"><img src="/img/services.png" class="nav-icon" alt="Services Icon">Services</a></div>
            <div class="navigation-items1"><a href="#"><img src="/img/payment info.png" class="nav-icon" alt="Payment Info Icon">Payment Info</a></div>
            <div class="navigation-items1"><a href="#"><img src="/img/tenant status.png" class="nav-icon" alt="Tenant Status Icon">Tenant Status</a></div>
            <div class="navigation-items1"><a href="OwnerSafetyguidelines.php"><img src="/img/safe guidelines.png" class="nav-icon" alt="Safety Guidelines Icon">Safety Guidelines</a></div>
            <div class="navigation-items1"><a href="OwnerCommunityfeedback.php"><img src="/img/comm feedback.png" class="nav-icon" alt="Community Feedback Icon">Community Feedback</a></div>
        <?php endif; ?>
    </div>
    
    <div class="content">
        <div class="main-content">
            <div class="rectangle-btn">
                <div class="postrec-container">
                    <div class="profile-pic"><img src="https://via.placeholder.com/60" alt="Profile Picture"></div>
                    <div class="postrec" id="postItemBtn"><span>Create Your Market Listing</span></div>
                </div>
                <div class="icon-toggle" id="iconToggleBtn">
                    <i class="fas fa-ellipsis-v"></i>
                </div>
                <div class="favourites-history" id="favouritesHistory" style="display:none;">
                    <div class="icon-btn" onclick="location.href='my-posts.html'"><i class="fas fa-clipboard-list" title="My Posts"></i><span>My Post</span></div>
                    <div class="icon-btn" onclick="location.href='favourites.html'"><i class="fas fa-heart" title="Favourites"></i><span>Favourites</span></div>
                    <div class="icon-btn" onclick="location.href='history.html'"><i class="fas fa-history" title="History"></i><span>History</span></div>
                </div>
            </div>
        
           <section class="product-section">
    <div class="filters">
        <input type="text" class="search-bar" placeholder="Search items">
        <div class="category-dropdown">
            <input list="categories" class="category-input" placeholder="Type or select a category">
            <datalist id="categories">
                <option value="Accessories">
                <option value="Clothing">
                <option value="Electronics">
                <option value="Food">  
                <option value="Home Appliances">
                <option value="Shoes">
            </datalist>
        </div>
    </div>
    </div>
    <h2>Market Listings</h2>
    <div class="listing-container">
        <?php while ($row = $result->fetch_assoc()) : ?>
            <div class="listing-card">
                <img src="<?= htmlspecialchars($row['image']) ?>" alt="Product Image">
                <h3><?= htmlspecialchars($row['item_title']) ?></h3>
                <p class="price">₱<?= htmlspecialchars(number_format($row['price_range'], 2)) ?></p>
                <p class="category"><?= htmlspecialchars($row['category']) ?></p>
                <p><?= htmlspecialchars($row['description']) ?></p>
                <p class="date">Posted on: <?= htmlspecialchars($row['created_at']) ?></p>
            </div>
        <?php endwhile; ?>
    </div>

    <div id="myModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Create Your Market Listing</h2>
            <form action="" method="POST" enctype="multipart/form-data">
                <div><label for="item-title">Item Title:</label><input name="item_title" id="item-title" type="text" placeholder="Enter Item Title" required></div>
                <div><label for="description">Description:</label><textarea name="description" id="description" placeholder="Enter Description" rows="4" required></textarea></div>
                <div><label for="price-range">Price Range:</label><input name="price_range" id="price-range" type="number" min="0" placeholder="Enter Price Range" required></div>
                <div><label for="category">Category:</label><input list="categories" name="category" id="category" placeholder="Enter or Select Category" required></div>
                <div><label for="image">Image Upload:</label><input name="image" id="image" type="file" accept="image/*" required></div>
                <button type="submit">Submit</button>
            </form>
        </div>
    </div>
</section>

<script>
let isSpinning = false;
let currentOpenMenu = null; // To track the currently open menu (either 'inbox', 'notif', 'settings')

// Settings Icon
document.getElementById('settingsButton').addEventListener('click', function() {
    const button = document.querySelector('.settings-btn');
    const icons = document.getElementById('hiddenIcons');

    if (currentOpenMenu && currentOpenMenu !== 'settings') {
        closeCurrentMenu(); // Close the currently open menu if it's not 'settings'
    }

    if (!isSpinning) {
        button.style.transform = 'rotate(90deg)';
    } else {
        button.style.transform = 'rotate(0deg)';
    }

    // Toggle the hidden icons
    icons.classList.toggle('show-icons');
    isSpinning = !isSpinning;

    currentOpenMenu = icons.classList.contains('show-icons') ? 'settings' : null; // Update the open menu state
});

// Dark Mode
document.getElementById('darkModeToggle').addEventListener('click', function() {
    const toggleSwitch = document.querySelector('.toggle-switch');
    toggleSwitch.classList.toggle('active');
});

// Inbox Animation
document.getElementById('inboxButton').addEventListener('click', function() {
    const inbox = document.getElementById('hiddenInbox');
    const inboxButton = this;

    if (currentOpenMenu && currentOpenMenu !== 'inbox') {
        closeCurrentMenu(); // Close the currently open menu if it's not 'inbox'
    }

    // Toggle the inbox display
    if (!inbox.classList.contains('show-inbox')) {
        inbox.classList.add('show-inbox');
        inboxButton.classList.add('inbox-open'); // Add shake animation class

        // Remove the animation class after 0.5 seconds (duration of animation)
        setTimeout(function() {
            inboxButton.classList.remove('inbox-open');
        }, 500); // 500ms = 0.5s (length of shake animation)
    } else {
        inbox.classList.remove('show-inbox');
    }

    currentOpenMenu = inbox.classList.contains('show-inbox') ? 'inbox' : null; // Update the open menu state
});

// Notification Animation
document.getElementById('notifButton').addEventListener('click', function() {
    const notif = document.getElementById('hiddenNotif');
    const notifButton = this;

    if (currentOpenMenu && currentOpenMenu !== 'notif') {
        closeCurrentMenu(); // Close the currently open menu if it's not 'notif'
    }

    if (!notif.classList.contains('show-notif')) {
        notif.classList.add('show-notif');
        notifButton.classList.add('ringing');

        setTimeout(function() {
            notifButton.classList.remove('ringing');
        }, 500); // 500ms = 0.5s (length of ringing animation)
    } else {
        notif.classList.remove('show-notif');
    }

    currentOpenMenu = notif.classList.contains('show-notif') ? 'notif' : null; // Update the open menu state
});

// Close the currently open menu
function closeCurrentMenu() {
    if (currentOpenMenu === 'settings') {
        const icons = document.getElementById('hiddenIcons');
        icons.classList.remove('show-icons');
        isSpinning = false;
        document.querySelector('.settings-btn').style.transform = 'rotate(0deg)';
    } else if (currentOpenMenu === 'inbox') {
        const inbox = document.getElementById('hiddenInbox');
        inbox.classList.remove('show-inbox');
    } else if (currentOpenMenu === 'notif') {
        const notif = document.getElementById('hiddenNotif');
        notif.classList.remove('show-notif');
    }

    currentOpenMenu = null; // Reset the open menu state
}

document.getElementById('iconToggleBtn').addEventListener('click', function() {
    const favouritesHistory = document.getElementById('favouritesHistory');
    const isVisible = favouritesHistory.style.display === 'block';
    favouritesHistory.style.display = isVisible ? 'none' : 'block'; // Toggle display
});
  


  // Get modal element and other necessary elements
  var modal = document.getElementById("myModal");
  var btn = document.getElementById("postItemBtn");
  var span = document.getElementsByClassName("close")[0];
  var categorySelect = document.getElementById('category-select');
  var newCategoryInput = document.getElementById('new-category');
  var fileInput = document.getElementById('file-upload');
  var imagePreview = document.getElementById('image-preview');
  var filesArray = [];

  // When the user clicks the button, open the modal 
  btn.onclick = function() {
      modal.style.display = "block";
  }

  // When the user clicks on <span> (x), close the modal
  span.onclick = function() {
      modal.style.display = "none";
  }

  // When the user clicks anywhere outside of the modal, close it
  window.onclick = function(event) {
      if (event.target == modal) {
          modal.style.display = "none";
      }
  }

  // Show the input field for adding a new category if 'Add New Category' is selected
  categorySelect.onchange = function() {
      if (categorySelect.value === 'Add') {
          newCategoryInput.style.display = 'block';
          newCategoryInput.required = true;  // Ensure the new category is required if shown
      } else {
          newCategoryInput.style.display = 'none';
          newCategoryInput.required = false;
      }
  }

  // Add drag-and-drop feature for rearranging images and preview updates
  function updatePreview() {
      imagePreview.innerHTML = '';

      // Iterate through all selected files and generate previews
      filesArray.forEach(function(file, index) {
          let imageContainer = document.createElement('div');
          imageContainer.classList.add('image-container');
          imageContainer.setAttribute('draggable', 'true');
          imageContainer.setAttribute('data-index', index);

          // Create the image element
          let imgElement = document.createElement('img');
          imgElement.src = URL.createObjectURL(file);
          imgElement.alt = "Image Preview";
          imgElement.style.width = "100px";
          imgElement.style.height = "100px";
          imgElement.style.objectFit = "cover";
          imgElement.style.margin = "5px";
          imgElement.style.borderRadius = "5px";

          // Add a small "X" button to remove the image
          let removeButton = document.createElement('button');
          removeButton.classList.add('remove-image');
          removeButton.innerHTML = 'X';
          removeButton.onclick = function() {
              filesArray.splice(index, 1);  // Remove the selected image
              updatePreview();  // Refresh the preview
          }

          // Append elements to the container
          imageContainer.appendChild(imgElement);
          imageContainer.appendChild(removeButton);
          imagePreview.appendChild(imageContainer);

          // Drag and Drop functionality
          imageContainer.addEventListener('dragstart', function(e) {
              e.dataTransfer.setData('text/plain', e.target.getAttribute('data-index'));
          });

          imageContainer.addEventListener('dragover', function(e) {
              e.preventDefault();
          });

          imageContainer.addEventListener('drop', function(e) {
              e.preventDefault();
              let oldIndex = e.dataTransfer.getData('text/plain');
              let newIndex = e.target.getAttribute('data-index');
              let movedImage = filesArray.splice(oldIndex, 1)[0];
              filesArray.splice(newIndex, 0, movedImage);
              updatePreview();  // Refresh preview after reorder
          });
      });

      // Create an info text for users about the main image preview
      var infoText = document.createElement('p');
      infoText.innerText = "The first uploaded image will be used as the main preview of the post.";
      infoText.style.fontSize = "14px";
      infoText.style.color = "#555";
      infoText.style.marginTop = "5px";
      imagePreview.appendChild(infoText);

      // Change the upload button style if there are images
      var uploadButton = document.querySelector('.custom-file-upload');
      if (filesArray.length > 0) {
          uploadButton.style.border = '2px solid #d4b008';
          uploadButton.style.backgroundColor = '#ebd052'; // Light green background
          uploadButton.style.borderStyle = 'solid'; // Solid border
          uploadButton.innerHTML = 'Upload More Images';
      } else {
          uploadButton.style.border = '2px dashed #b3b3b3';
          uploadButton.style.backgroundColor = '#f0f0f0'; // Original background
          uploadButton.innerHTML = 'Upload Image';
      }
  }

  // Handle image selection and update the preview section
  fileInput.onchange = function(event) {
      var newFiles = Array.from(event.target.files);
      filesArray.push(...newFiles);
      updatePreview();
  }

  // Open Modal function
  function openModal() {
      const modal = document.getElementById('productModal');
      modal.style.display = 'block'; // Show the modal
  }

  // Close Modal function when clicking the X button
  function closeModal() {
      const modal = document.getElementById('productModal');
      modal.style.display = 'none'; // Hide the modal
  }

  // Attach event listeners to product cards to open the modal
  document.querySelectorAll('.product-card').forEach(card => {
      card.addEventListener('click', openModal);
  });

  // Attach event listener to the "X" button only, to close the modal
  const closeModalButton = document.getElementById('closeModal');
  if (closeModalButton) {
      closeModalButton.addEventListener('click', closeModal);
  }

  // Prevent modal dialog itself from closing when clicked
  const modalDialog = document.querySelector('.modal-dialog');
  modalDialog.addEventListener('click', function(event) {
      event.stopPropagation(); // Prevent click events from bubbling to the modal
  });



  // Array of product images
  const productImages = [
      'https://thinsoldier.com/wip/nike-grid/images/lunar2_full.jpg',
      'https://thinsoldier.com/wip/nike-grid/images/lunar2_back.jpg',
      'https://thinsoldier.com/wip/nike-grid/images/lunar2_side.jpg'
  ];

  // Reference to the product slider div
  const productSlider = document.getElementById('productSlider');

  // Current image index for the slider
  let currentImageIndex = 0;

  // Function to display image based on index
  function showImage(index) {
      // Clear any previous content
      productSlider.innerHTML = '';

      // Create new image element
      const imgElement = document.createElement('img');
      imgElement.src = productImages[index];

      // Append the new image to the slider
      productSlider.appendChild(imgElement);

      // Check if there are multiple images to show buttons
      if (productImages.length > 1) {
          // Create previous button
  const prevButton = document.createElement('div');
  prevButton.classList.add('prev');
  prevButton.innerHTML = '&#10094;'; // Left arrow
  prevButton.onclick = function(event) {
      event.stopPropagation(); // Prevent closing the modal
      prevImage();
  };

  // Create next button
  const nextButton = document.createElement('div');
  nextButton.classList.add('next');
  nextButton.innerHTML = '&#10095;'; // Right arrow
  nextButton.onclick = function(event) {
      event.stopPropagation(); // Prevent closing the modal
      nextImage();
  };


          // Append buttons to the slider
          productSlider.appendChild(prevButton);
          productSlider.appendChild(nextButton);
      }
  }

  // Function for the next image
  function nextImage() {
      currentImageIndex = (currentImageIndex + 1) % productImages.length;
      showImage(currentImageIndex);
  }

  // Function for the previous image
  function prevImage() {
      currentImageIndex = (currentImageIndex - 1 + productImages.length) % productImages.length;
      showImage(currentImageIndex);
  }

  // Initialize the slider
  function initializeSlider() {
      // Display the first image
      showImage(currentImageIndex);
  }

  // Check if images exist and initialize the slider accordingly
  if (productImages.length > 0) {
      initializeSlider();
  } else {
      // If no images, display a default message or image
      productSlider.innerHTML = '<p>No images available</p>';
  }
  
// JavaScript for modal functionality if necessary
document.getElementById("postItemBtn").addEventListener("click", function() {
    document.getElementById("myModal").style.display = "block";
});
document.querySelector(".close").addEventListener("click", function() {
    document.getElementById("myModal").style.display = "none";
});
</script>

</body>
</html>