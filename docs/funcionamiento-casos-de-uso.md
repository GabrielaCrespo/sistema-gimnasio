# Funcionamiento de los Casos de Uso

Este documento explica, caso de uso por caso, cómo está implementada cada funcionalidad: qué archivos intervienen, cómo se comunican entre sí (Vista → Controlador → Modelo → PostgreSQL) y qué reglas de negocio aplica cada uno. Complementa a `docs/casos-de-uso.md` (que describe *qué* hace el sistema) explicando *cómo* lo hace en código.

## 0. Base común a todos los casos de uso

Antes de entrar en cada CU conviene tener claro el mecanismo que comparten todos: no hay un sistema de rutas ni un framework, así que cada caso de uso reutiliza las mismas piezas base.

* **`public/index.php`** — único punto de entrada (front controller). Lee `?controller=&action=` de la URL, autocarga la clase de controlador correspondiente (`spl_autoload_register`) y llama al método de la acción pasándole un objeto `Request`.
* **`app/core/Request.php`** — envuelve `$_GET`/`$_POST`/`$_SERVER` para que los controladores no accedan a las superglobales directamente (`input()`, `esPost()`, `todoPost()`).
* **`app/core/Controller.php`** — clase base de todos los controladores. Su método `render($vista, $datos)` arma la respuesta: incluye `app/views/layouts/header.php`, la vista pedida y `app/views/layouts/footer.php`. `redirect()` hace un `header('Location: ...')`.
* **`app/core/Model.php`** — clase base de todos los modelos; da acceso a `$this->db`, una conexión PDO obtenida de `config/Database.php` (singleton). Todos los modelos ejecutan SQL con sentencias preparadas.
* **`app/core/middlewares/AuthMiddleware.php`** — `AuthMiddleware::handle()` corta la ejecución y redirige a login si no hay `$_SESSION['user']`.
* **`app/core/middlewares/RoleMiddleware.php`** — `RoleMiddleware::handle([...roles])` corta con un 403 si el rol en sesión no está en la lista permitida.
* **`app/views/layouts/`** — `header.php`/`footer.php` arman el HTML común; `header.php` decide qué barra de navegación mostrar (`nav_administrador.php`, `nav_instructor.php` o `nav_cliente.php`) leyendo `$_SESSION['user']['rol']`.
* **`app/core/helpers.php`** — funciones globales `url($controlador, $accion, $parametros)` (arma enlaces internos) y `e($valor)` (escapa HTML para prevenir XSS), usadas en todas las vistas.

Cada controlador concreto llama a `AuthMiddleware::handle()` y `RoleMiddleware::handle([...])` como primeras líneas de cada acción protegida; eso es todo el "control de acceso" del sistema, sin tablas de rutas centralizadas.

---

## CU01 — Gestionar acceso al sistema

**Actores:** Administrador, Instructor, Cliente (inician y cierran sesión).

**Archivos que intervienen:**

| Capa | Archivo | Rol |
|---|---|---|
| Controlador | `app/controllers/AuthController.php` | Orquesta login y logout |
| Modelo | `app/models/Usuario.php` | Busca por correo, verifica credenciales |
| Vista | `app/views/auth/login.php` | Formulario de inicio de sesión |
| Vista | `app/views/home/index.php` | Portada/dashboard tras iniciar sesión |

**Flujo de login:**

1. `AuthController::login()` (GET) simplemente renderiza `auth/login`. Si ya hay sesión activa, redirige a `home` sin mostrar el formulario de nuevo.
2. El formulario envía `correo` y `password` por POST a `AuthController::autenticar()`.
3. `Usuario::buscarPorCorreo($correo)` hace `SELECT * FROM USUARIO WHERE correo = :correo`.
4. Se valida la contraseña con `password_verify($password, $usuario['password_hash'])` — nunca se compara texto plano, siempre contra el hash bcrypt guardado (columna `password_hash VARCHAR(60)`, exactamente el largo de un hash bcrypt).
5. Se comprueba `$usuario['estado']`: si la cuenta fue desactivada (ver CU02), se rechaza el login aunque la contraseña sea correcta.
6. Si todo es válido: `session_regenerate_id(true)` (evita *session fixation*, cambia el id de sesión justo al pasar de anónimo a autenticado) y se guarda `$_SESSION['user'] = ['id', 'nombre', 'correo', 'rol']`. Esa estructura en sesión es la que leen **todos** los demás controladores para saber quién es el usuario y qué rol tiene.
7. Redirige a `home`, que muestra un panel distinto según `$_SESSION['user']['rol']`.

