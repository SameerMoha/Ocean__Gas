<?php
// export_ledger_excel.php
session_start();
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'sales' && $_SESSION['staff_role'] !== 'admin' ) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit;
}
require_once '../includes/db.php';
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$query = "SELECT sale_id, order_number, sale_date, total_amount, payment_method FROM sales_record ORDER BY sale_date DESC";
$result = $conn->query($query);
if (!$result) { die("Query Failed: " . $conn->error); }

$data = [];
$cumulativeTotal = 0;
while ($row = $result->fetch_assoc()){
    $data[] = $row;
    $cumulativeTotal += $row['total_amount'];
}
$conn->close();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1','Sale ID');
$sheet->setCellValue('B1','Order Number');
$sheet->setCellValue('C1','Sale Date');
$sheet->setCellValue('D1','Total Amount (Ksh)');
$sheet->setCellValue('E1','Payment Method');

$rowNum = 2;
foreach ($data as $d) {
    $sheet->setCellValue('A' . $rowNum, $d['sale_id']);
    $sheet->setCellValue('B' . $rowNum, $d['order_number']);
    $sheet->setCellValue('C' . $rowNum, date("Y-m-d", strtotime($d['sale_date'])));
    $sheet->setCellValue('D' . $rowNum, $d['total_amount']);
    $sheet->setCellValue('E' . $rowNum, $d['payment_method']);
    $rowNum++;
}
$sheet->setCellValue('C' . $rowNum, 'Cumulative Total:');
$sheet->setCellValue('D' . $rowNum, $cumulativeTotal);

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="sales_statement.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
