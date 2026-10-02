-- Script de inicialización de la base de datos SQLite
-- Ejecutar desde la raíz: php database/seed.php

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