<?php
$host = getenv('DB_HOST');
$user = getenv('DB_USERNAME');
$pass = getenv('DB_PASSWORD');
$db = getenv('DB_DATABASE');

$pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
$pdo->exec(file_get_contents('database.sql'));  // Asume tu dump está en database.sql
echo "Importado!";
?>