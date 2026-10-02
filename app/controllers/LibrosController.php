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