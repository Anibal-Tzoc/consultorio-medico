<?php
session_start(); // Inicia sesiones para autenticación

// =======================
// CONFIGURACIÓN BASE DE DATOS (Railway + Local)
// =======================
// Reemplaza la sección DB con:
$servername = getenv('DB_HOST') ?: '127.0.0.1';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$dbname = getenv('DB_NAME') ?: 'consultorio_medico';
// ... resto igual
$recaptcha_site_key = getenv('RECAPTCHA_SITE_KEY') ?: '';
$recaptcha_secret_key = getenv('RECAPTCHA_SECRET_KEY') ?: '';
try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
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


