<?php
require_once __DIR__ . '/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $roomid = trim((string)($_POST['roomid'] ?? ''));
    $floor = trim((string)($_POST['floor'] ?? ''));
    $roomstyle = trim((string)($_POST['roomstyle'] ?? ''));
    $roomdetail = trim((string)($_POST['roomdetail'] ?? ''));
    $price = max(0, (float)($_POST['price'] ?? 0));
    $countroom = max(0, (int)($_POST['countroom'] ?? 0));
    $countuser = max(1, (int)($_POST['countuser'] ?? 1));
    $star = min(5, max(0, (float)($_POST['star'] ?? 0)));
    $picture = trim((string)($_POST['picture'] ?? ''));
    $name = trim((string)($_POST['name'] ?? ''));

    if ($roomid === '' || $name === '') { http_response_code(400); exit('Room ID and name are required.'); }

    if (isset($_POST['add'])) {
        $stmt = $conn->prepare('INSERT INTO room (roomid,floor,roomstyle,roomdetail,price,countroom,countuser,star,picture,name) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $stmt->bind_param('ssssdiidss', $roomid,$floor,$roomstyle,$roomdetail,$price,$countroom,$countuser,$star,$picture,$name);
        $stmt->execute();
    } elseif (isset($_POST['update'])) {
        $stmt = $conn->prepare('UPDATE room SET floor=?,roomstyle=?,roomdetail=?,price=?,countroom=?,countuser=?,star=?,picture=?,name=? WHERE roomid=?');
        $stmt->bind_param('sssdiidsss', $floor,$roomstyle,$roomdetail,$price,$countroom,$countuser,$star,$picture,$name,$roomid);
        $stmt->execute();
    }
    header('Location: edit_room.php');
    exit;
}

$result = $conn->query('SELECT * FROM room ORDER BY roomid');
?>

<html>
<head>

<style>

body{
background:#111;
font-family:Arial;
color:white;
}


h2{
color:orange;
text-align:center;
}

table{
width:95%;
margin:auto;
border-collapse:collapse;
}

th{
background:orange;
color:black;
padding:10px;
}

td{
padding:8px;
background:#222;
}

input{
width:100%;
padding:5px;
}

button{
background:orange;
border:none;
padding:8px 15px;
font-weight:bold;
cursor:pointer;
}

button:hover{
background:#ff6a00;
}


.back-btn{
    display:inline-block;
    margin:20px;
    padding:10px 20px;
    background:#ff7a00;
    color:white;
    text-decoration:none;
    border-radius:8px;
    font-weight:bold;
}

.back-btn:hover{
    background:#cc6200;
}
</style>

</head>

<body>

<h2>จัดการข้อมูลห้องพัก</h2>
<a href="adminplay.php" class="back-btn">← play back</a>

<table border="1">

<tr>

<th>RoomID</th>
<th>Floor</th>
<th>Style</th>
<th>Detail</th>
<th>Price</th>
<th>Room</th>
<th>User</th>
<th>Star</th>
<th>Picture</th>
<th>Name</th>
<th>Update</th>

</tr>

<form method="post">
<input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

<tr>

<td><input name="roomid"></td>
<td><input name="floor"></td>
<td><input name="roomstyle"></td>
<td><input name="roomdetail"></td>
<td><input name="price"></td>
<td><input name="countroom"></td>
<td><input name="countuser"></td>
<td><input name="star"></td>
<td><input name="picture"></td>
<td><input name="name"></td>

<td>
<button name="add">เพิ่ม</button>
</td>

</tr>

</form>

<?php

while($row=$result->fetch_array()){

?>

<form method="post">
<input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

<tr>

<td><input name="roomid" value="<?php echo $row['roomid']; ?>" readonly></td>
<td><input name="floor" value="<?php echo $row['floor']; ?>"></td>
<td><input name="roomstyle" value="<?php echo $row['roomstyle']; ?>"></td>
<td><input name="roomdetail" value="<?php echo $row['roomdetail']; ?>"></td>
<td><input name="price" value="<?php echo $row['price']; ?>"></td>
<td><input name="countroom" value="<?php echo $row['countroom']; ?>"></td>
<td><input name="countuser" value="<?php echo $row['countuser']; ?>"></td>
<td><input name="star" value="<?php echo $row['star']; ?>"></td>
<td><input name="picture" value="<?php echo $row['picture']; ?>"></td>
<td><input name="name" value="<?php echo $row['name']; ?>"></td>

<td>
<button name="update">Update</button>
</td>

</tr>

</form>

<?php
}
?>

</table>

</body>
</html>