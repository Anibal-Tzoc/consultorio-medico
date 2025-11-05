<?php
require_once 'config.php';
header('Content-Type: application/json');

$busqueda = $_GET['buscar'] ?? '';
$filtro_fecha_desde = $_GET['fecha_desde'] ?? '';
$filtro_fecha_hasta = $_GET['fecha_hasta'] ?? '';

// Query con filtros (similar a expedientes.php)
$where_conditions = [];
$params = [];

if (!empty($busqueda)) {
    $where_conditions[] = "(p.nombre LIKE ? OR p.apellido LIKE ? OR e.diagnostico LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%"; // Nota: Para diagnóstico encriptado, filtra raw; desencripta después
}

if (!empty($filtro_fecha_desde)) {
    $where_conditions[] = "e.fecha_consulta >= ?";
    $params[] = $filtro_fecha_desde;
}

if (!empty($filtro_fecha_hasta)) {
    $where_conditions[] = "e.fecha_consulta <= ?";
    $params[] = $filtro_fecha_hasta;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    $query = "SELECT e.*, p.nombre, p.apellido FROM expedientes e JOIN pacientes p ON e.paciente_id = p.id $where_clause ORDER BY e.fecha_consulta DESC LIMIT 50"; // Límite para performance
    $stmt_list = $pdo->prepare($query);
    $stmt_list->execute($params);
    $expedientes = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

    // Desencripta para JSON
    $resultados = [];
    foreach ($expedientes as $exp) {
        $notas_dec = decryptData($exp['notas_consulta'], $encryption_key) ?: 'N/A';
        $diagnostico_dec = decryptData($exp['diagnostico'], $encryption_key) ?: 'N/A';
        $receta_dec = decryptData($exp['receta'], $encryption_key) ?: 'N/A';
        $resultados[] = [
            'id' => $exp['id'],
            'paciente' => htmlspecialchars($exp['nombre'] . ' ' . $exp['apellido']),
            'fecha' => $exp['fecha_consulta'],
            'ingreso' => number_format($exp['ingreso'], 2),
            'adjunto' => $exp['adjunto'] ? 'uploads/' . $exp['adjunto'] : null,
            'detalles' => [
                'notas' => htmlspecialchars($notas_dec),
                'diagnostico' => htmlspecialchars($diagnostico_dec),
                'receta' => htmlspecialchars($receta_dec)
            ]
        ];
    }

    echo json_encode(['expedientes' => $resultados, 'total' => count($resultados)]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>