<?php
require_once __DIR__ . '/config.php';

if(!isset($_SESSION['cart'])){
    $_SESSION['cart'] = [];
}



if (isset($_POST['add_to_cart'])) {
    requireLogin();
    try {
        $checkin = new DateTime((string)($_POST['check_in'] ?? ''));
        $checkout = new DateTime((string)($_POST['check_out'] ?? ''));
    } catch (Throwable $e) {
        http_response_code(400); exit('Invalid booking dates.');
    }
    $today = new DateTime('today');
    if ($checkin < $today || $checkout <= $checkin) {
        http_response_code(400); exit('Check-out must be after check-in and dates cannot be in the past.');
    }
    $roomid = trim((string)($_POST['roomid'] ?? ''));
    $amount = max(1, (int)($_POST['amount'] ?? 0));
    if ($roomid === '' || $amount < 1) { http_response_code(400); exit('Invalid room or quantity.'); }

    $roomStmt = $conn->prepare('SELECT roomid,name,price,countroom,countuser,roomstyle,picture FROM room WHERE roomid = ? LIMIT 1');
    $roomStmt->bind_param('s', $roomid);
    $roomStmt->execute();
    $room = $roomStmt->get_result()->fetch_assoc();
    if (!$room) { http_response_code(404); exit('Room not found.'); }
    if ((int)$room['countroom'] < $amount) { http_response_code(400); exit('Not enough rooms available.'); }

    $nights = $checkin->diff($checkout)->days;
    $totalPrice = (float)$room['price'] * $nights * $amount;
    $_SESSION['cart'][] = [
        'roomid' => $room['roomid'],
        'name' => $room['name'],
        'price' => $totalPrice,
        'check_in' => $checkin->format('Y-m-d'),
        'check_out' => $checkout->format('Y-m-d'),
        'phone' => (string)($_POST['phone'] ?? ''),
        'countuser' => (int)$room['countuser'],
        'roomstyle' => $room['roomstyle'],
        'amount' => $amount,
        'picture' => $room['picture'] ?: 'default.jpg'
    ];
    header('Location: cart.php');
    exit;
}

if (isset($_GET['remove'])) {
    $remove = filter_input(INPUT_GET, 'remove', FILTER_VALIDATE_INT);
    if ($remove !== false && $remove !== null && isset($_SESSION['cart'][$remove])) {
        unset($_SESSION['cart'][$remove]);
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']); 
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="nav.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f9; }
        .cart-item-card { border-radius: 15px; border: none; transition: 0.3s; }
        .badge-unpaid { background-color: #f8f9fa; color: #333; border: 1px solid #ddd; font-weight: 500; }
        .summary-card { border-radius: 20px; border: none; position: sticky; top: 20px; }
        .hotel-thumb { width: 140px; height: 110px; object-fit: cover; border-radius: 12px; }
        .btn-pay { background-color: #000; color: #fff; border-radius: 10px; padding: 12px; }
        .btn-pay:hover { background-color: #222; color: #fff; }
    </style>
</head>
<body>
 <div class="navbar">

    <div class="nav-left">
        <h2>Soda Hotel</h2>
    </div>

    <div class="nav-right">
        <a href="main.php">Home</a>
        <span>|</span>
        <a href="#">Hotel</a>

        <div class="auth-buttons">
        <?php if(isset($_SESSION['Username'])): ?>

           <a href="profile.php"> <span>👤 <?= $_SESSION['Username']; ?></span></a>
            <a href="logout.php">
                <button class="register-btn">Logout</button>
            </a>

        <?php else: ?>

            <a href="loginhotel.php">
                <button class="login-btn">Login</button>
            </a>

            <a href="register.php">
                <button class="register-btn">Register</button>
            </a>

        <?php endif; ?>
        </div>

    </div>
</div>



<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <h4 class="fw-bold mb-4"><i class="bi bi-cart3 me-2"></i>Shopping Cart (<?php echo count($_SESSION['cart']); ?>)</h4>
            <p class="text-muted small fw-bold text-uppercase mb-3">Pending Payment</p>

            <?php if(empty($_SESSION['cart'])): ?>
                <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                    <i class="bi bi-cart-x fs-1 text-muted"></i>
                    <p class="mt-3">Your cart is empty</p>
                    <a href="main.php" class="btn btn-dark px-4">Browse Hotels</a>
                </div>

            <?php else: ?>
                <?php foreach($_SESSION['cart'] as $key => $item): 
                    $checkin  = new DateTime($item['check_in']);
                    $checkout = new DateTime($item['check_out']);
                    $interval = $checkin->diff($checkout);
                    $nights   = max(1, $interval->days);

                    $totalPrice = $item['price'];
                    ?>
                <div class="card cart-item-card shadow-sm p-3 mb-3">
                    <div class="d-flex">
                       <img src="<?php echo $item['picture']; ?>" class="hotel-thumb">
                        <div class="ms-3 flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="fw-bold mb-0"><?php echo $item['name']; ?></h5>
                                    <p class="text-muted small mb-2"><?php echo $item['roomstyle']; ?></p>
                                </div>
                                <span class="badge badge-unpaid">Unpaid</span>
                            </div>
                            <div class="d-flex gap-3 text-muted small mb-3">
                        

                                <span><i class="bi bi-calendar-event me-1"></i> <?php echo $item['check_in']; ?></span>
                                <span><i class="bi bi-people me-1"></i> <?php echo $item['countuser']; ?></span>
                                <span><i class="bi bi-moon me-1"></i> <?php echo $nights; ?></span>
                            </div>
                            <div class="d-flex gap-3 text-muted small mb-3">
                                <span><i class="bi bi-door-open"></i>  <?php echo $item['amount']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="payment.php?id=<?php echo $key; ?>" class="btn btn-dark btn-sm rounded-3 px-3">Pay Now</a>
                                    <a href="?remove=<?php echo $key; ?>" class="btn btn-link text-danger text-decoration-none btn-sm ms-2"><i class="bi bi-trash"></i> Remove</a>
                                </div>
                                <h5 class="fw-bold mb-0 text-dark">฿<?php echo number_format($item['price'], 2); ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card summary-card shadow-sm p-4">
                <h5 class="fw-bold mb-4">Order Summary</h5>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total Items</span>
                    <span class="fw-bold"><?php echo count($_SESSION['cart']); ?></span>
                </div>
                <?php $total = array_sum(array_column($_SESSION['cart'], 'price')); ?>
                <div class="d-flex justify-content-between mb-4">
                    <span class="text-muted">Unpaid (<?php echo count($_SESSION['cart']); ?>)</span>
                    <span class="text-danger fw-bold">฿<?php echo number_format($total, 2); ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Total Price</h5>
                    <h4 class="fw-bold mb-0">฿<?php echo number_format($total, 2); ?></h4>
                </div>
                <a href="payment.php" class="btn btn-pay w-100 fw-bold mb-3">Pay All Unpaid ($<?php echo number_format($total, 2); ?>)</a>
                <p class="text-center small text-muted">Or pay items individually</p>
                <a href="main.php" class="btn btn-outline-secondary w-100 py-2 border-0">Continue Shopping</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>