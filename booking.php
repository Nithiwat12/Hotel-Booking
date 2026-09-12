<?php
require_once __DIR__ . '/config.php';
requireLogin();

$roomid = trim((string)($_GET['id'] ?? ''));
if ($roomid === '') { http_response_code(400); exit('ไม่พบรหัสห้องพัก'); }

$stmt = $conn->prepare('SELECT * FROM room WHERE roomid = ? LIMIT 1');
$stmt->bind_param('s', $roomid);
$stmt->execute();
$rs = $stmt->get_result()->fetch_assoc();
if (!$rs) { http_response_code(404); exit('ไม่พบข้อมูลห้องพัก'); }

$userStmt = $conn->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
$username = $_SESSION['Username'];
$userStmt->bind_param('s', $username);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
if (!$user) { session_destroy(); header('Location: loginhotel.php'); exit; }

if ((int)$rs['countroom'] <= 0) {
    echo "<script>alert('ห้องเต็มแล้ว'); window.location='main.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Complete Your Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f9; font-family: 'Segoe UI', sans-serif; }
        .booking-card { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .form-control, .form-select { border: none; background-color: #f0f2f5; padding: 12px 15px; border-radius: 10px; }
        .form-label { font-weight: 600; font-size: 0.9rem; color: #444; margin-top: 10px; }
        .section-title { font-weight: 700; margin-top: 25px; margin-bottom: 15px; }
        .btn-add-cart { background-color: #6c757d; color: white; border: none; padding: 12px; border-radius: 10px; transition: 0.3s; }
        .btn-add-cart:hover { background-color: #495057; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="card booking-card mx-auto p-4 p-md-5" style="max-width: 850px;">
        <h3 class="fw-bold mb-1">Complete Your Booking</h3>
        <p class="text-muted mb-4"><?php echo $rs['name']; ?></p>

        <form action="cart.php" method="POST">
            <input type="hidden" name="roomid" value="<?php echo $rs['roomid']; ?>">
            <input type="hidden" name="name" value="<?php echo $rs['name']; ?>">
            <input type="hidden" name="price" value="<?php echo e((string)$rs['price']); ?>">
            <input type="hidden" name="picture" value="<?php echo e((string)$rs['picture']); ?>">

            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label"><i class="bi bi-calendar3 me-2"></i>Check-in Date</label>
                    <input type="date" name="check_in" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><i class="bi bi-calendar3 me-2"></i>Check-out Date</label>
                    <input type="date" name="check_out" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><i class="bi bi-people me-2"></i>Number of Guests</label>
                    <input type="text" name="countuser" value="<?php echo $rs['countuser'] ?>" class="form-control" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><i class="bi bi-door-open me-2"></i>Room Type</label>
                    <input type="text" name="roomstyle" value="<?php echo $rs['roomstyle'] ?>" class="form-control" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><i class="bi bi-door-open me-2"></i>Amount</label>
                    <input type="number" name="amount" min="1" max="<?php echo (int)$rs['countroom']; ?>" class="form-control" required>
                </div>

                <div class="col-12"><h5 class="section-title">Guest Information</h5></div>

                <div class="col-12">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo $user ['username'] ?> " readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo $user ['email'] ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control"   value="<?php echo $user ['phone'] ?>"readonly>
                </div>
            </div>

            <div class="row mt-5 pt-3">
                <div class="col-6">
                    <a href="main.php" class="btn btn-outline-light text-dark w-100 py-3 rounded-3 border">Cancel</a>
                </div>
                <div class="col-6">
                    <button type="submit" name="add_to_cart" class="btn btn-add-cart w-100 py-3 fw-bold">Add to Cart</button>
                </div>
            </div>
        </form>
    </div>
</div>
</body>
</html>