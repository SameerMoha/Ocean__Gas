<?php
// export_product_performance_excel.php
session_start();
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'sales' && $_SESSION['staff_role'] !== 'admin' ) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit;
}
require_once '../includes/db.php';
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$query = "
    SELECT p.product_name, SUM(sr.quantity) AS total_units, SUM(sr.total_amount) AS total_revenue
    FROM sales_record sr
    JOIN products p ON sr.product_id = p.product_id
    GROUP BY sr.product_id
    ORDER BY total_revenue DESC
";
$result = $conn->query($query);
if (!$result) { die("Query Failed: " . $conn->error); }
$data = [];
while($row = $result->fetch_assoc()){
    $data[] = $row;
}
$conn->close();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Product');
$sheet->setCellValue('B1', 'Total Units Sold');
$sheet->setCellValue('C1', 'Total Revenue (Ksh)');

$rowNum = 2;
foreach ($data as $d) {
    $sheet->setCellValue('A' . $rowNum, $d['product_name']);
    $sheet->setCellValue('B' . $rowNum, $d['total_units']);
    $sheet->setCellValue('C' . $rowNum, $d['total_revenue']);
    $rowNum++;
}

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="product_performance.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
