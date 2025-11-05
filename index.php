<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head><title>Consultorio Médico</title> <link rel="stylesheet" href="styles.css">

</head>
<body>
    <h1>Bienvenido, <?= $_SESSION['user_id'] ?>!</h1>
    <nav><a href="expedientes.php">Expedientes</a> | <a href="portal-paciente.php">Portal del Paciente</a> |


        <a href="pacientes.php">Gestión de Pacientes</a> | 
        <a href="reportes.php">Reportes</a> | 
        <a href="logout.php">Salir</a>
    </nav>
</body>
</html>