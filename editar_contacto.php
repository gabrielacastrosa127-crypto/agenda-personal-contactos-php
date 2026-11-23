<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

verificarAutenticacion();

$idUsuario  = (int)$_SESSION['usuario_id'];
$idContacto = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

// Verificar que el contacto exista y pertenezca al usuario autenticado
$stmt = $pdo->prepare('SELECT * FROM contactos WHERE id = :id AND id_usuario = :id_usuario');
$stmt->execute([
    ':id'         => $idContacto,
    ':id_usuario' => $idUsuario,
]);
$contacto = $stmt->fetch();

if (!$contacto) {
    header('Location: panel.php?msg=no_encontrado');
    exit;
}

$categorias  = $pdo->query('SELECT id, nombre FROM categorias ORDER BY id ASC')->fetchAll();
$errores     = [];
$nombre      = $contacto['nombre'];
$telefono    = $contacto['telefono'];
$correo      = $contacto['correo'] ?? '';
$ciudad      = $contacto['ciudad'] ?? '';
$idCategoria = (int)$contacto['id_categoria'];
$favorito    = (int)$contacto['favorito'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarCsrf($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido. Recarga la página e inténtalo de nuevo.';
    }

    $nombre      = trim($_POST['nombre'] ?? '');
    $telefono    = trim($_POST['telefono'] ?? '');
    $correo      = trim($_POST['correo'] ?? '');
    $ciudad      = trim($_POST['ciudad'] ?? '');
    $idCategoria = (int)($_POST['id_categoria'] ?? 5);
    $favorito    = isset($_POST['favorito']) ? 1 : 0;

    if ($nombre === '') {
        $errores[] = 'El nombre del contacto es obligatorio.';
    }

    $soloDigitos = preg_replace('/\D/', '', $telefono);
    if ($telefono === '') {
        $errores[] = 'El teléfono es obligatorio.';
    } elseif (!preg_match('/^[0-9+\-\s()]+$/', $telefono) || strlen($soloDigitos) < 7 || strlen($soloDigitos) > 15) {
        $errores[] = 'El teléfono debe contener entre 7 y 15 dígitos numéricos válidos.';
    }

    if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El formato del correo electrónico no es válido.';
    }

    $idsValidos = array_map('intval', array_column($categorias, 'id'));
    if (!in_array($idCategoria, $idsValidos, true)) {
        $idCategoria = 5;
    }

    if (empty($errores)) {
        $stmtUpdate = $pdo->prepare(
            'UPDATE contactos
             SET id_categoria = :id_categoria,
                 nombre = :nombre,
                 telefono = :telefono,
                 correo = :correo,
                 ciudad = :ciudad,
                 favorito = :favorito
             WHERE id = :id AND id_usuario = :id_usuario'
        );
        $stmtUpdate->execute([
            ':id_categoria' => $idCategoria,
            ':nombre'       => $nombre,
            ':telefono'     => $telefono,
            ':correo'       => $correo !== '' ? $correo : null,
            ':ciudad'       => $ciudad !== '' ? $ciudad : null,
            ':favorito'     => $favorito,
            ':id'           => $idContacto,
            ':id_usuario'   => $idUsuario,
        ]);

        header('Location: panel.php?msg=actualizado');
        exit;
    }
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
    <div class="container form-container">
        <h1>Editar Contacto</h1>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="editar_contacto.php?id=<?= (int)$idContacto ?>" class="contact-form">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="id" value="<?= (int)$idContacto ?>">

            <div class="form-group">
                <label for="nombre">Nombre *</label>
                <input type="text" id="nombre" name="nombre" value="<?= e($nombre) ?>" required>
            </div>

            <div class="form-group">
                <label for="telefono">Teléfono *</label>
                <input type="tel" id="telefono" name="telefono" value="<?= e($telefono) ?>"
                       pattern="[0-9+\-\s()]{7,20}"
                       title="Ingrese un número de teléfono válido (mínimo 7 dígitos)" required>
            </div>

            <div class="form-group">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" value="<?= e($correo) ?>">
            </div>

            <div class="form-group">
                <label for="ciudad">Ciudad</label>
                <input type="text" id="ciudad" name="ciudad" value="<?= e($ciudad) ?>">
            </div>

            <div class="form-group">
                <label for="id_categoria">Categoría *</label>
                <select id="id_categoria" name="id_categoria">
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= (int)$cat['id'] === $idCategoria ? 'selected' : '' ?>>
                            <?= e($cat['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="favorito" value="1" <?= $favorito === 1 ? 'checked' : '' ?>>
                    Marcar como contacto favorito
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                <a href="panel.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>