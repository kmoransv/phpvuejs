# Guía Práctica — PHP Puro MVC + Vue.js 3 + DataTables Server-side

**Proyecto:** `phpvuejs`
**Stack:** PHP 8.1 puro (sin framework) · SQLite (PDO) · Vue.js 3 (CDN) · jQuery DataTables (CDN) · Bootstrap 5.3 · SweetAlert2

---

## 1. Requisitos previos (Windows + XAMPP)

| Herramienta | Verificación en CMD |
|---|---|
| XAMPP (PHP 8.1+) | `php -v` |
| Apache con `mod_rewrite` | Activo por defecto en XAMPP |
| Extensiones PHP | `pdo_sqlite`, `sqlite3` (activas por defecto) |
| Git | `git --version` |

---

## 2. Ubicación del proyecto

```
C:\xampp\htdocs\
```

Abrir CMD:

```cmd
cd C:\xampp\htdocs
```

---

## 3. Crear estructura del proyecto a mano

A diferencia de Laravel o CodeIgniter, aquí **no hay comando que genere el esqueleto**. Creamos la estructura de carpetas y archivos manualmente.

### 3.1 Estructura final

```
phpvuejs/
├── app/
│   ├── core/
│   │   ├── App.php          ← Router manual (parsea URL)
│   │   ├── Controller.php   ← Clase base con helpers
│   │   ├── Database.php     ← Conexión PDO a SQLite (Singleton)
│   │   └── helpers.php      ← csrf_token(), verify_csrf()
│   ├── controllers/
│   │   ├── AutoresController.php  ← CRUD + endpoint DataTable
│   │   ├── LibrosController.php   ← CRUD + endpoint DataTable
│   │   └── DashboardController.php ← Endpoint JSON para Vue.js
│   ├── models/
│   │   ├── Autor.php        ← CRUD + paginación
│   │   └── Libro.php        ← CRUD + paginación
│   └── views/
│       ├── layouts/
│       │   ├── header.php    ← Layout superior
│       │   └── footer.php    ← Layout inferior
│       ├── autores/
│       │   ├── index.php
│       │   ├── create.php
│       │   └── edit.php
│       └── libros/
│           ├── index.php
│           ├── create.php
│           └── edit.php
├── database/
│   ├── init.sql             ← Script SQL
│   ├── seed.php             ← Llena BD con 10 autores y 15 libros
│   └── database.sqlite      ← (NO se sube a git)
├── public/
│   ├── .htaccess            ← Reescribe URLs a index.php
│   └── index.php            ← Front controller (única entrada)
└── README.md
```

### 3.2 Crear carpetas

```cmd
cd C:\xampp\htdocs
mkdir phpvuejs
cd phpvuejs
mkdir app\core app\controllers app\models app\views\layouts app\views\autores app\views\libros database public
```

---

## 4. Conceptos fundamentales del patrón MVC

| Capa | Responsabilidad |
|---|---|
| **Modelo** | Acceso a datos (SQL a mano con PDO) |
| **Vista** | Solo HTML/PHP para presentar los datos |
| **Controlador** | Recibe la petición, llama al modelo, decide qué vista mostrar |

**Flujo de una petición:**

```
URL: /autores/edit/3
       ↓
.htaccess → public/index.php (front controller)
       ↓
App.php parsea: controlador = Autores, método = edit, param = 3
       ↓
AutoresController->edit(3)
       ↓
Autor::find(3) ← PDO query
       ↓
view('autores/edit', ['autor' => $data]) ← carga header + vista + footer
       ↓
HTML al navegador
```

---

## 5. Front Controller (`public/index.php`)

Único punto de entrada. Apache reescribe todas las URLs hacia aquí.

```php
<?php

require_once __DIR__ . '/../app/core/App.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/helpers.php';

$app = new App();
$app->run();
```

---

## 6. Reescritura de URLs (`public/.htaccess`)

```apache
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
```

> **Qué hace:** si la URL no apunta a un archivo/carpeta real, redirige a `index.php?url=lo_que_sea`.

---

## 7. Router (`app/core/App.php`)

```php
<?php

class App
{
    private string $controllerClass = 'AutoresController';
    private string $method = 'index';
    private array $params = [];
    private $controllerInstance;
    private bool $dispatched = false;

    public function __construct()
    {
        session_start();
        $url = $this->parseUrl();

        // API: /api/contadores -> DashboardController@contadores
        if (!empty($url[0]) && $url[0] === 'api' && !empty($url[1]) && $url[1] === 'contadores') {
            require_once __DIR__ . '/../controllers/DashboardController.php';
            $this->controllerInstance = new DashboardController();
            $this->method = 'contadores';
            $this->params = array_slice($url, 2);
            $this->dispatched = true;
        }

        if (!empty($url[0])) {
            if ($this->dispatched) {
                // ya manejado arriba
            } elseif ($url[0] === 'api') {
                // otros endpoints api no implementados
            } else {
                $candidate = ucfirst($url[0]) . 'Controller';
                if (file_exists(__DIR__ . '/../controllers/' . $candidate . '.php')) {
                    $this->controllerClass = $candidate;
                    unset($url[0]);
                }
            }
        }

        if (!$this->dispatched) {
            require_once __DIR__ . '/../controllers/' . $this->controllerClass . '.php';
            $this->controllerInstance = new $this->controllerClass();

            if (!empty($url[1])) {
                if (method_exists($this->controllerInstance, $url[1])) {
                    $this->method = $url[1];
                    unset($url[0], $url[1]);
                } else {
                    unset($url[0]);
                }
            }

            $this->params = $url ? array_values($url) : [];
        }
    }

    public function run(): void
    {
        try {
            call_user_func_array([$this->controllerInstance, $this->method], $this->params);
        } catch (Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error'   => 'Server error',
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
        }
    }

    private function parseUrl(): array
    {
        $url = $_GET['url'] ?? '';
        return explode('/', filter_var(trim($url, '/'), FILTER_SANITIZE_URL));
    }
}
```

