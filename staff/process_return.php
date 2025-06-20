<?php
// ----------------------------------------------------------------------------
// process_return.php
// AJAX handler: approve/decline a return request using free‑form return_reason.
// ----------------------------------------------------------------------------

// 1) Error handling: disable display, log to file
ini_set('display_errors',        0);
ini_set('display_startup_errors',0);
ini_set('log_errors',            1);
ini_set('error_log',             __DIR__ . '/process_return_errors.log');

// 2) Start output buffering & session, set JSON header
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');

// 3) Helper to send JSON and exit
function sendJson($ok, $msg, $debug = null) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success'=>$ok,'message'=>$msg,'debug'=>$debug]);
    exit;
}

// 4) Authentication
if (!isset($_SESSION['staff_username'])) {
    http_response_code(401);
    sendJson(false, 'Unauthorized');
}

// 5) Parse JSON input
$input = file_get_contents('php://input');
$data  = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    sendJson(false, 'Invalid JSON: '.json_last_error_msg());
}

$return_id = isset($data['return_id']) ? (int)$data['return_id'] : 0;
$action    = $data['action'] ?? '';
$notes     = $data['notes']  ?? '';

if ($return_id <= 0 || !in_array($action, ['approve','decline'], true)) {
    http_response_code(400);
    sendJson(false, 'Missing or invalid parameters');
}

// 6) Connect to DB
$conn = new mysqli('localhost','root','','oceangas');
if ($conn->connect_error) {
    http_response_code(500);
    sendJson(false, 'DB connect failed: '.$conn->connect_error);
}

// 7) Begin transaction
$conn->begin_transaction();

try {
    // 8) Fetch the return request row
    $rq = $conn->prepare("
      SELECT order_id, return_reason, return_quantity
        FROM return_requests
       WHERE return_id = ?
       LIMIT 1
    ");
    if (!$rq) throw new Exception('Prepare fetch-return failed: '.$conn->error);
    $rq->bind_param('i', $return_id);
    $rq->execute();
    $rq->bind_result($order_id, $reasonText, $fallbackQty);
    if (!$rq->fetch()) {
        throw new Exception('Return request not found');
    }
    $rq->close();

    // 9) Parse lines and compute refund
    $totalRefund = 0.0;
    $lines = preg_split('/[\r\n]+/', trim($reasonText));
    foreach ($lines as $line) {
        if (preg_match('/-\s*(.+?):\s*Returning\s*(\d+)\s*units?/i', $line, $m)) {
            $productName = $m[1];
            $qty         = (int)$m[2];

            // lookup unit_price and product_id
            $pi = $conn->prepare("
              SELECT unit_price, product_id
                FROM order_items
               WHERE order_id    = ?
                 AND product_name= ?
               LIMIT 1
            ");
            if (!$pi) throw new Exception('Prepare lookup item failed: '.$conn->error);
            $pi->bind_param('is', $order_id, $productName);
            $pi->execute();
            $pi->bind_result($unitPrice, $productId);
            $pi->fetch();
            $pi->close();

            $unitPrice = (float)$unitPrice;
            $totalRefund += $unitPrice * $qty;

            // restock inventory immediately for this line
            if ($productId !== null) {
                $rs = $conn->prepare("
                  UPDATE products
                     SET quantity = quantity + ?
                   WHERE product_id = ?
                ");
                if (!$rs) throw new Exception('Prepare restock failed: '.$conn->error);
                $rs->bind_param('ii', $qty, $productId);
                $rs->execute();
                $rs->close();
            }
        }
    }

    // fallback if no lines parsed
    if ($totalRefund <= 0) {
        // grab one unit_price as fallback
        $fb = $conn->prepare("
          SELECT unit_price, product_id
            FROM order_items
           WHERE order_id = ?
           LIMIT 1
        ");
        if (!$fb) throw new Exception('Prepare fallback failed: '.$conn->error);
        $fb->bind_param('i', $order_id);
        $fb->execute();
        $fb->bind_result($unitPrice, $productId);
        $fb->fetch();
        $fb->close();

        $totalRefund = (float)$unitPrice * (int)$fallbackQty;

        // restock fallback quantity
        if ($productId !== null) {
            $rs = $conn->prepare("
              UPDATE products
                 SET quantity = quantity + ?
               WHERE product_id = ?
            ");
            $rs->bind_param('ii', $fallbackQty, $productId);
            $rs->execute();
            $rs->close();
        }
    }

    // 10) Update return_requests status & notes
    $newStatus = $action === 'approve' ? 'approved' : 'declined';
    $up = $conn->prepare("
      UPDATE return_requests
         SET return_status = ?, notes = ?
       WHERE return_id = ?
    ");
    if (!$up) throw new Exception('Prepare update failed: '.$conn->error);
    $up->bind_param('ssi', $newStatus, $notes, $return_id);
    $up->execute();
    $up->close();

    // 11) If approved, log a financial transaction
    if ($newStatus === 'approved') {
        $tx = $conn->prepare("
          INSERT INTO financial_transactions
            (order_id, amount, transaction_type, status, notes)
          VALUES (?, ?, 'return', 'approved', ?)
        ");
        if (!$tx) throw new Exception('Prepare tx insert failed: '.$conn->error);
        $tx->bind_param('ids', $order_id, $totalRefund, $notes);
        $tx->execute();
        $tx->close();
    }

    // 12) Commit & respond
    $conn->commit();
    sendJson(true, "Return {$newStatus} successfully", [
        'return_id'    => $return_id,
        'return_amount'=> $totalRefund
    ]);

} catch (Exception $e) {
    // Roll back on any error
    $conn->rollback();
    http_response_code(500);
    sendJson(false, 'Error: '.$e->getMessage());
}

// 13) Close connection (though sendJson has exited)
$conn->close();
