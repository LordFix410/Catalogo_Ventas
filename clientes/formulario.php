<?php
// Requiere $cliente, $acceso, $tieneUsuario, $errores y $textoBoton
?>

<form method="POST" novalidate>

    <div class="row g-3">

        <div class="col-md-6">
            <label for="nombre" class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
            <input type="text"
                   id="nombre"
                   name="nombre"
                   maxlength="100"
                   class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>"
                   value="<?= escapar($cliente['nombre'] ?? '') ?>"
                   placeholder="Ej: María"
                   required>
            <div class="invalid-feedback"><?= escapar($errores['nombre'] ?? '') ?></div>
        </div>

        <div class="col-md-6">
            <label for="apellido" class="form-label fw-bold">Apellido <span class="text-danger">*</span></label>
            <input type="text"
                   id="apellido"
                   name="apellido"
                   maxlength="100"
                   class="form-control <?= isset($errores['apellido']) ? 'is-invalid' : '' ?>"
                   value="<?= escapar($cliente['apellido'] ?? '') ?>"
                   placeholder="Ej: López"
                   required>
            <div class="invalid-feedback"><?= escapar($errores['apellido'] ?? '') ?></div>
        </div>

        <div class="col-md-6">
            <label for="telefono" class="form-label fw-bold">Teléfono</label>
            <div class="input-group has-validation">
                <span class="input-group-text bg-white"><i class="bi bi-telephone"></i></span>
                <input type="tel"
                       id="telefono"
                       name="telefono"
                       maxlength="20"
                       class="form-control <?= isset($errores['telefono']) ? 'is-invalid' : '' ?>"
                       value="<?= escapar($cliente['telefono'] ?? '') ?>"
                       placeholder="Ej: 5555-1234">
                <div class="invalid-feedback"><?= escapar($errores['telefono'] ?? '') ?></div>
            </div>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-bold">Tipo de cliente <span class="text-danger">*</span></label>
            <div class="d-flex gap-3 flex-wrap">
                <?php foreach (TIPOS_CLIENTE as $tipo): ?>
                    <?php $idTipo = 'tipo_' . strtolower($tipo); ?>
                    <div class="form-check">
                        <input class="form-check-input <?= isset($errores['tipo_cliente']) ? 'is-invalid' : '' ?>"
                               type="radio"
                               name="tipo_cliente"
                               id="<?= $idTipo ?>"
                               value="<?= $tipo ?>"
                               <?= ($cliente['tipo_cliente'] ?? 'Minorista') === $tipo ? 'checked' : '' ?>>
                        <label class="form-check-label" for="<?= $idTipo ?>">
                            <?= $tipo ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (isset($errores['tipo_cliente'])): ?>
                <div class="text-danger small mt-1"><?= escapar($errores['tipo_cliente']) ?></div>
            <?php else: ?>
                <div class="form-text">Minorista: compra al detalle. Mayorista: compra por volumen.</div>
            <?php endif; ?>
        </div>

        <div class="col-12">
            <label for="direccion" class="form-label fw-bold">Dirección</label>
            <textarea id="direccion"
                      name="direccion"
                      rows="2"
                      maxlength="255"
                      class="form-control <?= isset($errores['direccion']) ? 'is-invalid' : '' ?>"
                      placeholder="Ej: 5a. avenida 10-20, zona 14, Guatemala"><?= escapar($cliente['direccion'] ?? '') ?></textarea>
            <div class="invalid-feedback"><?= escapar($errores['direccion'] ?? '') ?></div>
        </div>

        <div class="col-12">
            <hr class="my-2">
            <h3 class="section-title mt-3 mb-1">
                <i class="bi bi-key"></i> Acceso al Portal del Cliente
            </h3>
            <?php if ($tieneUsuario): ?>
                <div class="form-text">Cuenta con rol Cliente. Deje la contraseña vacía para conservar la actual.</div>
            <?php else: ?>
                <div class="form-check mt-2">
                    <input class="form-check-input"
                           type="checkbox"
                           id="crear_acceso"
                           name="crear_acceso"
                           value="1"
                           <?= $acceso['con_acceso'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="crear_acceso">
                        Crear cuenta para que el cliente pueda hacer pedidos desde el portal
                    </label>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-12" id="camposAcceso">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="correo" class="form-label fw-bold">Correo electrónico <span class="text-danger">*</span></label>
                    <div class="input-group has-validation">
                        <span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
                        <input type="email"
                               id="correo"
                               name="correo"
                               maxlength="100"
                               autocomplete="off"
                               class="form-control <?= isset($errores['correo']) ? 'is-invalid' : '' ?>"
                               value="<?= escapar($acceso['correo']) ?>"
                               placeholder="Ej: maria.lopez@gmail.com">
                        <div class="invalid-feedback"><?= escapar($errores['correo'] ?? '') ?></div>
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="password" class="form-label fw-bold">
                        <?= $tieneUsuario ? 'Nueva contraseña' : 'Contraseña <span class="text-danger">*</span>' ?>
                    </label>
                    <input type="password"
                           id="password"
                           name="password"
                           autocomplete="new-password"
                           class="form-control <?= isset($errores['password']) ? 'is-invalid' : '' ?>"
                           placeholder="Mínimo <?= LARGO_MINIMO_PASSWORD ?> caracteres">
                    <div class="invalid-feedback"><?= escapar($errores['password'] ?? '') ?></div>
                </div>

                <div class="col-md-3">
                    <label for="confirmar_password" class="form-label fw-bold">Confirmar contraseña</label>
                    <input type="password"
                           id="confirmar_password"
                           name="confirmar_password"
                           autocomplete="new-password"
                           class="form-control <?= isset($errores['confirmar_password']) ? 'is-invalid' : '' ?>">
                    <div class="invalid-feedback"><?= escapar($errores['confirmar_password'] ?? '') ?></div>
                </div>
            </div>
        </div>

    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-chiquis">
            <i class="bi bi-save me-1"></i> <?= escapar($textoBoton) ?>
        </button>
    </div>

</form>

<?php if (!$tieneUsuario): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const check  = document.getElementById('crear_acceso');
    const campos = document.getElementById('camposAcceso');

    function mostrarCampos() {
        campos.style.display = check.checked ? '' : 'none';
    }

    check.addEventListener('change', mostrarCampos);
    mostrarCampos();
});
</script>
<?php endif; ?>
