<?php
session_start();
if (!isset($_SESSION['staff_username'])) { http_response_code(403); exit; }

$conn = new mysqli('localhost','root','', 'oceangas');
if($conn->connect_error) die('DB error');

$sale_day = $_GET['sale_day']??date('Y-m-d');
$safe_day = $conn->real_escape_string($sale_day);
$filter_product = $_GET['product']??'';
$safe_product = $conn->real_escape_string($filter_product);
$where_product = $safe_product?"AND product_name='$safe_product'":""; 

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="OceanGas_Report_'.$safe_day.'.csv"');
$out = fopen('php://output','w');

// Revenue
fputcsv($out, ["Revenue on $sale_day"]);
fputcsv($out, ['Time','Order#','Customer','Product','Qty','Payment','Amount']);
$res = $conn->query("
  SELECT sale_date,order_number,customer_name,product_name,quantity,payment_method,total_amount
  FROM sales_record
  WHERE DATE(sale_date)='$safe_day' $where_product
");
while($r=$res->fetch_assoc()){
    fputcsv($out,[
      date('H:i',strtotime($r['sale_date'])),
      $r['order_number'],
      $r['customer_name'],
      $r['product_name'],
      $r['quantity'],
      $r['payment_method'],
      number_format($r['total_amount'],2)
    ]);
}

fputcsv($out, []);  // blank line

// Procurement
fputcsv($out, ["Procurement on $sale_day"]);
fputcsv($out, ['Time','Product','Qty','Supplier','By','Cost']);
$pres = $conn->query("
  SELECT ph.purchase_date,ph.product,ph.quantity,
         s.name AS supplier,u.username AS purchased_by,
         (CASE WHEN ph.product='6kg' THEN s.cost_6kg ELSE s.cost_12kg END*ph.quantity) AS total_cost
  FROM purchase_history ph
  JOIN suppliers s ON ph.supplier_id=s.id
  JOIN users u ON ph.purchased_by=u.id
  WHERE DATE(ph.purchase_date)='$safe_day'
");
while($p=$pres->fetch_assoc()){
    fputcsv($out,[
      date('H:i',strtotime($p['purchase_date'])),
      $p['product'],
      $p['quantity'],
      $p['supplier'],
      $p['purchased_by'],
      number_format($p['total_cost'],2)
    ]);
}

fclose($out);
exit;
