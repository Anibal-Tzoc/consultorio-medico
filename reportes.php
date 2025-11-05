<?php
require_once 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Filtros avanzados
$fecha_desde = $_GET['desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['hasta'] ?? date('Y-m-t');
$doctor_id = $_GET['doctor'] ?? '';
$tipo_ingreso = $_GET['tipo_ingreso'] ?? '';
$diagnostico_busqueda = $_GET['diagnostico'] ?? '';

// Condiciones base para WHERE
$where_conditions = [];
$params = [];

$where_conditions[] = "e.fecha_consulta BETWEEN ? AND ?";
$params[] = $fecha_desde;
$params[] = $fecha_hasta;

if (!empty($doctor_id)) {
    $where_conditions[] = "e.doctor_id = ?";
    $params[] = $doctor_id;
}

if (!empty($tipo_ingreso)) {
    $where_conditions[] = "e.tipo_ingreso = ?";
    $params[] = $tipo_ingreso;
}

if (!empty($diagnostico_busqueda)) {
    $where_conditions[] = "e.diagnostico LIKE ?";
    $params[] = "%$diagnostico_busqueda%";
}

// Construye WHERE correctamente
$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    // Total pacientes
    $stmt_pacientes = $pdo->prepare("SELECT COUNT(DISTINCT e.paciente_id) FROM expedientes e $where_clause");
    $stmt_pacientes->execute($params);
    $total_pacientes = $stmt_pacientes->fetchColumn();

    // Total ingresos
    $stmt_ingresos = $pdo->prepare("SELECT SUM(e.ingreso) FROM expedientes e $where_clause");
    $stmt_ingresos->execute($params);
    $total_ingresos = $stmt_ingresos->fetchColumn() ?: 0;

    // Promedio
    $stmt_total_consultas = $pdo->prepare("SELECT COUNT(*) FROM expedientes e $where_clause");
    $stmt_total_consultas->execute($params);
    $total_consultas = $stmt_total_consultas->fetchColumn();
    $ingresos_promedio = $total_consultas > 0 ? $total_ingresos / $total_consultas : 0;

    // Tendencias mensuales
    $stmt_mensual = $pdo->prepare("SELECT MONTH(e.fecha_consulta) as mes, SUM(e.ingreso) as ingresos FROM expedientes e $where_clause GROUP BY mes ORDER BY mes");
    $stmt_mensual->execute($params);
    $datos_grafico = $stmt_mensual->fetchAll(PDO::FETCH_ASSOC);

    // Pacientes recurrentes
    $stmt_recurrentes = $pdo->prepare("SELECT p.nombre, p.apellido, COUNT(e.id) as num_consultas FROM pacientes p JOIN expedientes e ON p.id = e.paciente_id $where_clause GROUP BY p.id HAVING num_consultas > 2");
    $stmt_recurrentes->execute($params);
    $recurrentes = $stmt_recurrentes->fetchAll(PDO::FETCH_ASSOC);

    // Lista de doctores para filtro (asume todos usuarios son doctores o filtra por role si agregas)
    $doctores = $pdo->query("SELECT id, username FROM usuarios ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error en consultas: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Reportes y Analíticas</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .filtros-row { display: flex; gap: 10px; flex-wrap: wrap; }
        .filtros-row label { min-width: 150px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reportes y Analíticas</h1>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

        <!-- Filtros Avanzados -->
        <h3>Filtros Avanzados</h3>
        <form method="GET">
            <div class="filtros-row">
                <label>Desde: <input type="date" name="desde" value="<?= htmlspecialchars($fecha_desde) ?>"></label>
                <label>Hasta: <input type="date" name="hasta" value="<?= htmlspecialchars($fecha_hasta) ?>"></label>
                <label>Doctor: 
                    <select name="doctor">
                        <option value="">Todos</option>
                        <?php foreach ($doctores as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $doctor_id == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Tipo de Ingreso: 
                    <select name="tipo_ingreso">
                        <option value="">Todos</option>
                        <option value="consulta" <?= $tipo_ingreso == 'consulta' ? 'selected' : '' ?>>Consulta</option>
                        <option value="examen" <?= $tipo_ingreso == 'examen' ? 'selected' : '' ?>>Examen</option>
                        <option value="receta" <?= $tipo_ingreso == 'receta' ? 'selected' : '' ?>>Receta</option>
                        <option value="otro" <?= $tipo_ingreso == 'otro' ? 'selected' : '' ?>>Otro</option>
                    </select>
                </label>
                <label>Diagnóstico: <input type="text" name="diagnostico" value="<?= htmlspecialchars($diagnostico_busqueda) ?>" placeholder="Palabra clave..."></label>
            </div>
            <button type="submit">Filtrar</button>
            <a href="reportes.php" class="btn-export">Limpiar Filtros</a>
        </form>

        <!-- KPIs Básicos -->
        <h2>Estadísticas Generales (Filtradas)</h2>
        <div style="display: flex; justify-content: space-around; flex-wrap: wrap; margin: 20px 0;">
            <div style="text-align: center; margin: 10px; padding: 20px; background: #f8f9fa; border-radius: 8px; flex: 1; min-width: 200px;">
                <h3>Total Pacientes Atendidos</h3>
                <p style="font-size: 2em; color: #007bff;"><?= number_format($total_pacientes) ?></p>
            </div>
            <div style="text-align: center; margin: 10px; padding: 20px; background: #f8f9fa; border-radius: 8px; flex: 1; min-width: 200px;">
                <h3>Total Ingresos</h3>
                <p style="font-size: 2em; color: #28a745;">$<?= number_format($total_ingresos, 2) ?></p>
            </div>
            <div style="text-align: center; margin: 10px; padding: 20px; background: #f8f9fa; border-radius: 8px; flex: 1; min-width: 200px;">
                <h3>Ingreso Promedio</h3>
                <p style="font-size: 2em; color: #ffc107;">$<?= number_format($ingresos_promedio, 2) ?></p>
            </div>
        </div>

        <!-- Gráfico de Ingresos Mensuales -->
        <h2>Tendencias de Ingresos por Mes</h2>
        <canvas id="graficoIngresos" width="400" height="200" style="max-width: 100%;"></canvas>
        <script>
            const ctx = document.getElementById('graficoIngresos').getContext('2d');
            const meses = [<?php 
                $meses_js = [];
                foreach($datos_grafico as $d) {
                    $meses_js[] = "'Mes " . $d['mes'] . "'";
                    echo $meses_js[count($meses_js)-1] . ",";
                }
                if (empty($meses_js)) echo "''";
            ?>];
            const ingresos = [<?php 
                $ingresos_js = [];
                foreach($datos_grafico as $d) {
                    $ingresos_js[] = $d['ingresos'];
                    echo $ingresos_js[count($ingresos_js)-1] . ",";
                }
                if (empty($ingresos_js)) echo "0";
            ?>];
            new Chart(ctx, {
                type: 'bar',
                data: { labels: meses, datasets: [{ label: 'Ingresos ($)', data: ingresos, backgroundColor: '#007bff' }] },
                options: { responsive: true, scales: { y: { beginAtZero: true } } }
            });
        </script>

        <!-- Tabla de Pacientes Recurrentes -->
        <h2>Pacientes Recurrentes (>2 Consultas)</h2>
        <?php if (empty($recurrentes)): ?>
            <p>No hay pacientes recurrentes en el período.</p>
        <?php else: ?>
        <table>
            <thead><tr><th>Nombre</th><th>Consultas</th></tr></thead>
            <tbody>
                <?php foreach ($recurrentes as $r): ?>
                <tr><td><?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) ?></td><td><?= $r['num_consultas'] ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <nav>
            <a href="index.php">Menú Principal</a> | <a href="pacientes.php">Pacientes</a> | <a href="expedientes.php">Expedientes</a>
        </nav>
    </div>
</body>
</html>