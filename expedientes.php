<?php
require_once 'config.php';
error_reporting(E_ALL); // Para debug
ini_set('display_errors', 1);

$success = $error = $success_exp = $error_exp = '';

// Crear expediente nuevo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_expediente'])) {
    $paciente_id = (int)$_POST['paciente_id'];
    $fecha_consulta = $_POST['fecha_consulta'];
    $notas_raw = trim($_POST['notas_consulta']);
    $diagnostico_raw = trim($_POST['diagnostico']);
    $receta_raw = trim($_POST['receta']);
    $ingreso = (float)$_POST['ingreso'];

    if (empty($paciente_id) || empty($fecha_consulta) || empty($notas_raw)) {
        $error_exp = "Faltan datos obligatorios para el expediente.";
    } else {
        // Verifica si paciente existe
        $stmt_check = $pdo->prepare("SELECT id FROM pacientes WHERE id = ?");
        $stmt_check->execute([$paciente_id]);
        if (!$stmt_check->fetch()) {
            $error_exp = "Paciente no encontrado.";
        } else {
            $notas = encryptData($notas_raw, $encryption_key);
            $diagnostico = encryptData($diagnostico_raw, $encryption_key);
            $receta = encryptData($receta_raw, $encryption_key);

            try {
                $stmt = $pdo->prepare("INSERT INTO expedientes (paciente_id, fecha_consulta, notas_consulta, diagnostico, receta, ingreso) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$paciente_id, $fecha_consulta, $notas, $diagnostico, $receta, $ingreso]);
                $success_exp = "Expediente creado correctamente.";
            } catch (Exception $e) {
                $error_exp = "Error al guardar expediente: " . $e->getMessage();
            }
        }
    }
}

// Listar expedientes con JOIN a pacientes
try {
    $stmt_list = $pdo->query("
        SELECT e.*, p.nombre, p.apellido 
        FROM expedientes e 
        JOIN pacientes p ON e.paciente_id = p.id 
        ORDER BY e.fecha_consulta DESC
    ");
    $expedientes = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error al listar expedientes: " . $e->getMessage();
    $expedientes = [];
}

// Obtener lista de pacientes para el formulario
$pacientes = $pdo->query("SELECT id, nombre, apellido FROM pacientes ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Expedientes Clínicos</title>
    <link rel="stylesheet" href="styles.css"> <!-- Si usas el CSS anterior; omite si no -->
   
</head>
<body>
    <div class="container">
        <h2>Expedientes Clínicos</h2>
        <?php if (isset($success_exp)) echo "<p style='color:green;'>$success_exp</p>"; ?>
        <?php if (isset($error_exp)) echo "<p style='color:red;'>$error_exp</p>"; ?>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

        <!-- Formulario para Crear Nuevo Expediente -->
        <h3>Crear Nuevo Expediente</h3>
        <form method="POST">
            <label>Paciente: 
                <select name="paciente_id" required>
                    <option value="">Selecciona...</option>
                    <?php foreach ($pacientes as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label><br><br>
            <label>Fecha Consulta: <input type="date" name="fecha_consulta" required></label><br><br>
            <label>Notas de Consulta: <textarea name="notas_consulta" required placeholder="Describe la consulta..."></textarea></label><br><br>
            <label>Diagnóstico: <textarea name="diagnostico" placeholder="Diagnóstico médico..."></textarea></label><br><br>
            <label>Receta: <textarea name="receta" placeholder="Medicamentos y dosis..."></textarea></label><br><br>
            <label>Ingreso ($): <input type="number" step="0.01" name="ingreso" value="0" min="0"></label><br><br>
            <button type="submit" name="crear_expediente">Crear Expediente</button>
        </form>

        <!-- Lista de Expedientes -->
        <h3>Lista de Expedientes</h3>
        <?php if (empty($expedientes)): ?>
            <p>No hay expedientes aún.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Paciente</th>
                    <th>Fecha</th>
                    <th>Ingreso</th>
                    <th>Detalles (Desencriptados)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expedientes as $exp): 
                    $notas_dec = decryptData($exp['notas_consulta'], $encryption_key);
                    $diagnostico_dec = decryptData($exp['diagnostico'], $encryption_key);
                    $receta_dec = decryptData($exp['receta'], $encryption_key);
                ?>
                <tr>
                    <td><?= $exp['id'] ?></td>
                    <td><?= htmlspecialchars($exp['nombre'] . ' ' . $exp['apellido']) ?></td>
                    <td><?= $exp['fecha_consulta'] ?></td>
                    <td>$<?= number_format($exp['ingreso'], 2) ?></td>
                    <td>
                        <div class="detalle">
                            <strong>Notas:</strong> <?= htmlspecialchars($notas_dec ?: 'N/A') ?><br>
                            <strong>Diagnóstico:</strong> <?= htmlspecialchars($diagnostico_dec ?: 'N/A') ?><br>
                            <strong>Receta:</strong> <?= htmlspecialchars($receta_dec ?: 'N/A') ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <nav>
            <a href="index.php">Volver al Menú Principal</a> | 
            <a href="pacientes.php">Gestión de Pacientes</a> | 
            <a href="reportes.php">Reportes</a>
        </nav>
    </div>
</body>
</html>