<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-person-plus-fill"></i> Nuevo Autor
            </div>
            <div class="card-body">
                <form action="<?= $base ?>/autores/store" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($_SESSION['flash']['old']['nombre'] ?? '') ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nacionalidad</label>
                            <input type="text" name="nacionalidad" class="form-control" value="<?= htmlspecialchars($_SESSION['flash']['old']['nacionalidad'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha de nacimiento</label>
                            <input type="date" name="fecha_nacimiento" class="form-control" value="<?= htmlspecialchars($_SESSION['flash']['old']['fecha_nacimiento'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Biografía</label>
                        <textarea name="biografia" class="form-control" rows="4"><?= htmlspecialchars($_SESSION['flash']['old']['biografia'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?= $base ?>/autores" class="btn btn-secondary">
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