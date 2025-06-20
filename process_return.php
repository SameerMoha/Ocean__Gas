<?php
// CORS headers (adjust origin as needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Cookie');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();
header('Content-Type: application/json');

// Decode JSON input
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

// Debug logging
error_log("🔍 [DEBUG] Incoming raw JSON: {$rawInput}");
error_log('🔍 [DEBUG] Decoded payload: ' . print_r($data, true));
error_log('🔍 [DEBUG] session_id=' . session_id() . ', session user_id=' . var_export($_SESSION['user_id'] ?? null, true));

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check login
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Validate payload structure
if (!isset($data['products']) || !is_array($data['products']) || empty($data['reason'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request payload']);
    exit;
}

$cust_id = (int) $_SESSION['user_id'];
$order_id = null;
$orderNumber = null;

// Database connection (single connection)
$conn = new mysqli('localhost', 'root', '', 'oceangas');
if ($conn->connect_error) {
    http_response_code(500);
    error_log('❌ [ERROR] DB connection failed: ' . $conn->connect_error);
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

// Determine order_id and capture order_number
if (!empty(trim($data['order_number']))) {
    $ordNum = trim($data['order_number']);
    error_log("🔍 [DEBUG] Looking up order_number='{$ordNum}' for cust_id={$cust_id}");
    $stmt = $conn->prepare("SELECT order_id, order_number FROM orders WHERE order_number = ? AND cust_id = ?");
    $stmt->bind_param('si', $ordNum, $cust_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    if ($res->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    $row = $res->fetch_assoc();
    $order_id = (int) $row['order_id'];
    $orderNumber = $row['order_number'];
} else {
    // Fallback via first order_item_id
    $firstOi = (int) $data['products'][0]['order_item_id'];
    error_log("🔍 [DEBUG] Looking up order_id for order_item_id={$firstOi}");
    $stmt = $conn->prepare(
        "SELECT oi.order_id, o.order_number
         FROM order_items oi
         JOIN orders o ON oi.order_id = o.order_id
         WHERE oi.order_item_id = ? AND o.cust_id = ?"
    );
    $stmt->bind_param('ii', $firstOi, $cust_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    if ($res->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found for item']);
        exit;
    }
    $row = $res->fetch_assoc();
    $order_id = (int) $row['order_id'];
    $orderNumber = $row['order_number'];
}

error_log("🔍 [DEBUG] Determined order_id={$order_id}, order_number={$orderNumber}");

// Start transaction
$conn->begin_transaction();
try {
    foreach ($data['products'] as $prod) {
        $prodOi = (int) $prod['order_item_id'];
        $req_qty = (int) $prod['quantity'];
        // Validate order_item exists & quantity
        $oi_stmt = $conn->prepare("SELECT quantity FROM order_items WHERE order_item_id = ? AND order_id = ?");
        $oi_stmt->bind_param('ii', $prodOi, $order_id);
        $oi_stmt->execute();
        $oi_res = $oi_stmt->get_result();
        if ($oi_res->num_rows === 0) throw new Exception('Invalid product in order: ' . $prodOi);
        $max_qty = $oi_res->fetch_assoc()['quantity'];
        if ($req_qty < 1 || $req_qty > $max_qty) throw new Exception('Invalid return quantity for item ' . $prodOi);
        $oi_stmt->close();
        // Check for existing return request
        $chk = $conn->prepare("SELECT 1 FROM return_requests WHERE order_item_id = ?");
        $chk->bind_param('i', $prodOi);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) throw new Exception('Return already requested for item ' . $prodOi);
        $chk->close();
        // Insert return request
        $reason = trim($prod['reason'] ?? $data['reason']);
        $ins = $conn->prepare(
            "INSERT INTO return_requests
             (order_id, order_item_id, cust_id, return_reason, return_quantity, return_status, request_date)
             VALUES (?, ?, ?, ?, ?, 'pending', NOW())"
        );
        $ins->bind_param('iiisi', $order_id, $prodOi, $cust_id, $reason, $req_qty);
        if (!$ins->execute()) throw new Exception('Failed to submit return request for item ' . $prodOi);
        $ins->close();
    }
    $conn->commit();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Return request submitted successfully',
        'order_number' => $orderNumber
    ]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    error_log('❌ [ERROR] Transaction failed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'order_number' => $orderNumber]);
}

$conn->close();
?>
