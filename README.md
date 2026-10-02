# phpvuejs

CRUD de **Autores** y **Libros** con relación uno-a-muchos en **PHP puro** (sin framework), patrón MVC manual, SQLite, DataTables server-side hecho a mano, y Vue.js 3 desde CDN.

## Stack

- PHP 8.1 puro (sin Composer, sin framework)
- SQLite vía PDO
- Vue.js 3 (CDN)
- jQuery DataTables (CDN) — server-side
- Bootstrap 5.3 + Bootstrap Icons + SweetAlert2 (CDN)

## Estructura

```
phpvuejs/
├── app/
│   ├── core/           ← App.php, Database.php, Controller.php, helpers.php
│   ├── controllers/    ← AutoresController, LibrosController, DashboardController
│   ├── models/         ← Autor, Libro
│   └── views/          ← layouts + autores + libros
├── database/           ← init.sql, seed.php, database.sqlite
├── public/             ← .htaccess + index.php (front controller)
└── README.md
```

## Instalación

1. Clonar el repositorio
3. Inicializar la base de datos:
   ```cmd
   php database\seed.php
   ```
4. Acceder vía Apache (XAMPP):
   ```
   http://localhost/fdtw2-2026/periodo2/phpvuejs/public/autores
   ```

## Características

- **MVC puro en PHP** — sin frameworks, todo a mano
- **Relación 1-a-N** — Autor tiene muchos Libros
- **Server-side DataTables** — paginación, búsqueda y ordenamiento se hacen en el servidor con SQL a mano
- **CSRF protection** — implementado manualmente con sesiones
- **Vue.js 3** — consume el endpoint `/api/contadores`
- **SweetAlert2** — confirmación de eliminación con delegación de eventos
- **Búsqueda por relación** — en libros se busca también por nombre del autor