<?php
$data = "Prueba";
$key = "mi-clave-secreta-super-larga-para-aes-256"; // Tu clave
$iv = openssl_random_pseudo_bytes(16);
$encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
if ($encrypted === false) {
    echo "Error: " . openssl_error_string();
} else {
    echo "Encriptado OK: " . base64_encode($encrypted . '::' . $iv);
}
?>