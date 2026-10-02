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