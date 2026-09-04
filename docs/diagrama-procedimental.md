# Documentación para el Diagrama de Diseño Procedimental

Este documento describe, para cada caso de uso definido en `docs/casos-de-uso.md`, el flujo real de ejecución **Vista → Controlador(es) → Modelo(s) → Base de Datos**, tal como está implementado en el código (sin routing centralizado: el front controller `public/index.php` invoca directamente `Controlador::accion($request)` según `?controller=&action=` de la URL).

Está pensado para pasar directamente a un diagrama de diseño procedimental en Enterprise Architect: cada sección "Componentes para el diagrama" lista los nodos (Vista / Controlador / Modelo) y las flechas de llamada entre ellos, en el orden real en que ocurren.

Convenciones usadas en todo el documento:

* **Vista** = archivo `.php` en `app/views/...` que arma el HTML de entrada o salida.
* **Controlador** = clase en `app/controllers/...`, extiende `Controller` (`app/core/Controller.php`).
* **Modelo** = clase en `app/models/...`, extiende `Model` (`app/core/Model.php`), accede a PDO vía `$this->db`.
* Todas las consultas usan sentencias preparadas (`$this->db->prepare(...)->execute([...])`).
* `AuthMiddleware::handle()` y `RoleMiddleware::handle([...])` son los guardias de acceso; se mencionan porque interrumpen el flujo antes de llegar al modelo si el usuario no está autenticado o no tiene el rol requerido.

---

## CU01 — Gestionar acceso al sistema

**Actores:** Administrador, Instructor, Cliente.

### 1. Vista

| Archivo | Uso |
|---|---|
| `app/views/auth/login.php` | Formulario de inicio de sesión (`correo`, `password`), muestra `$error` si el intento anterior falló. Su `<form>` hace POST a `AuthController::autenticar`. |
| `app/views/home/index.php` | Portada/dashboard mostrado tras el login (o antes, en modo público). |

`auth/login.php` es puramente de entrada: no consulta modelos por sí misma, solo recibe `error` desde el controlador y envía el formulario. El enlace "Cerrar sesión", presente en `nav_administrador.php`, `nav_instructor.php` y `nav_cliente.php` (incluidos por `app/views/layouts/header.php`), apunta a `AuthController::logout`.

### 2. Controlador

**`AuthController`** (único controlador de este CU) — construye `Usuario` como dependencia.

* **`login(Request)`**: si ya hay `$_SESSION['user']`, redirige a `home`; si no, `render('auth/login', ['error' => null])`.
* **`autenticar(Request)`**: exige POST; toma `correo`/`password` del `Request`; llama a `Usuario::buscarPorCorreo()`; valida con `password_verify()`; valida `estado`; si todo es correcto, `session_regenerate_id(true)`, arma `$_SESSION['user']` (id, nombre, correo, rol) y redirige a `home`. Si falla, vuelve a renderizar `auth/login` con `$error`.
* **`logout()`**: vacía `$_SESSION`, `session_destroy()`, redirige a `home`.

No hay un segundo controlador en este CU (el registro público, aunque vive en `AuthController`, se documenta en CU02 por ser conceptualmente una creación de cuenta).

### 3. Modelo

**`Usuario`** (tabla `USUARIO`):

* `buscarPorCorreo(correo)` → `SELECT * FROM USUARIO WHERE correo = :correo` — trae la fila completa (incluye `password_hash` y `estado`) para que el controlador verifique credenciales y estado de la cuenta.

No se ejecuta ningún `INSERT`/`UPDATE` en este CU: login y logout solo leen y trabajan con la sesión PHP.

### 4. Funcionamiento completo

1. El usuario abre `auth/login.php` (vía `AuthController::login`).
2. Envía el formulario por POST → `AuthController::autenticar()`.
3. El controlador llama a `Usuario::buscarPorCorreo()`, que ejecuta el `SELECT` contra `USUARIO`.
4. El controlador valida el hash y el estado en PHP (no en el modelo).
5. Si es válido, crea `$_SESSION['user']` y redirige a `HomeController::index` → `home/index.php`.
6. Si es inválido, vuelve a `auth/login.php` con un mensaje de error, sin tocar la base de datos de nuevo.
7. En cualquier momento, desde la barra de navegación, `AuthController::logout()` destruye la sesión y vuelve a `home`.

### Componentes para el diagrama

```
[Vista] auth/login.php
   -> (POST autenticar) [Controlador] AuthController::autenticar
        -> [Modelo] Usuario::buscarPorCorreo -> SELECT USUARIO
        <- fila de USUARIO (o null)
   -> (éxito) redirect [Controlador] HomeController::index -> [Vista] home/index.php
   -> (error) [Vista] auth/login.php (con $error)

[Vista] nav_*.php (enlace "Cerrar sesión")
   -> [Controlador] AuthController::logout (sin modelo)
   -> redirect [Vista] home/index.php
```

---

## CU02 — Gestionar cuentas de usuario

**Actores:** Administrador (control total); Instructor y Cliente (solo su perfil); visitante anónimo (auto-registro).

Este CU tiene **dos controladores** porque el auto-registro público (sin sesión) y la administración de cuentas (con sesión de administrador) son entradas completamente distintas al sistema, aunque ambas terminan escribiendo en las mismas tablas.

### 1. Vista

