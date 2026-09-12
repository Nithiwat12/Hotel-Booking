<?php
require_once __DIR__ . '/config.php';

$destination = trim((string)($_GET['destination'] ?? ''));
$guests = isset($_GET['guests']) && $_GET['guests'] !== '' ? max(0, (int)$_GET['guests']) : 0;
$category = trim((string)($_GET['category'] ?? 'All'));
$price_limit = isset($_GET['price']) ? max(0, min(2000, (int)$_GET['price'])) : 2000;

$sql = 'SELECT * FROM room WHERE price <= ?';
$params = [$price_limit];
$types = 'i';

if ($destination !== '') {
    $sql .= ' AND name ILIKE ?';
    $params[] = '%' . $destination . '%';
    $types .= 's';
}
if ($guests > 0) {
    $sql .= ' AND countuser >= ?';
    $params[] = $guests;
    $types .= 'i';
}
if ($category !== '' && strtolower($category) !== 'all') {
    $sql .= ' AND LOWER(roomstyle) = LOWER(?)';
    $params[] = $category;
    $types .= 's';
}
$sql .= ' ORDER BY roomid';
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$dbQuery = $stmt->get_result();

$catStmt = $conn->prepare("SELECT DISTINCT roomstyle FROM room WHERE roomstyle IS NOT NULL AND roomstyle <> '' ORDER BY roomstyle");
$catStmt->execute();
$catQuery = $catStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soda Hotel - Search</title>
    <link rel="stylesheet" href="index.css">
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
                <a href="profile.php"><span>👤 <?= htmlspecialchars($_SESSION['Username']); ?></span></a>
                <a href="logout.php"><button class="register-btn">Logout</button></a>
            <?php else: ?>
                <a href="loginhotel.php"><button class="login-btn">Login</button></a>
                <a href="register.php"><button class="register-btn">Register</button></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<form method="GET" action="main.php">
    <div class="search-box">
        <div class="search-item">
            <label>Destination</label>
            <input type="text" name="destination" placeholder="Where are you going?" value="<?= htmlspecialchars($destination) ?>">
        </div>
        <div class="search-item">
            <label>Guests</label>
            <input type="number" name="guests" placeholder="Guests" value="<?= $guests > 0 ? $guests : '' ?>">
        </div>
        <button type="submit" class="search-btn">Search Hotels</button>
    </div>

    <div class="main-layout">
        <div class="sidebar">
            <h2 class="filter-title">⚙ Filters</h2>
            
            <div class="filter-section">
                <h3>Price Range</h3>
                <input type="range" name="price" min="0" max="2000" step="100" value="<?= $price_limit ?>" id="priceRange" onchange="this.form.submit()">
                <div class="price-values">
                    <span>$0</span>
                    <span>$<?= $price_limit ?></span>
                </div>
            </div>

            <div class="filter-section">
                <h3>Category</h3>
                <?php
                // แนะนำให้ดึง Category จากฐานข้อมูลโดยตรงเพื่อให้ตรงกับห้องที่มีอยู่จริง
                $cat_query = $catQuery;
                
                // แสดงปุ่ม All ก่อน
                $all_checked = ($category == 'All') ? 'checked' : '';
                echo "<label class='radio-item'><input type='radio' name='category' value='All' $all_checked onchange='this.form.submit()'> All</label>";

                if ($cat_query) {
                    while($cat_row = $cat_query->fetch_assoc()) {
                        $cat_name = $cat_row['roomstyle'];
                        if(empty($cat_name)) continue;
                        
                        $checked = (strtolower($category) == strtolower($cat_name)) ? 'checked' : '';
                        echo "<label class='radio-item'>";
                        echo "<input type='radio' name='category' value='".htmlspecialchars($cat_name)."' $checked onchange='this.form.submit()'>";
                        echo " " . ucfirst(htmlspecialchars($cat_name));
                        echo "</label>";
                    }
                }
                ?>
            </div>
        </div>
</form> <div class="hotel-list"> 
        <?php if($dbQuery->num_rows() > 0): ?>
            <?php while($row = $dbQuery->fetch_assoc()): ?>  
                <div class="hotel-card">
                    <img src="<?php echo htmlspecialchars($row['picture']); ?>" class="hotel-img">
                    <div class="hotel-content">
                        <div class='card'>
                            <div class="hotel-header">
                                <a href="detail.php?id=<?php echo $row['roomid']; ?>">
                                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                                </a>
                                <span class="badge">⭐ <?php echo $row['star']; ?></span>
                            </div>
                            <p><strong>Style:</strong> <?php echo ucfirst(htmlspecialchars($row['roomstyle'])); ?> | <strong>Floor:</strong> <?php echo $row['floor']; ?></p>
                            <p>For <?php echo $row['countuser']; ?> Guests</p>
                            
                            <p>Available Rooms: <?php echo $row['countroom']; ?></p>
                            <div class="price">฿<?php echo number_format($row['price']); ?> /night</div>
                        </div>
                        <div style="text-align:right;">
                            <a href="booking.php?id=<?php echo $row['roomid']; ?>" class="btn">Book Now</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="padding: 20px; text-align: center; width: 100%;">
                <h3>❌ ไม่พบห้องพักที่ตรงตามเงื่อนไข</h3>
                <p>ลองปรับราคาหรือเปลี่ยนประเภทห้องดูนะครับ</p>
                <a href="main.php" class="btn" style="display:inline-block; margin-top:10px; background:#444;">ล้างตัวกรองทั้งหมด</a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>