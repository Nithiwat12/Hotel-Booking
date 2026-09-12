<?php
require_once __DIR__ . '/config.php';
$roomid = trim((string)($_GET['id'] ?? ''));
if ($roomid === '') { http_response_code(400); exit('ไม่พบรหัสห้องพัก'); }
$stmt = $conn->prepare('SELECT * FROM room WHERE roomid = ? LIMIT 1');
$stmt->bind_param('s', $roomid);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) { http_response_code(404); exit('ไม่พบข้อมูลโรงแรมที่คุณต้องการ'); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Detail - <?php echo $row['name']; ?></title>
    <link rel="stylesheet" href="main.css"> <style>
        .detail-container { max-width: 800px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .detail-img { width: 100%; height: 400px; object-fit: cover; border-radius: 8px; }
        .detail-info { margin-top: 20px; }
        .price-tag { font-size: 24px; color: #e67e22; font-weight: bold; }
        .back-btn { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #3498db; }
    </style>
</head>
<body>

<div class="detail-container">
    <a href="main.php" class="back-btn">← Back to Home</a>
    
    <img src="<?php echo $row['picture']; ?>" class="detail-img" alt="Hotel Image">

    <div class="detail-info">
        <h1><?php echo $row['name']; ?> <span style="font-size: 18px;">⭐ <?php echo $row['star']; ?></span></h1>
        <hr>
        
        <h3>Room Details</h3>
        <p><strong>Style:</strong> <?php echo ucfirst($row['roomstyle']); ?></p>
        <p><strong>Floor:</strong> <?php echo $row['floor']; ?></p>
        <p><strong>Capacity:</strong> Up to <?php echo $row['countuser']; ?> Guests</p>
        
        <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h4>Description</h4>
            <p><?php echo $row['roomdetail']; ?></p>
        </div>

        <div class="price-tag">
            Price: $<?php echo $row['price']; ?> / night
        </div>
        
        <p>Available: <?php echo $row['countroom']; ?> rooms left</p>

        <div style="margin-top: 30px;">
            <a href="booking.php?id=<?php echo $row['roomid']; ?>" 
               style="background-color: #27ae60; color: white; padding: 10px 25px; text-decoration: none; border-radius: 5px;">
               Confirm Booking Now
            </a>
        </div>
    </div>
</div>

</body>
</html>