| Archivo | Uso |
|---|---|
| `app/views/auth/register.php` | Formulario público de auto-registro (`ci`, `nombres`, `apellidos`, `fecha_nacimiento`, `correo`, `password`, `password_confirmacion`). POST a `AuthController::crearCuenta`. |
| `app/views/usuario/index.php` | Tabla con todas las cuentas (`$usuarios`), insignias de rol/estado, botones "Editar" y "Activar/Desactivar". Solo administrador. |
| `app/views/usuario/crear.php` | Formulario de alta con selector de rol; si el rol es `instructor` se muestra el campo "especialidad" (mostrado/ocultado con `onchange` en el `<select>`, aunque la validación real ocurre en el backend). POST a `UsuarioController::guardar`. |
| `app/views/usuario/editar.php` | Igual que crear, pero el rol viene fijo/deshabilitado y no incluye contraseña. POST a `UsuarioController::actualizar`. |
| `app/views/usuario/perfil.php` | Formulario de datos personales del usuario en sesión; si es cliente muestra altura/peso, si es instructor muestra especialidad. POST a `UsuarioController::actualizarPerfil`. |

### 2. Controlador

**`AuthController`** — auto-registro público:

* `register(Request)`: si hay sesión, redirige a `home`; si no, `render('auth/register')`.
* `crearCuenta(Request)`: exige POST; arma `$datos` desde el `Request`; valida con `validarRegistro()` (obligatorios, formato de correo, contraseña ≥ 6, confirmación, correo/CI únicos vía `Usuario::buscarPorCorreo`/`buscarPorCi`); si es válido, llama a `Usuario::crear()` (con `rol` forzado a `'cliente'` por código) y luego a `Cliente::crear($idUsuario, null, null)`; redirige a `auth/login`.

**`UsuarioController`** — administración (todas las acciones abren con `AuthMiddleware::handle()` + `RoleMiddleware::handle(['administrador'])`, salvo `perfil()`/`actualizarPerfil()` que solo exigen sesión):

* `index()`: `Usuario::listarTodos()` → `usuario/index`.
* `crear()`/`guardar(Request)`: `guardar()` valida con `validarDatosBasicos()` (+ especialidad obligatoria si rol=instructor); llama a `Usuario::crear()` y, según el rol elegido, a `Instructor::crear()` o `Cliente::crear()`; redirige a `usuario/index`.
* `editar(Request)`/`actualizar(Request)`: `editar()` trae `Usuario::buscarPorId()` y, si es instructor, `Instructor::buscarPorId()` para precargar la especialidad; `actualizar()` valida y llama a `Usuario::actualizar()` y, si corresponde, `Instructor::actualizar()`.
* `cambiarEstado(Request)`: si el id no es el del propio administrador en sesión, llama a `Usuario::cambiarEstado()`.
* `perfil()`: usa el método privado `datosPerfil($id)`, que llama a `Usuario::buscarPorId()` y, según el rol, a `Cliente::buscarPorId()` o `Instructor::buscarPorId()`.
* `actualizarPerfil(Request)`: llama a `Usuario::actualizar()` y, según el rol, a `Cliente::actualizar()` (altura/peso) o `Instructor::actualizar()` (especialidad); además actualiza `$_SESSION['user']['nombre']`/`['correo']`.

Relación entre ambos controladores: `AuthController` es el único punto de entrada para crear una cuenta **sin sesión** y siempre de rol `cliente`; `UsuarioController` es el único que permite crear cuentas de **cualquier rol**, pero exige sesión de administrador. Ambos delegan la parte "hija" de la cuenta (`Cliente`/`Instructor`) de la misma forma, reflejando la herencia por tabla de `USUARIO`.

### 3. Modelo

**`Usuario`** (tabla `USUARIO`):
* `crear(datos)` → `INSERT INTO USUARIO (...) VALUES (...) RETURNING id_usuario`.
* `actualizar(id, datos)` → `UPDATE USUARIO SET ci=, nombres=, apellidos=, fecha_nacimiento=, correo= WHERE id_usuario=:id`.
* `cambiarEstado(id, estado)` → `UPDATE USUARIO SET estado=:estado WHERE id_usuario=:id`.
* `buscarPorId(id)` → `SELECT * FROM USUARIO WHERE id_usuario=:id`.
* `buscarPorCorreo(correo)` / `buscarPorCi(ci)` → `SELECT * FROM USUARIO WHERE correo=:correo` / `WHERE ci=:ci` (usados tanto para login como para validar unicidad).
* `listarTodos()` → `SELECT * FROM USUARIO ORDER BY apellidos, nombres`.

**`Cliente`** (tabla `CLIENTE`, hereda de `USUARIO` por PK/FK compartida):
* `crear(idUsuario, altura, peso)` → `INSERT INTO CLIENTE (id_usuario, altura, peso) VALUES (...)`.
* `actualizar(idUsuario, altura, peso)` → `UPDATE CLIENTE SET altura=, peso= WHERE id_usuario=`.
* `buscarPorId(idUsuario)` → `SELECT u.*, c.fecha_registro, c.altura, c.peso FROM CLIENTE c JOIN USUARIO u ON u.id_usuario=c.id_usuario WHERE c.id_usuario=:id_usuario`.

**`Instructor`** (tabla `INSTRUCTOR`, misma herencia):
* `crear(idUsuario, especialidad)` → `INSERT INTO INSTRUCTOR (id_usuario, especialidad) VALUES (...)`.
* `actualizar(idUsuario, especialidad)` → `UPDATE INSTRUCTOR SET especialidad= WHERE id_usuario=`.
* `buscarPorId(idUsuario)` → `SELECT u.*, i.especialidad FROM INSTRUCTOR i JOIN USUARIO u ON u.id_usuario=i.id_usuario WHERE i.id_usuario=:id_usuario`.