**Flujo de logout:** `AuthController::logout()` vacía `$_SESSION` y llama a `session_destroy()`, luego redirige a `home`. El enlace "Cerrar sesión" está en las tres barras de navegación (`nav_administrador.php`, `nav_instructor.php`, `nav_cliente.php`), visibles en cuanto hay sesión activa.

Nota: `AuthController` también implementa el formulario público de auto-registro (`register()`/`crearCuenta()`). Vive en este controlador por ser el único flujo accesible sin sesión, pero conceptualmente es una operación de **creación de cuenta** y se documenta dentro de CU02 — Gestionar cuentas de usuario.

---

## CU02 — Gestionar cuentas de usuario

**Actores:** Administrador (control total sobre todas las cuentas); Instructor y Cliente (solo su propio perfil); cualquier visitante sin cuenta (auto-registro, solo como cliente).

**Archivos que intervienen:**

| Capa | Archivo | Rol |
|---|---|---|
| Controlador | `app/controllers/UsuarioController.php` | CRUD de cuentas (rol administrador) + perfil propio |
| Controlador | `app/controllers/AuthController.php` | Auto-registro público de cuentas de rol cliente |
| Modelo | `app/models/Usuario.php` | Tabla `USUARIO` (datos comunes a los 3 roles) |
| Modelo | `app/models/Cliente.php` | Tabla `CLIENTE` (altura, peso) |
| Modelo | `app/models/Instructor.php` | Tabla `INSTRUCTOR` (especialidad) |
| Vistas | `app/views/usuario/index.php`, `crear.php`, `editar.php`, `perfil.php` | Listado, alta, edición y perfil propio |
| Vista | `app/views/auth/register.php` | Formulario de auto-registro |

**Auto-registro público (cualquier visitante, sin sesión, solo rol `cliente`):**

1. `AuthController::register()` muestra el formulario (bloqueado si ya hay sesión).
2. `AuthController::crearCuenta()` valida los datos (`validarRegistro()`: campos obligatorios, correo con formato válido, contraseña ≥ 6 caracteres, confirmación de contraseña, correo/CI no repetidos).
3. Si es válido, `Usuario::crear()` inserta en `USUARIO` con `rol` fijado por código en `'cliente'` (el formulario público **no** tiene forma de elegir otro rol) y `password_hash($password, PASSWORD_BCRYPT)`.
4. Inmediatamente después, `Cliente::crear($idUsuario, null, null)` inserta la fila hija en `CLIENTE` (herencia por tabla: `CLIENTE.id_usuario` es a la vez PK y FK a `USUARIO`). Altura y peso quedan `null` hasta que el cliente los complete en su perfil o hasta su primera evaluación física (CU05).
5. Redirige a login para que inicie sesión con la cuenta recién creada.

**Administración (solo rol `administrador`, verificado con `RoleMiddleware::handle(['administrador'])` en cada acción):**

1. **`index()`** — `Usuario::listarTodos()` trae todas las cuentas y la vista las pinta en una tabla con insignias de rol y estado.
2. **`crear()` / `guardar()`** — el formulario deja elegir el rol (administrador/instructor/cliente); si es instructor pide además "especialidad" (el campo se muestra/oculta con un `onchange` en el `<select>`, sin depender de JS para que el backend valide igual). `guardar()` valida (`validarDatosBasicos()`: obligatorios, correo válido, contraseña ≥ 6, correo/CI únicos), inserta en `USUARIO` con `Usuario::crear()` y, según el rol elegido, crea también la fila hija correspondiente (`Instructor::crear()` o `Cliente::crear()`). Esto es la misma **herencia por tabla** que en el auto-registro: cada rol especializado vive en su propia tabla con la misma PK que `USUARIO`.
3. **`editar()` / `actualizar()`** — igual que crear, pero el rol ya no se puede cambiar (se muestra deshabilitado en el formulario) y no se toca la contraseña desde aquí.
4. **`cambiarEstado()`** — activa/desactiva una cuenta (`UPDATE USUARIO SET estado = ...`). **No se implementó DELETE** porque `INSTRUCTOR` y `CLIENTE` referencian a `USUARIO` con `ON DELETE RESTRICT`, y porque un usuario desactivado normalmente ya tiene evaluaciones o rutinas históricas que deben conservarse íntegras. Por seguridad, el controlador impide que un administrador se desactive a sí mismo (`if ($id !== (int) $_SESSION['user']['id'])`).