**Convenciones de URL:**
- `/autores` → `AutoresController@index`
- `/autores/create` → `AutoresController@create`
- `/autores/store` → `AutoresController@store`
- `/autores/edit/3` → `AutoresController@edit(3)`
- `/autores/update/3` → `AutoresController@update(3)`
- `/autores/destroy/3` → `AutoresController@destroy(3)`
- `/api/contadores` → `DashboardController@contadores`

> **Patrón:** `/controlador/método/paramo1/paramo2/...`

---

## 8. Helpers (`app/core/helpers.php`)

```php
<?php

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_token'] ?? '';
    if (!hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        http_response_code(419);
        die('CSRF token mismatch.');
    }
}
```

---

## 9. Conexión a la BD (`app/core/Database.php`)

Patrón Singleton: solo se crea una conexión PDO durante toda la petición.

```php
<?php

class Database
{
    private static $instance = null;

    public static function connect(): PDO
    {
        if (self::$instance === null) {
            $dbPath = __DIR__ . '/../../database/database.sqlite';
            self::$instance = new PDO('sqlite:' . $dbPath);
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$instance->exec('PRAGMA foreign_keys = ON');
        }
        return self::$instance;
    }
}
```

> **Importante:** `PRAGMA foreign_keys = ON` activa las FK en SQLite (que están desactivadas por defecto).

---

## 10. Clase Controller base (`app/core/Controller.php`)

```php
<?php

class Controller
{
    public function model(string $model)
    {
        require_once __DIR__ . '/../models/' . $model . '.php';
        return new $model();
    }

    public function view(string $view, array $data = []): void
    {
        extract($data);
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/' . $view . '.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function redirect(string $url): void
    {
        $base = '/fdtw2-2026/periodo2/phpvuejs/public';
        header('Location: ' . $base . $url);
        exit;
    }

    public function input(string $key): ?string
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? null;
        return $value !== null ? htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8') : null;
    }

    public function inputRaw(string $key): ?string
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? null;
        return $value !== null ? trim($value) : null;
    }

    public function flash(string $key, $value): void
    {
        $_SESSION['flash'][$key] = $value;
    }

    public function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
```

> **Nota:** los alumnos deben ajustar `$base` en `redirect()` según su ruta.

---

## 11. Script SQL (`database/init.sql`)

```sql
CREATE TABLE IF NOT EXISTS autores (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre           VARCHAR(150) NOT NULL,
    nacionalidad     VARCHAR(100),
    fecha_nacimiento DATE,
    biografia        TEXT,
    created_at       DATETIME,
    updated_at       DATETIME
);

CREATE TABLE IF NOT EXISTS libros (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    titulo            VARCHAR(200) NOT NULL,
    isbn              VARCHAR(20) NOT NULL UNIQUE,
    editorial         VARCHAR(100),
    anio_publicacion  INTEGER,
    autor_id          INTEGER NOT NULL,
    created_at        DATETIME,
    updated_at        DATETIME,
    FOREIGN KEY (autor_id) REFERENCES autores(id) ON DELETE CASCADE
);
```

---

## 12. Seeder PHP (`database/seed.php`)

`database/seed.php`:

