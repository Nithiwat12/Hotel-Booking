<?php
require_once __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: loginhotel.php'); exit; }
$username = trim($_POST['Username'] ?? '');
$password = $_POST['Password'] ?? '';
if ($username === '' || $password === '') { header('Location: loginhotel.php'); exit; }
$stmt = $conn->prepare('SELECT username, password, status FROM users WHERE username = ? LIMIT 1');
$stmt->bind_param('s', $username); $stmt->execute(); $user = $stmt->get_result()->fetch_assoc();
if (!$user || !password_verify($password, $user['password'])) { header('Location: loginhotel.php?error=1'); exit; }
session_regenerate_id(true);
$_SESSION['Username'] = $user['username'];
$_SESSION['status'] = $user['status'];
header('Location: ' . ($user['status'] === 'admin' ? 'adminplay.php' : 'profile.php')); exit;
