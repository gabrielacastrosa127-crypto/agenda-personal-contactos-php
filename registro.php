<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: panel.php');
    exit;
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $correo    = trim($_POST['correo'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar_password'] ?? '';

    if (!verificarCsrf($_POST['csrf_token'] ?? '')) {
        $errores[] = 'Solicitud no válida. Recarga la página e intenta de nuevo.';
    }
    if ($nombre === '' || mb_strlen($nombre) < 3) {
        $errores[] = 'El nombre completo es obligatorio (mínimo 3 caracteres).';
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresa un correo electrónico válido.';
    }
    if (strlen($password) < 6) {
        $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    }
    if ($password !== $confirmar) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if (empty($errores)) {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE correo = :correo LIMIT 1');
        $stmt->execute([':correo' => $correo]);

        if ($stmt->fetch()) {
            $errores[] = 'El correo electrónico ya se encuentra registrado.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $insert = $pdo->prepare('INSERT INTO usuarios (nombre, correo, password) VALUES (:nombre, :correo, :password)');
            $insert->execute([
                ':nombre'   => $nombre,
                ':correo'   => $correo,
                ':password' => $hash,
            ]);

            header('Location: index.php?registrado=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Agenda Personal</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="auth-container card">
        <h2>Crear Cuenta</h2>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-error">
                <ul style="margin-left: 18px;">
                    <?php foreach ($errores as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="registro.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <div class="form-group">
                <label for="nombre">Nombre completo</label>
                <input type="text" id="nombre" name="nombre" value="<?= e($_POST['nombre'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" value="<?= e($_POST['correo'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Contraseña (mínimo 6 caracteres)</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="confirmar_password">Confirmar contraseña</label>
                <input type="password" id="confirmar_password" name="confirmar_password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Registrarme</button>
        </form>

        <p class="text-center mt-3">
            ¿Ya tienes cuenta? <a href="index.php">Inicia sesión</a>
        </p>
    </div>
</body>
</html>