```php
<?php

require_once __DIR__ . '/../app/core/Database.php';

$db = Database::connect();

$db->exec("
    CREATE TABLE IF NOT EXISTS autores (
        id               INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre           VARCHAR(150) NOT NULL,
        nacionalidad     VARCHAR(100),
        fecha_nacimiento DATE,
        biografia        TEXT,
        created_at       DATETIME,
        updated_at       DATETIME
    )
");

$db->exec("
    CREATE TABLE IF NOT EXISTS libros (
        id                INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo            VARCHAR(200) NOT NULL,
        isbn              VARCHAR(20) NOT NULL UNIQUE,
        editorial         VARCHAR(100),
        anio_publicacion  INTEGER,
        autor_id          INTEGER NOT NULL,
        created_at        DATETIME,
        updated_at        DATETIME,
        FOREIGN KEY (autor_id) REFERENCES autores(id) ON DELETE CASCADE
    )
");

$db->exec("DELETE FROM libros");
$db->exec("DELETE FROM autores");
$db->exec("DELETE FROM sqlite_sequence WHERE name IN ('autores', 'libros')");

$autores = [
    ['Gabriel García Márquez', 'Colombiana', '1927-03-06', 'Escritor, guionista y periodista colombiano, ganador del Premio Nobel de Literatura en 1982.'],
    ['Isabel Allende',          'Chilena',    '1942-08-02', 'Escritora chilena, autora de "La casa de los espíritus".'],
    ['Jorge Luis Borges',       'Argentina',  '1899-08-24', 'Escritor argentino, uno de los autores más destacados de la literatura del siglo XX.'],
    ['Pablo Neruda',            'Chilena',    '1904-07-12', 'Poeta chileno, ganador del Premio Nobel de Literatura en 1971.'],
    ['Mario Vargas Llosa',      'Peruana',    '1936-03-28', 'Escritor peruano, ganador del Premio Nobel de Literatura en 2010.'],
    ['Octavio Paz',             'Mexicana',   '1914-03-31', 'Poeta y ensayista mexicano, Premio Nobel de Literatura 1990.'],
    ['Miguel de Cervantes',     'Española',   '1547-09-29', 'Escritor español, autor de "Don Quijote de la Mancha".'],
    ['Federico García Lorca',   'Española',   '1898-06-05', 'Poeta y dramaturgo español, miembro de la Generación del 27.'],
    ['Julio Cortázar',          'Argentina',  '1914-08-26', 'Escritor argentino, autor de "Rayuela" entre otras obras.'],
    ['Carlos Fuentes',          'Mexicana',   '1928-11-11', 'Escritor y diplomático mexicano, autor de "La región más transparente".'],
];

$stmtAutor = $db->prepare('INSERT INTO autores (nombre, nacionalidad, fecha_nacimiento, biografia, created_at) VALUES (?, ?, ?, ?, ?)');

foreach ($autores as [$nombre, $nacionalidad, $fechaNac, $bio]) {
    $stmtAutor->execute([$nombre, $nacionalidad, $fechaNac, $bio, date('Y-m-d H:i:s')]);
}

$libros = [
    ['Cien años de soledad',              '978-0307474728', 'Sudamericana',        1967, 1],
    ['El amor en los tiempos del cólera', '978-0307389732', 'Sudamericana',        1985, 1],
    ['La casa de los espíritus',          '978-1501117015', 'Plaza & Janés',       1982, 2],
    ['Eva Luna',                          '978-1501117053', 'Plaza & Janés',       1987, 2],
    ['Ficciones',                         '978-0802130303', 'Emecé',               1944, 3],
    ['El Aleph',                          '978-0142437880', 'Emecé',               1949, 3],
    ['Veinte poemas de amor',             '978-9500722038', 'Nascimiento',         1924, 4],
    ['Canto general',                     '978-9500722045', 'Nascimiento',         1950, 4],
    ['La ciudad y los perros',            '978-8420474489', 'Seix Barral',         1963, 5],
    ['Conversación en La Catedral',       '978-8420474496', 'Seix Barral',         1969, 5],
    ['El laberinto de la soledad',        '978-9685208550', 'Fondo de Cultura',    1950, 6],
    ['Don Quijote de la Mancha',          '978-8424116058', 'Francisco de Robles', 1605, 7],
    ['Bodas de sangre',                   '978-8437604804', 'Cruz y Raya',         1933, 8],
    ['Rayuela',                           '978-0394752846', 'Sudamericana',        1963, 9],
    ['La región más transparente',        '978-9685208697', 'Fondo de Cultura',    1958, 10],
];

$stmtLibro = $db->prepare('INSERT INTO libros (titulo, isbn, editorial, anio_publicacion, autor_id, created_at) VALUES (?, ?, ?, ?, ?, ?)');

foreach ($libros as [$titulo, $isbn, $editorial, $anio, $autorId]) {
    $stmtLibro->execute([$titulo, $isbn, $editorial, $anio, $autorId, date('Y-m-d H:i:s')]);
}

echo "Base de datos inicializada: " . count($autores) . " autores y " . count($libros) . " libros.\n";
```

**Ejecutar:**

```cmd
cd C:\xampp\htdocs\phpvuejs
type nul > database\database.sqlite
php database\seed.php
```

> Si Apache corre en Docker, el archivo `database.sqlite` debe tener permisos de escritura para el usuario del contenedor. Si tienes problemas, ejecuta:
> ```cmd
> icacls database\database.sqlite /grant Everyone:F
> ```

---

## 13. Modelos

### 13.1 Modelo `Autor` (`app/models/Autor.php`)

```php
<?php

require_once __DIR__ . '/../core/Database.php';

class Autor
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function all(): array
    {
        return $this->db->query('SELECT * FROM autores ORDER BY id DESC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM autores WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare('INSERT INTO autores (nombre, nacionalidad, fecha_nacimiento, biografia, created_at) VALUES (:nombre, :nacionalidad, :fecha_nacimiento, :biografia, :created_at)');
        return $stmt->execute([
            'nombre'           => $data['nombre'],
            'nacionalidad'     => $data['nacionalidad'] ?? null,
            'fecha_nacimiento' => !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null,
            'biografia'        => $data['biografia'] ?? null,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE autores SET nombre = :nombre, nacionalidad = :nacionalidad, fecha_nacimiento = :fecha_nacimiento, biografia = :biografia, updated_at = :updated_at WHERE id = :id');
        return $stmt->execute([
            'id'               => $id,
            'nombre'           => $data['nombre'],
            'nacionalidad'     => $data['nacionalidad'] ?? null,
            'fecha_nacimiento' => !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null,
            'biografia'        => $data['biografia'] ?? null,
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM autores WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Devuelve los datos para DataTable server-side:
     *   - recordsTotal
     *   - recordsFiltered
     *   - data (filas ya formateadas para DataTables JS)
     */
    public function datatable(int $start, int $length, string $search, int $orderCol, string $orderDir): array
    {
        $columns = ['id', 'nombre', 'nacionalidad', 'fecha_nacimiento', 'libros_count'];

        $orderColumn = $columns[$orderCol] ?? 'id';
        $orderDir    = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';

        $recordsTotal = (int) $this->db->query('SELECT COUNT(*) AS c FROM autores')->fetch()['c'];

        $where = '';
        $params = [];
        if ($search !== '') {
            $where = 'WHERE nombre LIKE :s OR nacionalidad LIKE :s';
            $params['s'] = "%{$search}%";
        }

        $stmtFiltered = $this->db->prepare("SELECT COUNT(*) AS c FROM autores {$where}");
        $stmtFiltered->execute($params);
        $recordsFiltered = (int) $stmtFiltered->fetch()['c'];

        if ($length > 0) {
            $sql = "SELECT a.*, (SELECT COUNT(*) FROM libros WHERE autor_id = a.id) AS libros_count
                    FROM autores a
                    {$where}
                    ORDER BY {$orderColumn} {$orderDir}, a.id ASC
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue('limit',  $length, PDO::PARAM_INT);
            $stmt->bindValue('offset', $start,  $start, PDO::PARAM_INT);
        } else {
            $sql = "SELECT a.*, (SELECT COUNT(*) FROM libros WHERE autor_id = a.id) AS libros_count
                    FROM autores a
                    {$where}
                    ORDER BY {$orderColumn} {$orderDir}, a.id ASC";
            $stmt = $this->db->prepare($sql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'id'                => (int) $row['id'],
                'nombre'            => $row['nombre'],
                'nacionalidad'      => $row['nacionalidad'] ?? '—',
                'fecha_nacimiento'  => $row['fecha_nacimiento']
                    ? date('d/m/Y', strtotime($row['fecha_nacimiento']))
                    : '—',
                'libros_count'      => (int) $row['libros_count'],
            ];
        }

        return [
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ];
    }
}
```

