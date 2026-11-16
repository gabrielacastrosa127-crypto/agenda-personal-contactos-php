<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

verificarAutenticacion();

$idUsuario = (int)$_SESSION['usuario_id'];

$stmt = $pdo->prepare('SELECT id, nombre, telefono, correo, ciudad FROM contactos WHERE id_usuario = :id_usuario ORDER BY id DESC');
$stmt->execute([':id_usuario' => $idUsuario]);
$contactos = $stmt->fetchAll();

$mensajes = [
    'creado'        => ['tipo' => 'success', 'texto' => 'Contacto registrado exitosamente.'],
    'editado'       => ['tipo' => 'success', 'texto' => 'Contacto actualizado exitosamente.'],
    'eliminado'     => ['tipo' => 'success', 'texto' => 'Contacto eliminado exitosamente.'],
    'no_autorizado' => ['tipo' => 'error',   'texto' => 'El contacto solicitado no existe o no tienes permiso para acceder a él.'],
];
$alerta = null;
if (isset($_GET['msg']) && isset($mensajes[$_GET['msg']])) {
    $alerta = $mensajes[$_GET['msg']];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Agenda de Contactos</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <header class="topbar">
            <div class="user-info">
                <h1>Agenda Personal</h1>
                <p><strong>Usuario:</strong> <?= e($_SESSION['usuario_nombre']) ?> | <strong>Correo:</strong> <?= e($_SESSION['usuario_correo']) ?></p>
            </div>
            <div>
                <a href="logout.php" class="btn btn-secondary">Cerrar sesión</a>
            </div>
        </header>

        <main class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <h2>Mis Contactos (<?= count($contactos) ?>)</h2>
                <a href="crear_contacto.php" class="btn btn-primary">+ Nuevo Contacto</a>
            </div>

            <?php if ($alerta): ?>
                <div class="alert alert-<?= e($alerta['tipo']) ?> mt-3"><?= e($alerta['texto']) ?></div>
            <?php endif; ?>

            <?php if (empty($contactos)): ?>
                <p class="mt-3" style="color: #64748b;">Aún no tienes contactos registrados. Haz clic en <strong>+ Nuevo Contacto</strong> para comenzar.</p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Teléfono</th>
                                <th>Correo electrónico</th>
                                <th>Ciudad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contactos as $contacto): ?>
                                <tr>
                                    <td><?= e($contacto['nombre']) ?></td>
                                    <td><?= e($contacto['telefono']) ?></td>
                                    <td><?= e($contacto['correo']) ?></td>
                                    <td><?= e($contacto['ciudad']) ?></td>
                                    <td class="actions">
                                        <a href="editar_contacto.php?id=<?= (int)$contacto['id'] ?>" class="btn btn-secondary btn-sm">Editar</a>

                                        <form method="POST" action="eliminar_contacto.php" onsubmit="return confirm('¿Estás seguro de que deseas eliminar a <?= e($contacto['nombre']) ?>? Esta acción no se puede deshacer.');" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="id" value="<?= (int)$contacto['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>