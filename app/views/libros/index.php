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