### 13.2 Modelo `Libro` (`app/models/Libro.php`)

```php
<?php

require_once __DIR__ . '/../core/Database.php';

class Libro
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function all(): array
    {
        return $this->db->query('SELECT l.*, a.nombre AS autor_nombre FROM libros l LEFT JOIN autores a ON l.autor_id = a.id ORDER BY l.id DESC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM libros WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare('INSERT INTO libros (titulo, isbn, editorial, anio_publicacion, autor_id, created_at) VALUES (:titulo, :isbn, :editorial, :anio_publicacion, :autor_id, :created_at)');
        return $stmt->execute([
            'titulo'           => $data['titulo'],
            'isbn'             => $data['isbn'],
            'editorial'        => $data['editorial'] ?? null,
            'anio_publicacion' => !empty($data['anio_publicacion']) ? (int) $data['anio_publicacion'] : null,
            'autor_id'         => (int) $data['autor_id'],
            'created_at'       => date('Y-m-d H:i:s'),
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE libros SET titulo = :titulo, isbn = :isbn, editorial = :editorial, anio_publicacion = :anio_publicacion, autor_id = :autor_id, updated_at = :updated_at WHERE id = :id');
        return $stmt->execute([
            'id'               => $id,
            'titulo'           => $data['titulo'],
            'isbn'             => $data['isbn'],
            'editorial'        => $data['editorial'] ?? null,
            'anio_publicacion' => !empty($data['anio_publicacion']) ? (int) $data['anio_publicacion'] : null,
            'autor_id'         => (int) $data['autor_id'],
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM libros WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function autores(): array
    {
        return $this->db->query('SELECT id, nombre, nacionalidad FROM autores ORDER BY nombre ASC')->fetchAll();
    }

    public function isbnExists(string $isbn, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM libros WHERE isbn = :isbn';
        $params = ['isbn' => $isbn];
        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['c'] > 0;
    }

    public function datatable(int $start, int $length, string $search, int $orderCol, string $orderDir): array
    {
        $columns = ['id', 'titulo', 'isbn', 'editorial', 'anio_publicacion', 'autor'];

        $orderColumn = $columns[$orderCol] ?? 'id';
        $orderDir    = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';

        $recordsTotal = (int) $this->db->query('SELECT COUNT(*) AS c FROM libros')->fetch()['c'];

        $where = '';
        $params = [];
        if ($search !== '') {
            $where = 'WHERE l.titulo LIKE :s OR l.isbn LIKE :s OR l.editorial LIKE :s OR a.nombre LIKE :s';
            $params['s'] = "%{$search}%";
        }

        $sqlFiltered = "SELECT COUNT(*) AS c FROM libros l LEFT JOIN autores a ON l.autor_id = a.id {$where}";
        $stmtFiltered = $this->db->prepare($sqlFiltered);
        $stmtFiltered->execute($params);
        $recordsFiltered = (int) $stmtFiltered->fetch()['c'];

        $orderBy = 'l.id';
        if ($orderColumn === 'autor') $orderBy = 'a.nombre';

        $sql = "SELECT l.*, a.nombre AS autor_nombre
                FROM libros l
                LEFT JOIN autores a ON l.autor_id = a.id
                {$where}
                ORDER BY {$orderBy} {$orderDir}, l.id ASC";

        if ($length > 0) {
            $sql .= ' LIMIT :limit OFFSET :offset';
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        if ($length > 0) {
            $stmt->bindValue('limit',  $length, PDO::PARAM_INT);
            $stmt->bindValue('offset', $start,  PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'id'               => (int) $row['id'],
                'titulo'           => $row['titulo'],
                'isbn'             => $row['isbn'],
                'editorial'        => $row['editorial'] ?? '—',
                'anio_publicacion' => $row['anio_publicacion'] ?? '—',
                'autor'            => $row['autor_nombre'] ?? '—',
            ];
        }

        return [
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ];
    }
}
```

---

## 14. Controladores

### 14.1 `AutoresController` (`app/controllers/AutoresController.php`)

