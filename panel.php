<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

verificarAutenticacion();

$idUsuario = (int)$_SESSION['usuario_id'];

// Acción rápida por POST para alternar favorito (Opción D)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'toggle_favorito') {
    if (verificarCsrf($_POST['csrf_token'] ?? null)) {
        $idContacto = (int)($_POST['id_contacto'] ?? 0);
        $stmtFav = $pdo->prepare(
            'UPDATE contactos
             SET favorito = IF(favorito = 1, 0, 1)
             WHERE id = :id AND id_usuario = :id_usuario'
        );
        $stmtFav->execute([
            ':id'         => $idContacto,
            ':id_usuario' => $idUsuario,
        ]);
    }
    $queryString = !empty($_GET) ? '?' . http_build_query($_GET) : '';
    header('Location: panel.php' . $queryString);
    exit;
}

// Cargar categorías para el filtro (Opción C)
$categorias = $pdo->query('SELECT id, nombre FROM categorias ORDER BY id ASC')->fetchAll();

// Parámetros GET de búsqueda, ordenamiento, categoría y favoritos
$buscar          = trim($_GET['buscar'] ?? '');
$orden           = $_GET['orden'] ?? 'nombre_asc';
$categoriaFiltro = (int)($_GET['categoria'] ?? 0);
$soloFavoritos   = isset($_GET['favoritos']) && $_GET['favoritos'] === '1';

// Construcción dinámica y segura de la consulta
$condiciones = ['c.id_usuario = :id_usuario'];
$parametros  = [':id_usuario' => $idUsuario];

// Opción A: Búsqueda por nombre, correo o ciudad
if ($buscar !== '') {
    $condiciones[] = '(c.nombre LIKE :buscar_nombre OR c.correo LIKE :buscar_correo OR c.ciudad LIKE :buscar_ciudad)';
    $termino = '%' . $buscar . '%';
    $parametros[':buscar_nombre'] = $termino;
    $parametros[':buscar_correo'] = $termino;
    $parametros[':buscar_ciudad'] = $termino;
}

// Opción C: Filtro por categoría
if ($categoriaFiltro > 0) {
    $condiciones[] = 'c.id_categoria = :id_categoria';
    $parametros[':id_categoria'] = $categoriaFiltro;
}

// Opción D: Visualizar únicamente favoritos
if ($soloFavoritos) {
    $condiciones[] = 'c.favorito = 1';
}

// Opción B: Ordenamiento seguro por lista blanca
$orderBy = match ($orden) {
    'nombre_desc' => 'c.nombre DESC',
    'ciudad_asc'  => "CASE WHEN c.ciudad IS NULL OR c.ciudad = '' THEN 1 ELSE 0 END, c.ciudad ASC, c.nombre ASC",
    'fecha_desc'  => 'c.fecha_creacion DESC',
    'fecha_asc'   => 'c.fecha_creacion ASC',
    default       => 'c.nombre ASC',
};

$sql = "SELECT c.*, cat.nombre AS categoria_nombre
        FROM contactos c
        INNER JOIN categorias cat ON c.id_categoria = cat.id
        WHERE " . implode(' AND ', $condiciones) . "
        ORDER BY $orderBy";

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$contactos = $stmt->fetchAll();

// Mensajes de alerta conservando tu variable $alerta
$alerta = null;
$mapaMensajes = [
    'creado'        => ['tipo' => 'success', 'texto' => 'Contacto creado correctamente.'],
    'actualizado'   => ['tipo' => 'success', 'texto' => 'Contacto actualizado correctamente.'],
    'eliminado'     => ['tipo' => 'success', 'texto' => 'Contacto eliminado correctamente.'],
    'no_encontrado' => ['tipo' => 'error',   'texto' => 'El contacto solicitado no existe o no tienes permiso para verlo.'],
    'error'         => ['tipo' => 'error',   'texto' => 'Ocurrió un error al procesar la solicitud.'],
];
if (isset($_GET['msg'], $mapaMensajes[$_GET['msg']])) {
    $alerta = $mapaMensajes[$_GET['msg']];
}

