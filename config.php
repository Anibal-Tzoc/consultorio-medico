<?php
session_start(); // Inicia sesiones para auth

// Define variables de entorno (de Railway o local)
$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1'; // mysql.railway.internal en Railway
$DB_NAME = getenv('DB_NAME') ?: 'railway'; // o 'consultorio_medico' local
$DB_PASSWORD = getenv('DB_PASSWORD') ?: ''; // tu pass: hVQXbZykIwasSgczFFymXvrUzmqwzqRF
$DB_PORT = getenv('DB_PORT') ?: '3306';
$DB_USER = getenv('DB_USER') ?: 'root';

// Conexión MySQLi (corregida, sin PDO mixto)
$db = mysqli_connect($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME, $DB_PORT);

if (!$db) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Opcional: Set charset para UTF-8
mysqli_set_charset($db, "utf8mb4");

// ... (resto de tu código: encriptación, funciones, etc.)

// Clave base para encriptación (cámbiala en producción)
$base_key = 'mi-clave-secreta-super-larga-para-aes-256';
$encryption_key = hash('sha256', $base_key, true); // 32 bytes exactos

// Función encriptar/desencriptar
function encryptData($data, $key) {
    if (empty($data)) return null;
    $iv = openssl_random_pseudo_bytes(16);
    if ($iv === false) {
        error_log("Error IV: " . openssl_error_string());
        return $data; // Texto plano si falla (dev)
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
    return $decrypted === false ? $data : $decrypted; // Fallback
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

// Configuración de reCAPTCHA (para portal-paciente) - Reemplaza con tus keys reales
$recaptcha_site_key = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI'; // Tu SITE KEY
$recaptcha_secret_key = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'; // Tu SECRET KEY

// Carpeta para uploads (para expedientes)
$upload_dir = 'uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}
?>



