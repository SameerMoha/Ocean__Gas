<?php
session_start();

// Check if staff is logged in
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

// Include Composer's autoload file (adjust path if needed)
require_once __DIR__ . '/../vendor/autoload.php';

// Include TCPDF namespace if needed (TCPDF does not require a namespace)
// require_once('tcpdf_include.php'); // Uncomment if necessary

// Database connection
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve filter parameters from GET
$start_date      = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date        = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$supplier_filter = isset($_GET['supplier']) ? $_GET['supplier'] : '';
$product_filter  = isset($_GET['product']) ? $_GET['product'] : '';

// Build dynamic SQL query with filters
$query = "SELECT ph.purchase_date, ph.product, ph.quantity, s.name AS supplier, 
                 u.username AS purchased_by,
                 (CASE 
                    WHEN ph.product = '6kg' THEN s.cost_6kg 
                    ELSE s.cost_12kg 
                  END * ph.quantity) AS total_cost
          FROM purchase_history ph
          JOIN suppliers s ON ph.supplier_id = s.id
          JOIN users u ON ph.purchased_by = u.id
          WHERE 1=1 ";
$params = [];
$types  = "";

// Filter by purchase date range
if (!empty($start_date)) {
    $query .= " AND ph.purchase_date >= ? ";
    $params[] = $start_date;
    $types   .= "s";
}
if (!empty($end_date)) {
    $query .= " AND ph.purchase_date <= ? ";
    $params[] = $end_date;
    $types   .= "s";
}

// Filter by supplier name
if (!empty($supplier_filter)) {
    $query .= " AND s.name LIKE ? ";
    $params[] = "%{$supplier_filter}%";
    $types   .= "s";
}

// Filter by product
if (!empty($product_filter)) {
    $query .= " AND ph.product LIKE ? ";
    $params[] = "%{$product_filter}%";
    $types   .= "s";
}

$query .= " ORDER BY ph.purchase_date DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$purchase_history = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $purchase_history[] = $row;
    }
}
$stmt->close();
$conn->close();

// Start capturing HTML output
ob_start();
?>
<html>
<head>
  <style>
    body { font-family: Arial, sans-serif; font-size: 12px; }
    h2 { text-align: center; }
    .filters { text-align: center; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    table, th, td { border: 1px solid #ddd; }
    th, td { padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
  </style>
</head>
<body>
  <h2>Purchase History Report</h2>
  <?php if (!empty($start_date) || !empty($end_date) || !empty($supplier_filter) || !empty($product_filter)): ?>
    <div class="filters">
      <strong>Filters Applied:</strong>
      <?php
        $filters = [];
        if (!empty($start_date))      $filters[] = "Start Date: " . htmlspecialchars($start_date);
        if (!empty($end_date))        $filters[] = "End Date: " . htmlspecialchars($end_date);
        if (!empty($supplier_filter)) $filters[] = "Supplier: " . htmlspecialchars($supplier_filter);
        if (!empty($product_filter))  $filters[] = "Product: " . htmlspecialchars($product_filter);
        echo implode(" | ", $filters);
      ?>
    </div>
  <?php endif; ?>
  <table>
    <thead>
      <tr>
        <th>Purchase Date</th>
        <th>Supplier Name</th>
        <th>Product</th>
        <th>Quantity</th>
        <th>Total Cost (KES)</th>
        <th>Procurement Staff</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($purchase_history) > 0): ?>
        <?php foreach ($purchase_history as $history): ?>
          <tr>
            <td><?php echo htmlspecialchars($history['purchase_date']); ?></td>
            <td><?php echo htmlspecialchars($history['supplier']); ?></td>
            <td><?php echo htmlspecialchars($history['product']); ?></td>
            <td><?php echo htmlspecialchars($history['quantity']); ?></td>
            <td>KES <?php echo number_format($history['total_cost'], 2); ?></td>
            <td><?php echo htmlspecialchars($history['purchased_by']); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="6" style="text-align: center;">No purchase history found.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
<?php
// Get the HTML content
$html = ob_get_clean();

// Instantiate TCPDF
// Create new PDF document (orientation, unit, format)
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information (optional)
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('OceanGas');
$pdf->SetTitle('Purchase History Report');

// Remove default header/footer if desired
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set default font subsetting mode
$pdf->setFontSubsetting(true);
$pdf->SetFont('dejavusans', '', 10);

// Add a page
$pdf->AddPage();

// Write the HTML content
$pdf->writeHTML($html, true, false, true, false, '');

// Output the PDF as a download (D sends it as a download)
$pdf->Output('purchase_history.pdf', 'D');
exit();
