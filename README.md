# Agenda Personal de Contactos (PHP & MySQL)

## Descripción del proyecto
Aplicación web desarrollada en PHP y MySQL que implementa registro de usuarios, autenticación mediante sesiones y gestión privada de una agenda personal de contactos (CRUD). El sistema aplica controles de seguridad en el servidor para garantizar el aislamiento de datos entre usuarios y prevenir accesos no autorizados.

## Tecnologías utilizadas
- **Backend:** PHP 8.x (PDO para acceso seguro a datos)
- **Base de datos:** MySQL / MariaDB
- **Frontend:** HTML5 y CSS3 responsivo

## Requisitos para su ejecución
- Servidor web local con soporte para PHP 8.0 o superior (XAMPP, Laragon, WAMP o servidor embebido de PHP).
- Gestor de base de datos MySQL 5.7+ o MariaDB 10.4+.
- Extensión `pdo_mysql` habilitada en `php.ini`.

## Pasos para instalar la aplicación
1. **Clonar o descargar el repositorio:**
   Ubica la carpeta `agenda_contactos` dentro del directorio raíz de tu servidor web (por ejemplo, `C:\xampp\htdocs\agenda_contactos` o `C:\laragon\www\agenda_contactos`).
2. **Crear la base de datos:**
   - Abre phpMyAdmin o tu cliente MySQL.
   - Importa o ejecuta el script ubicado en `sql/database.sql` para crear la base de datos `agenda_contactos` y las tablas `usuarios` y `contactos`.
3. **Configurar la conexión:**
   - Abre `config/db.php` y ajusta las variables `$host`, `$user` y `$pass` según las credenciales de tu servidor MySQL local.
4. **Ejecutar la aplicación:**
   - Inicia los servicios de Apache y MySQL.
   - Abre en tu navegador: `http://localhost/agenda_contactos/`