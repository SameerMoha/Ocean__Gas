<?php
session_start();

// Check if the staff user is logged in
if (!isset($_SESSION['staff_username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Database connection
$host = 'localhost';
$db   = 'oceangas';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    // Fetch all return requests with customer and order details
    $sql = "
        SELECT 
            r.return_id,
            r.return_reason,
            r.return_quantity,
            r.return_status,
            r.request_date,
            o.order_number,
            o.invoice_summary,
            c.F_name,
            c.L_name,
            c.Phone_number
        FROM return_requests r
        JOIN orders o ON r.order_id = o.order_id
        JOIN customers c ON r.cust_id = c.cust_id
        ORDER BY 
            CASE 
                WHEN r.return_status = 'pending' THEN 1
                WHEN r.return_status = 'approved' THEN 2
                ELSE 3
            END,
            r.request_date DESC
    ";

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $returns = [];
    while ($row = $result->fetch_assoc()) {
        $returns[] = $row;
    }

    echo json_encode([
        'success' => true,
        'returns' => $returns
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching return requests: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 