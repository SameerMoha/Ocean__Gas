<?php
// export_ledger_pdf.php
session_start();
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'sales' && $_SESSION['staff_role'] !== 'admin' ) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit;
}
require_once '../includes/db.php';
require_once 'fpdf/fpdf.php';

$query = "SELECT sale_id, order_number, sale_date, total_amount, payment_method 
          FROM sales_record 
          ORDER BY sale_date DESC";
$result = $conn->query($query);
if (!$result) { die("Query Failed: " . $conn->error); }

$data = [];
$cumulativeTotal = 0;
while ($row = $result->fetch_assoc()){
    $data[] = $row;
    $cumulativeTotal += $row['total_amount'];
}
$conn->close();

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Sales Statement (Bank Ledger)',0,1,'C');
$pdf->Ln(5);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(20,10,'Sale ID',1);
$pdf->Cell(40,10,'Order No',1);
$pdf->Cell(40,10,'Sale Date',1);
$pdf->Cell(40,10,'Amount (Ksh)',1);
$pdf->Cell(40,10,'Payment Method',1);
$pdf->Ln();
$pdf->SetFont('Arial','',12);
foreach ($data as $d) {
    $pdf->Cell(20,10, $d['sale_id'],1);
    $pdf->Cell(40,10, $d['order_number'],1);
    $pdf->Cell(40,10, date("Y-m-d", strtotime($d['sale_date'])),1);
    $pdf->Cell(40,10, 'Ksh ' . number_format($d['total_amount'],2),1);
    $pdf->Cell(40,10, $d['payment_method'],1);
    $pdf->Ln();
}
$pdf->SetFont('Arial','B',12);
$pdf->Cell(100,10,'Cumulative Total:',1);
$pdf->Cell(80,10, 'Ksh ' . number_format($cumulativeTotal,2),1);
$pdf->Output('D', 'sales_statement.pdf');
exit;
?>
