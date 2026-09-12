<?php
require_once __DIR__ . '/config.php';
requireAdmin();
$result = $conn->query('SELECT * FROM orders ORDER BY bookingdate DESC, created_at DESC');
if ($result === false) { http_response_code(500); exit('Unable to load orders.'); }
?>

<!DOCTYPE html>
<html>
<head>
<title>Check Orders</title>

<style>

body{
    font-family: Arial;
    background:#111;
    margin:0;
}

.header{
    background:#ff7a00;
    color:white;
    padding:15px;
    font-size:22px;
}

.container{
    width:80%;
    margin:auto;
    margin-top:30px;
}

.card{
    background:white;
    border-left:8px solid #ff7a00;
    border-radius:10px;
    padding:20px;
    margin-bottom:20px;
    box-shadow:0 4px 10px rgba(0,0,0,0.2);
}

.row{
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.hotelname{
    font-size:20px;
    font-weight:bold;
}

.info{
    color:#555;
    margin-top:5px;
}

.price{
    font-size:20px;
    font-weight:bold;
}

.status{
    background:#c8f1d3;
    color:#2e7d32;
    padding:5px 10px;
    border-radius:20px;
    font-size:13px;
}

.btn{
    background:#222;
    color:white;
    padding:10px 20px;
    border-radius:20px;
    text-decoration:none;
}

.btn:hover{
    background:#ff7a00;
}

</style>
</head>

<body>

<div class="header">
    Admin : ตรวจสอบข้อมูลการจอง
    <a class="btn" href="adminplay.php">
    play back
    </a>
</div>

<div class="container">

<?php
while($row = $result->fetch_array()){
?>

<div class="card">

    <div class="row">

    <div>

    <div class="hotelname">
    OrderID : <?php echo $row['bookingid']; ?>
    
    </div>
    <div class="info">
     Room ID : <?php echo $row['roomid']; ?>
    </div>
    <div class="info">
    Booking : <?php echo $row['bookingdate']; ?>
    </div>

    <div class="info">
    Check-in : <?php echo $row['checkin']; ?> |
    Check-out : <?php echo $row['checkout']; ?>
    </div>

    <div class="info">
    User : <?php echo $row['username']; ?> |
    Phone : <?php echo $row['phone']; ?>
    </div>

</div>


<div align="right">

<div class="status">
<?php echo $row['paymentstatus']; ?>
</div>

<br>

<div class="price">
฿<?php echo number_format($row['totalprice'],2); ?>
</div>

<br>

<?php
if($row['paymentstatus'] == "Upcoming"){
     echo '<a class="btn" href="checkout.php?id='.$row['bookingid'].'">
    Check - out
    </a>';
}
?>

</div>

</div>

</div>

<?php
}
?>

</div>

</body>
</html>