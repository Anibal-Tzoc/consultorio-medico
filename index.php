<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head><title>Consultorio Médico</title><link rel="stylesheet" href="styles.css"></head>
<body>
    <div class="container">
        <h1>Bienvenido, <?= htmlspecialchars($_SESSION['username']) ?>!</h1> <!-- ¡Cambio: username en vez de user_id! -->
        <nav>
            <a href="pacientes.php">Gestión de Pacientes</a> | 
            <a href="expedientes.php">Expedientes</a> | 
            <a href="reportes.php">Reportes</a> | 
            <a href="portal-paciente.php">Portal del Paciente</a> | 
            <a href="logout.php">Salir</a>
        </nav>
    </div>
</body>
</html>