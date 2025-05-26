<?php
require '..OceanGas/vendor/autoload.php';
require '../includes/db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function exportToExcel($sheetTitle, $filename, $headers, $dataRows) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle($sheetTitle);

    // Set header
    $column = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($column . '1', $header);
        $column++;
    }

    // Fill data
    $row = 2;
    foreach ($dataRows as $dataRow) {
        $column = 'A';
        foreach ($dataRow as $cell) {
            $sheet->setCellValue($column . $row, $cell);
            $column++;
        }
        $row++;
    }

    // Output
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=\"$filename.xlsx\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

if (isset($_GET['type'])) {
    $type = $_GET['type'];
    switch ($type) {
        case 'sales_trend':
            $query = "SELECT date, total_sales FROM sales_trend WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            $result = $conn->query($query);
            $headers = ['Date', 'Total Sales'];
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = [$row['date'], $row['total_sales']];
            }
            exportToExcel("Sales Trend", "sales_trend", $headers, $data);
            break;

        case 'recent_sales':
            $query = "SELECT customer_name, product_name, quantity, total_price, sale_date FROM sales ORDER BY sale_date DESC LIMIT 50";
            $result = $conn->query($query);
            $headers = ['Customer', 'Product', 'Quantity', 'Total Price', 'Date'];
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = [$row['customer_name'], $row['product_name'], $row['quantity'], $row['total_price'], $row['sale_date']];
            }
            exportToExcel("Recent Sales", "recent_sales", $headers, $data);
            break;

        case 'bank_ledger':
            $query = "SELECT date, description, amount, balance FROM bank_ledger ORDER BY date DESC";
            $result = $conn->query($query);
            $headers = ['Date', 'Description', 'Amount', 'Balance'];
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = [$row['date'], $row['description'], $row['amount'], $row['balance']];
            }
            exportToExcel("Bank Ledger", "bank_ledger", $headers, $data);
            break;

        case 'product_performance':
            $query = "SELECT product_name, total_quantity_sold, total_revenue FROM product_performance";
            $result = $conn->query($query);
            $headers = ['Product', 'Quantity Sold', 'Total Revenue'];
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = [$row['product_name'], $row['total_quantity_sold'], $row['total_revenue']];
            }
            exportToExcel("Product Performance", "product_performance", $headers, $data);
            break;

        case 'customer_loyalty':
            $query = "SELECT customer_name, total_orders, total_spent FROM customer_loyalty";
            $result = $conn->query($query);
            $headers = ['Customer Name', 'Total Orders', 'Total Spent'];
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = [$row['customer_name'], $row['total_orders'], $row['total_spent']];
            }
            exportToExcel("Customer Loyalty", "customer_loyalty", $headers, $data);
            break;

        default:
            echo "Invalid report type.";
    }
} else {
    echo "No report type specified.";
}
?>
