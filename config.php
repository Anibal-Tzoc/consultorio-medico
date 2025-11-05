<?php
session_start();

$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "consultorio_medico";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Clave base (cámbiala)
$base_key = 'mi-clave-secreta-super-larga-para-aes-256';
$encryption_key = hash('sha256', $base_key, true); // Ahora 32 bytes exactos

function encryptData($data, $key) {
    if (empty($data)) return null; // Si vacío, no encripta
    $iv = openssl_random_pseudo_bytes(16);
    if ($iv === false) {
        error_log("Error IV: " . openssl_error_string());
        return $data; // Texto plano si falla (para dev)
    }
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
    if ($encrypted === false) {
        error_log("Error encriptación: " . openssl_error_string());
        return $data; // Texto plano si falla
    }
    return base64_encode($encrypted . '::' . $iv);
}

function decryptData($data, $key) {
    if (empty($data) || $data === null) return null;
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    $decrypted = openssl_decrypt($encrypted_data, 'AES-256-CBC', $key, 0, $iv);
    return $decrypted === false ? $data : $decrypted; // Fallback
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

if (!isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header("Location: login.php");
    exit();
}
?>