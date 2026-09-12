<?php
require_once __DIR__ . '/config.php';
requireLogin();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$items = ($id !== null && $id !== false && isset($_SESSION['cart'][$id])) ? [$_SESSION['cart'][$id]] : $_SESSION['cart'];
if (!$items) { header('Location: cart.php'); exit; }
$total_price = array_sum(array_map(fn($i)=>(float)($i['price'] ?? 0), $items));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    verifyCsrf();
    $payment = $_POST['pay_method'] ?? 'Pay at Hotel';
    $allowed = ['Pay at Hotel', 'QR Payment'];
    if (!in_array($payment, $allowed, true)) { http_response_code(400); exit('Invalid payment method.'); }
    $status = 'Unpaid'; // QR Payment is only a placeholder until a real payment gateway callback is integrated.
    $conn->begin_transaction();
    try {
      foreach ($items as $item) {
        $roomid=(string)($item['roomid']??''); $amount=max(1,(int)($item['amount']??1));
        $checkin=(string)($item['check_in']??''); $checkout=(string)($item['check_out']??'');
        if (!$roomid || !$checkin || !$checkout || $checkout <= $checkin) throw new RuntimeException('Invalid booking dates.');
        $stmt=$conn->prepare('SELECT countroom FROM room WHERE roomid=? FOR UPDATE'); $stmt->bind_param('s',$roomid); $stmt->execute(); $room=$stmt->get_result()->fetch_assoc();
        if (!$room || (int)$room['countroom'] < $amount) throw new RuntimeException('Room is no longer available.');
        $user=$_SESSION['Username']; $dup=$conn->prepare('SELECT 1 FROM orders WHERE username=? AND roomid=? AND checkin=? AND checkout=? LIMIT 1'); $dup->bind_param('ssss',$user,$roomid,$checkin,$checkout); $dup->execute();
        if ($dup->get_result()->num_rows) throw new RuntimeException('Duplicate booking.');
        $bookingid='BK'.bin2hex(random_bytes(8)); $date=date('Y-m-d'); $phone=(string)($item['phone']??'');
        // Re-read the room price from the database; never trust the client/session cart price as authoritative.
        $priceStmt=$conn->prepare('SELECT price FROM room WHERE roomid=? LIMIT 1');
        $priceStmt->bind_param('s',$roomid); $priceStmt->execute(); $priceRow=$priceStmt->get_result()->fetch_assoc();
        if (!$priceRow) throw new RuntimeException('Room not found.');
        $price=(float)$priceRow['price'] * $amount * max(1, (new DateTime($checkin))->diff(new DateTime($checkout))->days);
        $ins=$conn->prepare('INSERT INTO orders (bookingid,username,bookingdate,checkin,checkout,countroom,roomid,phone,totalprice,payment,paymentstatus) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        $ins->bind_param('sssssissdss',$bookingid,$user,$date,$checkin,$checkout,$amount,$roomid,$phone,$price,$payment,$status); $ins->execute();
        $upd=$conn->prepare('UPDATE room SET countroom=countroom-? WHERE roomid=?'); $upd->bind_param('is',$amount,$roomid); $upd->execute();
      }
      $conn->commit(); if ($id !== null && $id !== false) unset($_SESSION['cart'][$id]); else $_SESSION['cart']=[]; $_SESSION['cart']=array_values($_SESSION['cart']); header('Location: profile.php?booking=success'); exit;
    } catch (Throwable $e) { if (method_exists($conn, 'rollback')) { $conn->rollback(); } http_response_code(400); exit('Booking failed. Please review the booking details and try again.'); }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Payment Selection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .payment-card { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .method-option { border: 2px solid #eee; border-radius: 15px; cursor: pointer; transition: 0.3s; padding: 20px; }
        .method-option:hover { border-color: #000; background-color: #fdfdfd; }
        .form-check-input:checked + .method-option { border-color: #000; background-color: #f8f9fa; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .qr-mock { width: 180px; height: 180px; background: #fff; border: 1px solid #eee; padding: 10px; margin: 15px auto; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="card payment-card mx-auto p-4 p-md-5" style="max-width: 700px;">
        <h3 class="fw-bold mb-4 text-center">Checkout</h3>
        
        <div class="bg-light p-3 rounded-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted">Total Amount</span>
                <h3 class="fw-bold mb-0 text-dark">$<?php echo number_format($total_price, 2); ?></h3>
            </div>
        </div>

        <form method="POST">
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
            <h6 class="fw-bold mb-3">Select Payment Method</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <input type="radio" class="btn-check" name="pay_method" id="method1" value="Pay at Hotel" checked autocomplete="off">
                    <label class="method-option w-100 h-100 text-center" for="method1">
                        <i class="bi bi-cash-stack fs-1 mb-2"></i>
                        <h6 class="fw-bold d-block">Pay at Hotel</h6>
                        <span class="text-muted small">No upfront payment needed</span>
                    </label>
                </div>
                <div class="col-md-6">
                    <input type="radio" class="btn-check" name="pay_method" id="method2" value="QR Payment" autocomplete="off">
                    <label class="method-option w-100 h-100 text-center" for="method2">
                        <i class="bi bi-qr-code-scan fs-1 mb-2"></i>
                        <h6 class="fw-bold d-block">QR Payment</h6>
                        <span class="text-muted small">Scan with banking app</span>
                    </label>
                </div>
            </div>

            <div id="qrSection" class="text-center mt-4 d-none">
                <div class="qr-mock shadow-sm">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=HotelPayment" alt="QR Code">
                </div>
                <p class="small text-muted">Please scan the QR code to complete the transaction.</p>
            </div>

            <button type="submit" name="confirm_payment" class="btn btn-dark w-100 py-3 rounded-3 fw-bold mt-5 shadow">
                Confirm & Pay Now
            </button>
            <a href="cart.php" class="btn btn-link w-100 text-muted text-decoration-none