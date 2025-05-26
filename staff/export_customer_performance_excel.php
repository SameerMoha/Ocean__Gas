<?php
// export_customer_performance_excel.php
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
    SELECT customer_name, COUNT(*) AS num_transactions, SUM(total_amount) AS total_revenue
    FROM sales_record
    GROUP BY customer_name
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
$sheet->setCellValue('A1', 'Customer Name');
$sheet->setCellValue('B1', 'Transactions');
$sheet->setCellValue('C1', 'Total Revenue (Ksh)');

$rowNum = 2;
foreach ($data as $d) {
    $sheet->setCellValue('A' . $rowNum, $d['customer_name']);
    $sheet->setCellValue('B' . $rowNum, $d['num_transactions']);
    $sheet->setCellValue('C' . $rowNum, $d['total_revenue']);
    $rowNum++;
}

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="customer_loyalty.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
