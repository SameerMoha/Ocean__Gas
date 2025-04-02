<?php
session_start();
if (!isset($_SESSION['staff_username'])) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit();
}

// Include your database connection file
require_once 'db_connect.php';

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
    $types .= "s";
}
if (!empty($end_date)) {
    $query .= " AND ph.purchase_date <= ? ";
    $params[] = $end_date;
    $types .= "s";
}
// Filter by supplier name
if (!empty($supplier_filter)) {
    $query .= " AND s.name LIKE ? ";
    $params[] = "%" . $supplier_filter . "%";
    $types .= "s";
}
// Filter by product
if (!empty($product_filter)) {
    $query .= " AND ph.product LIKE ? ";
    $params[] = "%" . $product_filter . "%";
    $types .= "s";
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

// Load PhpSpreadsheet classes
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set header row values
$sheet->setCellValue('A1', 'Purchase Date');
$sheet->setCellValue('B1', 'Supplier Name');
$sheet->setCellValue('C1', 'Product');
$sheet->setCellValue('D1', 'Quantity');
$sheet->setCellValue('E1', 'Total Cost (KES)');
$sheet->setCellValue('F1', 'Procurement Staff');

// Write data rows
$rowIndex = 2;
foreach ($purchase_history as $history) {
    $sheet->setCellValue('A' . $rowIndex, $history['purchase_date']);
    $sheet->setCellValue('B' . $rowIndex, $history['supplier']);
    $sheet->setCellValue('C' . $rowIndex, $history['product']);
    $sheet->setCellValue('D' . $rowIndex, $history['quantity']);
    $sheet->setCellValue('E' . $rowIndex, $history['total_cost']);
    $sheet->setCellValue('F' . $rowIndex, $history['purchased_by']);
    $rowIndex++;
}

// Auto size columns
foreach (range('A', 'F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Set headers to force download of Excel file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="purchase_history.xlsx"');
header('Cache-Control: max-age=0');

// Write file to output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>
