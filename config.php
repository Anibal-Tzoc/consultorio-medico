<?php
session_start(); // Inicia sesiones para auth

DB_HOST="mysql.railway.internal"
DB_NAME="railway"
DB_PASSWORD="hVQXbZykIwasSgczFFymXvrUzmqwzqRF"
DB_PORT="3306"
DB_USER="root"
$db=myqli_connect("$DB_HOST","$DB_USER","$DB_PASSWORD","$DB_NAME","$DB_PORT");
try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

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

// Configuración de reCAPTCHA (para portal-paciente)
$recaptcha_site_key = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI'; // Reemplaza con tu SITE KEY
$recaptcha_secret_key = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'; // Reemplaza con tu SECRET KEY

// Carpeta para uploads (para expedientes)
$upload_dir = 'uploads/';
if (!file_exists($upload_dir)) { mkdir($upload_dir, 0755, true); }
?>