**Perfil propio (cualquier rol autenticado, solo `AuthMiddleware::handle()`, sin restricción de rol):**

1. **`perfil()`** — arma los datos con el método privado `datosPerfil($id)`: siempre trae el usuario base y, según su rol, también `Cliente::buscarPorId()` (altura/peso) o `Instructor::buscarPorId()` (especialidad).
2. **`actualizarPerfil()`** — valida y actualiza los datos personales (`Usuario::actualizar()`) y, según el rol, `Cliente::actualizar()` (altura/peso) o `Instructor::actualizar()` (especialidad). Además refresca `$_SESSION['user']['nombre']`/`['correo']` para que la barra de navegación muestre el nombre actualizado sin tener que volver a iniciar sesión.

---

## CU03 — Administrar catálogo de grupos musculares

**Actores:** Administrador e Instructor (mismo permiso, `RoleMiddleware::handle(['administrador', 'instructor'])`).

**Archivos:** `app/controllers/GrupoMuscularController.php`, `app/models/GrupoMuscular.php`, vistas en `app/views/grupo_muscular/`.

Es el CRUD más simple del sistema, sobre la tabla `GRUPO_MUSCULAR` (`id_grupo_muscular`, `nombre` único, `descripcion`):

1. **`index()`** lista el catálogo completo (`GrupoMuscular::listarTodos()`).
2. **`crear()`/`guardar()`** y **`editar()`/`actualizar()`** validan que el nombre no esté vacío y que no exista ya otro grupo con el mismo nombre (`buscarPorNombre()`, excluyendo el propio id al editar).
3. **`eliminar()`** hace un `DELETE` real (no hay razón para desactivar en vez de borrar aquí). Es seguro porque `EJERCICIO_GRUPO_MUSCULAR` tiene `ON DELETE CASCADE` hacia esta tabla: al borrar un grupo muscular, PostgreSQL limpia automáticamente sus asociaciones con ejercicios sin afectar a los ejercicios en sí. La vista pide confirmación (`onsubmit="return confirm(...)"`) antes de enviar el formulario de borrado.

Este catálogo es la base que usa CU04 para clasificar ejercicios.

---

## CU04 — Administrar catálogo de ejercicios

**Actores:** Administrador e Instructor.

**Archivos:**

| Capa | Archivo | Rol |
|---|---|---|
| Controlador | `app/controllers/EjercicioController.php` | CRUD de ejercicios + su relación con grupos musculares |
| Modelo | `app/models/Ejercicio.php` | Tabla `EJERCICIO` |
| Modelo | `app/models/EjercicioGrupoMuscular.php` | Tabla pivote `EJERCICIO_GRUPO_MUSCULAR` (relación N:M) |
| Modelo | `app/models/GrupoMuscular.php` | Para listar las opciones del catálogo (checkboxes) |
| Vistas | `app/views/ejercicio/index.php`, `crear.php`, `editar.php`, `ver.php` | |

Un ejercicio puede pertenecer a varios grupos musculares y un grupo muscular puede tener varios ejercicios, de ahí la tabla pivote con clave compuesta `(id_ejercicio, id_grupo_muscular)`.

1. **`crear()`/`editar()`** cargan `GrupoMuscular::listarTodos()` para pintar un checkbox por cada grupo muscular (`ejercicio/crear.php`, `ejercicio/editar.php`); al editar, los checkboxes ya asociados vienen marcados gracias a `EjercicioGrupoMuscular::listarGruposPorEjercicio($id)`.
2. **`guardar()`/`actualizar()`** primero guardan los datos propios del ejercicio (`Ejercicio::crear()`/`actualizar()`: nombre, descripción, beneficio, indicaciones, url de video) y luego llaman a `EjercicioGrupoMuscular::asociar($idEjercicio, $idsSeleccionados)`. Ese método **no hace `UPDATE`**: borra todas las asociaciones existentes del ejercicio y vuelve a insertar la lista completa que llegó del formulario. Es la forma más simple y segura de sincronizar una selección múltiple sin tener que calcular qué filas agregar y cuáles quitar.
3. **`ver()`** muestra el detalle de un ejercicio junto a sus grupos musculares asociados (`EjercicioGrupoMuscular::listarGruposPorEjercicio()`).
4. **`eliminar()`** intenta el `DELETE`, pero `DETALLE_RUTINA` referencia a `EJERCICIO` con `ON DELETE RESTRICT`: si el ejercicio ya se usó en alguna rutina (CU06), PostgreSQL rechaza el borrado con una excepción. `Ejercicio::eliminar()` la captura y devuelve `false`; el controlador entonces vuelve a mostrar el listado con un mensaje de error en vez de dejar pasar un error 500.

