<?php
session_start();
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

// Autoload & TCPDF
require_once __DIR__ . '/../vendor/autoload.php'; 
// require_once('tcpdf_include.php'); // if you need it

// DB connection
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get filters
$start_date      = $_GET['start_date']      ?? '';
$end_date        = $_GET['end_date']        ?? '';
$supplier_filter = $_GET['supplier']        ?? '';
$product_filter  = $_GET['product']         ?? '';

// Build query using JOIN → products → price
$sql = "
  SELECT 
    ph.purchase_date,
    s.name           AS supplier,
    ph.product,
    ph.quantity,
    u.username       AS purchased_by,
    (pr.buying_price * ph.quantity) AS total_cost
  FROM purchase_history ph
  JOIN suppliers s    ON ph.supplier_id    = s.id
  JOIN users u        ON ph.purchased_by   = u.id
  JOIN products p     ON ph.product        = p.product_name
  JOIN price pr       ON pr.product_id     = p.product_id
                      AND pr.supplier_id  = ph.supplier_id
  WHERE 1=1
";

$params = [];
$types  = '';

// apply filters
if ($start_date !== '') {
    $sql      .= " AND ph.purchase_date >= ? ";
    $params[]  = $start_date;
    $types    .= 's';
}
if ($end_date !== '') {
    $sql      .= " AND ph.purchase_date <= ? ";
    $params[]  = $end_date;
    $types    .= 's';
}
if ($supplier_filter !== '') {
    $sql      .= " AND s.name LIKE ? ";
    $params[]  = "%{$supplier_filter}%";
    $types    .= 's';
}
if ($product_filter !== '') {
    $sql      .= " AND ph.product LIKE ? ";
    $params[]  = "%{$product_filter}%";
    $types    .= 's';
}

$sql .= " ORDER BY ph.purchase_date DESC";

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$purchase_history = [];
while ($row = $result->fetch_assoc()) {
    $purchase_history[] = $row;
}
$stmt->close();
$conn->close();

// Capture HTML
ob_start();
?>
<html><head>
  <style>
    body { font-family: Arial, sans-serif; font-size: 12px; }
    h2 { text-align: center; }
    .filters { text-align: center; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    table, th, td { border: 1px solid #ddd; }
    th, td { padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
  </style>
</head><body>
  <h2>Purchase History Report</h2>
  <?php if ($start_date || $end_date || $supplier_filter || $product_filter): ?>
    <div class="filters">
      <strong>Filters:</strong>
      <?php
        $f = [];
        if ($start_date)      $f[] = "From: {$start_date}";
        if ($end_date)        $f[] = "To: {$end_date}";
        if ($supplier_filter) $f[] = "Supplier: {$supplier_filter}";
        if ($product_filter)  $f[] = "Product: {$product_filter}";
        echo implode(' | ', $f);
      ?>
    </div>
  <?php endif; ?>
  <table>
    <thead>
      <tr>
        <th>Date</th><th>Supplier</th><th>Product</th>
        <th>Qty</th><th>Total Cost (KES)</th><th>Staff</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($purchase_history): ?>
        <?php foreach ($purchase_history as $h): ?>
          <tr>
            <td><?=htmlspecialchars($h['purchase_date'])?></td>
            <td><?=htmlspecialchars($h['supplier'])?></td>
            <td><?=htmlspecialchars($h['product'])?></td>
            <td><?=intval($h['quantity'])?></td>
            <td><?=number_format($h['total_cost'],2)?></td>
            <td><?=htmlspecialchars($h['purchased_by'])?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="6" style="text-align:center">No records found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body></html>
<?php
$html = ob_get_clean();

// Generate PDF
$pdf = new TCPDF('P','mm','A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('OceanGas');
$pdf->SetTitle('Purchase History Report');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetFont('dejavusans','',10);
$pdf->AddPage();
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('purchase_history.pdf', 'D');
exit();
