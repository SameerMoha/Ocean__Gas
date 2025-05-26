<?php
// export_sales_trend_excel.php
session_start();
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'sales' && $_SESSION['staff_role'] !== 'admin' ) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit;
}
require_once '../includes/db.php';
require __DIR__ . '/../vendor/autoload.php';


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Query: Get sales trend for the last 7 days
$query = "SELECT DATE(sale_date) as sale_date, IFNULL(SUM(total_amount),0) as dayTotal 
          FROM sales_record 
          WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
          GROUP BY DATE(sale_date) 
          ORDER BY sale_date ASC";
$result = $conn->query($query);
if (!$result) { die("Query Failed: " . $conn->error); }

$data = [];
while($row = $result->fetch_assoc()){
    $data[] = $row;
}
$conn->close();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Date');
$sheet->setCellValue('B1', 'Total Sales (Ksh)');

$rowNum = 2;
foreach ($data as $d) {
    $sheet->setCellValue('A' . $rowNum, date("D, M d", strtotime($d['sale_date'])));
    $sheet->setCellValue('B' . $rowNum, $d['dayTotal']);
    $rowNum++;
}

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="sales_trend.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