```php
<?php

class AutoresController extends Controller
{
    private Autor $autor;

    public function __construct()
    {
        $this->autor = $this->model('Autor');
    }

    public function index(): void
    {
        if ($this->isAjax()) {
            $draw    = (int) ($_GET['draw']    ?? 1);
            $start   = (int) ($_GET['start']   ?? 0);
            $length  = (int) ($_GET['length']  ?? 10);
            $search  = $_GET['search']['value'] ?? '';
            $orderCol = (int) ($_GET['order'][0]['column'] ?? 0);
            $orderDir = $_GET['order'][0]['dir'] ?? 'asc';

            $result = $this->autor->datatable($start, $length, $search, $orderCol, $orderDir);

            $base = '/fdtw2-2026/periodo2/phpvuejs/public';
            foreach ($result['data'] as &$row) {
                $editUrl    = $base . '/autores/edit/' . $row['id'];
                $deleteUrl  = $base . '/autores/destroy/' . $row['id'];
                $csrf       = csrf_token();
                $row['action'] =
                    '<a href="' . $editUrl . '" class="btn btn-sm btn-warning"><i class="bi bi-pencil-square"></i></a>'
                  . '<form action="' . $deleteUrl . '" method="POST" class="d-inline form-eliminar">'
                  .     '<input type="hidden" name="_token" value="' . $csrf . '">'
                  .     '<button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>'
                  . '</form>';
            }

            $this->json([
                'draw'            => $draw,
                'recordsTotal'    => $result['recordsTotal'],
                'recordsFiltered' => $result['recordsFiltered'],
                'data'            => $result['data'],
            ]);
        }

        $this->view('autores/index', ['title' => 'Autores']);
    }

    public function create(): void
    {
        $this->view('autores/create', ['title' => 'Nuevo Autor']);
    }

    public function store(): void
    {
        verify_csrf();

        $nombre           = $this->inputRaw('nombre');
        $nacionalidad     = $this->inputRaw('nacionalidad');
        $fecha_nacimiento = $this->inputRaw('fecha_nacimiento');
        $biografia        = $this->inputRaw('biografia');

        $errors = [];
        if (empty($nombre)) $errors[] = 'El nombre es obligatorio.';
        if (!empty($fecha_nacimiento) && !strtotime($fecha_nacimiento)) $errors[] = 'Fecha inválida.';

        if (!empty($errors)) {
            $_SESSION['flash']['errors'] = $errors;
            $_SESSION['flash']['old'] = compact('nombre', 'nacionalidad', 'fecha_nacimiento', 'biografia');
            $this->redirect('/autores/create');
        }

        $this->autor->create(compact('nombre', 'nacionalidad', 'fecha_nacimiento', 'biografia'));
        $_SESSION['flash']['success'] = 'Autor creado correctamente.';
        $this->redirect('/autores');
    }

    public function show($id = null): void { }

    public function edit($id = null): void
    {
        $autor = $this->autor->find((int) $id);
        if (!$autor) {
            $_SESSION['flash']['errors'] = ['Autor no encontrado.'];
            $this->redirect('/autores');
        }
        $this->view('autores/edit', ['autor' => $autor, 'title' => 'Editar Autor']);
    }

    public function update($id = null): void
    {
        verify_csrf();

        $id = (int) $id;
        $nombre           = $this->inputRaw('nombre');
        $nacionalidad     = $this->inputRaw('nacionalidad');
        $fecha_nacimiento = $this->inputRaw('fecha_nacimiento');
        $biografia        = $this->inputRaw('biografia');

        $errors = [];
        if (empty($nombre)) $errors[] = 'El nombre es obligatorio.';
        if (!empty($fecha_nacimiento) && !strtotime($fecha_nacimiento)) $errors[] = 'Fecha inválida.';

        if (!empty($errors)) {
            $_SESSION['flash']['errors'] = $errors;
            $this->redirect("/autores/edit/$id");
        }

        $this->autor->update($id, compact('nombre', 'nacionalidad', 'fecha_nacimiento', 'biografia'));
        $_SESSION['flash']['success'] = 'Autor actualizado correctamente.';
        $this->redirect('/autores');
    }

    public function destroy($id = null): void
    {
        verify_csrf();

        $this->autor->delete((int) $id);
        $_SESSION['flash']['success'] = 'Autor eliminado correctamente.';
        $this->redirect('/autores');
    }
}
```

### 14.2 `LibrosController` (`app/controllers/LibrosController.php`)

