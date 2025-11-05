<?php
require_once 'config.php';
error_reporting(E_ALL); // Para debug
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar_paciente'])) {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $historial_raw = trim($_POST['historial_medico']);
    $alergias_raw = trim($_POST['alergias']); // String coma separada

    if (empty($nombre) || empty($apellido) || empty($email)) {
        $error = "Faltan datos obligatorios.";
    } else {
        // Encripta
        $historial = encryptData($historial_raw, $encryption_key);
        if (empty($alergias_raw)) {
            $alergias = null;
        } else {
            $alergias_array = array_filter(array_map('trim', explode(',', $alergias_raw)));
            $alergias_json = json_encode($alergias_array);
            $alergias = encryptData($alergias_json, $encryption_key);
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO pacientes (nombre, apellido, email, telefono, fecha_nacimiento, historial_medico, alergias) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $apellido, $email, $telefono, $fecha_nacimiento, $historial, $alergias]);
            $success = "Paciente registrado correctamente.";
        } catch (Exception $e) {
            $error = "Error al guardar: " . $e->getMessage();
        }
    }
}

// Listar pacientes
try {
    $pacientes = $pdo->query("SELECT * FROM pacientes ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error al listar: " . $e->getMessage();
    $pacientes = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Pacientes</title>
    <link rel="stylesheet" href="styles.css">
    <style>body { font-family: Arial; } table { border-collapse: collapse; } th, td { border: 1px solid #ddd; padding: 8px; }</style>
</head>
<body>
    <div class="container">
        <h2>Gestión de Pacientes</h2>
        <?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

        <!-- Form Registro Paciente -->
        <h3>Registrar Nuevo Paciente</h3>
        <form method="POST">
            <label>Nombre: <input type="text" name="nombre" required></label><br><br>
            <label>Apellido: <input type="text" name="apellido" required></label><br><br>
            <label>Email: <input type="email" name="email" required></label><br><br>
            <label>Teléfono: <input type="text" name="telefono"></label><br><br>
            <label>Fecha Nacimiento: <input type="date" name="fecha_nacimiento"></label><br><br>
            <label>Historial Médico: <textarea name="historial_medico" rows="3" cols="50"></textarea></label><br><br>
            <label>Alergias (separadas por coma, ej: polen,penicilina): <input type="text" name="alergias" placeholder="polen,penicilina"></label><br><br>
            <button type="submit" name="registrar_paciente">Registrar</button>
        </form>

        <h3>Lista de Pacientes</h3>

        <table>
            <thead><tr><th>ID</th><th>Nombre Completo</th><th>Email</th><th>Historial (Desencriptado)</th><th>Alergias (Desencriptado)</th><th>Acciones</th></tr></thead>
         <tbody>
    <?php foreach ($pacientes as $p): 
        $historial_dec = decryptData($p['historial_medico'], $encryption_key);
        $alergias_json = decryptData($p['alergias'], $encryption_key);
        $alergias_dec = $alergias_json ? json_decode($alergias_json, true) : [];
        $alergias_str = implode(', ', $alergias_dec);
        $es_alerta = (strpos($alergias_str, 'penicilina') !== false || strpos(strtolower($historial_dec), 'diabetes') !== false); // Ejemplos de alertas
    ?>
    <tr class="<?= $es_alerta ? 'alerta-roja' : '' ?>">
        <td><?= $p['id'] ?></td>
        <td><?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido']) ?></td>
        <td><?= htmlspecialchars($p['email']) ?></td>
        <td><?= htmlspecialchars($historial_dec ?: 'Sin historial') ?></td>
        <td><?= htmlspecialchars($alergias_str ?: 'Ninguna') ?></td>
        <td>
            <?php if ($es_alerta): ?>
                <span style="color: red; font-weight: bold;">⚠️ ALERTA: Revisar alergias/historial</span>
            <?php endif; ?>
            <a href="expedientes.php?paciente_id=<?= $p['id'] ?>">Ver/Crear Expedientes</a>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
        </table>

        <nav>
            <a href="index.php">Volver al Menú</a> | <a href="expedientes.php">Expedientes</a> | <a href="reportes.php">Reportes</a>
        </nav>
    </div>
    
</body>

</html>