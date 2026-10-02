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