$hayFiltrosActivos = ($buscar !== '' || $categoriaFiltro > 0 || $soloFavoritos);
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

            <!-- Barra de búsqueda, ordenamiento, categoría y favoritos (Opciones A, B, C y D) -->
            <form method="GET" action="panel.php" class="filtros-form mt-3">
                <div class="filtro-item">
                    <label for="buscar">Buscar</label>
                    <input type="text" id="buscar" name="buscar" value="<?= e($buscar) ?>"
                           placeholder="Nombre, correo o ciudad...">
                </div>

                <div class="filtro-item">
                    <label for="categoria">Categoría</label>
                    <select id="categoria" name="categoria">
                        <option value="0">Todas las categorías</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= $categoriaFiltro === (int)$cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filtro-item">
                    <label for="orden">Ordenar por</label>
                    <select id="orden" name="orden">
                        <option value="nombre_asc" <?= $orden === 'nombre_asc' ? 'selected' : '' ?>>Nombre (A - Z)</option>
                        <option value="nombre_desc" <?= $orden === 'nombre_desc' ? 'selected' : '' ?>>Nombre (Z - A)</option>
                        <option value="ciudad_asc" <?= $orden === 'ciudad_asc' ? 'selected' : '' ?>>Ciudad (A - Z)</option>
                        <option value="fecha_desc" <?= $orden === 'fecha_desc' ? 'selected' : '' ?>>Fecha de creación (Recientes)</option>
                        <option value="fecha_asc" <?= $orden === 'fecha_asc' ? 'selected' : '' ?>>Fecha de creación (Antiguos)</option>
                    </select>
                </div>

                <div class="filtro-item filtro-check">
                    <label>
                        <input type="checkbox" name="favoritos" value="1" <?= $soloFavoritos ? 'checked' : '' ?>>
                        Solo favoritos (★)
                    </label>
                </div>

                <div class="filtro-botones">
                    <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                    <a href="panel.php" class="btn btn-secondary btn-sm">Limpiar</a>
                </div>
            </form>

            <?php if (empty($contactos)): ?>
                <?php if ($hayFiltrosActivos): ?>
                    <p class="mt-3" style="color: #64748b;">No se encontraron contactos que coincidan con los filtros aplicados.</p>
                <?php else: ?>
                    <p class="mt-3" style="color: #64748b;">Aún no tienes contactos registrados. Haz clic en <strong>+ Nuevo Contacto</strong> para comenzar.</p>
                <?php endif; ?>
            <?php else: ?>
                <div class="table-wrapper mt-3">
                    <table>
                        <thead>
                            <tr>
                                <th>Fav</th>
                                <th>Nombre</th>
                                <th>Teléfono</th>
                                <th>Correo electrónico</th>
                                <th>Ciudad</th>
                                <th>Categoría</th>
                                <th>Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contactos as $contacto): ?>
                                <?php $queryActual = !empty($_GET) ? '?' . e(http_build_query($_GET)) : ''; ?>
                                <tr>
                                    <td>
                                        <form method="POST" action="panel.php<?= $queryActual ?>" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="accion" value="toggle_favorito">
                                            <input type="hidden" name="id_contacto" value="<?= (int)$contacto['id'] ?>">
                                            <button type="submit"
                                                    class="btn-favorito <?= (int)$contacto['favorito'] === 1 ? 'activo' : '' ?>"
                                                    title="<?= (int)$contacto['favorito'] === 1 ? 'Quitar de favoritos' : 'Marcar como favorito' ?>">
                                                <?= (int)$contacto['favorito'] === 1 ? '★' : '☆' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td><strong><?= e($contacto['nombre']) ?></strong></td>
                                    <td><?= e($contacto['telefono']) ?></td>
                                    <td><?= e($contacto['correo'] ?: '—') ?></td>
                                    <td><?= e($contacto['ciudad'] ?: '—') ?></td>
                                    <td>
                                        <span class="badge-categoria cat-<?= (int)$contacto['id_categoria'] ?>">
                                            <?= e($contacto['categoria_nombre']) ?>
                                        </span>
                                    </td>
                                    <td><?= e(date('d/m/Y', strtotime($contacto['fecha_creacion']))) ?></td>
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