Relación entre modelos: cada alta o edición de cuenta primero escribe en `USUARIO` (datos comunes) y luego, según el rol, en `CLIENTE` o `INSTRUCTOR` (datos específicos) — nunca al revés, porque `CLIENTE.id_usuario`/`INSTRUCTOR.id_usuario` son FK hacia `USUARIO.id_usuario`.

### 4. Funcionamiento completo

**Auto-registro:** `auth/register.php` → `AuthController::crearCuenta` → valida → `Usuario::crear()` (INSERT en `USUARIO`) → `Cliente::crear()` (INSERT en `CLIENTE`) → redirige a `auth/login.php`.

**Alta por administrador:** `usuario/crear.php` → `UsuarioController::guardar` → valida → `Usuario::crear()` → `Instructor::crear()` o `Cliente::crear()` según el rol elegido → redirige a `usuario/index.php` (que vuelve a llamar a `Usuario::listarTodos()`).

**Edición/estado:** `usuario/editar.php` → `UsuarioController::actualizar` → `Usuario::actualizar()` (+ `Instructor::actualizar()` si aplica) → `usuario/index.php`. Desde la misma tabla, el botón de estado dispara `UsuarioController::cambiarEstado` → `Usuario::cambiarEstado()`.

**Perfil propio:** `usuario/perfil.php` → `UsuarioController::actualizarPerfil` → `Usuario::actualizar()` + (`Cliente::actualizar()` o `Instructor::actualizar()`) → vuelve a renderizar `usuario/perfil.php` con los datos frescos (`datosPerfil()` vuelve a consultar los tres modelos).

### Componentes para el diagrama

```
[Vista] auth/register.php
  -> [Controlador] AuthController::crearCuenta
       -> [Modelo] Usuario::buscarPorCorreo / buscarPorCi  (validación de unicidad)
       -> [Modelo] Usuario::crear -> INSERT USUARIO
       -> [Modelo] Cliente::crear -> INSERT CLIENTE
  -> redirect [Vista] auth/login.php

[Vista] usuario/index.php
  -> [Controlador] UsuarioController::index -> [Modelo] Usuario::listarTodos -> SELECT USUARIO

[Vista] usuario/crear.php
  -> [Controlador] UsuarioController::guardar
       -> [Modelo] Usuario::crear -> INSERT USUARIO
       -> [Modelo] Instructor::crear -> INSERT INSTRUCTOR   (si rol=instructor)
       -> [Modelo] Cliente::crear -> INSERT CLIENTE         (si rol=cliente)
  -> redirect [Vista] usuario/index.php

[Vista] usuario/editar.php
  -> [Controlador] UsuarioController::actualizar
       -> [Modelo] Usuario::actualizar -> UPDATE USUARIO
       -> [Modelo] Instructor::actualizar -> UPDATE INSTRUCTOR (si rol=instructor)
  -> redirect [Vista] usuario/index.php

[Vista] usuario/index.php (botón estado)
  -> [Controlador] UsuarioController::cambiarEstado -> [Modelo] Usuario::cambiarEstado -> UPDATE USUARIO
  -> redirect [Vista] usuario/index.php

[Vista] usuario/perfil.php
  -> [Controlador] UsuarioController::actualizarPerfil
       -> [Modelo] Usuario::actualizar -> UPDATE USUARIO
       -> [Modelo] Cliente::actualizar -> UPDATE CLIENTE       (si rol=cliente)
       -> [Modelo] Instructor::actualizar -> UPDATE INSTRUCTOR (si rol=instructor)
  -> [Vista] usuario/perfil.php (recargada con datosPerfil())
```

---

## CU03 — Administrar catálogo de grupos musculares

**Actores:** Administrador, Instructor (mismo permiso).

### 1. Vista

| Archivo | Uso |
|---|---|
| `app/views/grupo_muscular/index.php` | Tabla con el catálogo (`$grupos`), botones "Editar" y "Eliminar" (este último con `onsubmit="return confirm(...)"`). |
| `app/views/grupo_muscular/crear.php` | Formulario `nombre`/`descripcion`. POST a `GrupoMuscularController::guardar`. |
| `app/views/grupo_muscular/editar.php` | Igual, precargado con `$grupo`. POST a `GrupoMuscularController::actualizar`. |

### 2. Controlador

**`GrupoMuscularController`** (único controlador). Todas las acciones llaman primero a `verificarAcceso()` (`AuthMiddleware::handle()` + `RoleMiddleware::handle(['administrador','instructor'])`).

* `index()` → `GrupoMuscular::listarTodos()`.
* `crear()`/`guardar(Request)`: `guardar()` valida con `validar()` (nombre obligatorio y único, vía `GrupoMuscular::buscarPorNombre()`), luego `GrupoMuscular::crear()`.
* `editar(Request)`/`actualizar(Request)`: `editar()` trae `GrupoMuscular::buscarPorId()` (404 si no existe); `actualizar()` valida (excluyendo el propio id) y llama a `GrupoMuscular::actualizar()`.
* `eliminar(Request)`: exige POST, llama a `GrupoMuscular::eliminar()`.

### 3. Modelo

