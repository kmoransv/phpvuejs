<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-warning">
                <i class="bi bi-pencil-square"></i> Editar Libro
            </div>
            <div class="card-body">
                <form action="<?= $base ?>/libros/update/<?= (int) $libro['id'] ?>" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Título *</label>
                        <input type="text" name="titulo" class="form-control" value="<?= htmlspecialchars($libro['titulo']) ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ISBN *</label>
                            <input type="text" name="isbn" class="form-control" value="<?= htmlspecialchars($libro['isbn']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Año de publicación</label>
                            <input type="number" name="anio_publicacion" class="form-control" value="<?= htmlspecialchars($libro['anio_publicacion'] ?? '') ?>" min="1000" max="<?= date('Y') + 1 ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Editorial</label>
                        <input type="text" name="editorial" class="form-control" value="<?= htmlspecialchars($libro['editorial'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Autor *</label>
                        <select name="autor_id" class="form-select" required>
                            <option value="">Seleccione un autor...</option>
                            <?php foreach ($autores as $a): ?>
                                <option value="<?= (int) $a['id'] ?>" <?= $libro['autor_id'] == $a['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a['nombre']) ?> (<?= htmlspecialchars($a['nacionalidad'] ?? 'Sin nacionalidad') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?= $base ?>/libros" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-save"></i> Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>