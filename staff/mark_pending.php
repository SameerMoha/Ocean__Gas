<?php
// File: staff/mark_pending.php
session_start();
require_once('../includes/db.php');

$updateQuery = "UPDATE orders SET order_status = 'pending', is_new = 0 WHERE is_new = 1 OR order_status = 'new'";
if ($conn->query($updateQuery)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
$conn->close();
?>