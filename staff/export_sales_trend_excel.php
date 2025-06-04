<?php
// export_sales_trend_excel.php
session_start();
if (
    !isset($_SESSION['staff_username']) ||
    !isset($_SESSION['staff_role']) ||
    ($_SESSION['staff_role'] !== 'sales' && $_SESSION['staff_role'] !== 'admin')
) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit;
}

require_once '../includes/db.php';
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// 1. Figure out the month to export
$selectedMonth = $_GET['month'] ?? date('Y-m');      // e.g. “2025-05”
$startDate     = $selectedMonth . '-01';             // “2025-05-01”
$endDate       = date('Y-m-t', strtotime($startDate)); // “2025-05-31”

// 2. Pull daily totals for the entire month
$query = "
    SELECT
      DATE(sale_date) AS sale_date,
      IFNULL(SUM(total_amount),0) AS dayTotal
    FROM sales_record
    WHERE DATE(sale_date) BETWEEN '$startDate' AND '$endDate'
    GROUP BY DATE(sale_date)
    ORDER BY DATE(sale_date) ASC
";
$result = $conn->query($query);
if (!$result) {
    die("Query Failed: " . $conn->error);
}

// Build a map so days with zero sales still show up
$salesMap = [];
while ($row = $result->fetch_assoc()) {
    $salesMap[$row['sale_date']] = $row['dayTotal'];
}
$conn->close();

// 3. Create the spreadsheet
$spreadsheet = new Spreadsheet();
$sheet       = $spreadsheet->getActiveSheet();

// Header row
$monthLabel = date('F Y', strtotime($startDate));         // e.g. “May 2025”
$sheet->setCellValue('A1', "Day of {$monthLabel}");
$sheet->setCellValue('B1', 'Total Sales (Ksh)');

// 4. Fill in Day 1…Day N
$daysInMonth = (int)date('t', strtotime($startDate));
for ($day = 1; $day <= $daysInMonth; $day++) {
    $dateStr = sprintf('%s-%02d', $selectedMonth, $day);
    $total   = $salesMap[$dateStr] ?? 0.00;

    $rowNum = $day + 1;
    $sheet->setCellValue('A' . $rowNum, "Day {$day}");
    $sheet->setCellValue('B' . $rowNum, $total);
}

// 5. Send it to the browser
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"sales_trend_{$selectedMonth}.xlsx\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
