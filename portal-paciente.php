<?php
require_once 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$success = $error = '';
$mensaje_whatsapp = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_completo = trim(strtoupper($_POST['nombre'])); // Upper para comparación insensible
    $telefono = trim($_POST['telefono']); // e.g., +521234567890

    if (empty($nombre_completo) || empty($telefono)) {
        $error = "Por favor, ingresa tu nombre completo y número de teléfono.";
    } else {
        // Busca paciente por nombre (nombre + apellido) y teléfono
        $stmt = $pdo->prepare("SELECT id, nombre, apellido FROM pacientes WHERE CONCAT(UPPER(nombre), ' ', UPPER(apellido)) = ? AND telefono = ?");
        $stmt->execute([$nombre_completo, $telefono]);
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($paciente) {
            // Obtiene las últimas 5 recetas (expedientes)
            $stmt_recetas = $pdo->prepare("SELECT fecha_consulta, receta FROM expedientes WHERE paciente_id = ? ORDER BY fecha_consulta DESC LIMIT 5");
            $stmt_recetas->execute([$paciente['id']]);
            $recetas = $stmt_recetas->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($recetas)) {
                // Formatea el mensaje con recetas desencriptadas
                $mensaje_whatsapp = "¡Hola! Aquí tienes tu historial de recetas recientes:\n\n";
                foreach ($recetas as $r) {
                    $receta_dec = decryptData($r['receta'], $encryption_key);
                    $receta_dec = $receta_dec ?: 'Sin receta en esta consulta.';
                    $mensaje_whatsapp .= "• " . date('d/m/Y', strtotime($r['fecha_consulta'])) . ":\n" . htmlspecialchars($receta_dec) . "\n\n";
                }
                $mensaje_whatsapp .= "Si necesitas más detalles, contacta al consultorio.\nSaludos, Dr. [Tu Nombre]";

                $success = "Datos verificados. Abriendo WhatsApp con tu historial...";
                // URL de WhatsApp (redirecciona al final)
                $numero_whatsapp = urlencode($telefono);
                $mensaje_encoded = urlencode($mensaje_whatsapp);
                $url_whatsapp = "https://wa.me/$numero_whatsapp?text=$mensaje_encoded";
            } else {
                $error = "No se encontraron recetas en tu historial.";
            }
        } else {
            $error = "No se encontró un paciente con ese nombre y número. Verifica los datos.";
        }
    }
}

// Si hay URL, redirige a WhatsApp
if (isset($url_whatsapp)) {
    header("Location: $url_whatsapp");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Portal del Paciente - Historial de Recetas</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { background: linear-gradient(135deg, #e3f2fd, #bbdefb); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { max-width: 500px; text-align: center; }
        form { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        input { margin: 10px 0; padding: 12px; width: 100%; border: 1px solid #ddd; border-radius: 6px; }
        button { background: #25D366; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }
        button:hover { background: #128C7E; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Portal del Paciente</h1>
        <p>Ingresa tu nombre completo y número de teléfono para recibir tu historial de recetas por WhatsApp.</p>

        <?php if ($success): ?>
            <p style="color: green;"><?= htmlspecialchars($success) ?></p>
            <script>window.location.href = '<?= $url_whatsapp ?>'; // Fallback JS</script>
        <?php endif; ?>

        <?php if ($error): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label><strong>Nombre Completo (ej: Juan Pérez):</strong></label>
            <input type="text" name="nombre" placeholder="Juan Pérez" required maxlength="100"><br>

            <label><strong>Número de Teléfono (con código país, ej: +521234567890):</strong></label>
            <input type="tel" name="telefono" placeholder="+521234567890" required pattern="^\+[1-9]\d{1,14}$"><br>

            <button type="submit">Obtener Historial por WhatsApp</button>
        </form>

        <p style="margin-top: 20px; font-size: 0.9em; color: #666;">
            <a href="index.php">Volver al Consultorio (para doctores)</a>
        </p>
    </div>
</body>
</html>