<?php
// export_customer_performance_pdf.php
session_start();
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'sales' && $_SESSION['staff_role'] !== 'admin' ) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit;
}
require_once '../includes/db.php';
require_once 'fpdf/fpdf.php';

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

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Customer Loyalty Report',0,1,'C');
$pdf->Ln(5);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(70,10,'Customer Name',1);
$pdf->Cell(40,10,'Transactions',1);
$pdf->Cell(60,10,'Total Revenue (Ksh)',1);
$pdf->Ln();
$pdf->SetFont('Arial','',12);
foreach ($data as $d) {
    $pdf->Cell(70,10, $d['customer_name'], 1);
    $pdf->Cell(40,10, number_format($d['num_transactions']), 1);
    $pdf->Cell(60,10, 'Ksh ' . number_format($d['total_revenue'],2), 1);
    $pdf->Ln();
}
$pdf->Output('D', 'customer_loyalty.pdf');
exit;
?>