**`GrupoMuscular`** (tabla `GRUPO_MUSCULAR`):
* `crear(nombre, descripcion)` → `INSERT INTO GRUPO_MUSCULAR (nombre, descripcion) VALUES (...) RETURNING id_grupo_muscular`.
* `actualizar(id, nombre, descripcion)` → `UPDATE GRUPO_MUSCULAR SET nombre=, descripcion= WHERE id_grupo_muscular=:id`.
* `eliminar(id)` → `DELETE FROM GRUPO_MUSCULAR WHERE id_grupo_muscular=:id` (seguro porque `EJERCICIO_GRUPO_MUSCULAR` tiene `ON DELETE CASCADE` hacia esta tabla).
* `buscarPorId(id)` → `SELECT * FROM GRUPO_MUSCULAR WHERE id_grupo_muscular=:id`.
* `buscarPorNombre(nombre)` → `SELECT * FROM GRUPO_MUSCULAR WHERE nombre=:nombre` (para validar unicidad).
* `listarTodos()` → `SELECT * FROM GRUPO_MUSCULAR ORDER BY nombre`.

### 4. Funcionamiento completo

CRUD directo de un solo controlador sobre un solo modelo: la vista envía el formulario, el controlador valida y delega en `GrupoMuscular`, y siempre redirige de vuelta a `grupo_muscular/index.php`, que vuelve a pedir el listado completo.

### Componentes para el diagrama

```
[Vista] grupo_muscular/index.php
  -> [Controlador] GrupoMuscularController::index -> [Modelo] GrupoMuscular::listarTodos -> SELECT GRUPO_MUSCULAR

[Vista] grupo_muscular/crear.php
  -> [Controlador] GrupoMuscularController::guardar
       -> [Modelo] GrupoMuscular::buscarPorNombre (validación)
       -> [Modelo] GrupoMuscular::crear -> INSERT GRUPO_MUSCULAR
  -> redirect [Vista] grupo_muscular/index.php

[Vista] grupo_muscular/editar.php
  -> [Controlador] GrupoMuscularController::actualizar
       -> [Modelo] GrupoMuscular::buscarPorNombre (validación)
       -> [Modelo] GrupoMuscular::actualizar -> UPDATE GRUPO_MUSCULAR
  -> redirect [Vista] grupo_muscular/index.php

[Vista] grupo_muscular/index.php (botón eliminar)
  -> [Controlador] GrupoMuscularController::eliminar -> [Modelo] GrupoMuscular::eliminar -> DELETE GRUPO_MUSCULAR
  -> redirect [Vista] grupo_muscular/index.php
```

---

## CU04 — Administrar catálogo de ejercicios

**Actores:** Administrador, Instructor.

### 1. Vista

| Archivo | Uso |
|---|---|
| `app/views/ejercicio/index.php` | Tabla con `$ejercicios`, botones "Ver", "Editar", "Eliminar"; muestra `$error` si un borrado fue rechazado. |
| `app/views/ejercicio/crear.php` | Formulario (`nombre`, `descripcion`, `beneficio`, `indicaciones`, `url_video`) + checkboxes de `$gruposDisponibles`. POST a `EjercicioController::guardar`. |
| `app/views/ejercicio/editar.php` | Igual, con `$gruposSeleccionados` ya marcados. POST a `EjercicioController::actualizar`. |
| `app/views/ejercicio/ver.php` | Detalle de un ejercicio junto a `$grupos` (sus grupos musculares asociados). |

### 2. Controlador

**`EjercicioController`** (único controlador; también instancia `GrupoMuscular` para poblar los checkboxes y `EjercicioGrupoMuscular` para la relación N:M). Todas las acciones llaman a `verificarAcceso()` (administrador o instructor).

* `index()` → `Ejercicio::listarTodos()`.
* `ver(Request)` → `Ejercicio::buscarPorId()` (404 si no existe) + `EjercicioGrupoMuscular::listarGruposPorEjercicio()`.
* `crear()` → `GrupoMuscular::listarTodos()` (para los checkboxes).
* `guardar(Request)`: arma `$datos` con `datosFormulario()`, toma `grupos[]` del POST; valida con `validar()` (nombre obligatorio y único vía `Ejercicio::buscarPorNombre()`); llama a `Ejercicio::crear()` y luego a `EjercicioGrupoMuscular::asociar($idEjercicio, $gruposSeleccionados)`.
* `editar(Request)` → `Ejercicio::buscarPorId()` + `GrupoMuscular::listarTodos()` + `EjercicioGrupoMuscular::listarGruposPorEjercicio()` (para precargar los checkboxes ya marcados).
* `actualizar(Request)`: igual que `guardar()` pero llama a `Ejercicio::actualizar()` y `EjercicioGrupoMuscular::asociar()`.
* `eliminar(Request)`: llama a `Ejercicio::eliminar()`; si devuelve `false` (porque `DETALLE_RUTINA` lo referencia con `ON DELETE RESTRICT`), vuelve a `ejercicio/index` con un mensaje de error en vez de redirigir.

### 3. Modelo

**`Ejercicio`** (tabla `EJERCICIO`):
* `crear(datos)` → `INSERT INTO EJERCICIO (nombre, descripcion, beneficio, indicaciones, url_video) VALUES (...) RETURNING id_ejercicio`.
* `actualizar(id, datos)` → `UPDATE EJERCICIO SET nombre=, descripcion=, beneficio=, indicaciones=, url_video= WHERE id_ejercicio=:id`.
* `eliminar(id)` → intenta `DELETE FROM EJERCICIO WHERE id_ejercicio=:id`; captura `PDOException` (violación de `ON DELETE RESTRICT` desde `DETALLE_RUTINA`) y devuelve `false` en ese caso.
* `buscarPorId(id)` → `SELECT * FROM EJERCICIO WHERE id_ejercicio=:id`.
* `buscarPorNombre(nombre)` → `SELECT * FROM EJERCICIO WHERE nombre=:nombre`.
* `listarTodos()` → `SELECT * FROM EJERCICIO ORDER BY nombre`.

