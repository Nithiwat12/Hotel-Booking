<?php
require_once __DIR__ . '/config.php';
requireAdmin();
$bookingid = trim((string)($_GET['id'] ?? ''));
if ($bookingid === '') { http_response_code(400); exit('ไม่พบ booking'); }
$stmt = $conn->prepare('SELECT bookingid FROM orders WHERE bookingid = ? LIMIT 1');
$stmt->bind_param('s', $bookingid);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) { http_response_code(404); exit('ไม่พบ booking'); }
$update = $conn->prepare("UPDATE orders SET paymentstatus = 'past' WHERE bookingid = ?");
$update->bind_param('s', $bookingid);
$update->execute();
header('Location: adminplay.php');
exit;
?>