---

## CU05 — Gestionar evaluación física

**Actores:** Instructor (registra evaluaciones y puede consultar el historial de cualquier cliente); Cliente (solo puede consultar su propio historial).

**Archivos:** `app/controllers/EvaluacionFisicaController.php` (acciones `registrar`/`guardar`/`historial`), `app/models/EvaluacionFisica.php`, `app/models/Cliente.php`, vistas `app/views/evaluacion_fisica/registrar.php` y `app/views/evaluacion_fisica/historial.php`.

**Registrar una evaluación (solo instructor, `RoleMiddleware::handle(['instructor'])`):**

1. **`registrar()`** carga `Cliente::listarTodos()` para que el instructor elija a qué cliente le va a registrar la evaluación (un `<select>` en el formulario).
2. **`guardar()`** valida (`validar()`: cliente seleccionado, peso y altura obligatorios y numéricos > 0; el resto de campos — % grasa, masa muscular, flexibilidad, objetivo, observaciones — son opcionales) y luego:
   * `EvaluacionFisica::crear()` inserta la fila en `EVALUACION_FISICA`, guardando tanto `id_cliente` como `id_instructor` (que sale de `$_SESSION['user']['id']`, no de un campo del formulario, para que quede registrado con certeza quién hizo la evaluación).
   * `Cliente::actualizar($idCliente, $altura, $peso)` sincroniza la altura/peso **actuales** del cliente en la tabla `CLIENTE`, que es lo que se muestra en su perfil (CU02). La tabla `EVALUACION_FISICA` guarda el histórico completo; `CLIENTE.altura`/`CLIENTE.peso` reflejan siempre el último valor conocido.
3. Al terminar, redirige directo al historial de ese cliente (`evaluacionFisica/historial?id=...`), para que el instructor vea de inmediato la evaluación recién guardada dentro del historial completo.

**Consultar historial (`RoleMiddleware::handle(['instructor', 'cliente'])`, ambos roles entran a la misma acción pero con comportamiento distinto):**

La acción `historial()` se ramifica según el rol en sesión:

* **Si el rol es `cliente`**: se ignora cualquier `id` que venga en la URL y siempre se usa `$_SESSION['user']['id']` — un cliente no puede ver el historial de otro cliente aunque intente adivinar un id en la URL.
* **Si el rol es `instructor`**: si no llega `?id=`, la vista muestra un selector con tarjetas (una por cliente, `Cliente::listarTodos()`) en vez de una tabla vacía; al elegir uno, `historial()` vuelve a ejecutarse con `?id=X` y ahí sí se listan sus evaluaciones.

En ambos casos, `EvaluacionFisica::listarPorCliente($idCliente)` hace el `JOIN` con `USUARIO` para traer también el nombre del instructor que registró cada evaluación, ordenadas de la más reciente a la más antigua (`ORDER BY fecha DESC`).

---

## CU06 — Gestionar rutinas de entrenamiento

**Actores:** Instructor (crea, modifica, asigna ejercicios y visualiza las rutinas que creó); Cliente (visualiza las rutinas que tiene asignadas).

**Archivos:**

| Capa | Archivo | Rol |
|---|---|---|
| Controlador | `app/controllers/RutinaController.php` | Crear, editar, asignar ejercicios y visualizar |
| Modelo | `app/models/Rutina.php` | Tabla `RUTINA` (datos generales) |
| Modelo | `app/models/DetalleRutina.php` | Tabla `DETALLE_RUTINA` (ejercicios de la rutina) |
| Modelo | `app/models/Cliente.php` / `app/models/Ejercicio.php` | Para los selectores de los formularios |
| Vistas | `app/views/rutina/index.php`, `crear.php`, `editar.php`, `asignar.php`, `ver.php` | |