```php
<?php

class LibrosController extends Controller
{
    private Libro $libro;
    private Autor $autor;

    public function __construct()
    {
        $this->libro = $this->model('Libro');
        $this->autor = $this->model('Autor');
    }

    public function index(): void
    {
        if ($this->isAjax()) {
            $draw    = (int) ($_GET['draw']    ?? 1);
            $start   = (int) ($_GET['start']   ?? 0);
            $length  = (int) ($_GET['length']  ?? 10);
            $search  = $_GET['search']['value'] ?? '';
            $orderCol = (int) ($_GET['order'][0]['column'] ?? 0);
            $orderDir = $_GET['order'][0]['dir'] ?? 'asc';

            $result = $this->libro->datatable($start, $length, $search, $orderCol, $orderDir);

            $base = '/fdtw2-2026/periodo2/phpvuejs/public';
            foreach ($result['data'] as &$row) {
                $editUrl    = $base . '/libros/edit/' . $row['id'];
                $deleteUrl  = $base . '/libros/destroy/' . $row['id'];
                $csrf       = csrf_token();
                $row['action'] =
                    '<a href="' . $editUrl . '" class="btn btn-sm btn-warning"><i class="bi bi-pencil-square"></i></a>'
                  . '<form action="' . $deleteUrl . '" method="POST" class="d-inline form-eliminar">'
                  .     '<input type="hidden" name="_token" value="' . $csrf . '">'
                  .     '<button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>'
                  . '</form>';
            }

            $this->json([
                'draw'            => $draw,
                'recordsTotal'    => $result['recordsTotal'],
                'recordsFiltered' => $result['recordsFiltered'],
                'data'            => $result['data'],
            ]);
        }

        $this->view('libros/index', ['title' => 'Libros']);
    }

    public function create(): void
    {
        $autores = $this->autor->autores();
        $this->view('libros/create', ['autores' => $autores, 'title' => 'Nuevo Libro']);
    }

    public function store(): void
    {
        verify_csrf();

        $titulo           = $this->inputRaw('titulo');
        $isbn             = $this->inputRaw('isbn');
        $editorial        = $this->inputRaw('editorial');
        $anio_publicacion = $this->inputRaw('anio_publicacion');
        $autor_id         = (int) ($this->inputRaw('autor_id') ?? 0);

        $errors = [];
        if (empty($titulo)) $errors[] = 'El título es obligatorio.';
        if (empty($isbn)) $errors[] = 'El ISBN es obligatorio.';
        if ($autor_id <= 0) $errors[] = 'Debe seleccionar un autor.';
        if ($this->libro->isbnExists($isbn)) $errors[] = 'Este ISBN ya está registrado.';

        if (!empty($errors)) {
            $_SESSION['flash']['errors'] = $errors;
            $_SESSION['flash']['old'] = compact('titulo', 'isbn', 'editorial', 'anio_publicacion', 'autor_id');
            $this->redirect('/libros/create');
        }

        $this->libro->create(compact('titulo', 'isbn', 'editorial', 'anio_publicacion', 'autor_id'));
        $_SESSION['flash']['success'] = 'Libro creado correctamente.';
        $this->redirect('/libros');
    }

    public function show($id = null): void { }

    public function edit($id = null): void
    {
        $libro = $this->libro->find((int) $id);
        if (!$libro) {
            $_SESSION['flash']['errors'] = ['Libro no encontrado.'];
            $this->redirect('/libros');
        }
        $autores = $this->autor->autores();
        $this->view('libros/edit', ['libro' => $libro, 'autores' => $autores, 'title' => 'Editar Libro']);
    }

    public function update($id = null): void
    {
        verify_csrf();

        $id = (int) $id;
        $titulo           = $this->inputRaw('titulo');
        $isbn             = $this->inputRaw('isbn');
        $editorial        = $this->inputRaw('editorial');
        $anio_publicacion = $this->inputRaw('anio_publicacion');
        $autor_id         = (int) ($this->inputRaw('autor_id') ?? 0);

        $errors = [];
        if (empty($titulo)) $errors[] = 'El título es obligatorio.';
        if (empty($isbn)) $errors[] = 'El ISBN es obligatorio.';
        if ($autor_id <= 0) $errors[] = 'Debe seleccionar un autor.';
        if ($this->libro->isbnExists($isbn, $id)) $errors[] = 'Este ISBN ya está registrado.';

        if (!empty($errors)) {
            $_SESSION['flash']['errors'] = $errors;
            $this->redirect("/libros/edit/$id");
        }

        $this->libro->update($id, compact('titulo', 'isbn', 'editorial', 'anio_publicacion', 'autor_id'));
        $_SESSION['flash']['success'] = 'Libro actualizado correctamente.';
        $this->redirect('/libros');
    }

    public function destroy($id = null): void
    {
        verify_csrf();

        $this->libro->delete((int) $id);
        $_SESSION['flash']['success'] = 'Libro eliminado correctamente.';
        $this->redirect('/libros');
    }
}
```

### 14.3 `DashboardController` (`app/controllers/DashboardController.php`)

```php
<?php

require_once __DIR__ . '/../core/Database.php';

class DashboardController extends Controller
{
    public function contadores(): void
    {
        $db = Database::connect();
        $autores = (int) $db->query('SELECT COUNT(*) AS c FROM autores')->fetch()['c'];
        $libros  = (int) $db->query('SELECT COUNT(*) AS c FROM libros')->fetch()['c'];
        $this->json(compact('autores', 'libros'));
    }
}
```

---

## 15. Layout base con Vue.js + DataTables (CDN)

`app/views/layouts/header.php`:

```php
<?php
$base = '/fdtw2-2026/periodo2/phpvuejs/public';
$flashSuccess = $_SESSION['flash']['success'] ?? null;
$flashErrors  = $_SESSION['flash']['errors']  ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'PHP Vue Yajra') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <style>
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 0.2em 0.6em; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="<?= $base ?>/autores">
            <i class="bi bi-book-half"></i> PHP + Vue.js + DataTables
        </a>
        <ul class="navbar-nav me-auto">
            <li class="nav-item"><a class="nav-link" href="<?= $base ?>/autores">Autores</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= $base ?>/libros">Libros</a></li>
        </ul>
        <span class="navbar-text text-light" id="contadores-nav">
            <i class="bi bi-person-vcard"></i> Autores: <strong id="nav-autores">…</strong>
            &nbsp;|&nbsp;
            <i class="bi bi-book"></i> Libros: <strong id="nav-libros">…</strong>
        </span>
    </div>
</nav>

<main class="container">
    <div id="app">
        <?php if ($flashSuccess): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($flashSuccess) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($flashErrors): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">
                    <?php foreach ($flashErrors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>
</main>

<footer class="container text-center text-muted py-4 mt-5 border-top">
    <small>&copy; <?= date('Y') ?> - PHP Puro + Vue.js 3 + DataTables</small>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>

<script>
    const CSRF_TOKEN = '<?= csrf_token() ?>';

    // SweetAlert vía delegación (DataTable renderiza filas después)
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form && form.classList && form.classList.contains('form-eliminar')) {
            e.preventDefault();
            Swal.fire({
                title: '¿Estás seguro?',
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) form.submit();
            });
        }
    }, true);

    // Vue.js 3: contadores desde API interna
    const { createApp, ref, onMounted } = Vue;
    createApp({
        setup() {
            const autores = ref('—');
            const libros  = ref('—');
            onMounted(async () => {
                try {
                    const r = await fetch('<?= $base ?>/api/contadores');
                    const d = await r.json();
                    autores.value = d.autores ?? '—';
                    libros.value  = d.libros  ?? '—';
                    document.getElementById('nav-autores').textContent = autores.value;
                    document.getElementById('nav-libros').textContent  = libros.value;
                } catch (e) {
                    console.error('Error cargando contadores', e);
                }
            });
            return { autores, libros };
        }
    }).mount('#app');
</script>

</body>
</html>
```

`app/views/layouts/footer.php`:

```php
</body>
</html>
```

**Conceptos clave:**
- DataTables CDN (CSS + JS) — depende de jQuery
- Vue.js 3 desde CDN (`vue.global.prod.js` = versión producción)
- **Delegación de eventos** en `document` con captura (`true`): captura los submits incluso de elementos que aún no existen cuando se carga la página (los que DataTable inserta después vía AJAX)

