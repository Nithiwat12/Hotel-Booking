<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <title>เข้าสู่ระบบ</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>

<div class="container">
    <a href="main.php"><button class="btn">Back</button></a>
    <h2>เข้าสู่ระบบ</h2>
    <form action="checkLogin.php" method="post">
        <label>Username</label>
        <input type="text" name="Username" required>
        
        <label>Password</label>
        <input type="password" name="Password" required>
        
        <input type="submit" value="Login">
    </form>
    
    <div class="link-group">
        <a href="register.php">ยังไม่มีบัญชี? สมัครสมาชิก</a>
    </div>
</div>

</body>
</html>