**`EjercicioGrupoMuscular`** (tabla pivote `EJERCICIO_GRUPO_MUSCULAR`, N:M):
* `asociar(idEjercicio, idsGrupoMuscular)` → `DELETE FROM EJERCICIO_GRUPO_MUSCULAR WHERE id_ejercicio=:id_ejercicio` seguido de un `INSERT INTO EJERCICIO_GRUPO_MUSCULAR (id_ejercicio, id_grupo_muscular) VALUES (...)` por cada id seleccionado. No hay `UPDATE`: siempre se reemplaza el conjunto completo.
* `listarGruposPorEjercicio(idEjercicio)` → `SELECT gm.* FROM EJERCICIO_GRUPO_MUSCULAR egm JOIN GRUPO_MUSCULAR gm ON gm.id_grupo_muscular=egm.id_grupo_muscular WHERE egm.id_ejercicio=:id_ejercicio ORDER BY gm.nombre`.

**`GrupoMuscular`**: reutiliza `listarTodos()` (ver CU03) solo para poblar los checkboxes del formulario; este CU no modifica esa tabla.

### 4. Funcionamiento completo

1. `ejercicio/crear.php` (o `editar.php`) muestra el formulario junto a los checkboxes de `GrupoMuscular::listarTodos()`.
2. Al enviar, `EjercicioController::guardar()`/`actualizar()` valida y guarda primero el ejercicio (`Ejercicio::crear()`/`actualizar()`), obteniendo/usando su `id_ejercicio`.
3. Inmediatamente después sincroniza sus grupos musculares con `EjercicioGrupoMuscular::asociar()` (borra todo y reinserta la selección actual).
4. Redirige a `ejercicio/index.php`, que vuelve a listar con `Ejercicio::listarTodos()`.
5. Al pedir `ver()`, se cargan ejercicio + sus grupos asociados y se muestran juntos en `ejercicio/ver.php`.
6. Al eliminar, si `DETALLE_RUTINA` ya usa ese ejercicio, la base de datos rechaza el `DELETE`; el controlador lo detecta y vuelve a `ejercicio/index.php` con un mensaje, sin producir un error 500.

### Componentes para el diagrama

```
[Vista] ejercicio/index.php
  -> [Controlador] EjercicioController::index -> [Modelo] Ejercicio::listarTodos -> SELECT EJERCICIO

[Vista] ejercicio/ver.php
  -> [Controlador] EjercicioController::ver
       -> [Modelo] Ejercicio::buscarPorId -> SELECT EJERCICIO
       -> [Modelo] EjercicioGrupoMuscular::listarGruposPorEjercicio -> SELECT (JOIN EJERCICIO_GRUPO_MUSCULAR + GRUPO_MUSCULAR)

[Vista] ejercicio/crear.php
  -> [Controlador] EjercicioController::crear -> [Modelo] GrupoMuscular::listarTodos -> SELECT GRUPO_MUSCULAR
  -> (POST) [Controlador] EjercicioController::guardar
       -> [Modelo] Ejercicio::buscarPorNombre (validación)
       -> [Modelo] Ejercicio::crear -> INSERT EJERCICIO
       -> [Modelo] EjercicioGrupoMuscular::asociar -> DELETE + INSERT EJERCICIO_GRUPO_MUSCULAR
  -> redirect [Vista] ejercicio/index.php

[Vista] ejercicio/editar.php
  -> [Controlador] EjercicioController::editar
       -> [Modelo] Ejercicio::buscarPorId
       -> [Modelo] GrupoMuscular::listarTodos
       -> [Modelo] EjercicioGrupoMuscular::listarGruposPorEjercicio
  -> (POST) [Controlador] EjercicioController::actualizar
       -> [Modelo] Ejercicio::actualizar -> UPDATE EJERCICIO
       -> [Modelo] EjercicioGrupoMuscular::asociar -> DELETE + INSERT EJERCICIO_GRUPO_MUSCULAR
  -> redirect [Vista] ejercicio/index.php

[Vista] ejercicio/index.php (botón eliminar)
  -> [Controlador] EjercicioController::eliminar -> [Modelo] Ejercicio::eliminar -> DELETE EJERCICIO
       -> (rechazado por ON DELETE RESTRICT) [Vista] ejercicio/index.php con $error
       -> (éxito) redirect [Vista] ejercicio/index.php
```

---

## CU05 — Gestionar evaluación física

**Actores:** Instructor (registra y consulta cualquier historial); Cliente (consulta solo el propio).

### 1. Vista

| Archivo | Uso |
|---|---|
| `app/views/evaluacion_fisica/registrar.php` | Formulario con `<select>` de `$clientes` (`Cliente::listarTodos()`) y campos peso, altura, objetivo, % grasa, masa muscular, flexibilidad, observaciones. POST a `EvaluacionFisicaController::guardar`. Solo instructor. |
| `app/views/evaluacion_fisica/historial.php` | Doble comportamiento: si el rol es cliente, tabla directa de `$evaluaciones`; si es instructor sin `id` en la URL, muestra tarjetas seleccionables de `$clientes`; si es instructor con `id`, tabla de `$evaluaciones` de ese cliente. |

### 2. Controlador

**`EvaluacionFisicaController`** (único controlador; usa `EvaluacionFisica` y `Cliente`).

