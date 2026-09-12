<?php
require_once __DIR__ . '/config.php';
requireLogin();
$username = $_SESSION['Username'];

$userStmt = $conn->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
$userStmt->bind_param('s', $username);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
if (!$user) { session_destroy(); header('Location: loginhotel.php'); exit; }

$countStmt = $conn->prepare('SELECT COUNT(*) AS total FROM orders WHERE username = ?');
$countStmt->bind_param('s', $username);
$countStmt->execute();
$row = $countStmt->get_result()->fetch_assoc();

$orderStmt = $conn->prepare('SELECT * FROM orders WHERE username = ? ORDER BY bookingdate DESC, created_at DESC');
$orderStmt->bind_param('s', $username);
$orderStmt->execute();
$resultorder = $orderStmt->get_result();

$roomStmt = $conn->prepare('SELECT r.*, o.* FROM room r JOIN orders o ON o.roomid = r.roomid WHERE o.username = ? ORDER BY o.bookingdate DESC, o.created_at DESC');
$roomStmt->bind_param('s', $username);
$roomStmt->execute();
$check_room = $roomStmt->get_result();

$unpaid = 0; $upcoming = 0; $pasting = 0;
$un = []; $up = []; $past = [];
while ($order = $check_room->fetch_assoc()) {
    if ($order['paymentstatus'] === 'Unpaid') { $unpaid++; $un[] = $order; }
    elseif ($order['paymentstatus'] === 'Upcoming') { $upcoming++; $up[] = $order; }
    elseif ($order['paymentstatus'] === 'past') { $pasting++; $past[] = $order; }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>My Profile - Booking History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="nav.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .profile-card { border: none; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .avatar-circle { width: 100px; height: 100px; background: #e9ecef; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; }
        .nav-status { background: #f1f3f5; border-radius: 12px; padding: 5px; border: none; }
        .nav-status .nav-link { border: none; border-radius: 10px; color: #6c757d; font-weight: 500; transition: 0.3s; padding: 10px 20px; }
        .nav-status .nav-link.active { background: #fff; color: #000; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .booking-item { border: 1px solid #edf2f7; border-radius: 12px; padding: 20px; margin-bottom: 15px; background: #fff; transition: 0.2s; }
        .booking-item:hover { border-color: #cbd5e0; }
        .status-badge { font-size: 0.75rem; font-weight: 600; padding: 4px 12px; border-radius: 8px; }
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
        <a href="cart.php">Hotel</a>

        <div class="auth-buttons">
        <?php if(isset($_SESSION['Username'])): ?>

           <a href="#"> <span>👤 <?= $_SESSION['Username']; ?></span></a>
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

<div class="container py-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card profile-card p-4 text-center mb-4">
                <div class="avatar-circle"><i class="bi bi-person-fill fs-1 text-secondary"></i></div>
                <h4 class="fw-bold mb-1"><?php echo  $user['username']?></p></h4>
                <p class="text-muted small">Member since March 2026</p>
                <div class="text-start mt-4 border-top pt-3">
                    <p class="mb-2 small"><i class="bi bi-envelope me-2"></i> <?php echo  $user['email']?></p>
                    <p class="mb-2 small"><i class="bi bi-telephone me-2"></i> <?php echo  $user['phone']?></p></p>
                    <p class="mb-0 small"><i class="bi bi-journal-check me-2"></i> <?php echo $row['total']; ?> Total Bookings</p>
                </div>
                <div class="row mt-4 pt-3 border-top">
                    <div class="col-4 border-end">
                        <h5 class="fw-bold text-danger mb-0"><?php echo $unpaid ; ?></h5>
                        <p class="text-muted small mb-0">Unpaid</p>
                    </div>
                    <div class="col-4 border-end">
                        <?php 
                        
                        ?>
                        <h5 class="fw-bold text-primary mb-0"><?php echo $upcoming; ?></h5>
                        <p class="text-muted small mb-0">Upcoming</p>
                    </div>
                    <div class="col-4">
                        <h5 class="fw-bold mb-0"><?php echo $pasting; ?></h5>
                        <p class="text-muted small mb-0">Past</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card profile-card p-4">
                <ul class="nav nav-tabs nav-status mb-4" id="bookingTabs">
                    <li class="nav-item flex-fill">
                        <button class="nav-link w-100 active" data-bs-toggle="tab" data-bs-target="#unpaid">Unpaid (<?php echo $unpaid; ?>)</button>
                    </li>
                    <li class="nav-item flex-fill">
                        <button class="nav-link w-100" data-bs-toggle="tab" data-bs-target="#upcoming">Upcoming (<?php echo $upcoming; ?>)</button>
                    </li>
                    <li class="nav-item flex-fill">
                        <button class="nav-link w-100" data-bs-toggle="tab" data-bs-target="#past">Past (<?php echo $pasting; ?>)</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="unpaid">
                        <?php if(empty($un)): ?>
                            <div class="text-center py-5 text-muted">No unpaid bookings</div>
                        <?php else: ?>
                            <?php foreach($un as $index => $item): ?>
                                <div class="booking-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold mb-1"><?php echo $item['name']; ?></h6>
                                        <p class="text-muted small mb-0"><i class="bi bi-calendar"></i> <?php echo $item['checkin']; ?> | <i class="bi bi-door-open"></i> <?php echo $item['roomstyle']; ?></p>
                                    </div>
                                    <div class="text-end">
                                        <div class="mb-2"><span class="status-badge bg-warning-subtle text-warning">Waiting for Payment</span></div>
                                        <h6 class="fw-bold">฿<?php echo number_format($item['totalprice'], 2); ?></h6>
                                        <a href="payment.php?id=<?php echo $index; ?>" class="btn btn-dark btn-sm rounded-pill px-3">Pay Now</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="upcoming">
                                <?php if(empty($up)): ?>
                                    <div class="text-center py-5 text-muted">No upcoming bookings</div>
                                <?php else: ?>
                                <?php foreach($up as $item): ?>
                    <div class="booking-item d-flex justify-content-between align-items-center border-start border-success border-4">
                        <div>
                            <h6 class="fw-bold mb-1"><?php echo $item['name']; ?></h6>
                            <p class="text-muted small mb-0">
                                <i class="bi bi-calendar-check"></i> <?php echo $item['checkin']; ?> 
                                | 
                                <i class="bi bi-door-open"></i> <?php echo $item['roomstyle']; ?>
                            </p>
                        </div>
                        <div class="text-end">
                            <div class="mb-2">
                                <span class="status-badge bg-success-subtle text-success">
                                    Payment Completed
                                </span>
                            </div>
                            <h6 class="fw-bold">฿<?php echo number_format($item['totalprice'], 2); ?></h6>
                            

                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="past">
                <?php if(empty($past)): ?>
                    <div class="text-center py-5 text-muted">No past booking history</div>
                <?php else: ?>
                <?php foreach($past as $item): ?>

    <div class="booking-item d-flex justify-content-between align-items-center border-start border-secondary border-4 opacity-75">
    
                <div>
                    <h6 class="fw-bold mb-1 text-muted"><?php echo $item['name']; ?></h6>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-calendar-check"></i> <?php echo $item['checkin']; ?> 
                        | 
                        <i class="bi bi-door-open"></i> <?php echo $item['roomstyle']; ?>
                    </p>
                </div>

                <div class="text-end">
                    <div class="mb-2">
                        <span class="status-badge bg-secondary-subtle text-secondary">
                            Completed
                        </span>
                    </div>

                    <h6 class="fw-bold text-muted">
                        ฿<?php echo number_format($item['totalprice'], 2); ?>
                    </h6>

                    <!-- ไม่มีปุ่ม checkout เพราะ checkout แล้ว -->

                </div>

    </div>

        <?php endforeach; ?>
        <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>