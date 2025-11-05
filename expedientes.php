<?php
require_once 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$success = $error = $success_exp = $error_exp = '';

// Inicializa $expedientes como array vacío para evitar error
$expedientes = [];

// Manejo de uploads
$upload_dir = 'uploads/';
if (!file_exists($upload_dir)) { mkdir($upload_dir, 0755, true); }
if (!empty($_FILES['adjunto']['name'])) {
    $target_file = $upload_dir . basename($_FILES['adjunto']['name']);
    if (move_uploaded_file($_FILES['adjunto']['tmp_name'], $target_file)) {
        $adjunto_path = basename($_FILES['adjunto']['name']);
    } else {
        $error_exp = "Error al subir imagen.";
    }
} else {
    $adjunto_path = null;
}

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
                $stmt = $pdo->prepare("INSERT INTO expedientes (paciente_id, fecha_consulta, notas_consulta, diagnostico, receta, ingreso, adjunto) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$paciente_id, $fecha_consulta, $notas, $diagnostico, $receta, $ingreso, $adjunto_path]);
                $success_exp = "Expediente creado correctamente.";
                // Recarga lista después de crear (para consistencia)
                $expedientes = cargarExpedientesLocal(); // Función helper abajo
            } catch (Exception $e) {
                $error_exp = "Error al guardar expediente: " . $e->getMessage();
            }
        }
    }
}

// Función helper para cargar expedientes iniciales en PHP (sin filtros por simplicidad)
function cargarExpedientesLocal() {
    global $pdo, $encryption_key;
    try {
        $stmt_list = $pdo->query("SELECT e.*, p.nombre, p.apellido FROM expedientes e JOIN pacientes p ON e.paciente_id = p.id ORDER BY e.fecha_consulta DESC LIMIT 50");
        $expedientes = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
        // Desencripta para render inicial (opcional, ya que JS lo hace)
        return $expedientes;
    } catch (Exception $e) {
        return [];
    }
}

// Carga inicial si no hay POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $expedientes = cargarExpedientesLocal();
}

// Obtener lista de pacientes para el formulario
$pacientes = $pdo->query("SELECT id, nombre, apellido FROM pacientes ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Expedientes Clínicos</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Expedientes Clínicos</h2>
        <?php if (isset($success_exp)) echo "<p style='color:green;'>$success_exp</p>"; ?>
        <?php if (isset($error_exp)) echo "<p style='color:red;'>$error_exp</p>"; ?>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

        <!-- Formulario de Búsqueda en Tiempo Real -->
        <div class="filtros">
            <h3>Búsqueda en Tiempo Real</h3>
            <input type="text" id="busqueda-input" placeholder="Buscar por paciente, fecha o diagnóstico... (tipea para filtrar)" style="width: 100%; padding: 10px;">
            <p id="resultado-count" style="margin-top: 10px; font-weight: bold;">Cargando...</p>
        </div>
  <!-- Lista de Expedientes -->
        <h3>Lista de Expedientes (<?= count($expedientes) ?> resultados)</h3>
        <table id="tabla-expedientes">
            <thead>
                <tr><th>ID</th><th>Paciente</th><th>Fecha</th><th>Ingreso</th><th>Adjunto</th><th>Detalles (Desencriptados)</th></tr>
            </thead>
            <tbody id="tabla-body">
                <?php if (!empty($expedientes)): ?>
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
                            <?php if ($exp['adjunto']): ?>
                                <a href="#" class="adjunto-link" onclick="abrirModal('uploads/<?= htmlspecialchars($exp['adjunto']) ?>')" title="Ver en popup">Ver Imagen</a>
                            <?php else: ?>
                                Sin adjunto
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="detalle">
                                <strong>Notas:</strong> <?= htmlspecialchars($notas_dec ?: 'N/A') ?><br>
                                <strong>Diagnóstico:</strong> <?= htmlspecialchars($diagnostico_dec ?: 'N/A') ?><br>
                                <strong>Receta:</strong> <?= htmlspecialchars($receta_dec ?: 'N/A') ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align: center; color: #999;">No hay expedientes aún.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <!-- Formulario para Crear Nuevo Expediente -->
        <h3>Crear Nuevo Expediente</h3>
        <form method="POST" enctype="multipart/form-data">
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
            <div class="upload-area">
                <label>Adjunto (imagen de examen o firma): <input type="file" name="adjunto" accept="image/*" /></label>
                <p>Sube foto o archivo (máx 5MB).</p>
            </div>
            <button type="submit" name="crear_expediente">Crear Expediente</button>
        </form>

      

        <!-- Nav intacto -->
        <nav>
            <a href="index.php">Volver al Menú Principal</a> | 
            <a href="pacientes.php">Gestión de Pacientes</a> | 
            <a href="reportes.php">Reportes</a>
        </nav>
    </div>

    <!-- Modal para imágenes (igual que antes) -->
    <div id="modal-imagen" class="modal">
        <span class="close" onclick="cerrarModal()">&times;</span>
        <div class="modal-content">
            <img id="imagen-modal" src="" alt="Imagen del expediente">
        </div>
    </div>

    <script>
        // JS para AJAX (igual, pero ahora con inicialización PHP)
        function cargarExpedientes(query = '') {
            const params = new URLSearchParams({ buscar: query });
            fetch(`buscar_expedientes.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('tabla-body');
                    const count = document.getElementById('resultado-count');
                    if (data.error) {
                        tbody.innerHTML = '<tr><td colspan="6">Error: ' + data.error + '</td></tr>';
                        return;
                    }
                    let html = '';
                    data.expedientes.forEach(exp => {
                        const detallesHtml = `
                            <div class="detalle">
                                <strong>Notas:</strong> ${exp.detalles.notas}<br>
                                <strong>Diagnóstico:</strong> ${exp.detalles.diagnostico}<br>
                                <strong>Receta:</strong> ${exp.detalles.receta}
                            </div>
                        `;
                        const adjuntoHtml = exp.adjunto ? `<a href="#" class="adjunto-link" onclick="abrirModal('${exp.adjunto}')">Ver Imagen</a>` : 'Sin adjunto';
                        html += `
                            <tr>
                                <td>${exp.id}</td>
                                <td>${exp.paciente}</td>
                                <td>${exp.fecha}</td>
                                <td>$${exp.ingreso}</td>
                                <td>${adjuntoHtml}</td>
                                <td>${detallesHtml}</td>
                            </tr>
                        `;
                    });
                    tbody.innerHTML = html || '<tr><td colspan="6">No hay resultados.</td></tr>';
                    count.textContent = `Resultados: ${data.total}`;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('tabla-body').innerHTML = '<tr><td colspan="6">Error de conexión. Recarga la página.</td></tr>';
                });
        }

        // Evento de búsqueda
        let timeout;
        document.getElementById('busqueda-input').addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                cargarExpedientes(this.value);
            }, 300);
        });

        // Funciones para modal (igual)
        function abrirModal(src) {
            const modal = document.getElementById('modal-imagen');
            const img = document.getElementById('imagen-modal');
            img.src = src;
            modal.style.display = 'block';
        }

        function cerrarModal() {
            document.getElementById('modal-imagen').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('modal-imagen');
            if (event.target == modal) cerrarModal();
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') cerrarModal();
        });
    </script>
</body>
</html>