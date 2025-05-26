<?php
// export_recent_sales_excel.php
session_start();
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'sales' && $_SESSION['staff_role'] !== 'admin' ) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit;
}
require_once '../includes/db.php';
 require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$query = "SELECT sale_date, total_amount FROM sales_record ORDER BY sale_date DESC LIMIT 10";
$result = $conn->query($query);
if (!$result) { die("Query Failed: " . $conn->error); }
$data = [];
while ($row = $result->fetch_assoc()){
    $data[] = $row;
}
$conn->close();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Sale Date');
$sheet->setCellValue('B1', 'Total Sales (Ksh)');

$rowNum = 2;
foreach ($data as $d) {
    $sheet->setCellValue('A' . $rowNum, date("Y-m-d", strtotime($d['sale_date'])));
    $sheet->setCellValue('B' . $rowNum, $d['total_amount']);
    $rowNum++;
}

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="recent_sales.xlsx"');
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
