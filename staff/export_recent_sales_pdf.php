<?php
// export_recent_sales_pdf.php
session_start();
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'sales' && $_SESSION['staff_role'] !== 'admin' ) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit;
}
require_once '../includes/db.php';
require_once 'fpdf/fpdf.php';

// Query: Get the 10 most recent sales
$query = "SELECT sale_date, total_amount FROM sales_record ORDER BY sale_date DESC LIMIT 10";
$result = $conn->query($query);
if (!$result) { die("Query Failed: " . $conn->error); }

$data = [];
while ($row = $result->fetch_assoc()){
    $data[] = $row;
}
$conn->close();

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Recent Sales Details',0,1,'C');
$pdf->Ln(5);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(60,10, 'Sale Date', 1);
$pdf->Cell(60,10, 'Total Sales (Ksh)', 1);
$pdf->Ln();
$pdf->SetFont('Arial','',12);
foreach ($data as $d) {
    $pdf->Cell(60,10, date("Y-m-d", strtotime($d['sale_date'])), 1);
    $pdf->Cell(60,10, 'Ksh ' . number_format($d['total_amount'],2), 1);
    $pdf->Ln();
}
$pdf->Output('D', 'recent_sales.pdf');
exit;
?>
