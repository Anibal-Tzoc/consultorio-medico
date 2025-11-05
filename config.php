<?php
session_start(); // Inicia sesiones para autenticación

// =======================
// CONFIGURACIÓN BASE DE DATOS (Railway + Local)
// =======================
$servername = $_ENV['DB_HOST']      ?? getenv('DB_HOST')      ?? '127.0.0.1';
$username   = $_ENV['DB_USER']      ?? getenv('DB_USER')      ?? 'root';
$password   = $_ENV['DB_PASSWORD']  ?? getenv('DB_PASSWORD')  ?? '';
$dbname     = $_ENV['DB_NAME']      ?? getenv('DB_NAME')      ?? 'railway';
$port       = $_ENV['DB_PORT']      ?? getenv('DB_PORT')      ?? '3306';

try {
    // Conexión PDO con PUERTO incluido (OBLIGATORIO en Railway)
    $pdo = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8");
} catch(PDOException $e) {
    // En producción, muestra error genérico (no detalles)
    die("Error de conexión a la base de datos. Intenta más tarde.");
    // Para depuración local: descomenta la línea de abajo
    // die("Error de conexión: " . $e->getMessage());
}

// =======================
// CLAVE DE ENCRIPTACIÓN (SEGURA - NUNCA EN CÓDIGO)
// =======================
$base_key = $_ENV['ENCRYPTION_KEY'] ?? 'clave-local-solo-para-xampp-1234567890';
$encryption_key = hash('sha256', $base_key, true); // 32 bytes exactos para AES-256

// =======================
// FUNCIONES DE ENCRIPTACIÓN
// =======================
function encryptData($data, $key) {
    if (empty($data)) return null;
    $iv = openssl_random_pseudo_bytes(16);
    if ($iv === false) return $data;
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
    if ($encrypted === false) return $data;
    return base64_encode($encrypted . '::' . base64_encode($iv));
}

function decryptData($data, $key) {
    if (empty($data)) return null;
    $decoded = base64_decode($data);
    if ($decoded === false) return $data;
    list($encrypted_data, $iv_b64) = explode('::', $decoded, 2);
    $iv = base64_decode($iv_b64);
    if ($iv === false) return $data;
    $decrypted = openssl_decrypt($encrypted_data, 'AES-256-CBC', $key, 0, $iv);
    return $decrypted === false ? $data : $decrypted;
}

// =======================
// AUTENTICACIÓN
// =======================
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirigir si no está logueado (excepto en login.php)
if (!isLoggedIn() && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header("Location: login.php");
    exit();
}

// =======================
// RECAPTCHA (Portal Paciente)
// =======================
$recaptcha_site_key   = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
$recaptcha_secret_key = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

// =======================
// CARPETA DE UPLOADS (Expedientes)
// =======================
$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}
?>
