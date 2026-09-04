<!-- Vista para listar todos los usuarios -->
<section class="panel">
    <div class="panel-cabecera">
        <h1>Usuarios</h1>
        <a class="boton" href="<?= url('usuario', 'crear') ?>">Crear cuenta</a>
    </div>

<!-- Verifica si hay usuarios para mostrar, de lo contrario muestra un mensaje -->
    <div class="tabla-envoltura">
        <table>
            <thead>
                <tr>
                    <th>CI</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>

<!-- Itera sobre la lista de usuarios y muestra cada uno en una fila de la tabla -->            
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?= e($usuario['ci']) ?></td>
                        <td><?= e($usuario['nombres'] . ' ' . $usuario['apellidos']) ?></td>
                        <td><?= e($usuario['correo']) ?></td>
                        <td><span class="insignia insignia-rol"><?= e(ucfirst($usuario['rol'])) ?></span></td>
                        <td>
                            <?php if ($usuario['estado']): ?>
                                <span class="insignia insignia-activo">Activo</span>
                            <?php else: ?>
                                <span class="insignia insignia-inactivo">Inactivo</span>
                            <?php endif; ?>
                        </td>
<!-- Muestra botones para editar o cambiar el estado del usuario, excepto para el usuario actualmente logueado -->
                        <td class="acciones">
                            <a class="boton boton-pequeno boton-secundario" href="<?= url('usuario', 'editar', ['id' => $usuario['id_usuario']]) ?>">Editar</a>
                            <?php if ((int) $usuario['id_usuario'] !== (int) $_SESSION['user']['id']): ?>
                                <form method="post" action="<?= url('usuario', 'cambiarEstado') ?>">
                                    <input type="hidden" name="id" value="<?= e($usuario['id_usuario']) ?>">
                                    <input type="hidden" name="estado" value="<?= $usuario['estado'] ? '0' : '1' ?>">
                                    <button type="submit" class="boton boton-pequeno <?= $usuario['estado'] ? 'boton-peligro' : '' ?>">
                                        <?= $usuario['estado'] ? 'Desactivar' : 'Activar' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
