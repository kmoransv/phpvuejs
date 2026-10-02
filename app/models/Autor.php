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

    public function countLibros(int $autorId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS c FROM libros WHERE autor_id = :id');
        $stmt->execute(['id' => $autorId]);
        return (int) $stmt->fetch()['c'];
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
            $stmt->bindValue('offset', $start,  PDO::PARAM_INT);
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