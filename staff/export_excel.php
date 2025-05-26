<?php
session_start();
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

// Include DB connection
require_once 'db_connect.php';

// Retrieve filter parameters
$start_date      = $_GET['start_date']      ?? '';
$end_date        = $_GET['end_date']        ?? '';
$supplier_filter = $_GET['supplier']        ?? '';
$product_filter  = $_GET['product']         ?? '';

// Build base query
$sql = "
  SELECT 
    ph.purchase_date,
    s.name           AS supplier,
    ph.product,
    ph.quantity,
    u.username       AS purchased_by,
    (pr.buying_price * ph.quantity) AS total_cost
  FROM purchase_history ph
  JOIN suppliers s    ON ph.supplier_id = s.id
  JOIN users u        ON ph.purchased_by = u.id
  JOIN products p     ON ph.product    = p.product_name
  JOIN price pr       ON pr.product_id  = p.product_id
                      AND pr.supplier_id = ph.supplier_id
  WHERE 1=1
";

$params = [];
$types  = "";

// Apply filters
if ($start_date !== '') {
    $sql      .= " AND ph.purchase_date >= ? ";
    $params[]  = $start_date;
    $types    .= "s";
}
if ($end_date !== '') {
    $sql      .= " AND ph.purchase_date <= ? ";
    $params[]  = $end_date;
    $types    .= "s";
}
if ($supplier_filter !== '') {
    $sql      .= " AND s.name LIKE ? ";
    $params[]  = "%$supplier_filter%";
    $types    .= "s";
}
if ($product_filter !== '') {
    $sql      .= " AND ph.product LIKE ? ";
    $params[]  = "%$product_filter%";
    $types    .= "s";
}

$sql .= " ORDER BY ph.purchase_date DESC";

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch into array
$purchase_history = [];
while ($row = $result->fetch_assoc()) {
    $purchase_history[] = $row;
}
$stmt->close();
$conn->close();

// PhpSpreadsheet export
require_once __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Header row
$headers = ['A1'=>'Purchase Date','B1'=>'Supplier Name','C1'=>'Product','D1'=>'Quantity','E1'=>'Total Cost (KES)','F1'=>'Procurement Staff'];
foreach ($headers as $cell => $text) {
    $sheet->setCellValue($cell, $text);
}

// Data rows
$rowIndex = 2;
foreach ($purchase_history as $h) {
    $sheet->setCellValue("A{$rowIndex}", $h['purchase_date']);
    $sheet->setCellValue("B{$rowIndex}", $h['supplier']);
    $sheet->setCellValue("C{$rowIndex}", $h['product']);
    $sheet->setCellValue("D{$rowIndex}", $h['quantity']);
    $sheet->setCellValue("E{$rowIndex}", $h['total_cost']);
    $sheet->setCellValue("F{$rowIndex}", $h['purchased_by']);
    $rowIndex++;
}

// Auto-size columns A–F
foreach (range('A','F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Send to browser
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="purchase_history.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
