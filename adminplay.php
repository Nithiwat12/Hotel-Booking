<?php
require_once __DIR__ . '/config.php';

if(!isset($_SESSION['status']) || $_SESSION['status']!="admin"){
echo "สำหรับ admin เท่านั้น";
exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Panel</title>

<style>

body{
background:#111;
font-family:Arial;
color:white;
text-align:center;
padding-top:100px;
}

.box{
background:#1c1c1c;
width:400px;
margin:auto;
padding:40px;
border-radius:10px;
box-shadow:0 0 15px rgba(255,140,0,0.5);
}

h1{
color:orange;
}

.btn{
display:block;
margin:20px;
padding:15px;
background:orange;
color:black;
text-decoration:none;
font-weight:bold;
border-radius:8px;
transition:0.3s;
}

.btn:hover{
background:#ff6a00;
}

.logout{
background:#333;
color:white;
}

</style>
</head>

<body>

<div class="box">

<h1>Admin Panel</h1>

<p>ยินดีต้อนรับ <?php echo $_SESSION['Username']; ?></p>

<a class="btn" href="edit_room.php">จัดการข้อมูลห้องพัก</a>

<a class="btn" href="check_orders.php">ตรวจสอบข้อมูลการจอง</a>

<a class="btn logout" href="logout.php">Logout</a>

</div>

</body>
</html>