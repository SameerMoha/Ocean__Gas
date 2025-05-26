<?php
require __DIR__.'/fpdf/fpdf.php';
session_start();
if (!isset($_SESSION['staff_username'])) { http_response_code(403); exit; }

$conn = new mysqli('localhost','root','', 'oceangas');
if($conn->connect_error) die('DB error');

// filters
$sale_day = $_GET['sale_day']??date('Y-m-d');
$safe_day = $conn->real_escape_string($sale_day);
$filter_product = $_GET['product']??'';
$safe_product = $conn->real_escape_string($filter_product);
$where_product = $safe_product?"AND product_name='$safe_product'":""; 

// fetch revenue
$rev_res = $conn->query("
  SELECT sale_date,order_number,customer_name,product_name,
         quantity,payment_method,total_amount
  FROM sales_record
  WHERE DATE(sale_date)='$safe_day' $where_product
  ORDER BY sale_date
") or die("Revenue Query Error: " . $conn->error);

// fetch procurement
$proc_res = $conn->query("
  SELECT 
    ph.purchase_date,
    ph.product,
    ph.quantity,
    (pr.buying_price * ph.quantity) AS total_cost,
    s.name AS supplier,
    u.username AS purchased_by
  FROM purchase_history ph
  JOIN suppliers s ON ph.supplier_id = s.id
  JOIN users u     ON ph.purchased_by = u.id
  JOIN products pd ON pd.product_name = ph.product
  JOIN price pr    ON pr.product_id = pd.product_id AND pr.supplier_id = s.id
  WHERE DATE(ph.purchase_date) = '$safe_day'
  ORDER BY ph.purchase_date
") or die("Procurement Query Error: " . $conn->error);


// build PDF
$pdf=new FPDF(); $pdf->AddPage(); $pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,"OceanGas Report: $sale_day",0,1,'C');

// Revenue table
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,"Revenue",0,1);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(25,6,'Time',1);
$pdf->Cell(30,6,'Order#',1);
$pdf->Cell(40,6,'Customer',1);
$pdf->Cell(30,6,'Product',1);
$pdf->Cell(15,6,'Qty',1,0,'R');
$pdf->Cell(30,6,'Method',1);
$pdf->Cell(25,6,'Amount',1,1,'R');
$pdf->SetFont('Arial','',10);
while($r=$rev_res->fetch_assoc()){
    $pdf->Cell(25,6,date('H:i',strtotime($r['sale_date'])),1);
    $pdf->Cell(30,6,$r['order_number'],1);
    $pdf->Cell(40,6,$r['customer_name'],1);
    $pdf->Cell(30,6,$r['product_name'],1);
    $pdf->Cell(15,6,$r['quantity'],1,0,'R');
    $pdf->Cell(30,6,$r['payment_method'],1);
    $pdf->Cell(25,6,number_format($r['total_amount'],2),1,1,'R');
}

// Procurement table
$pdf->Ln(4);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,"Procurement",0,1);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(25,6,'Time',1);
$pdf->Cell(30,6,'Product',1);
$pdf->Cell(15,6,'Qty',1,0,'R');
$pdf->Cell(30,6,'Supplier',1);
$pdf->Cell(30,6,'By',1);
$pdf->Cell(30,6,'Cost',1,1,'R');
$pdf->SetFont('Arial','',10);
while($p=$proc_res->fetch_assoc()){
    $pdf->Cell(25,6,date('H:i',strtotime($p['purchase_date'])),1);
    $pdf->Cell(30,6,$p['product'],1);
    $pdf->Cell(15,6,$p['quantity'],1,0,'R');
    $pdf->Cell(30,6,$p['supplier'],1);
    $pdf->Cell(30,6,$p['purchased_by'],1);
    $pdf->Cell(30,6,number_format($p['total_cost'],2),1,1,'R');
}

$pdf->Output('I',"OceanGas_Report_$sale_day.pdf");
