<?php
require_once __DIR__ . '/config.php';
$_SESSION=[]; session_destroy();
header('Location: main.php'); exit;