Es el caso de uso con más pasos porque una rutina tiene dos niveles: sus datos generales (`RUTINA`) y la lista de ejercicios que la componen (`DETALLE_RUTINA`), cada uno con día de la semana, series, repeticiones, descanso y orden.

**Crear y asignar ejercicios (solo instructor, `RoleMiddleware::handle(['instructor'])`):**

1. **`crear()`/`guardar()`** — el instructor elige el cliente (`id_cliente` es `NOT NULL` en `RUTINA`, así que "crear" y "asignar a un cliente" son el mismo paso), nombre, tipo y fechas. `validarDatosGenerales()` exige nombre y fecha de inicio, y que la fecha de fin (si se indica) no sea anterior a la de inicio — la misma regla que ya impone el `CHECK chk_rutina_fechas` de la base de datos, validada también en PHP para dar un mensaje de error claro antes de llegar a SQL. `Rutina::crear()` inserta la fila con `id_instructor = $_SESSION['user']['id']` y, al terminar, **redirige directo a `asignar`** para continuar con el segundo paso: agregarle ejercicios.
2. **`asignar()`** — pantalla de gestión de ejercicios de una rutina puntual. Muestra la tabla de ejercicios ya agregados (`DetalleRutina::listarPorRutina()`) y un formulario para agregar uno nuevo, con un `<select>` de ejercicios (`Ejercicio::listarTodos()`) y de días de la semana (constante `RutinaController::DIAS_SEMANA`, que fija tanto las opciones válidas como el orden en que se agrupan más adelante al visualizar la rutina).
3. **`agregarEjercicio()`** — valida (`validarDetalle()`: ejercicio y día seleccionados, series/repeticiones > 0, descanso ≥ 0 — las mismas restricciones que los `CHECK` de `DETALLE_RUTINA`) e inserta con `DetalleRutina::agregar()`. Redirige de vuelta a `asignar` para seguir agregando más ejercicios sin perder el contexto de la rutina.
4. **`quitarEjercicio()`** — borra una fila de `DETALLE_RUTINA`. El `DELETE` filtra tanto por `id_detalle` como por `id_rutina` a propósito: como `id_detalle` es un `SERIAL` compartido entre todas las rutinas (la PK real es compuesta `(id_detalle, id_rutina)`), filtrar solo por `id_rutina` evita que alguien manipule el formulario para borrar un detalle de una rutina ajena.
5. **`editar()`/`actualizar()`** — modifican los datos generales de la rutina (nombre, tipo, fechas, y el `estado`: `activa`/`completada`/`cancelada`). No permiten cambiar el cliente ni el instructor asignados.

**Control de propiedad:** todas las acciones de gestión llaman a `esPropietario($rutina)`, que compara `$rutina['id_instructor']` con `$_SESSION['user']['id']`. Un instructor no puede editar, asignar ejercicios ni quitar ejercicios de una rutina creada por otro instructor, aunque conozca su id.

**Visualizar rutinas (`RoleMiddleware::handle(['instructor', 'cliente'])`, ambos roles entran a las mismas acciones `index()`/`ver()` pero con comportamiento distinto):**

1. **`index()`** — cambia la fuente de datos según el rol en sesión: `Rutina::listarPorInstructor($id)` para instructor o `Rutina::listarPorCliente($id)` para cliente. La misma vista (`rutina/index.php`) ajusta columnas y botones según `$rol` (el cliente no ve enlaces de "Editar" ni "Ejercicios", solo "Ver").
2. **`ver()`** — antes de mostrar nada, llama a `tieneAcceso($rutina)`, que permite el acceso tanto al instructor dueño de la rutina como al cliente al que fue asignada (comparando ambos contra `$_SESSION['user']['id']`); cualquier otro usuario recibe un 404 en vez de un 403, para no confirmarle que el id de rutina existe. Trae el detalle con `DetalleRutina::listarPorRutina($id)` y lo agrupa por día de la semana con `agruparPorDia()` (respetando el orden fijo Lunes→Domingo de `DIAS_SEMANA`), para que la vista pinte una tabla de ejercicios por cada día en vez de una lista plana.

La vista `rutina/ver.php` es idéntica para instructor y cliente; lo único que cambia es un botón condicional ("Gestionar ejercicios") que solo se muestra si `$_SESSION['user']['id']` coincide con `$rutina['id_instructor']` — el cliente ve exactamente la misma información pero en modo solo lectura.