---

## 16. Vistas

### 16.1 Listado de Autores (`app/views/autores/index.php`)

```php
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><i class="bi bi-person-vcard"></i> Autores</h1>
    <a href="<?= $base ?>/autores/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo Autor
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <table id="autores-table" class="table table-striped table-hover align-middle" style="width:100%">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Nacionalidad</th>
                    <th>Nacimiento</th>
                    <th class="text-center">Libros</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<script>
$(function () {
    $('#autores-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        ajax: {
            url: '<?= $base ?>/autores',
            type: 'GET',
            data: function (d) {
                d.draw    = d.draw || 1;
                d.start   = d.start || 0;
                d.length  = d.length || 10;
                d.search  = d.search || { value: '' };
                d.order   = d.order  || [{ column: 0, dir: 'asc' }];
                return d;
            }
        },
        columns: [
            { data: 'id', name: 'id', width: '60px' },
            { data: 'nombre', name: 'nombre' },
            { data: 'nacionalidad', name: 'nacionalidad' },
            { data: 'fecha_nacimiento', name: 'fecha_nacimiento' },
            { data: 'libros_count', name: 'libros_count', className: 'text-center', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center', width: '120px' },
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        order: [[1, 'asc']],
    });
});
</script>
```

### 16.2 Formulario Crear Autor (`app/views/autores/create.php`)

```php
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-person-plus-fill"></i> Nuevo Autor
            </div>
            <div class="card-body">
                <form action="<?= $base ?>/autores/store" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($_SESSION['flash']['old']['nombre'] ?? '') ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nacionalidad</label>
                            <input type="text" name="nacionalidad" class="form-control" value="<?= htmlspecialchars($_SESSION['flash']['old']['nacionalidad'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha de nacimiento</label>
                            <input type="date" name="fecha_nacimiento" class="form-control" value="<?= htmlspecialchars($_SESSION['flash']['old']['fecha_nacimiento'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Biografía</label>
                        <textarea name="biografia" class="form-control" rows="4"><?= htmlspecialchars($_SESSION['flash']['old']['biografia'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?= $base ?>/autores" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php unset($_SESSION['flash']['old']); ?>
```

### 16.3 Formulario Editar Autor (`app/views/autores/edit.php`)

```php
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-warning">
                <i class="bi bi-pencil-square"></i> Editar Autor
            </div>
            <div class="card-body">
                <form action="<?= $base ?>/autores/update/<?= (int) $autor['id'] ?>" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($autor['nombre']) ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nacionalidad</label>
                            <input type="text" name="nacionalidad" class="form-control" value="<?= htmlspecialchars($autor['nacionalidad'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha de nacimiento</label>
                            <input type="date" name="fecha_nacimiento" class="form-control" value="<?= htmlspecialchars($autor['fecha_nacimiento'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Biografía</label>
                        <textarea name="biografia" class="form-control" rows="4"><?= htmlspecialchars($autor['biografia'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?= $base ?>/autores" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-save"></i> Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
```

### 16.4 Listado de Libros (`app/views/libros/index.php`)

```php
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><i class="bi bi-book"></i> Libros</h1>
    <a href="<?= $base ?>/libros/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo Libro
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <table id="libros-table" class="table table-striped table-hover align-middle" style="width:100%">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Título</th>
                    <th>ISBN</th>
                    <th>Editorial</th>
                    <th>Año</th>
                    <th>Autor</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<script>
$(function () {
    $('#libros-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        ajax: {
            url: '<?= $base ?>/libros',
            type: 'GET',
            data: function (d) {
                d.draw    = d.draw || 1;
                d.start   = d.start || 0;
                d.length  = d.length || 10;
                d.search  = d.search || { value: '' };
                d.order   = d.order  || [{ column: 0, dir: 'asc' }];
                return d;
            }
        },
        columns: [
            { data: 'id', name: 'id', width: '60px' },
            { data: 'titulo', name: 'titulo' },
            { data: 'isbn', name: 'isbn' },
            { data: 'editorial', name: 'editorial' },
            { data: 'anio_publicacion', name: 'anio_publicacion', className: 'text-center', width: '80px' },
            { data: 'autor', name: 'autor' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center', width: '120px' },
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        order: [[1, 'asc']],
    });
});
</script>
```

### 16.5 Formulario Crear Libro (`app/views/libros/create.php`)

```php
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-bookmark-plus"></i> Nuevo Libro
            </div>
            <div class="card-body">
                <form action="<?= $base ?>/libros/store" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Título *</label>
                        <input type="text" name="titulo" class="form-control" value="<?= htmlspecialchars($_SESSION['flash']['old']['titulo'] ?? '') ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ISBN *</label>
                            <input type="text" name="isbn" class="form-control" value="<?= htmlspecialchars($_SESSION['flash']['old']['isbn'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Año de publicación</label>
                            <input type="number" name="anio_publicacion" class="form-control" value="<?= htmlspecialchars($_SESSION['flash']['old']['anio_publicacion'] ?? '') ?>" min="1000" max="<?= date('Y') + 1 ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Editorial</label>
                        <input type="text" name="editorial" class="form-control" value="<?= htmlspecialchars($_SESSION['flash']['old']['editorial'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Autor *</label>
                        <select name="autor_id" class="form-select" required>
                            <option value="">Seleccione un autor...</option>
                            <?php foreach ($autores as $a): ?>
                                <option value="<?= (int) $a['id'] ?>" <?= ($_SESSION['flash']['old']['autor_id'] ?? '') == $a['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a['nombre']) ?> (<?= htmlspecialchars($a['nacionalidad'] ?? 'Sin nacionalidad') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?= $base ?>/libros" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php unset($_SESSION['flash']['old']); ?>
```

