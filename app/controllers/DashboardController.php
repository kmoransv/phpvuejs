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