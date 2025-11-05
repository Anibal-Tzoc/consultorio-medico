<?php
require_once 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Filtros por fecha (opcional) - Usar prepared para seguridad
$fecha_desde = $_GET['desde'] ?? date('Y-m-01'); // Primer día del mes
$fecha_hasta = $_GET['hasta'] ?? date('Y-m-t'); // Último día del mes

// Queries mejoradas con alias 'e' en FROM y prepared statements
try {
    // Total pacientes (DISTINCT en el período)
    $stmt_pacientes = $pdo->prepare("SELECT COUNT(DISTINCT paciente_id) FROM expedientes e WHERE e.fecha_consulta BETWEEN ? AND ?");
    $stmt_pacientes->execute([$fecha_desde, $fecha_hasta]);
    $total_pacientes = $stmt_pacientes->fetchColumn();

    // Total ingresos
    $stmt_ingresos = $pdo->prepare("SELECT SUM(e.ingreso) FROM expedientes e WHERE e.fecha_consulta BETWEEN ? AND ?");
    $stmt_ingresos->execute([$fecha_desde, $fecha_hasta]);
    $total_ingresos = $stmt_ingresos->fetchColumn() ?: 0;

    // Promedio
    $stmt_total_consultas = $pdo->prepare("SELECT COUNT(*) FROM expedientes e WHERE e.fecha_consulta BETWEEN ? AND ?");
    $stmt_total_consultas->execute([$fecha_desde, $fecha_hasta]);
    $total_consultas = $stmt_total_consultas->fetchColumn();
    $ingresos_promedio = $total_consultas > 0 ? $total_ingresos / $total_consultas : 0;

    // Tendencias mensuales (agrupado por mes en el período)
    $stmt_mensual = $pdo->prepare("SELECT MONTH(e.fecha_consulta) as mes, SUM(e.ingreso) as ingresos FROM expedientes e WHERE e.fecha_consulta BETWEEN ? AND ? GROUP BY mes ORDER BY mes");
    $stmt_mensual->execute([$fecha_desde, $fecha_hasta]);
    $datos_grafico = $stmt_mensual->fetchAll(PDO::FETCH_ASSOC);

    // Pacientes recurrentes (>2 consultas en el período)
    $stmt_recurrentes = $pdo->prepare("SELECT p.nombre, p.apellido, COUNT(e.id) as num_consultas FROM pacientes p JOIN expedientes e ON p.id = e.paciente_id WHERE e.fecha_consulta BETWEEN ? AND ? GROUP BY p.id HAVING num_consultas > 2");
    $stmt_recurrentes->execute([$fecha_desde, $fecha_hasta]);
    $recurrentes = $stmt_recurrentes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error en consultas: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Reportes y Analíticas</title>
    <link rel="stylesheet" href="styles.css"> <!-- Tu CSS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <h1>Reportes y Analíticas</h1>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

        <!-- Filtros -->
        <h3>Filtros por Fecha</h3>
        <form method="GET">
            Desde: <input type="date" name="desde" value="<?= htmlspecialchars($fecha_desde) ?>"><br>
            Hasta: <input type="date" name="hasta" value="<?= htmlspecialchars($fecha_hasta) ?>"><br>
            <button type="submit">Filtrar</button>
        </form>

        <!-- KPIs Básicos -->
        <h2>Estadísticas Generales</h2>
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
                data: { 
                    labels: meses, 
                    datasets: [{ 
                        label: 'Ingresos ($)', 
                        data: ingresos, 
                        backgroundColor: '#007bff',
                        borderColor: '#0056b3',
                        borderWidth: 1
                    }] 
                },
                options: { 
                    responsive: true,
                    scales: { y: { beginAtZero: true } },
                    plugins: { legend: { display: true } }
                }
            });
        </script>

        <!-- Tabla de Pacientes Recurrentes -->
        <h2>Pacientes Recurrentes (>2 Consultas)</h2>
        <?php if (empty($recurrentes)): ?>
            <p>No hay pacientes recurrentes en el período seleccionado.</p>
        <?php else: ?>
        <table>
            <thead><tr><th>Nombre</th><th>Número de Consultas</th></tr></thead>
            <tbody>
                <?php foreach ($recurrentes as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) ?></td>
                    <td><?= $r['num_consultas'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <nav>
            <a href="index.php">Menú Principal</a> | 
            <a href="pacientes.php">Pacientes</a> | 
            <a href="expedientes.php">Expedientes</a>
        </nav>
    </div>
</body>
</html>