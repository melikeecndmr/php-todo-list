<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'todo_app';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
} catch (PDOException $e) {
    die("Bağlantı hatası: " . $e->getMessage());
}
?>