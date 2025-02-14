<?php
$host = "localhost"; 
$db_name = "attendance_system";
$username = "root"; 
$password = "1532910"; 

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    die("Connection failed: " . $exception->getMessage());
}
?>
