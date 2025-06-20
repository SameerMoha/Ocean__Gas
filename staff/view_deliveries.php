<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Deliveries</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    body {
      display: flex;
      background: #f8f9fa;
      font-family: Arial, sans-serif;
    }
    .sidebar {
      width: 250px;
      background: #6a008a;
      color: white;
      padding: 20px;
      height: 100vh;
      position: fixed;
    }
    .sidebar a {
      color: white;
      display: block;
      padding: 10px;
      text-decoration: none;
      margin: 5px 0;
    }
    .sidebar a:hover, .sidebar a.active {
      background: rgba(255,255,255,0.2);
      border-radius: 5px;
                  font-weight: bold;

    }
    .dropdown-btn {
      background: none;
      border: none;
      color: white;
      padding: 10px;
      text-align: left;
      width: 100%;
      cursor: pointer;
      font-size: 16px;
    }
    .dropdown-container {
      display: none;
      background-color: #6a008a;
      padding-left: 20px;
    }
    .dropdown-btn.active + .dropdown-container {
      display: block;
    }
    .content {
      margin-left: 270px;
      padding: 30px;
      width: calc(100% - 270px);
    }
    .status-badge {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: bold;
    }
    .status-pending {
      background-color: #ffc107;
      color: #000;
    }
    .status-transit {
      background-color: #17a2b8;
      color: #fff;
    }
    .status-delivered {
      background-color: #28a745;
      color: #fff;
    }
    .status-cancelled {
      background-color: #dc3545;
      color: #fff;
    }
    .filter-card {
      background-color: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 0.25rem;
      padding: 15px;
      margin-bottom: 20px;
    }
    .stats-item {
      text-align: center;
      padding: 15px;
      border-radius: 8px;
      background-color: #f8f9fa;
      margin-bottom: 10px;
    }
    .stats-item h4 {
      font-weight: bold;
      margin-bottom: 5px;
      color: #6a008a;
    }
    .stats-item p {
      margin-bottom: 0;
      color: #6c757d;
    }
    /* Added CSS for export buttons */
    .ms-1 {
      margin-left: 0.25rem !important;
    }
  </style>
</head>
<body>
  
<div class="sidebar"> 
    <h2>Admin Panel</h2>
    <a href="/OceanGas/staff/admin_dashboard.php">
      <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a href="/OceanGas/staff/stock_admin.php">
      <i class="fas fa-box"></i> Stock/Inventory
    </a>
    <a href="/OceanGas/staff/users.php">
      <i class="fas fa-users"></i> Manage Users
    </a>
    <a href="/OceanGas/staff/finance.php">
      <i class="fas fa-dollar-sign"></i> Finance
    </a>

    <button class="dropdown-btn">
      <i class="fas fa-truck"></i> Deliveries
      <i class="fas fa-caret-down"></i>
    </button>
    <div class="dropdown-container">
      <a href="add_delivery.php">Add Delivery</a>
      <a href="view_deliveries.php" class="active">View Deliveries</a>
    </div>

    <button class="dropdown-btn">
      <i class="fas fa-truck"></i> Procurement
      <i class="fas fa-caret-down"></i>
    </button>
    <div class="dropdown-container">
      <a href="/OceanGas/staff/procurement_staff_dashboard.php?embedded=1">Dashboard</a>
      <a href="/OceanGas/staff/purchase_history_reports.php?embedded=1">Purchase History</a>
      <a href="/OceanGas/staff/suppliers.php?embedded=1">Suppliers</a>
      <a href="/OceanGas/staff/financial_overview.php?embedded=1">Financial Overview</a>
    </div>

    <button class="dropdown-btn">
      <i class="fas fa-shopping-cart"></i> Sales
      <i class="fas fa-caret-down"></i>
    </button>
    <div class="dropdown-container">
      <a href="/OceanGas/staff/sales_staff_dashboard.php?embedded=1">Dashboard</a>
      <a href="/OceanGas/staff/sales_invoice.php?embedded=1">Sales Invoice</a>
      <a href="/OceanGas/staff/reports.php?embedded=1">Reports</a>
    </div>