### 16.6 Formulario Editar Libro (`app/views/libros/edit.php`)

```php
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-warning">
                <i class="bi bi-pencil-square"></i> Editar Libro
            </div>
            <div class="card-body">
                <form action="<?= $base ?>/libros/update/<?= (int) $libro['id'] ?>" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Título *</label>
                        <input type="text" name="titulo" class="form-control" value="<?= htmlspecialchars($libro['titulo']) ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ISBN *</label>
                            <input type="text" name="isbn" class="form-control" value="<?= htmlspecialchars($libro['isbn']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Año de publicación</label>
                            <input type="number" name="anio_publicacion" class="form-control" value="<?= htmlspecialchars($libro['anio_publicacion'] ?? '') ?>" min="1000" max="<?= date('Y') + 1 ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Editorial</label>
                        <input type="text" name="editorial" class="form-control" value="<?= htmlspecialchars($libro['editorial'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Autor *</label>
                        <select name="autor_id" class="form-select" required>
                            <option value="">Seleccione un autor...</option>
                            <?php foreach ($autores as $a): ?>
                                <option value="<?= (int) $a['id'] ?>" <?= $libro['autor_id'] == $a['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a['nombre']) ?> (<?= htmlspecialchars($a['nacionalidad'] ?? 'Sin nacionalidad') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?= $base ?>/libros" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-save"></i> Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
```

---

## 17. Acceder al proyecto

```
http://localhost/fdtw2-2026/periodo2/phpvuejs/public/autores
http://localhost/fdtw2-2026/periodo2/phpvuejs/public/libros
http://localhost/fdtw2-2026/periodo2/phpvuejs/public/api/contadores
```

---

## 18. Resumen del flujo lógico

```
1. Crear estructura de carpetas       → mkdir + archivos a mano
2. Crear .htaccess + front controller → public/.htaccess + public/index.php
3. Crear Router (App.php)             → parsea URL, mapea a Controller@método
4. Crear Database (Singleton PDO)     → app/core/Database.php
5. Crear Controller base              → helpers: model, view, redirect, input, json
6. Crear helpers (CSRF)              → csrf_token(), csrf_field(), verify_csrf()
7. Crear SQL init.sql                 → tablas autores y libros con FK
8. Crear seed.php                     → 10 autores + 15 libros
9. Crear modelos                      → CRUD manual + datatable() con paginación
10. Crear controladores               → 7 métodos resource + endpoint AJAX
11. Crear DashboardController          → JSON para Vue.js
12. Crear layout (header/footer)      → CDN Vue + DataTables + SweetAlert
13. Crear vistas                      → 2 index (DataTable JS) + 4 formularios
14. Probar                            → CRUD + paginación server-side
```

---

## 19. Posibles errores comunes en clase

| Error | Causa | Solución |
|---|---|---|
| "Unable to open database file" | BD SQLite no existe o sin permisos | `type nul > database\database.sqlite` + permisos de escritura |
| "no such table: autores" | No se ejecutó el seed | `php database/seed.php` |
| "Foreign key constraint failed" | FK en SQLite desactivada | Verificar `PRAGMA foreign_keys = ON` en `Database::connect()` |
| Página en blanco / HTML gigante | El `.htaccess` no se está leyendo | Verificar `AllowOverride All` en la config de Apache |
| "Class 'Controller' not found" | Falta require del core | Verificar los 4 requires en `public/index.php` |
| API contadores retorna HTML | Bug en routing del API | Verificar rama `if ($url[1] === 'contadores')` antes de ucfirst |
| 419 en POST | CSRF token mismatch | Verificar `<?= csrf_field() ?>` en cada `<form>` |
| Elimina sin confirmación | SweetAlert asignado antes de DataTable | Usar **delegación de eventos** en `document` con captura `true` |
| `htmlspecialchars()` warning en null | Valor vacío en form | Usar `$valor ?? ''` o `old($key, '')` |
| DataTable no muestra datos | `column.data` no coincide con el JSON | Verificar las claves devueltas por `datatable()` en el modelo |
| Foreign key violada al eliminar autor | Tabla libros tiene FK sin cascade | Agregar `ON DELETE CASCADE` en la FK (ya incluido en este init.sql) |

---

## 20. Git y GitHub

### 20.1 Configurar identidad

```cmd
git config --global user.name "Tu Nombre Completo"
git config --global user.email "tu_email@ejemplo.com"
```

### 20.2 `.gitignore`

```gitignore
# Base de datos
database/database.sqlite

# Sistema operativo
.DS_Store
Thumbs.db
desktop.ini

# Editores
.vscode/
.idea/
*.swp
*.swo
*~

# Configuración local (no incluir)
.env
.env.local
config/local.php
```

### 20.3 Inicializar y subir

```cmd
cd C:\xampp\htdocs\phpvuejs
git init
git add .
git commit -m "initial: PHP Puro MVC + Vue.js 3 + DataTables server-side"
git remote add origin https://github.com/TU_USUARIO/phpvuejs.git
git push -u origin main
```

### 20.4 Rama `desarrollo`

```cmd
git checkout -b desarrollo
git push -u origin desarrollo
```

### 20.5 Flujo de trabajo

```cmd
git add .
git commit -m "feat: agregar búsqueda por rango de años"
git push
```

> **Convención de mensajes:**
> - `feat:` nueva funcionalidad
> - `fix:` corrección de bug
> - `docs:` cambios en documentación
> - `style:` formato
> - `refactor:` reescritura de código
> - `test:` tests

---

**Actividad finalizada.** La guía cubre la implementación completa de un CRUD con relación 1-a-N usando PHP puro (sin framework), DataTables server-side hecho a mano, Vue.js 3 desde CDN, y el flujo de Git para publicar en GitHub.