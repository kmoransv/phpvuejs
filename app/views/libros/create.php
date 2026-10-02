<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-bookmark-plus"></i> Nuevo Libro
            </div>
            <div class="card-body">
                <form action="<?= $base ?>/libros/store" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Título *</label>
                        <input type="text" name="titulo" class="form-control" value="<?= htmlspecialchars($_SESSION['flash']['old']['titulo'] ?? '') ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ISBN *</label>
                            <input type="text" name="isbn" class="form-control" value="<?= htmlspecialchars($_SESSION['flash']['old']['isbn'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Año de publicación</label>
                            <input type="number" name="anio_publicacion" class="form-control" value="<?= htmlspecialchars($_SESSION['flash']['old']['anio_publicacion'] ?? '') ?>" min="1000" max="<?= date('Y') + 1 ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Editorial</label>
                        <input type="text" name="editorial" class="form-control" value="<?= htmlspecialchars($_SESSION['flash']['old']['editorial'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Autor *</label>
                        <select name="autor_id" class="form-select" required>
                            <option value="">Seleccione un autor...</option>
                            <?php foreach ($autores as $a): ?>
                                <option value="<?= (int) $a['id'] ?>" <?= ($_SESSION['flash']['old']['autor_id'] ?? '') == $a['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a['nombre']) ?> (<?= htmlspecialchars($a['nacionalidad'] ?? 'Sin nacionalidad') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?= $base ?>/libros" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php unset($_SESSION['flash']['old']); ?>