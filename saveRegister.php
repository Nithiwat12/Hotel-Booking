<?php
require_once __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: register.php'); exit; }
$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$phone = trim((string)($_POST['phone'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));

if ($username === '' || strlen($username) > 100 || strlen($password) < 8 || strlen($password) > 200 || strlen($phone) > 30 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    http_response_code(400);
    exit('Invalid registration data. Password must be 8-200 characters.');
}

$hash = password_hash($password, PASSWORD_DEFAULT);
try {
    $stmt = $conn->prepare("INSERT INTO users (username,password,phone,email,status) VALUES (?,?,?,?, 'user')");
    $stmt->bind_param('ssss', $username, $hash, $phone, $email);
    $stmt->execute();
    header('Location: loginhotel.php?registered=1');
    exit;
} catch (Throwable $e) {
    http_response_code(400);
    exit('Username or email is already in use.');
}
