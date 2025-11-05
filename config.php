<?php
session_start(); // Inicia sesiones

// Configuración DB usando variables de entorno (Railway)
$servername = $_ENV['DB_HOST']      ?? getenv('DB_HOST')      ?? '127.0.0.1';
$username   = $_ENV['DB_USER']      ?? getenv('DB_USER')      ?? 'root';
$password   = $_ENV['DB_PASSWORD']  ?? getenv('DB_PASSWORD')  ?? '';
$dbname     = $_ENV['DB_NAME']      ?? getenv('DB_NAME')      ?? 'railway';
$port       = $_ENV['DB_PORT']      ?? getenv('DB_PORT')      ?? '3306';

try {
    // AÑADIDO: port=$port
    $pdo = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Clave base (cámbiala)
$base_key = 'mi-clave-secreta-super-larga-para-aes-256';
$encryption_key = hash('sha256', $base_key, true); // Ahora 32 bytes exactos

// Función encriptar/desencriptar
function encryptData($data, $key) {
    if (empty($data)) return null;
    $iv = openssl_random_pseudo_bytes(16);
    if ($iv === false) {
        error_log("Error IV: " . openssl_error_string());
        return $data;
    }
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
    if ($encrypted === false) {
        error_log("Error encriptación: " . openssl_error_string());
        return $data;
    }
    return base64_encode($encrypted . '::' . $iv);
}

function decryptData($data, $key) {
    if (empty($data) || $data === null) return null;
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    $decrypted = openssl_decrypt($encrypted_data, 'AES-256-CBC', $key, 0, $iv);
    return $decrypted === false ? $data : $decrypted;
}

// Verificar si usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirigir si no logueado
if (!isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header("Location: login.php");
    exit();
}

// Configuración de reCAPTCHA
$recaptcha_site_key = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
$recaptcha_secret_key = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

// Carpeta para uploads
$upload_dir = 'uploads/';
if (!file_exists($upload_dir)) { 
    mkdir($upload_dir, 0755, true); 
}
?>