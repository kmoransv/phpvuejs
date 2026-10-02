# Listado de archivos del proyecto

Proyecto PHP Puro MVC + Vue.js 3 + DataTables — CRUD de Autores y Libros con relación 1-a-N.

## Modificados

_(ninguno, todo creado desde cero)_

## Creados

| Archivo | Función |
|---|---|
| `public/.htaccess` | Reescritura de URLs hacia index.php |
| `public/index.php` | Front controller (única entrada al sistema) |
| `app/core/App.php` | Router manual (parsea URL, soporta `/api/contadores`) |
| `app/core/Database.php` | Conexión PDO a SQLite (patrón Singleton + PRAGMA foreign_keys) |
| `app/core/Controller.php` | Clase base con helpers (model, view, redirect, input, json, isAjax) |
| `app/core/helpers.php` | csrf_token(), csrf_field(), verify_csrf() |
| `app/models/Autor.php` | CRUD + método `datatable()` con paginación server-side |
| `app/models/Libro.php` | CRUD + `datatable()` con búsqueda por relación autor |
| `app/controllers/AutoresController.php` | 7 métodos resource + endpoint AJAX DataTable |
| `app/controllers/LibrosController.php` | 7 métodos resource + endpoint AJAX DataTable |
| `app/controllers/DashboardController.php` | Endpoint JSON `contadores` para Vue.js |
| `app/views/layouts/header.php` | Layout superior (navbar, Bootstrap CDN, Vue.js, SweetAlert, DataTable CDN) |
| `app/views/layouts/footer.php` | Layout inferior |
| `app/views/autores/index.php` | Listado autores con DataTable JS |
| `app/views/autores/create.php` | Formulario crear autor |
| `app/views/autores/edit.php` | Formulario editar autor |
| `app/views/libros/index.php` | Listado libros con DataTable JS |
| `app/views/libros/create.php` | Formulario crear libro con dropdown de autores |
| `app/views/libros/edit.php` | Formulario editar libro |
| `database/init.sql` | Script SQL con tablas autores y libros (FK con cascade) |
| `database/seed.php` | Inicializa la BD con 10 autores y 15 libros |
| `database/database.sqlite` | _(NO se sube a git — está en .gitignore)_ |
| `.gitignore` | Exclusiones (BD, SO, editores) |
| `README.md` | Documentación del proyecto |
| `GUIA.md` | Guía práctica paso a paso |
| `ARCHIVOS.md` | Este listado |