* `registrar()` (`RoleMiddleware::handle(['instructor'])`) → `Cliente::listarTodos()` para el `<select>`.
* `guardar(Request)` (solo instructor): arma `$datos` desde el `Request`; valida con `validar()` (cliente seleccionado, peso/altura obligatorios y numéricos > 0); llama a `EvaluacionFisica::crear()` con `id_instructor` tomado de `$_SESSION['user']['id']` (nunca del formulario); luego llama a `Cliente::actualizar($idCliente, $altura, $peso)` para sincronizar el peso/altura vigente del cliente; redirige a `historial` pasando `id` del cliente evaluado.
* `historial(Request)` (`RoleMiddleware::handle(['instructor','cliente'])`): si el rol en sesión es `cliente`, ignora cualquier `id` de la URL y usa siempre `$_SESSION['user']['id']`, llamando a `EvaluacionFisica::listarPorCliente()`. Si el rol es `instructor`: si no llega `?id=`, solo carga `Cliente::listarTodos()` para el selector (sin evaluaciones); si llega `id`, llama a `Cliente::buscarPorId()` y, si existe, a `EvaluacionFisica::listarPorCliente()`.

### 3. Modelo

**`EvaluacionFisica`** (tabla `EVALUACION_FISICA`):
* `crear(datos)` → `INSERT INTO EVALUACION_FISICA (peso, altura, objetivo, porcentaje_grasa, masa_muscular, flexibilidad, observaciones, id_cliente, id_instructor) VALUES (...) RETURNING id_evaluacion_fisica`.
* `listarPorCliente(idCliente)` → `SELECT ef.*, u.nombres AS instructor_nombres, u.apellidos AS instructor_apellidos FROM EVALUACION_FISICA ef JOIN USUARIO u ON u.id_usuario=ef.id_instructor WHERE ef.id_cliente=:id_cliente ORDER BY ef.fecha DESC, ef.id_evaluacion_fisica DESC`.

**`Cliente`** (tabla `CLIENTE`, reutilizado de CU02):
* `listarTodos()` → para el `<select>`/tarjetas de clientes.
* `buscarPorId(idCliente)` → para mostrar el cliente elegido en el historial.
* `actualizar(idCliente, altura, peso)` → `UPDATE CLIENTE SET altura=, peso= WHERE id_usuario=` — sincroniza los valores "actuales" del cliente después de cada evaluación; `EVALUACION_FISICA` conserva el histórico completo por separado.

### 4. Funcionamiento completo

1. El instructor abre `evaluacion_fisica/registrar.php` (carga `Cliente::listarTodos()`).
2. Envía el formulario → `EvaluacionFisicaController::guardar()` valida y llama a `EvaluacionFisica::crear()` (INSERT en `EVALUACION_FISICA`) y luego a `Cliente::actualizar()` (UPDATE en `CLIENTE`).
3. Redirige a `evaluacion_fisica/historial.php?id=<idCliente>`, que dispara `historial()` con ese id y muestra de inmediato la evaluación recién creada dentro del historial completo (`EvaluacionFisica::listarPorCliente()`).
4. Un cliente que entra directamente a `historial()` siempre ve su propio historial, sin selector y sin poder cambiar de cliente vía URL.
5. Un instructor que entra sin `id` ve primero el selector de clientes (`Cliente::listarTodos()`) antes de ver ninguna evaluación.

### Componentes para el diagrama

```
[Vista] evaluacion_fisica/registrar.php
  -> [Controlador] EvaluacionFisicaController::registrar -> [Modelo] Cliente::listarTodos -> SELECT CLIENTE JOIN USUARIO
  -> (POST) [Controlador] EvaluacionFisicaController::guardar
       -> [Modelo] EvaluacionFisica::crear -> INSERT EVALUACION_FISICA
       -> [Modelo] Cliente::actualizar -> UPDATE CLIENTE
  -> redirect [Vista] evaluacion_fisica/historial.php (con id del cliente)

[Vista] evaluacion_fisica/historial.php (rol cliente)
  -> [Controlador] EvaluacionFisicaController::historial
       -> [Modelo] EvaluacionFisica::listarPorCliente -> SELECT EVALUACION_FISICA JOIN USUARIO

[Vista] evaluacion_fisica/historial.php (rol instructor, sin id)
  -> [Controlador] EvaluacionFisicaController::historial -> [Modelo] Cliente::listarTodos -> SELECT CLIENTE JOIN USUARIO

[Vista] evaluacion_fisica/historial.php (rol instructor, con id)
  -> [Controlador] EvaluacionFisicaController::historial
       -> [Modelo] Cliente::listarTodos
       -> [Modelo] Cliente::buscarPorId -> SELECT CLIENTE JOIN USUARIO
       -> [Modelo] EvaluacionFisica::listarPorCliente -> SELECT EVALUACION_FISICA JOIN USUARIO
```

---

## CU06 — Gestionar rutinas de entrenamiento

**Actores:** Instructor (crea, edita, asigna ejercicios, visualiza las que creó); Cliente (visualiza las que tiene asignadas).

Es el CU con más pasos: una rutina tiene datos generales (`RUTINA`) y una lista de ejercicios (`DETALLE_RUTINA`), gestionados en pantallas separadas.

### 1. Vista