</div>

  <div class="content">
    <?php
    $host = 'localhost';
    $db   = 'oceangas';
    $user = 'root';
    $pass = '';
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Process status update if form is submitted
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_status'])) {
        $delivery_id = $_POST['delivery_id'];
        $new_status = $_POST['new_status'];
        
        $update_sql = "UPDATE deliveries SET delivery_status = ? WHERE delivery_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $new_status, $delivery_id);
        
        if ($stmt->execute()) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                  Delivery status updated successfully!
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        } else {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                  Error updating status: ' . $stmt->error . '
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        }
        
        $stmt->close();
    }

    // Get all available drivers for filter dropdown
    $drivers_query = "SELECT DISTINCT assigned_to FROM deliveries WHERE assigned_to IS NOT NULL ORDER BY assigned_to";
    $drivers_result = $conn->query($drivers_query);
    $drivers = [];
    while ($driver_row = $drivers_result->fetch_assoc()) {
        $drivers[] = $driver_row['assigned_to'];
    }

    // Initialize filter variables
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    $driver_filter = isset($_GET['driver']) ? $_GET['driver'] : '';
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
    $search_query = isset($_GET['search']) ? $_GET['search'] : '';

    // Build query with filters - Updated to include order_number
    $sql = "SELECT d.*, o.invoice_summary, o.delivery_info, o.order_number
            FROM deliveries d
            LEFT JOIN orders o ON d.order_id = o.order_id
            WHERE 1=1"; // 1=1 is always true, allows us to conditionally append filters

    $params = [];
    $types = "";

    if (!empty($status_filter)) {
        $sql .= " AND d.delivery_status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }

    if (!empty($driver_filter)) {
        $sql .= " AND d.assigned_to = ?";
        $params[] = $driver_filter;
        $types .= "s";
    }

    if (!empty($date_from)) {
        $sql .= " AND d.delivery_date >= ?";
        $params[] = $date_from;
        $types .= "s";
    }

    if (!empty($date_to)) {
        $sql .= " AND d.delivery_date <= ?";
        $params[] = $date_to;
        $types .= "s";
    }

    if (!empty($search_query)) {
        $sql .= " AND (d.order_id LIKE ? OR d.assigned_to LIKE ? OR d.notes LIKE ? OR o.invoice_summary LIKE ? OR o.order_number LIKE ?)";
        $search_param = "%" . $search_query . "%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "sssss";
    }

    $sql .= " ORDER BY d.delivery_date DESC";

    // Execute query with filters
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    ?>
    
    <h2 class="mb-4">All Deliveries</h2>

    <!-- Filter Section -->
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold">Filter Deliveries</h6>
      </div>
      <div class="card-body">
        <form method="GET" action="" class="row g-3">
          <!-- Search Bar -->
          <div class="col-md-12 mb-3">
            <div class="input-group">
              <input type="text" class="form-control" placeholder="Search deliveries..." name="search" value="<?= htmlspecialchars($search_query) ?>">
              <button class="btn btn-primary" type="submit">
                <i class="fas fa-search"></i> Search
              </button>
            </div>
          </div>

          <!-- Status Filter -->
          <div class="col-md-3">
            <label for="statusFilter" class="form-label">Status</label>
            <select class="form-select" id="statusFilter" name="status">
              <option value="">All Statuses</option>
              <option value="In Transit" <?= ($status_filter === 'In Transit') ? 'selected' : '' ?>>In Transit</option>
              <option value="Delivered" <?= ($status_filter === 'Delivered') ? 'selected' : '' ?>>Delivered</option>
              <option value="Cancelled" <?= ($status_filter === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
            </select>
          </div>

          <!-- Driver Filter -->
          <div class="col-md-3">
            <label for="driverFilter" class="form-label">Driver</label>
            <select class="form-select" id="driverFilter" name="driver">
              <option value="">All Drivers</option>
              <?php foreach ($drivers as $driver): ?>
                <option value="<?= htmlspecialchars($driver) ?>" <?= ($driver_filter === $driver) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($driver) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Date Range Filters -->
          <div class="col-md-3">
            <label for="dateFrom" class="form-label">From Date</label>
            <input type="date" class="form-control" id="dateFrom" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
          </div>

          <div class="col-md-3">
            <label for="dateTo" class="form-label">To Date</label>
            <input type="date" class="form-control" id="dateTo" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
          </div>

          <!-- Filter Buttons -->
          <div class="col-12 mt-3">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-filter"></i> Apply Filters
            </button>
            <a href="view_deliveries.php" class="btn btn-secondary">
              <i class="fas fa-times"></i> Clear Filters
            </a>
            <a href="add_delivery.php" class="btn btn-success">
              <i class="fas fa-plus"></i> Add New Delivery
            </a>
            
            <!-- New Export Buttons -->
            <button type="button" onclick="exportTableToCSV('deliveries.csv')" class="btn btn-success ms-1">
              <i class="fas fa-file-excel"></i> Export to Excel
            </button>
            <button type="button" onclick="printTable()" class="btn btn-danger ms-1">
              <i class="fas fa-file-pdf"></i> Export/Print
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Deliveries Table -->
    <div class="card shadow mb-4">
      <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold">Manage Deliveries</h6>
        <span class="badge bg-primary"><?= $result->num_rows ?> deliveries found</span>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-hover">
            <thead class="table-dark">
              <tr>
                <th>Delivery ID</th>
                <th>Order Number</th>
                <th>Order Details</th>
                <th>Delivery Location</th>
                <th>Assigned To</th>
                <th>Delivery Date</th>
                <th>Status</th>
                <th>Notes</th>
                <th>Actions</th>
              </tr>
            </thead>
            
            <tbody>
            <?php 
            if ($result->num_rows > 0) {
              while ($row = $result->fetch_assoc()): 
                // Parse delivery info (JSON string)
                $delivery_info = json_decode($row['delivery_info'] ?? '{}', true);
                $delivery_address = $delivery_info['address'] ?? 'N/A';
                $apartment = $delivery_info['apartment'] ?? '';
                if (!empty($apartment)) {
                  $delivery_address .= ", Apt: " . $apartment;
                }
                
                // Define status badge class
                $status_class = 'status-pending';
                if ($row['delivery_status'] === 'In Transit') {
                  $status_class = 'status-transit';
                } elseif ($row['delivery_status'] === 'Delivered') {
                  $status_class = 'status-delivered';
                } elseif ($row['delivery_status'] === 'Cancelled') {
                  $status_class = 'status-cancelled';
                }
            ?>
              <tr>
                <td><?= htmlspecialchars($row['delivery_id']) ?></td>
                <td><?= htmlspecialchars($row['order_number'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($row['invoice_summary'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($delivery_address) ?></td>
                <td><?= htmlspecialchars($row['assigned_to']) ?></td>
                <td><?= htmlspecialchars($row['delivery_date']) ?></td>
                <td>
                  <span class="status-badge <?= $status_class ?>">
                    <?= htmlspecialchars($row['delivery_status']) ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($row['notes']) ?></td>
                <td>
                  <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateStatusModal<?= $row['delivery_id'] ?>">
                    <i class="fas fa-edit"></i> Update Status
                  </button>
                  
                  <!-- Status Update Modal -->
                  <div class="modal fade" id="updateStatusModal<?= $row['delivery_id'] ?>" tabindex="-1" aria-labelledby="updateStatusModalLabel<?= $row['delivery_id'] ?>" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="updateStatusModalLabel<?= $row['delivery_id'] ?>">Update Delivery Status</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST">
                          <div class="modal-body">
                            <input type="hidden" name="delivery_id" value="<?= $row['delivery_id'] ?>">
                            <div class="mb-3">
                              <label for="statusSelect<?= $row['delivery_id'] ?>" class="form-label">New Status</label>
                              <select class="form-select" id="statusSelect<?= $row['delivery_id'] ?>" name="new_status" required>
                                <option value="In Transit" <?= ($row['delivery_status'] === 'In Transit') ? 'selected' : '' ?>>In Transit</option>
                                <option value="Delivered" <?= ($row['delivery_status'] === 'Delivered') ? 'selected' : '' ?>>Delivered</option>
                                <option value="Cancelled" <?= ($row['delivery_status'] === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                              </select>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            <?php 
              endwhile; 
            } else {
              echo '<tr><td colspan="9" class="text-center">No deliveries found matching your filters</td></tr>';
            }
            ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    
    <!-- Status Distribution Chart -->
    <div class="row mt-4">
      <div class="col-md-6">
        <div class="card shadow">
          <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">Delivery Status Distribution</h6>
          </div>
          <div class="card-body">
            <canvas id="statusChart"></canvas>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="card shadow">
          <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">Delivery Statistics</h6>
          </div>
          <div class="card-body">
            <?php
            // Calculate statistics - use unfiltered data for overall stats
            $total_query = "SELECT COUNT(*) as total FROM deliveries";
            $pending_query = "SELECT COUNT(*) as pending FROM deliveries WHERE delivery_status = 'Pending'";
            $transit_query = "SELECT COUNT(*) as in_transit FROM deliveries WHERE delivery_status = 'In Transit'";
            $delivered_query = "SELECT COUNT(*) as delivered FROM deliveries WHERE delivery_status = 'Delivered'";
            $cancelled_query = "SELECT COUNT(*) as cancelled FROM deliveries WHERE delivery_status = 'Cancelled'";
            
            $total_result = $conn->query($total_query);
            $pending_result = $conn->query($pending_query);
            $transit_result = $conn->query($transit_query);
            $delivered_result = $conn->query($delivered_query);
            $cancelled_result = $conn->query($cancelled_query);
            
            $total = $total_result->fetch_assoc()['total'];
            $pending = $pending_result->fetch_assoc()['pending'];
            $in_transit = $transit_result->fetch_assoc()['in_transit'];
            $delivered = $delivered_result->fetch_assoc()['delivered'];
            $cancelled = $cancelled_result->fetch_assoc()['cancelled'];
            ?>
            
            <div class="row mb-4">
              <div class="">
                <div class="stats-item">
                  <h4><?= $total ?></h4>
                  <p>Total Deliveries</p>
                </div>
              </div>
             
            </div>
            
            <div class="row">
              <div class="col-md-4">
                <div class="stats-item">
                  <h4><?= $in_transit ?></h4>
                  <p>In Transit</p>
                </div>
              </div>
              <div class="col-md-4">
                <div class="stats-item">
                  <h4><?= $delivered ?></h4>
                  <p>Delivered</p>
                </div>
              </div>
              <div class="col-md-4">
                <div class="stats-item">
                  <h4><?= $cancelled ?></h4>
                  <p>Cancelled</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <?php $conn->close(); ?>
  </div>

  <!-- Bootstrap JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
  <script>
    // Initialize dropdown functionality
    document.addEventListener('DOMContentLoaded', function() {
      var dropdowns = document.getElementsByClassName("dropdown-btn");
      for (var i = 0; i < dropdowns.length; i++) {
        dropdowns[i].addEventListener("click", function() {
          this.classList.toggle("active");
          var dropdownContent = this.nextElementSibling;
          if (dropdownContent.style.display === "block") {
            dropdownContent.style.display = "none";
          } else {
            dropdownContent.style.display = "block";
          }
        });
      }
      
      // Auto-open Deliveries dropdown
      document.querySelectorAll('.dropdown-btn')[0].click();
      
      // Initialize status chart
      var ctx = document.getElementById('statusChart').getContext('2d');
      var myChart = new Chart(ctx, {
        type: 'pie',
        data: {
          labels: [ 'In Transit', 'Delivered', 'Cancelled'],
          datasets: [{
            data: [ <?= $in_transit ?>, <?= $delivered ?>, <?= $cancelled ?>],
            backgroundColor: [
              '#ffc107', // Pending - yellow
              '#17a2b8', // In Transit - teal
              '#28a745', // Delivered - green
              '#dc3545'  // Cancelled - red
            ],
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: {
              position: 'right',
            }
          }
        }
      });
    });
    
    // Export to CSV (Excel) function
    function exportTableToCSV(filename) {
      var csv = [];
      var rows = document.querySelectorAll('table tr');
      
      for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (var j = 0; j < cols.length - 1; j++) { // Skip last column (Actions)
          // Replace HTML entities and clean text
          var text = cols[j].innerText.replace(/"/g, '""');
          row.push('"' + text + '"');
        }
        
        csv.push(row.join(','));
      }
      
      // Download CSV file
      downloadCSV(csv.join('\n'), filename);
    }
    
    function downloadCSV(csv, filename) {
      var csvFile;
      var downloadLink;
      
      // CSV file
      csvFile = new Blob([csv], {type: "text/csv"});
      
      // Download link
      downloadLink = document.createElement("a");
      
      // File name
      downloadLink.download = filename;
      
      // Create a link to the file
      downloadLink.href = window.URL.createObjectURL(csvFile);
      
      // Hide download link
      downloadLink.style.display = "none";
      
      // Add the link to DOM
      document.body.appendChild(downloadLink);
      
      // Click download link
      downloadLink.click();
    }
    
    function printTable() {
      // Create a new window
      var printWindow = window.open('', '_blank');
      
      // Get the table element
      var table = document.querySelector('.table').cloneNode(true);
      
      // Remove the action column
      var rows = table.querySelectorAll('tr');
      for (var i = 0; i < rows.length; i++) {
        var cells = rows[i].querySelectorAll('th, td');
        if (cells.length > 0) {
          // Remove the last cell (Actions column)
          cells[cells.length - 1].remove();
        }
      }
      
      // Apply print styles
      var style = `
        <style>
          body { font-family: Arial, sans-serif; }
          table { border-collapse: collapse; width: 100%; }
          th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
          th { background-color: #6a008a; color: white; }
          .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
          }
          .status-pending { background-color: #ffc107; color: #000; }
          .status-transit { background-color: #17a2b8; color: #fff; }
          .status-delivered { background-color: #28a745; color: #fff; }
          .status-cancelled { background-color: #dc3545; color: #fff; }
          h1 { color: #6a008a; }
          .header { display: flex; justify-content: space-between; align-items: center; }
          .header img { height: 60px; }
          @media print {
            button { display: none; }
          }
        </style>
      `;
      
      // Get current date and time
      var now = new Date();
      var dateString = now.toLocaleDateString();
      var timeString = now.toLocaleTimeString();
      
      // Create header with OceanGas branding
      var header = `
        <div class="header">
          <div>
            <h1>OceanGas Delivery Report</h1>
            <p>Generated on: ${dateString} at ${timeString}</p>
          </div>
        </div>
        <hr>
        <button onclick="window.print();" style="margin: 20px 0; padding: 10px 20px; background: #6a008a; color: white; border: none; border-radius: 4px; cursor: pointer;">
          Print / Save as PDF
        </button>
      `;
      
      // Get filters information
      var filtersInfo = "";
      if ('<?= $status_filter ?>' !== '') filtersInfo += "Status: <?= htmlspecialchars($status_filter) ?>, ";
      if ('<?= $driver_filter ?>' !== '') filtersInfo += "Driver: <?= htmlspecialchars($driver_filter) ?>, ";
      if ('<?= $date_from ?>' !== '') filtersInfo += "From: <?= htmlspecialchars($date_from) ?>, ";
      if ('<?= $date_to ?>' !== '') filtersInfo += "To: <?= htmlspecialchars($date_to) ?>, ";
      if ('<?= $search_query ?>' !== '') filtersInfo += "Search: <?= htmlspecialchars($search_query) ?>, ";
      
      if (filtersInfo !== "") {
        filtersInfo = "<p><strong>Filters applied:</strong> " + filtersInfo.slice(0, -2) + "</p>";
      }
      
      // Construct the HTML document
      var html = `
        <!DOCTYPE html>
        <html>
          <head>
            <title>OceanGas Delivery Report</title>
            ${style}
          </head>
          <body>
            ${header}
            ${filtersInfo}
            ${table.outerHTML}
          </body>
        </html>
      `;
      
      // Write to the new window
      printWindow.document.open();
      printWindow.document.write(html);
      printWindow.document.close();
    }
  </script>
</body>
</html>