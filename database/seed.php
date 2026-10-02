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
    ['Veinte poemas de amor',              '978-9500722038', 'Nascimiento',         1924, 4],
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