| Archivo | Uso |
|---|---|
| `app/views/rutina/index.php` | Lista de rutinas (`$rutinas`); columnas/botones varían según `$rol` (cliente no ve "Editar"/"Ejercicios", solo "Ver"). |
| `app/views/rutina/crear.php` | Formulario general (`nombre`, `tipo`, `fecha_inicio`, `fecha_fin`) + `<select>` de `$clientes`. POST a `RutinaController::guardar`. Solo instructor. |
| `app/views/rutina/editar.php` | Igual sin el `<select>` de cliente, más `estado` (activa/completada/cancelada). POST a `RutinaController::actualizar`. |
| `app/views/rutina/asignar.php` | Pantalla de gestión de ejercicios de una rutina puntual: tabla de `$detalle` ya agregado + formulario para agregar uno nuevo (`<select>` de `$ejercicios` y de `$dias`). Botones "Agregar" (POST a `agregarEjercicio`) y "Quitar" (POST a `quitarEjercicio`). Solo instructor. |
| `app/views/rutina/ver.php` | Detalle de una rutina con `$detallePorDia` (ejercicios agrupados por día, Lunes→Domingo); idéntica para instructor y cliente salvo un botón condicional "Gestionar ejercicios" que solo aparece si el usuario en sesión es el instructor dueño. |

### 2. Controlador

**`RutinaController`** (único controlador; usa `Rutina`, `DetalleRutina`, `Cliente` y `Ejercicio`).

* `index()` (`RoleMiddleware::handle(['instructor','cliente'])`): según el rol, `Rutina::listarPorInstructor($id)` o `Rutina::listarPorCliente($id)`.
* `ver(Request)` (ambos roles): `Rutina::buscarPorId()`; si no existe o `!tieneAcceso($rutina)` (instructor dueño o cliente asignado), 404; si no, `DetalleRutina::listarPorRutina($id)` y lo agrupa en PHP con `agruparPorDia()` (usa la constante `DIAS_SEMANA` para fijar el orden).
* `crear()`/`guardar(Request)` (solo instructor): `crear()` trae `Cliente::listarTodos()` para el `<select>`; `guardar()` valida con `validarDatosGenerales()` (nombre y fecha de inicio obligatorios, fecha fin ≥ fecha inicio, cliente seleccionado) y llama a `Rutina::crear()` con `id_instructor` de la sesión; redirige a `asignar` con el id recién creado.
* `editar(Request)`/`actualizar(Request)` (solo instructor, requiere `esPropietario($rutina)`): `actualizar()` valida (`validarDatosGenerales()` + estado válido) y llama a `Rutina::actualizar()`; redirige a `ver`.
* `asignar(Request)` (solo instructor, requiere `esPropietario`): carga `DetalleRutina::listarPorRutina()`, `Ejercicio::listarTodos()` y `DIAS_SEMANA` para el formulario de detalle.
* `agregarEjercicio(Request)` (solo instructor, requiere `esPropietario`): valida con `validarDetalle()` (ejercicio y día seleccionados, series/repeticiones > 0, descanso ≥ 0) y llama a `DetalleRutina::agregar()`; redirige de vuelta a `asignar`.
* `quitarEjercicio(Request)` (solo instructor, requiere `esPropietario`): llama a `DetalleRutina::eliminar($idDetalle, $idRutina)`; redirige a `asignar`.

Control de propiedad (`esPropietario`) y de acceso de lectura (`tieneAcceso`) se resuelven enteramente en el controlador comparando `$_SESSION['user']['id']` contra `id_instructor`/`id_cliente` de la fila ya traída del modelo — no hay una consulta SQL dedicada a esa verificación.

### 3. Modelo

**`Rutina`** (tabla `RUTINA`):
* `crear(datos)` → `INSERT INTO RUTINA (nombre, tipo, fecha_inicio, fecha_fin, id_cliente, id_instructor) VALUES (...) RETURNING id_rutina`.
* `actualizar(id, datos)` → `UPDATE RUTINA SET nombre=, tipo=, fecha_inicio=, fecha_fin=, estado= WHERE id_rutina=:id`.
* `buscarPorId(id)` → `SELECT r.*, uc.nombres/apellidos AS cliente_*, ui.nombres/apellidos AS instructor_* FROM RUTINA r JOIN USUARIO uc ON uc.id_usuario=r.id_cliente JOIN USUARIO ui ON ui.id_usuario=r.id_instructor WHERE r.id_rutina=:id`.
* `listarPorCliente(idCliente)` → `SELECT r.*, ui.nombres/apellidos AS instructor_* FROM RUTINA r JOIN USUARIO ui ON ui.id_usuario=r.id_instructor WHERE r.id_cliente=:id_cliente ORDER BY r.fecha_inicio DESC`.
* `listarPorInstructor(idInstructor)` → `SELECT r.*, uc.nombres/apellidos AS cliente_* FROM RUTINA r JOIN USUARIO uc ON uc.id_usuario=r.id_cliente WHERE r.id_instructor=:id_instructor ORDER BY r.fecha_inicio DESC`.

**`DetalleRutina`** (tabla `DETALLE_RUTINA`):
* `agregar(idRutina, datos)` → `INSERT INTO DETALLE_RUTINA (id_rutina, id_ejercicio, dia_semana, series, repeticiones, tiempo_descanso, orden) VALUES (...) RETURNING id_detalle`.
* `eliminar(idDetalle, idRutina)` → `DELETE FROM DETALLE_RUTINA WHERE id_detalle=:id_detalle AND id_rutina=:id_rutina` (filtra por ambas columnas para que un instructor no borre un detalle de una rutina ajena adivinando el id).
* `listarPorRutina(idRutina)` → `SELECT dr.*, e.nombre AS ejercicio_nombre FROM DETALLE_RUTINA dr JOIN EJERCICIO e ON e.id_ejercicio=dr.id_ejercicio WHERE dr.id_rutina=:id_rutina ORDER BY dr.dia_semana, dr.orden`.

**`Cliente`** y **`Ejercicio`**: reutilizados solo para `listarTodos()` (poblar los `<select>` de cliente y de ejercicio de los formularios); este CU no modifica esas tablas.

