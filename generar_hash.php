<?php
$password = '123456'; // Cambia si quieres otra
echo "Hash para '$password': " . password_hash($password, PASSWORD_DEFAULT);
?>