<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

verificarAutenticacion();

$idUsuario  = (int)$_SESSION['usuario_id'];
$idContacto = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idContacto) {
    header('Location: panel.php?msg=no_autorizado');
    exit;
}

$stmt = $pdo->prepare('SELECT id, nombre, telefono, correo, ciudad FROM contactos WHERE id = :id AND id_usuario = :id_usuario LIMIT 1');
$stmt->execute([
    ':id'         => $idContacto,
    ':id_usuario' => $idUsuario,
]);
$contacto = $stmt->fetch();

if (!$contacto) {
    header('Location: panel.php?msg=no_autorizado');
    exit;
}

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
        $errores[] = 'El teléfono es obligatorio.';
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo electrónico no es válido.';
    }
    if ($ciudad === '') {
        $errores[] = 'La ciudad es obligatoria.';
    }

    if (empty($errores)) {
        $update = $pdo->prepare('UPDATE contactos SET nombre = :nombre, telefono = :telefono, correo = :correo, ciudad = :ciudad WHERE id = :id AND id_usuario = :id_usuario');
        $update->execute([
            ':nombre'     => $nombre,
            ':telefono'   => $telefono,
            ':correo'     => $correo,
            ':ciudad'     => $ciudad,
            ':id'         => $idContacto,
            ':id_usuario' => $idUsuario,
        ]);

        header('Location: panel.php?msg=editado');
        exit;
    }

    $contacto['nombre']   = $nombre;
    $contacto['telefono'] = $telefono;
    $contacto['correo']   = $correo;
    $contacto['ciudad']   = $ciudad;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Contacto - Agenda Personal</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="auth-container card">
        <h2>Editar Contacto</h2>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-error">
                <ul style="margin-left: 18px;">
                    <?php foreach ($errores as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="editar_contacto.php?id=<?= (int)$contacto['id'] ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <div class="form-group">
                <label for="nombre">Nombre completo</label>
                <input type="text" id="nombre" name="nombre" value="<?= e($contacto['nombre']) ?>" required>
            </div>

            <div class="form-group">
                <label for="telefono">Teléfono</label>
                <input type="tel" id="telefono" name="telefono" value="<?= e($contacto['telefono']) ?>" required>
            </div>

            <div class="form-group">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" value="<?= e($contacto['correo']) ?>" required>
            </div>

            <div class="form-group">
                <label for="ciudad">Ciudad</label>
                <input type="text" id="ciudad" name="ciudad" value="<?= e($contacto['ciudad']) ?>" required>
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-primary">Actualizar Contacto</button>
                <a href="panel.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>