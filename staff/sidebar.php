<?php
// sidebar.php
// This file contains the HTML structure, CSS styles, and JavaScript for the sidebar.
// It is designed to be included in other PHP files.

// Determine the current page basename for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    /* CSS for the Sidebar, Overlay, Toggle Buttons, and Main Content Layout */
    /* Minified CSS */
    :root{--primary-purple:#6a008a;--light-purple:#E3DAFF;--dark-purple:#2A2656;--hover-effect:rgba(255,255,255,0.1)}body{background:#f8f9fa;font-family:'Poppins',sans-serif;min-height:100vh;overflow-x:hidden;margin:0;padding:0}.sidebar{background:var(--primary-purple);width:280px;height:100vh;position:fixed;left:0;top:0;z-index:1000;box-shadow:2px 0 8px rgba(0,0,0,0.1);transition:transform .3s cubic-bezier(.4,0,.2,1)}.sidebar-content{padding:20px;height:100%;overflow-y:auto;position:relative}.sidebar a{color:white;padding:12px 20px;margin:4px 0;border-radius:8px;display:flex;align-items:center;gap:12px;transition:all .3s ease;text-decoration:none}.sidebar a:hover{background:var(--hover-effect);transform:translateX(5px)}.sidebar a.active{background:var(--light-purple);color:var(--primary-purple)}.sidebar .dropdown{position:relative}.sidebar .dropdown-btn{width:100%;padding:12px 20px;color:white;background:none;border:none;text-align:left;display:flex;align-items:center;gap:12px;cursor:pointer}.sidebar .dropdown-container{padding-left:20px;border-radius:4px;margin-top:4px;display:none}.sidebar .dropdown-container a{color:white;padding:8px 20px;display:block;text-decoration:none;transition:all .2s ease}.sidebar .dropdown-container a:hover{background:rgba(255,255,255,0.1)}.menu-toggle{position:fixed;left:20px;top:20px;z-index:1100;background:var(--primary-purple);border:none;color:white;width:40px;height:40px;border-radius:50%;display:none;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,0.1);transition:transform .3s ease;cursor:pointer}.menu-toggle:hover{transform:scale(1.1)}.sidebar-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.3);z-index:999;display:none}.content-wrapper{margin-left:280px;transition:margin-left .3s cubic-bezier(.4,0,.2,1);padding:0}.content-wrapper .navbar{padding:15px 20px;margin-bottom:20px}.content-wrapper .navbar .menu-btn{background:none;border:none;color:var(--dark-purple);font-size:1.5rem;cursor:pointer;display:none}.rotate-180{transform:rotate(180deg);transition:transform .2s ease-in-out}.sidebar .close-btn{position:absolute;top:10px;right:10px;color:white;font-size:1.5rem;background:none;border:none;cursor:pointer;z-index:1050;display:none}@media (max-width:768px){.sidebar{transform:translateX(-100%)}.sidebar.mobile-open{transform:translateX(0)}.content-wrapper{margin-left:0}.menu-toggle{display:flex;left:10px;top:10px;right:auto}.content-wrapper .navbar .menu-btn{display:block}.sidebar-overlay.active{display:block}.sidebar .dropdown-container{position:static}.sidebar.mobile-open .close-btn{display:block}}
</style>

<nav class="sidebar">
    <div class="sidebar-content">
        <button class="close-btn">
            <i class="fas fa-times"></i> </button>

        <div class="brand p-3">
            <h4 class="text-white">Admin Panel</h4>
        </div>

        <a href="/OceanGas/staff/admin_dashboard.php" class="<?=($current_page==='admin_dashboard.php')?'active':''?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>


        <a href="/OceanGas/staff/users.php" class="<?=($current_page==='users.php')?'active':''?>">
            <i class="fas fa-users"></i>
            <span>Manage Users</span>
        </a>
        <a href="/OceanGas/staff/stock_admin.php" class="<?=($current_page==='stock_admin.php')?'active':''?>">
            <i class="fas fa-cubes"></i>
            <span>Stock/Inventory</span>
        </a>

        <a href="/OceanGas/staff/finance.php" class="<?=($current_page==='finance.php')?'active':''?>">
            <i class="fas fa-dollar-sign"></i>
            <span>Finance</span>
        </a>

        <div class="dropdown procurement-dropdown">
            <button class="dropdown-btn procurement-dropdown-btn">
                <i class="fas fa-truck"></i>
                <span>Procurement</span>
                <i class="fas fa-chevron-down ms-auto"></i>
            </button>
            <div class="dropdown-container procurement-dropdown-container">
                <a href="/OceanGas/staff/procurement_staff_dashboard.php" data-target="#mainFrame" class="sidebar-link">
        <i class="fas fa-tachometer-alt"></i> Dashboard
      </a>
                
                <a href="/OceanGas/staff/purchase_history_reports.php" data-target="#mainFrame" class="sidebar-link">
                    <i class="fas fa-history"></i>
                    <span>Purchase History</span>
                </a>
                <a href="/OceanGas/staff/suppliers.php" data-target="#mainFrame" class="sidebar-link">
                    <i class="fas fa-truck"></i>
                    <span>Suppliers</span>
                </a>
                <a href="/OceanGas/staff/financial_overview.php" data-target="#mainFrame" class="sidebar-link">
                    <i class="fas fa-chart-line"></i>
                    <span>Financial Overview</span>
                </a>
            </div>
        </div>

        <div class="dropdown sales-dropdown">
            <button class="dropdown-btn sales-dropdown-btn">
                <i class="fas fa-shopping-cart"></i>
                <span>Sales</span>
                <i class="fas fa-chevron-down ms-auto"></i>
            </button>
            <div class="dropdown-container sales-dropdown-container">
                <a href="/OceanGas/staff/sales_staff_dashboard.php" data-target="#mainFrame" class="sidebar-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Sales Dashboard</span>
                </a>
                <a href="/OceanGas/staff/sales_invoice.php" data-target="#mainFrame" class="sidebar-link">
                    <i class="fas fa-file-invoice"></i>
                    <span>Sales Invoice</span>
                </a>
                <a href="/OceanGas/staff/reports.php" data-target="#mainFrame" class="sidebar-link">
                    <i class="fas fa-chart-pie"></i>
                    <span>Reports</span>
                </a>
            </div>
        </div>

         <div class="dropdown deliveries-dropdown">
            <button class="dropdown-btn deliveries-dropdown-btn">
                <i class="fas fa-truck-moving"></i>
                <span>Deliveries</span>
                <i class="fas fa-chevron-down ms-auto"></i>
            </button>
            <div class="dropdown-container deliveries-dropdown-container">
                <a href="/OceanGas/staff/add_delivery.php" data-target="#mainFrame" class="sidebar-link">
                    <i class="fas fa-plus"></i>
                    <span>Add Delivery</span>
                </a>
                <a href="/OceanGas/staff/view_deliveries.php" data-target="#mainFrame" class="sidebar-link">
                    <i class="fas fa-edit"></i>
                    <span>View Delivery</span>
                </a>
            </div>
        </div>

    </div>
</nav>

<button class="menu-toggle">
    <i class="fas fa-chevron-right"></i>
</button>

<div class="sidebar-overlay"></div>


<script>
    // Declare variables at the top of the script block
    let sidebar;
    let overlay;
    let fixedToggleBtn;
    let sidebarCloseBtn;
    let content; // Assuming .content is the main content wrapper in the parent page
    let sidebarLinks;
    let dropdownContainers;
    let dropdownButtons;


    // Use DOMContentLoaded to ensure elements from the included file are available
    document.addEventListener('DOMContentLoaded', function() {

        // Assign elements to the variables declared above
        sidebar = document.querySelector('.sidebar');
        overlay = document.querySelector('.sidebar-overlay');
        fixedToggleBtn = document.querySelector('.menu-toggle'); // The fixed mobile toggle button (chevron)
        sidebarCloseBtn = document.querySelector('.sidebar .close-btn'); // The new close button inside sidebar ('X')
        content = document.querySelector('.content'); // The main content area in the parent page

        // Select sidebar links, dropdown containers, and dropdown buttons
        sidebarLinks = document.querySelectorAll('.sidebar a[href]'); // Select all links with href in sidebar
        dropdownContainers = document.querySelectorAll('.sidebar .dropdown-container');
        dropdownButtons = document.querySelectorAll('.sidebar .dropdown-btn');


        // --- Sidebar Toggle Functionality ---
        // Function to toggle the sidebar visibility
        // 'open' parameter (optional): true to open, false to close, undefined to toggle
        window.toggleSidebar = function(open) { // Make it global so it can be called from the main page
            // Check if all necessary elements exist before proceeding
            if (!sidebar || !overlay || !fixedToggleBtn || !content || !sidebarCloseBtn) {
                console.warn("Sidebar or related layout elements not found. Ensure sidebar, sidebar-overlay, menu-toggle, close-btn, and content are in sidebar.php/main page.");
                return;
            }

            // Determine if the sidebar should be opened or closed
            // If 'open' is undefined, toggle based on current state
            // Otherwise, explicitly open or close based on the 'open' boolean
            const shouldOpen = open === undefined ? !sidebar.classList.contains('mobile-open') : open;

            if (shouldOpen) {
                // Open the sidebar
                sidebar.classList.add('mobile-open'); // Add class to slide sidebar into view
                overlay.classList.add('active'); // Show the overlay

                // On mobile, hide the fixed toggle button and show the close button when sidebar is open
                 if (window.innerWidth <= 768) {
                     fixedToggleBtn.style.display = 'none'; // Explicitly hide the chevron toggle
                     sidebarCloseBtn.style.display = 'block'; // Show the close button
                 }

            } else {
                // Close the sidebar
                sidebar.classList.remove('mobile-open'); // Remove class to slide sidebar out of view
                overlay.classList.remove('active'); // Hide the overlay

                 // On mobile, show the fixed toggle button and hide the close button when sidebar is closed
                 if (window.innerWidth <= 768) {
                     fixedToggleBtn.style.display = 'flex'; // Explicitly show the chevron toggle
                     sidebarCloseBtn.style.display = 'none'; // Hide the close button
                 }
            }
        }

        // Add event listener to the fixed mobile toggle button (the chevron)
        if (fixedToggleBtn) {
            fixedToggleBtn.addEventListener('click', () => toggleSidebar()); // Toggle sidebar on click
        } else {
             console.warn(".menu-toggle button not found on DOMContentLoaded.");
        }


        // Add event listener to the new sidebar close button (the 'X')
        if (sidebarCloseBtn) {
            sidebarCloseBtn.addEventListener('click', () => toggleSidebar(false)); // Explicitly close sidebar on click
        }

        // Close sidebar when clicking on the overlay (mobile)
        if (overlay) {
            overlay.addEventListener('click', () => toggleSidebar(false)); // Explicitly close the sidebar
        }

        // Handle window resize events to adjust layout and toggle button visibility
        window.addEventListener('resize', () => {
            // Check if necessary elements exist (using the variables assigned in DOMContentLoaded)
            if (!sidebar || !overlay || !fixedToggleBtn || !content || !sidebarCloseBtn) return;

            if (window.innerWidth > 768) {
                // On larger screens (desktop)
                // Ensure sidebar is not in the mobile-open state
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
                // Hide mobile toggle buttons
                fixedToggleBtn.style.display = 'none';
                // Hide the close button on desktop
                sidebarCloseBtn.style.display = 'none';

                // Set the correct left margin for the content wrapper
                content.style.marginLeft = '280px';
                content.style.width = 'calc(100% - 280px)';

                // Reset fixed toggle icon (not strictly necessary with display: none)
                fixedToggleBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';


            } else {
                // On smaller screens (mobile/tablet)
                // Show fixed mobile toggle button if sidebar is closed
                if (!sidebar.classList.contains('mobile-open')) {
                    fixedToggleBtn.style.display = 'flex'; // Show chevron toggle
                    sidebarCloseBtn.style.display = 'none'; // Hide close button
                    content.style.marginLeft = '0'; // No margin when sidebar is closed on mobile
                    content.style.width = '100%';
                } else {
                    // If sidebar is open on mobile, hide the fixed toggle button and show the close button
                    fixedToggleBtn.style.display = 'none'; // Hide chevron toggle
                    sidebarCloseBtn.style.display = 'block'; // Show close button
                    // content.style.marginLeft remains 0 on mobile
                    content.style.width = '100%';
                }

            }
        });

        // Trigger the resize event once on page load to set the initial layout state
        window.dispatchEvent(new Event('resize'));


        // --- Content Loading Functionality (Main Content vs Iframe) ---
        // Function to load content either into the main dashboard div or the iframe
        // This function is made available globally via `window.loadContent`
        window.loadContent = function(url, target) {
             // Check if main content areas exist
             const mainContent = document.getElementById('mainContent'); // Assuming mainContent is in the main page
             const mainFrame = document.getElementById('mainFrame'); // Assuming mainFrame is in the main page

             if (!mainContent || !mainFrame || !content) { // Use 'content' which is the wrapper
                 console.warn("Main content elements not found (#mainContent, #mainFrame, .content). Ensure they are in the main page.");
                 return;
             }

             // Remove the 'active' class from all sidebar links
             document.querySelectorAll('.sidebar a').forEach(link => link.classList.remove('active')); // Select all links in sidebar


             if (target === '#mainContent') {
                 // If the target is the main dashboard div
                 // Hide the iframe and show the main content div
                 mainFrame.style.display = 'none';
                 mainContent.style.display = 'block';
                  // Update active class for the dashboard link
                  const dashboardLink = document.querySelector('.sidebar a[href="/OceanGas/staff/admin_dashboard.php"]');
                  if(dashboardLink) dashboardLink.classList.add('active');


             } else if (target === '#mainFrame') {
                 // If the target is the iframe
                 // Hide the main content div and show the iframe
                 mainContent.style.display = 'none';
                 mainFrame.style.display = 'block';
                 // Set the iframe's source to the desired URL
                 mainFrame.src = url;
                  // Update active class for the clicked link
                  const clickedLink = document.querySelector(`.sidebar a[href="${url}"]`);
                  if(clickedLink) clickedLink.classList.add('active');

                  // Open parent dropdowns if necessary
                  let parentDropdown = clickedLink.closest('.dropdown');
                  while(parentDropdown) {
                      const dropdownContainer = parentDropdown.querySelector('.dropdown-container');
                      const dropdownBtn = parentDropdown.querySelector('.dropdown-btn .fa-chevron-down');
                      if(dropdownContainer) dropdownContainer.style.display = 'block';
                      if(dropdownBtn) dropdownBtn.classList.add('rotate-180');
                      parentDropdown = parentDropdown.parentElement.closest('.dropdown');
                  }
             }

             // On mobile, close the sidebar after a link is clicked
             if (window.innerWidth <= 768 && sidebar.classList.contains('mobile-open')) {
                 // Call the toggleSidebar function to close it
                 // Ensure toggleSidebar is accessible (it's defined globally above)
                 window.toggleSidebar(false);
             }
        };


        // Add event listeners to all sidebar links with an href
        // These links should now use onclick="loadContent(this.href, '#mainFrame')" or similar
        sidebarLinks.forEach(link => {
             link.addEventListener('click', function(e) {
                // Check if the link's href is intended for content loading
                // This assumes links for content loading have a data-target attribute
                const target = link.getAttribute('data-target');

                if (target) { // If a target is specified, handle with loadContent
                    e.preventDefault(); // Prevent the default link navigation
                    const url = link.getAttribute('href');
                    if (url) {
                        window.loadContent(url, target);
                    } else {
                        console.warn("Sidebar link missing href attribute:", link);
                    }
                }
                // If no data-target, let the default link behavior happen (e.g., logout)
             });
        });


        // --- Sidebar Dropdown Toggling (Independent Behavior) ---
        // Use event delegation on the sidebar for dropdown clicks
        if (sidebar) {
            sidebar.addEventListener('click', function(e) {
                // Check if the clicked element or its parent is a dropdown button
                const dropdownBtn = e.target.closest('.dropdown-btn');

                if (dropdownBtn) {
                    e.stopPropagation(); // Prevent the click from propagating

                    const clickedDropdownContainer = dropdownBtn.nextElementSibling; // Get the container for the clicked button
                    const clickedChevron = dropdownBtn.querySelector('.fa-chevron-down'); // Get the chevron for the clicked button

                    // Toggle the display of the clicked dropdown's container
                    const isClickedOpen = clickedDropdownContainer.style.display === 'block';
                    clickedDropdownContainer.style.display = isClickedOpen ? 'none' : 'block';

                    // Toggle the rotation class on the chevron icon
                    if (clickedChevron) clickedChevron.classList.toggle('rotate-180', !isClickedOpen);

                    // Removed the logic to close other dropdowns
                }
            });
        } else {
             console.warn("Sidebar element not found for dropdown delegation.");
        }


        // Close dropdowns when clicking anywhere outside the sidebar or the fixed toggle button
        document.addEventListener('click', (e) => {
             // Check if the click target is not inside the sidebar AND not inside the fixed toggle button
             if (sidebar && !e.target.closest('.sidebar') && !(fixedToggleBtn && e.target.closest('.menu-toggle'))) {
                  // Hide all dropdown containers
                  dropdownContainers.forEach(container => {
                      container.style.display = 'none';
                      // Reset all chevron icons
                      const chevron = container.previousElementSibling.querySelector('.fa-chevron-down');
                      if (chevron) chevron.classList.remove('rotate-180');
                  });
             }
        });


        // --- Scroll Restoration Prevention ---
        // Prevents the browser from automatically scrolling to the previous position on page load/refresh
        if (history.scrollRestoration) {
            history.scrollRestoration = 'manual';
        } else {
            window.onbeforeunload = function () {
                window.scrollTo(0, 0);
            }
        }


        // --- Initial Dropdown State ---
        // The code to make dropdowns open by default on page load has been removed.
    });

    // NOTE: The resize listener is now inside DOMContentLoaded to ensure it uses
    // the correctly assigned variables.

</script>