### 4. Funcionamiento completo

1. `rutina/crear.php` carga `Cliente::listarTodos()`.
2. Al guardar, `RutinaController::guardar()` valida y llama a `Rutina::crear()` (INSERT en `RUTINA` con `id_instructor` de la sesión).
3. Redirige automáticamente a `rutina/asignar.php?id=<idRutina>`, que carga `DetalleRutina::listarPorRutina()` (vacío al inicio) y `Ejercicio::listarTodos()`.
4. Cada envío del formulario de esa pantalla dispara `agregarEjercicio()` → `DetalleRutina::agregar()` (INSERT en `DETALLE_RUTINA`) y vuelve a la misma pantalla de `asignar`, ahora con una fila más.
5. `quitarEjercicio()` hace lo inverso (`DetalleRutina::eliminar()`), también volviendo a `asignar`.
6. En cualquier momento, `editar()`/`actualizar()` modifican los datos generales (`Rutina::actualizar()`) y redirigen a `ver`.
7. `ver()` trae la rutina (`Rutina::buscarPorId()`) y su detalle completo (`DetalleRutina::listarPorRutina()`), lo agrupa por día en PHP, y lo muestra en `rutina/ver.php` — la misma vista para instructor dueño y cliente asignado, cambiando solo el botón de gestión.
8. `index()` lista, según el rol, las rutinas propias (instructor) o asignadas (cliente), cada una enlazando a `ver()`.

### Componentes para el diagrama

```
[Vista] rutina/index.php
  -> [Controlador] RutinaController::index
       -> [Modelo] Rutina::listarPorInstructor -> SELECT RUTINA JOIN USUARIO   (rol instructor)
       -> [Modelo] Rutina::listarPorCliente -> SELECT RUTINA JOIN USUARIO     (rol cliente)

[Vista] rutina/crear.php
  -> [Controlador] RutinaController::crear -> [Modelo] Cliente::listarTodos -> SELECT CLIENTE JOIN USUARIO
  -> (POST) [Controlador] RutinaController::guardar
       -> [Modelo] Rutina::crear -> INSERT RUTINA
  -> redirect [Vista] rutina/asignar.php (con id de la rutina nueva)

[Vista] rutina/asignar.php
  -> [Controlador] RutinaController::asignar
       -> [Modelo] Rutina::buscarPorId -> SELECT RUTINA JOIN USUARIO (x2)
       -> [Modelo] DetalleRutina::listarPorRutina -> SELECT DETALLE_RUTINA JOIN EJERCICIO
       -> [Modelo] Ejercicio::listarTodos -> SELECT EJERCICIO
  -> (POST agregar) [Controlador] RutinaController::agregarEjercicio
       -> [Modelo] Rutina::buscarPorId (verificar propietario)
       -> [Modelo] DetalleRutina::agregar -> INSERT DETALLE_RUTINA
  -> redirect [Vista] rutina/asignar.php
  -> (POST quitar) [Controlador] RutinaController::quitarEjercicio
       -> [Modelo] Rutina::buscarPorId (verificar propietario)
       -> [Modelo] DetalleRutina::eliminar -> DELETE DETALLE_RUTINA
  -> redirect [Vista] rutina/asignar.php

[Vista] rutina/editar.php
  -> [Controlador] RutinaController::editar -> [Modelo] Rutina::buscarPorId
  -> (POST) [Controlador] RutinaController::actualizar -> [Modelo] Rutina::actualizar -> UPDATE RUTINA
  -> redirect [Vista] rutina/ver.php

[Vista] rutina/ver.php
  -> [Controlador] RutinaController::ver
       -> [Modelo] Rutina::buscarPorId -> SELECT RUTINA JOIN USUARIO (x2)
       -> [Modelo] DetalleRutina::listarPorRutina -> SELECT DETALLE_RUTINA JOIN EJERCICIO
```

---

## Resumen de correspondencia CU → archivos (para armar los paquetes/carpetas del diagrama)

| CU | Vistas | Controlador(es) | Modelo(s) | Tablas |
|---|---|---|---|---|
| CU01 | `auth/login.php`, `home/index.php` | `AuthController` | `Usuario` | `USUARIO` |
| CU02 | `auth/register.php`, `usuario/index.php`, `usuario/crear.php`, `usuario/editar.php`, `usuario/perfil.php` | `AuthController`, `UsuarioController` | `Usuario`, `Cliente`, `Instructor` | `USUARIO`, `CLIENTE`, `INSTRUCTOR` |
| CU03 | `grupo_muscular/index.php`, `crear.php`, `editar.php` | `GrupoMuscularController` | `GrupoMuscular` | `GRUPO_MUSCULAR` |
| CU04 | `ejercicio/index.php`, `crear.php`, `editar.php`, `ver.php` | `EjercicioController` | `Ejercicio`, `EjercicioGrupoMuscular`, `GrupoMuscular` | `EJERCICIO`, `EJERCICIO_GRUPO_MUSCULAR`, `GRUPO_MUSCULAR` |
| CU05 | `evaluacion_fisica/registrar.php`, `historial.php` | `EvaluacionFisicaController` | `EvaluacionFisica`, `Cliente` | `EVALUACION_FISICA`, `CLIENTE`, `USUARIO` |
| CU06 | `rutina/index.php`, `crear.php`, `editar.php`, `asignar.php`, `ver.php` | `RutinaController` | `Rutina`, `DetalleRutina`, `Cliente`, `Ejercicio` | `RUTINA`, `DETALLE_RUTINA`, `CLIENTE`, `EJERCICIO` |
