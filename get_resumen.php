<?php
require_once 'config.php';
$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT historial_medico, alergias FROM pacientes WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();
if ($p) {
    $historial = decryptData($p['historial_medico'], $encryption_key);
    $alergias = json_decode(decryptData($p['alergias'], $encryption_key), true);
    echo json_encode(['historial' => $historial, 'alergias' => implode(', ', $alergias ?? [])]);
} else {
    echo json_encode(['historial' => '', 'alergias' => '']);
}
?>