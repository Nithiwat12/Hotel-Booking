<!DOCTYPE html>
<html>
<head>
    <title>สมัครสมาชิก</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>

<div class="container">
    <a href="main.php"><button class="btn">Back</button></a>
    <h2>สมัครสมาชิก</h2>
    <form action="saveRegister.php" method="post">
        <label>Username</label>
        <input type="text" name="username" required>
        
        <label>Password</label>
        <input type="password" name="password" required>
        
        <label>Phone</label>
        <input type="text" name="phone" required>
        
        <label>Email</label>
        <input type="email" name="email" required>

        <input type="submit" value="บันทึกข้อมูล">
    </form>
    
    <div class="link-group">
        <a href="loginhotel.php">มีบัญชีอยู่แล้ว? เข้าสู่ระบบ</a>
    </div>
</div>

</body>
</html>