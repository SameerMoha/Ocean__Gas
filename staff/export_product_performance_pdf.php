<?php
// export_product_performance_pdf.php
session_start();
if (!isset($_SESSION['staff_username']) || !isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'sales' && $_SESSION['staff_role'] !== 'admin' ) {
    header("Location: /OceanGas/staff/staff_login.php");
    exit;
}
require_once '../includes/db.php';
require_once 'fpdf/fpdf.php';

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

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Product Performance Report',0,1,'C');
$pdf->Ln(5);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(60,10,'Product',1);
$pdf->Cell(40,10,'Total Units',1);
$pdf->Cell(60,10,'Total Revenue (Ksh)',1);
$pdf->Ln();
$pdf->SetFont('Arial','',12);
foreach ($data as $d) {
    $pdf->Cell(60,10, $d['product_name'], 1);
    $pdf->Cell(40,10, number_format($d['total_units']), 1);
    $pdf->Cell(60,10, 'Ksh ' . number_format($d['total_revenue'], 2), 1);
    $pdf->Ln();
}
$pdf->Output('D', 'product_performance.pdf');
exit;
?>
