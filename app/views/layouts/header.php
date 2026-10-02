<?php
$base = '/fdtw2-2026/periodo2/phpvuejs/public';
$flashSuccess = $_SESSION['flash']['success'] ?? null;
$flashErrors  = $_SESSION['flash']['errors']  ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'PHP Vue Yajra') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <style>
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 0.2em 0.6em; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="<?= $base ?>/autores">
            <i class="bi bi-book-half"></i> PHP + Vue.js + DataTables
        </a>
        <ul class="navbar-nav me-auto">
            <li class="nav-item"><a class="nav-link" href="<?= $base ?>/autores">Autores</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= $base ?>/libros">Libros</a></li>
        </ul>
        <span class="navbar-text text-light" id="contadores-nav">
            <i class="bi bi-person-vcard"></i> Autores: <strong id="nav-autores">…</strong>
            &nbsp;|&nbsp;
            <i class="bi bi-book"></i> Libros: <strong id="nav-libros">…</strong>
        </span>
    </div>
</nav>

<main class="container">
    <div id="app">
        <?php if ($flashSuccess): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($flashSuccess) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($flashErrors): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">
                    <?php foreach ($flashErrors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?= '' /* placeholder, lo indica @yield */ ?>
        <?php /* la vista incluida en cada ítem */ ?>
    </div>
</main>

<footer class="container text-center text-muted py-4 mt-5 border-top">
    <small>&copy; <?= date('Y') ?> - PHP Puro + Vue.js 3 + DataTables</small>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>

<script>
    // CSRF para fetch/AJAX (DataTables manda POST por defecto)
    const CSRF_TOKEN = '<?= csrf_token() ?>';

    // SweetAlert vía delegación (DataTable renderiza filas después)
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form && form.classList && form.classList.contains('form-eliminar')) {
            e.preventDefault();
            Swal.fire({
                title: '¿Estás seguro?',
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) form.submit();
            });
        }
    }, true);

    // Vue.js 3: contadores desde API interna
    const { createApp, ref, onMounted } = Vue;
    createApp({
        setup() {
            const autores = ref('—');
            const libros  = ref('—');
            onMounted(async () => {
                try {
                    const r = await fetch('<?= $base ?>/api/contadores');
                    const d = await r.json();
                    autores.value = d.autores ?? '—';
                    libros.value  = d.libros  ?? '—';
                    document.getElementById('nav-autores').textContent = autores.value;
                    document.getElementById('nav-libros').textContent  = libros.value;
                } catch (e) {
                    console.error('Error cargando contadores', e);
                }
            });
            return { autores, libros };
        }
    }).mount('#app');
</script>

</body>
</html>
<!-- VERSION_MARKER_V1 -->
