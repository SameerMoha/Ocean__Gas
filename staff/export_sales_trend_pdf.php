<?php
// export_sales_trend_pdf.php
session_start();
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'sales' && $_SESSION['staff_role'] !== 'admin' ) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit;
}
require_once '../includes/db.php';

// Include FPDF library (adjust path as needed)
require_once 'fpdf/fpdf.php';

// Query: Get sales trend for the last 7 days
$query = "SELECT DATE(sale_date) as sale_date, IFNULL(SUM(total_amount),0) as dayTotal 
          FROM sales_record 
          WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
          GROUP BY DATE(sale_date) 
          ORDER BY sale_date ASC";
$result = $conn->query($query);
if (!$result) { die("Query Failed: " . $conn->error); }

// Prepare data array
$data = [];
while($row = $result->fetch_assoc()){
    $data[] = $row;
}
$conn->close();

// Create PDF using FPDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Sales Trend (Last 7 Days)',0,1,'C');
$pdf->Ln(5);
$pdf->SetFont('Arial','',12);
$pdf->Cell(50,10, 'Date', 1);
$pdf->Cell(50,10, 'Total Sales (Ksh)', 1);
$pdf->Ln();
foreach ($data as $d) {
    $pdf->Cell(50,10, date("D, M d", strtotime($d['sale_date'])), 1);
    $pdf->Cell(50,10, 'Ksh ' . number_format($d['dayTotal'],2), 1);
    $pdf->Ln();
}
$pdf->Output('D', 'sales_trend.pdf');
exit;
?>
