<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

verificarAutenticacion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panel.php');
    exit;
}

if (!verificarCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: panel.php?msg=no_autorizado');
    exit;
}

$idContacto = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$idUsuario  = (int)$_SESSION['usuario_id'];

if (!$idContacto) {
    header('Location: panel.php?msg=no_autorizado');
    exit;
}

$stmt = $pdo->prepare('DELETE FROM contactos WHERE id = :id AND id_usuario = :id_usuario');
$stmt->execute([
    ':id'         => $idContacto,
    ':id_usuario' => $idUsuario,
]);

if ($stmt->rowCount() > 0) {
    header('Location: panel.php?msg=eliminado');
} else {
    header('Location: panel.php?msg=no_autorizado');
}
exit;