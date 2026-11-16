<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

verificarAutenticacion();

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo   = trim($_POST['correo'] ?? '');
    $ciudad   = trim($_POST['ciudad'] ?? '');

    if (!verificarCsrf($_POST['csrf_token'] ?? '')) {
        $errores[] = 'Solicitud no válida.';
    }
    if ($nombre === '') {
        $errores[] = 'El nombre del contacto es obligatorio.';
    }
    if ($telefono === '') {
        $errores[] = 'El número telefónico es obligatorio.';
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo electrónico no es válido.';
    }
    if ($ciudad === '') {
        $errores[] = 'La ciudad es obligatoria.';
    }

    if (empty($errores)) {
        $stmt = $pdo->prepare('INSERT INTO contactos (id_usuario, nombre, telefono, correo, ciudad) VALUES (:id_usuario, :nombre, :telefono, :correo, :ciudad)');
        $stmt->execute([
            ':id_usuario' => (int)$_SESSION['usuario_id'],
            ':nombre'     => $nombre,
            ':telefono'   => $telefono,
            ':correo'     => $correo,
            ':ciudad'     => $ciudad,
        ]);

        header('Location: panel.php?msg=creado');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Contacto - Agenda Personal</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="auth-container card">
        <h2>Registrar Contacto</h2>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-error">
                <ul style="margin-left: 18px;">
                    <?php foreach ($errores as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="crear_contacto.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <div class="form-group">
                <label for="nombre">Nombre completo</label>
                <input type="text" id="nombre" name="nombre" value="<?= e($_POST['nombre'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="telefono">Teléfono</label>
                <input type="tel" id="telefono" name="telefono" value="<?= e($_POST['telefono'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" value="<?= e($_POST['correo'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="ciudad">Ciudad</label>
                <input type="text" id="ciudad" name="ciudad" value="<?= e($_POST['ciudad'] ?? '') ?>" required>
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-primary">Guardar Contacto</button>
                <a href="panel.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>