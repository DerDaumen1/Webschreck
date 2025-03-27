<?php
$dsn = "mysql:host=localhost;dbname=webdatabase;charset=utf8";
$user = "root";
$pass = "";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("DB-Verbindungsfehler: " . $e->getMessage());
}
