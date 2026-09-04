# Sistema de Gestión de Gimnasio — Guía para Diagramas UML (Enterprise Architect)

> Este documento se generó analizando exclusivamente el código real del proyecto: `app/controllers/`, `app/models/`, `app/views/`, `app/core/`, `config/`, `public/index.php` y `database/schema.sql`. No contiene clases, métodos, tablas ni relaciones inventadas. Cuando algo no está implementado (por ejemplo, un método definido pero nunca invocado), se indica explícitamente.

---

## 1. Introducción

### 1.1 Objetivo del sistema

El Sistema de Gestión de Gimnasio administra tres tipos de cuentas (**administrador**, **instructor**, **cliente**) y cubre seis funcionalidades principales:

1. Autenticación (inicio/cierre de sesión).
2. Gestión de cuentas de usuario (alta, edición, activación/desactivación, perfil propio, auto‑registro público de clientes).
3. Catálogo de grupos musculares.
4. Catálogo de ejercicios, asociados a uno o varios grupos musculares.
5. Registro y consulta del historial de evaluaciones físicas de los clientes.
6. Creación, edición y asignación de rutinas de entrenamiento (ejercicios por día, series, repeticiones, descanso y orden).

### 1.2 Tecnologías utilizadas

| Tecnología | Uso real en el proyecto |
|---|---|
| PHP (sin framework) | Todo el backend: controladores, modelos, vistas en PHP plano con `<?php ?>` embebido. |
| PostgreSQL | Motor de base de datos, accedido con **PDO** (`pdo_pgsql`). |
| PDO con sentencias preparadas | Todas las consultas en `app/models/*.php` usan `$this->db->prepare(...)->execute([...])`. |
| `.env` + `config/Config.php` | Configuración de conexión (host, puerto, nombre de BD, usuario, clave) fuera del código fuente. |
| Sesiones nativas de PHP (`$_SESSION`) | Autenticación y control de acceso; no hay JWT ni librerías externas. |
| HTML embebido en vistas `.php` | No hay motor de plantillas (ni Twig, ni Blade); las vistas son PHP con HTML directo. |
| `spl_autoload_register` | Autoload manual de clases, en reemplazo de Composer/PSR‑4. |

No se usa ningún framework MVC (no Laravel, no Symfony), ningún ORM (no Doctrine, no Eloquent) ni ningún sistema de rutas declarativo: el "ruteo" es un mapeo directo `?controller=&action=` a `NombreController::metodo()`.

### 1.3 Arquitectura MVC implementada

El proyecto implementa MVC de forma manual, en tres capas físicas más un front controller:

- **Modelo** (`app/models/`): una clase por tabla (o por relación N:M), hereda de `Model` (`app/core/Model.php`), y solo ejecuta SQL contra PostgreSQL mediante PDO.
- **Vista** (`app/views/`): archivos `.php` que reciben variables ya preparadas por el controlador (vía `extract()`); no acceden a `$_SESSION` para lógica de negocio, solo para mostrar datos ya presentes ahí (ej. nombre en la barra de navegación).
- **Controlador** (`app/controllers/`): hereda de `Controller` (`app/core/Controller.php`); orquesta: aplica middlewares, valida datos de entrada, llama a uno o varios modelos, y decide qué vista renderizar o a qué acción redirigir.

### 1.4 Propósito de los diagramas

Este documento sirve como insumo directo para construir manualmente, en Enterprise Architect, dos tipos de diagrama por cada uno de los seis casos de uso (CU01–CU06):

- **Diseño procedimental** (diagrama de comunicación/colaboración, dibujado en EA como diagrama de tipo **Class**): muestra qué objetos/clases colaboran en un CU y qué mensajes (métodos reales) se pasan entre ellos, sin eje temporal.
- **Diagrama de secuencia** (UML tradicional, con líneas de vida y orden temporal): muestra el mismo flujo, pero ordenado en el tiempo, con activaciones, retornos y fragmentos combinados (`alt`, `opt`, `loop`) cuando el código realmente los necesita.

Ambos diagramas, para un mismo CU, deben representar exactamente el mismo conjunto de clases, métodos y mensajes — solo cambia la notación.

---

## 2. Arquitectura General

### 2.1 Flujo real de una petición

```
Navegador
   │  GET/POST /index.php?controller=..&action=..
   ▼
public/index.php  (Front Controller)
   │  session_start()
   │  spl_autoload_register(...)
   │  $request = new Request()
   │  $controlador = new <Nombre>Controller()
   ▼
<Nombre>Controller::<accion>($request)
   │  AuthMiddleware::handle()          (si la acción exige sesión)
   │  RoleMiddleware::handle([roles])   (si la acción exige un rol concreto)
   │  validaciones privadas del controlador (validar(), validarDatosBasicos(), etc.)
   ▼
Modelo (extiende Model)
   │  $this->db->prepare($sql)->execute([...])   (PDO, sentencias preparadas)
   ▼
PostgreSQL  (vía config/Database.php, singleton PDO)
   ▲
   │  filas devueltas (FETCH_ASSOC)
Modelo → Controlador
   │  $this->render('vista/nombre', $datos)   o   $this->redirect('controlador','accion',[...])
   ▼
app/views/layouts/header.php + app/views/<vista>.php + app/views/layouts/footer.php
   ▼
HTML de respuesta
```

### 2.2 Front Controller — `public/index.php`

Único punto de entrada. No existe una tabla de rutas: `Request::__construct()` lee `$_GET['controller']` (por defecto `'home'`) y `$_GET['action']` (por defecto `'index'`). El front controller arma el nombre de clase con `ucfirst($request->controlador) . 'Controller'`, verifica con `class_exists()` y `method_exists()`, y si alguna falla devuelve una vista 404 (`http_response_code(404)` + `errors/404.php`, envuelta manualmente en `header.php`/`footer.php` porque en este punto todavía no existe una instancia de `Controller` para usar `render()`). Si todo existe, instancia el controlador y llama `$controlador->$accion($request)`.

`spl_autoload_register` busca la clase pedida, en orden, en `/config/`, `/app/core/`, `/app/core/middlewares/`, `/app/models/`, `/app/controllers/`, cargando el primer archivo `<Clase>.php` que exista.

### 2.3 Clases base

- **`Controller`** (`app/core/Controller.php`, abstracta): `render(string $vista, array $datos = []): void` hace `extract($datos)` y requiere en orden `layouts/header.php`, el archivo de la vista (o `errors/404.php` si no existe) y `layouts/footer.php`. `redirect(string $controlador, string $accion = 'index', array $parametros = []): void` hace `header('Location: ' . url(...))` y `exit`.
- **`Model`** (`app/core/Model.php`, abstracta): su constructor asigna `$this->db = Database::getConnection()`. No genera HTML ni toca `$_SESSION`.
- **`Request`** (`app/core/Request.php`): expone `$controlador`, `$accion`, `$metodo` (readonly), `esPost(): bool`, `input(string $clave, mixed $default = null): mixed` (POST tiene prioridad sobre GET, hace `trim()` de strings) y `todoPost(): array` (devuelve `$_POST` completo, usado para campos repetidos como `grupos[]`).

### 2.4 Middlewares

- **`AuthMiddleware::handle()`** (estático): si `$_SESSION['user']` está vacío, redirige a `auth/login` y hace `exit`.
- **`RoleMiddleware::handle(array $rolesPermitidos)`** (estático): si el rol de `$_SESSION['user']['rol']` no está en la lista permitida, responde `403` y renderiza `errors/403.php` (envuelto manualmente en header/footer) y hace `exit`.

Cada controlador concreto llama a estos middlewares como primeras líneas de cada acción protegida (o, en `GrupoMuscularController` y `EjercicioController`, a través de un método privado `verificarAcceso()` que agrupa ambas llamadas). No hay una tabla central de permisos: el control de acceso está distribuido en cada acción.

### 2.5 Helpers globales — `app/core/helpers.php`

- `url(string $controlador, string $accion = 'index', array $parametros = []): string` — arma `'/index.php?' . http_build_query([...])`. Usada por todas las vistas y por `Controller::redirect()`.
- `e(mixed $valor): string` — `htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8')`, usada en las vistas para evitar XSS.

### 2.6 Configuración y conexión a PostgreSQL

- **`Config`** (`config/Config.php`): parser propio de `.env` (formato `CLAVE=valor`, ignora líneas vacías y las que empiezan con `#`); `Config::get(string $clave, ?string $default = null): ?string`.
- **`Database`** (`config/Database.php`): singleton; `Database::getConnection(): PDO` arma el DSN `pgsql:host=...;port=...;dbname=...` con `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` leídos de `Config`, y crea el `PDO` con `PDO::ATTR_ERRMODE = PDO::ERRMODE_EXCEPTION` y `PDO::ATTR_DEFAULT_FETCH_MODE = PDO::FETCH_ASSOC`.

### 2.7 Sesiones

`session_start()` se ejecuta una sola vez, en `public/index.php`. La sesión activa se guarda como `$_SESSION['user'] = ['id', 'nombre', 'correo', 'rol']` (ver `AuthController::autenticar()`). Al iniciar sesión se llama `session_regenerate_id(true)` (previene *session fixation*). Al cerrar sesión, `AuthController::logout()` hace `$_SESSION = []` seguido de `session_destroy()`.

### 2.8 Validaciones

No hay una capa de validación aparte: cada controlador implementa métodos privados propios (`validarRegistro()`, `validarDatosBasicos()`, `validar()`, `validarDatosGenerales()`, `validarDetalle()`) que devuelven `?string` (`null` si es válido, o el mensaje de error). Estas validaciones duplican en PHP algunas restricciones que también existen como `CHECK`/`UNIQUE` en PostgreSQL (ver sección 5), para poder mostrar un mensaje amigable antes de llegar a una excepción de base de datos.

### 2.9 Redirecciones y manejo de errores

- Redirecciones: siempre vía `Controller::redirect()` → `header('Location: ...')` + `exit`.
- Error 404: cuando el front controller no encuentra el controlador/acción, o cuando un controlador busca una entidad por id y no la encuentra (`buscarPorId()` devuelve `null`), o cuando `RutinaController::ver()` deniega el acceso a una rutina ajena (se responde 404 en vez de 403, a propósito, para no confirmar que el id existe).
- Error 403: exclusivamente generado por `RoleMiddleware::handle()`.
- Excepciones de PostgreSQL: el único lugar del código que captura una excepción de base de datos es `Ejercicio::eliminar()`, que envuelve el `DELETE` en un `try { ... } catch (PDOException) { return false; }` porque `DETALLE_RUTINA` referencia a `EJERCICIO` con `ON DELETE RESTRICT`. El resto de los modelos no capturan excepciones: si una restricción de PostgreSQL las lanza (por ejemplo, un `UNIQUE` violado por una condición de carrera), el error se propaga sin manejo específico.

---

## 3. Inventario del Proyecto

### 3.1 Archivos base (núcleo, configuración y punto de entrada)

| Archivo | Responsabilidad | CU en los que participa |
|---|---|---|
| `public/index.php` | Front controller: enrutamiento por `?controller=&action=`, autoload, despacho al controlador. | CU01–CU06 (todas las peticiones pasan por aquí) |
| `app/core/Controller.php` | Clase base: `render()`, `redirect()`. | CU01–CU06 |
| `app/core/Model.php` | Clase base: expone `$this->db` (PDO). | CU01–CU06 |
| `app/core/Request.php` | Envoltorio de `$_GET`/`$_POST`/`$_SERVER`. | CU01–CU06 |
| `app/core/helpers.php` | Funciones globales `url()` y `e()`. | CU01–CU06 |
| `app/core/middlewares/AuthMiddleware.php` | Exige sesión activa. | CU02–CU06 (no CU01 login/registro, que son públicos) |
| `app/core/middlewares/RoleMiddleware.php` | Exige un rol permitido. | CU02–CU06 |
| `config/Config.php` | Lee `.env`. | CU01–CU06 (indirectamente, vía `Database`) |
| `config/Database.php` | Conexión PDO singleton a PostgreSQL. | CU01–CU06 |
| `database/schema.sql` | Script físico de la base de datos PostgreSQL. | CU01–CU06 |

### 3.2 Controladores

| Controlador | Archivo | Responsabilidad | CU |
|---|---|---|---|
| `AuthController` | `app/controllers/AuthController.php` | Login, logout y auto‑registro público de cuentas `cliente`. | CU01 (login/logout), CU02 (auto‑registro) |
| `UsuarioController` | `app/controllers/UsuarioController.php` | CRUD de cuentas por el administrador + perfil propio de cualquier rol. | CU02 |
| `GrupoMuscularController` | `app/controllers/GrupoMuscularController.php` | CRUD del catálogo de grupos musculares. | CU03 |
| `EjercicioController` | `app/controllers/EjercicioController.php` | CRUD del catálogo de ejercicios + su relación N:M con grupos musculares. | CU04 |
| `EvaluacionFisicaController` | `app/controllers/EvaluacionFisicaController.php` | Registro y consulta de historial de evaluaciones físicas. | CU05 |
| `RutinaController` | `app/controllers/RutinaController.php` | Creación, edición, asignación de ejercicios y visualización de rutinas. | CU06 |
| `HomeController` | `app/controllers/HomeController.php` | Portada pública / panel de bienvenida según rol. | CU01 (enlaces a login/registro), CU02–CU06 (accesos rápidos) |

### 3.3 Modelos

| Modelo | Archivo | Tabla(s) | Responsabilidad | CU |
|---|---|---|---|---|
| `Usuario` | `app/models/Usuario.php` | `USUARIO` | Datos comunes a los tres roles; autenticación y CRUD de cuentas. | CU01, CU02 |
| `Cliente` | `app/models/Cliente.php` | `CLIENTE` | Datos propios del rol cliente (fecha de registro, altura, peso). | CU02, CU05, CU06 |
| `Instructor` | `app/models/Instructor.php` | `INSTRUCTOR` | Dato propio del rol instructor (especialidad). | CU02 |
| `GrupoMuscular` | `app/models/GrupoMuscular.php` | `GRUPO_MUSCULAR` | Catálogo de grupos musculares. | CU03, CU04 |
| `Ejercicio` | `app/models/Ejercicio.php` | `EJERCICIO` | Catálogo de ejercicios. | CU04, CU06 |
| `EjercicioGrupoMuscular` | `app/models/EjercicioGrupoMuscular.php` | `EJERCICIO_GRUPO_MUSCULAR` | Relación N:M ejercicio↔grupo muscular. | CU04 |
| `EvaluacionFisica` | `app/models/EvaluacionFisica.php` | `EVALUACION_FISICA` | Registro histórico de evaluaciones físicas. | CU05 |
| `Rutina` | `app/models/Rutina.php` | `RUTINA` | Datos generales de una rutina de entrenamiento. | CU06 |
| `DetalleRutina` | `app/models/DetalleRutina.php` | `DETALLE_RUTINA` | Ejercicios que componen una rutina. | CU06 |

### 3.4 Vistas

| Vista | Archivo | CU |
|---|---|---|
| Login | `app/views/auth/login.php` | CU01 |
| Registro público | `app/views/auth/register.php` | CU02 |
| Portada / panel | `app/views/home/index.php` | CU01, CU02–CU06 |
| Listado de usuarios | `app/views/usuario/index.php` | CU02 |
| Crear usuario | `app/views/usuario/crear.php` | CU02 |
| Editar usuario | `app/views/usuario/editar.php` | CU02 |
| Perfil propio | `app/views/usuario/perfil.php` | CU02 |
| Listado de grupos musculares | `app/views/grupo_muscular/index.php` | CU03 |
| Crear grupo muscular | `app/views/grupo_muscular/crear.php` | CU03 |
| Editar grupo muscular | `app/views/grupo_muscular/editar.php` | CU03 |
| Listado de ejercicios | `app/views/ejercicio/index.php` | CU04 |
| Ver ejercicio | `app/views/ejercicio/ver.php` | CU04 |
| Crear ejercicio | `app/views/ejercicio/crear.php` | CU04 |
| Editar ejercicio | `app/views/ejercicio/editar.php` | CU04 |
| Registrar evaluación | `app/views/evaluacion_fisica/registrar.php` | CU05 |
| Historial de evaluaciones | `app/views/evaluacion_fisica/historial.php` | CU05 |
| Listado de rutinas | `app/views/rutina/index.php` | CU06 |
| Crear rutina | `app/views/rutina/crear.php` | CU06 |
| Editar rutina | `app/views/rutina/editar.php` | CU06 |
| Asignar ejercicios | `app/views/rutina/asignar.php` | CU06 |
| Ver rutina | `app/views/rutina/ver.php` | CU06 |
| Layout header | `app/views/layouts/header.php` | CU01–CU06 |
| Layout footer | `app/views/layouts/footer.php` | CU01–CU06 |
| Nav administrador | `app/views/layouts/nav_administrador.php` | CU02–CU04 |
| Nav instructor | `app/views/layouts/nav_instructor.php` | CU03–CU06 |
| Nav cliente | `app/views/layouts/nav_cliente.php` | CU02, CU05, CU06 |
| Error 403 | `app/views/errors/403.php` | CU02–CU06 |
| Error 404 | `app/views/errors/404.php` | CU01–CU06 |

### 3.5 Índice de métodos por clase

(La tabla completa con parámetros, retorno, SQL y vistas está en la **Sección 4**; esta es solo una referencia rápida por clase y CU.)

| Clase | Métodos públicos | Métodos privados | CU |
|---|---|---|---|
| `AuthController` | `login()`, `autenticar()`, `logout()`, `register()`, `crearCuenta()` | `validarRegistro()` | CU01, CU02 |
| `UsuarioController` | `index()`, `crear()`, `guardar()`, `editar()`, `actualizar()`, `cambiarEstado()`, `perfil()`, `actualizarPerfil()` | `datosPerfil()`, `validarDatosBasicos()`, `renderNoEncontrado()` | CU02 |
| `GrupoMuscularController` | `index()`, `crear()`, `guardar()`, `editar()`, `actualizar()`, `eliminar()` | `verificarAcceso()`, `validar()` | CU03 |
| `EjercicioController` | `index()`, `ver()`, `crear()`, `guardar()`, `editar()`, `actualizar()`, `eliminar()` | `verificarAcceso()`, `datosFormulario()`, `validar()` | CU04 |
| `EvaluacionFisicaController` | `registrar()`, `guardar()`, `historial()` | `validar()` | CU05 |
| `RutinaController` | `index()`, `ver()`, `crear()`, `guardar()`, `editar()`, `actualizar()`, `asignar()`, `agregarEjercicio()`, `quitarEjercicio()` | `esPropietario()`, `tieneAcceso()`, `agruparPorDia()`, `validarDatosGenerales()`, `validarDetalle()` | CU06 |
| `HomeController` | `index()` | — | CU01–CU06 |
| `Usuario` | `crear()`, `actualizar()`, `actualizarPassword()`\*, `cambiarEstado()`, `buscarPorId()`, `buscarPorCorreo()`, `buscarPorCi()`, `listarTodos()` | — | CU01, CU02 |
| `Cliente` | `crear()`, `actualizar()`, `buscarPorId()`, `listarTodos()` | — | CU02, CU05, CU06 |
| `Instructor` | `crear()`, `actualizar()`, `buscarPorId()`, `listarTodos()` | — | CU02 |
| `GrupoMuscular` | `crear()`, `actualizar()`, `eliminar()`, `buscarPorId()`, `buscarPorNombre()`, `listarTodos()` | — | CU03, CU04 |
| `Ejercicio` | `crear()`, `actualizar()`, `eliminar()`, `buscarPorId()`, `buscarPorNombre()`, `listarTodos()` | — | CU04, CU06 |
| `EjercicioGrupoMuscular` | `asociar()`, `listarGruposPorEjercicio()`, `listarEjerciciosPorGrupo()`\* | — | CU04 |
| `EvaluacionFisica` | `crear()`, `listarPorCliente()` | — | CU05 |
| `Rutina` | `crear()`, `actualizar()`, `buscarPorId()`, `listarPorCliente()`, `listarPorInstructor()` | — | CU06 |
| `DetalleRutina` | `agregar()`, `eliminar()`, `listarPorRutina()` | — | CU06 |
| `Controller` (base) | `render()`\*\*, `redirect()`\*\* | — | CU01–CU06 |
| `Model` (base) | constructor (`$this->db`) | — | CU01–CU06 |
| `Request` | `esPost()`, `input()`, `todoPost()` | — | CU01–CU06 |
| `AuthMiddleware` | `handle()` (estático) | — | CU02–CU06 |
| `RoleMiddleware` | `handle()` (estático) | — | CU02–CU06 |
| `Config` | `get()` (estático) | `cargar()` (estático, privado) | CU01–CU06 |
| `Database` | `getConnection()` (estático) | — | CU01–CU06 |

\* `Usuario::actualizarPassword()` y `EjercicioGrupoMuscular::listarEjerciciosPorGrupo()` **están implementados pero ningún controlador los invoca actualmente**. No hay caso de uso ni vista que dispare un cambio de contraseña, ni una pantalla "ejercicios por grupo muscular" navegable desde `GrupoMuscularController`. Se documentan como código muerto/no conectado a ningún flujo, no como parte de un CU.

\*\* `render()`/`redirect()` son `protected`, heredados y usados por todos los controladores concretos vía `$this->`.

### 3.6 Funciones globales

| Función | Archivo | Responsabilidad | CU |
|---|---|---|---|
| `url(string $controlador, string $accion = 'index', array $parametros = []): string` | `app/core/helpers.php` | Construye URLs internas `/index.php?controller=..&action=..`. | CU01–CU06 |
| `e(mixed $valor): string` | `app/core/helpers.php` | Escapa HTML (`htmlspecialchars`) para prevenir XSS. | CU01–CU06 |

### 3.7 Tablas de la base de datos

| Tabla | Responsabilidad | CU |
|---|---|---|
| `USUARIO` | Datos comunes a los tres roles; autenticación. | CU01, CU02 |
| `INSTRUCTOR` | Extiende `USUARIO` cuando `rol = 'instructor'` (especialidad). | CU02, CU05, CU06 |
| `CLIENTE` | Extiende `USUARIO` cuando `rol = 'cliente'` (altura, peso, fecha de registro). | CU02, CU05, CU06 |
| `EVALUACION_FISICA` | Historial de evaluaciones físicas de un cliente, registradas por un instructor. | CU05 |
| `RUTINA` | Datos generales de una rutina asignada a un cliente por un instructor. | CU06 |
| `GRUPO_MUSCULAR` | Catálogo de grupos musculares. | CU03, CU04 |
| `EJERCICIO` | Catálogo de ejercicios. | CU04, CU06 |
| `EJERCICIO_GRUPO_MUSCULAR` | Tabla pivote N:M entre `EJERCICIO` y `GRUPO_MUSCULAR`. | CU04 |
| `DETALLE_RUTINA` | Ejercicios que componen una rutina (día, series, repeticiones, descanso, orden). | CU06 |

### 3.8 Consultas SQL — resumen por operación CRUD

| Modelo.Método | Tipo | Tabla(s) afectadas | CU |
|---|---|---|---|
| `Usuario::crear()` | INSERT | `USUARIO` | CU01(auto‑registro)/CU02 |
| `Usuario::actualizar()` | UPDATE | `USUARIO` | CU02 |
| `Usuario::actualizarPassword()`\* | UPDATE | `USUARIO` | — (no conectado) |
| `Usuario::cambiarEstado()` | UPDATE | `USUARIO` | CU02 |
| `Usuario::buscarPorId()` / `buscarPorCorreo()` / `buscarPorCi()` | SELECT | `USUARIO` | CU01, CU02 |
| `Usuario::listarTodos()` | SELECT | `USUARIO` | CU02 |
| `Cliente::crear()` | INSERT | `CLIENTE` | CU02 |
| `Cliente::actualizar()` | UPDATE | `CLIENTE` | CU02, CU05 |
| `Cliente::buscarPorId()` (JOIN) | SELECT | `CLIENTE`, `USUARIO` | CU02, CU05, CU06 |
| `Cliente::listarTodos()` (JOIN) | SELECT | `CLIENTE`, `USUARIO` | CU05, CU06 |
| `Instructor::crear()` | INSERT | `INSTRUCTOR` | CU02 |
| `Instructor::actualizar()` | UPDATE | `INSTRUCTOR` | CU02 |
| `Instructor::buscarPorId()` / `listarTodos()` (JOIN) | SELECT | `INSTRUCTOR`, `USUARIO` | CU02 |
| `GrupoMuscular::crear()` | INSERT | `GRUPO_MUSCULAR` | CU03 |
| `GrupoMuscular::actualizar()` | UPDATE | `GRUPO_MUSCULAR` | CU03 |
| `GrupoMuscular::eliminar()` | DELETE | `GRUPO_MUSCULAR` (cascada a `EJERCICIO_GRUPO_MUSCULAR`) | CU03 |
| `GrupoMuscular::buscarPorId()` / `buscarPorNombre()` / `listarTodos()` | SELECT | `GRUPO_MUSCULAR` | CU03, CU04 |
| `Ejercicio::crear()` | INSERT | `EJERCICIO` | CU04 |
| `Ejercicio::actualizar()` | UPDATE | `EJERCICIO` | CU04 |
| `Ejercicio::eliminar()` | DELETE | `EJERCICIO` (restringido por `DETALLE_RUTINA`) | CU04 |
| `Ejercicio::buscarPorId()` / `buscarPorNombre()` / `listarTodos()` | SELECT | `EJERCICIO` | CU04, CU06 |
| `EjercicioGrupoMuscular::asociar()` | DELETE + INSERT (N veces) | `EJERCICIO_GRUPO_MUSCULAR` | CU04 |
| `EjercicioGrupoMuscular::listarGruposPorEjercicio()` (JOIN) | SELECT | `EJERCICIO_GRUPO_MUSCULAR`, `GRUPO_MUSCULAR` | CU04 |
| `EjercicioGrupoMuscular::listarEjerciciosPorGrupo()`\* (JOIN) | SELECT | `EJERCICIO_GRUPO_MUSCULAR`, `EJERCICIO` | — (no conectado) |
| `EvaluacionFisica::crear()` | INSERT | `EVALUACION_FISICA` | CU05 |
| `EvaluacionFisica::listarPorCliente()` (JOIN) | SELECT | `EVALUACION_FISICA`, `USUARIO` | CU05 |
| `Rutina::crear()` | INSERT | `RUTINA` | CU06 |
| `Rutina::actualizar()` | UPDATE | `RUTINA` | CU06 |
| `Rutina::buscarPorId()` (JOIN ×2) | SELECT | `RUTINA`, `USUARIO` | CU06 |
| `Rutina::listarPorCliente()` / `listarPorInstructor()` (JOIN) | SELECT | `RUTINA`, `USUARIO` | CU06 |
| `DetalleRutina::agregar()` | INSERT | `DETALLE_RUTINA` | CU06 |
| `DetalleRutina::eliminar()` | DELETE | `DETALLE_RUTINA` | CU06 |
| `DetalleRutina::listarPorRutina()` (JOIN) | SELECT | `DETALLE_RUTINA`, `EJERCICIO` | CU06 |

\* No invocados por ningún controlador actualmente (ver 3.5).

---

## 4. Operaciones y Métodos

### 4.1 Núcleo (sin SQL directo)

| Método | Archivo / Clase | Parámetros | Retorno | Operación | Vista origen | Vista destino |
|---|---|---|---|---|---|---|
| `render()` | `Controller` | `string $vista, array $datos = []` | `void` | `extract($datos)`; incluye `header.php` + vista pedida (o `errors/404.php`) + `footer.php`. | — | La vista pedida |
| `redirect()` | `Controller` | `string $controlador, string $accion, array $parametros` | `void` | `header('Location: '.url(...))`; `exit`. | — | La acción destino (vía HTTP redirect) |
| `esPost()` | `Request` | — | `bool` | Compara `$this->metodo === 'POST'`. | — | — |
| `input()` | `Request` | `string $clave, mixed $default = null` | `mixed` | Lee de `$_POST` (prioridad) o `$_GET`; `trim()` si es string. | — | — |
| `todoPost()` | `Request` | — | `array` | Devuelve `$_POST` completo. | — | — |
| `AuthMiddleware::handle()` | `AuthMiddleware` | — (estático) | `void` | Si `$_SESSION['user']` vacío: `header('Location: '.url('auth','login'))` + `exit`. | — | `auth/login` |
| `RoleMiddleware::handle()` | `RoleMiddleware` | `array $rolesPermitidos` (estático) | `void` | Si el rol de sesión no está permitido: `http_response_code(403)` + `errors/403.php` + `exit`. | — | `errors/403` |
| `Config::get()` | `Config` | `string $clave, ?string $default = null` (estático) | `?string` | Lee `.env` (una sola vez) y devuelve el valor. | — | — |
| `Database::getConnection()` | `Database` | — (estático) | `PDO` | Crea (una sola vez) y devuelve la conexión PDO a PostgreSQL. | — | — |

### 4.2 Modelos — operaciones CRUD con SQL real

#### `Usuario` (`app/models/Usuario.php`)

| Método | Parámetros | Retorno | Operación | Consulta SQL | CU |
|---|---|---|---|---|---|
| `crear()` | `array $datos` (ci, nombres, apellidos, fecha_nacimiento, correo, password_hash, rol) | `int` (id_usuario) | INSERT | `INSERT INTO USUARIO (ci, nombres, apellidos, fecha_nacimiento, correo, password_hash, rol) VALUES (...) RETURNING id_usuario` | CU01(registro)/CU02 |
| `actualizar()` | `int $id, array $datos` (ci, nombres, apellidos, fecha_nacimiento, correo) | `void` | UPDATE | `UPDATE USUARIO SET ci=:ci, nombres=:nombres, apellidos=:apellidos, fecha_nacimiento=:fecha_nacimiento, correo=:correo WHERE id_usuario=:id` | CU02 |
| `actualizarPassword()`\* | `int $id, string $passwordHash` | `void` | UPDATE | `UPDATE USUARIO SET password_hash=:hash WHERE id_usuario=:id` | No conectado a ningún controlador |
| `cambiarEstado()` | `int $id, bool $estado` | `void` | UPDATE | `UPDATE USUARIO SET estado=:estado WHERE id_usuario=:id` | CU02 |
| `buscarPorId()` | `int $id` | `?array` | SELECT | `SELECT * FROM USUARIO WHERE id_usuario=:id` | CU02 |
| `buscarPorCorreo()` | `string $correo` | `?array` | SELECT | `SELECT * FROM USUARIO WHERE correo=:correo` | CU01, CU02 |
| `buscarPorCi()` | `string $ci` | `?array` | SELECT | `SELECT * FROM USUARIO WHERE ci=:ci` | CU02 |
| `listarTodos()` | — | `array` | SELECT | `SELECT * FROM USUARIO ORDER BY apellidos, nombres` | CU02 |

#### `Cliente` (`app/models/Cliente.php`)

| Método | Parámetros | Retorno | Operación | Consulta SQL | CU |
|---|---|---|---|---|---|
| `crear()` | `int $idUsuario, ?float $altura, ?float $peso` | `void` | INSERT | `INSERT INTO CLIENTE (id_usuario, altura, peso) VALUES (:id_usuario, :altura, :peso)` | CU02 |
| `actualizar()` | `int $idUsuario, ?float $altura, ?float $peso` | `void` | UPDATE | `UPDATE CLIENTE SET altura=:altura, peso=:peso WHERE id_usuario=:id_usuario` | CU02, CU05 |
| `buscarPorId()` | `int $idUsuario` | `?array` | SELECT (JOIN) | `SELECT u.*, c.fecha_registro, c.altura, c.peso FROM CLIENTE c JOIN USUARIO u ON u.id_usuario=c.id_usuario WHERE c.id_usuario=:id_usuario` | CU02, CU05 |
| `listarTodos()` | — | `array` | SELECT (JOIN) | `SELECT u.*, c.fecha_registro, c.altura, c.peso FROM CLIENTE c JOIN USUARIO u ON u.id_usuario=c.id_usuario ORDER BY u.apellidos, u.nombres` | CU05, CU06 |

#### `Instructor` (`app/models/Instructor.php`)

| Método | Parámetros | Retorno | Operación | Consulta SQL | CU |
|---|---|---|---|---|---|
| `crear()` | `int $idUsuario, string $especialidad` | `void` | INSERT | `INSERT INTO INSTRUCTOR (id_usuario, especialidad) VALUES (:id_usuario, :especialidad)` | CU02 |
| `actualizar()` | `int $idUsuario, string $especialidad` | `void` | UPDATE | `UPDATE INSTRUCTOR SET especialidad=:especialidad WHERE id_usuario=:id_usuario` | CU02 |
| `buscarPorId()` | `int $idUsuario` | `?array` | SELECT (JOIN) | `SELECT u.*, i.especialidad FROM INSTRUCTOR i JOIN USUARIO u ON u.id_usuario=i.id_usuario WHERE i.id_usuario=:id_usuario` | CU02 |
| `listarTodos()` | — | `array` | SELECT (JOIN) | `SELECT u.*, i.especialidad FROM INSTRUCTOR i JOIN USUARIO u ON u.id_usuario=i.id_usuario ORDER BY u.apellidos, u.nombres` | CU02 |

#### `GrupoMuscular` (`app/models/GrupoMuscular.php`)

| Método | Parámetros | Retorno | Operación | Consulta SQL | CU |
|---|---|---|---|---|---|
| `crear()` | `string $nombre, ?string $descripcion` | `int` | INSERT | `INSERT INTO GRUPO_MUSCULAR (nombre, descripcion) VALUES (:nombre, :descripcion) RETURNING id_grupo_muscular` | CU03 |
| `actualizar()` | `int $id, string $nombre, ?string $descripcion` | `void` | UPDATE | `UPDATE GRUPO_MUSCULAR SET nombre=:nombre, descripcion=:descripcion WHERE id_grupo_muscular=:id` | CU03 |
| `eliminar()` | `int $id` | `void` | DELETE | `DELETE FROM GRUPO_MUSCULAR WHERE id_grupo_muscular=:id` | CU03 |
| `buscarPorId()` | `int $id` | `?array` | SELECT | `SELECT * FROM GRUPO_MUSCULAR WHERE id_grupo_muscular=:id` | CU03 |
| `buscarPorNombre()` | `string $nombre` | `?array` | SELECT | `SELECT * FROM GRUPO_MUSCULAR WHERE nombre=:nombre` | CU03 |
| `listarTodos()` | — | `array` | SELECT | `SELECT * FROM GRUPO_MUSCULAR ORDER BY nombre` | CU03, CU04 |

#### `Ejercicio` (`app/models/Ejercicio.php`)

| Método | Parámetros | Retorno | Operación | Consulta SQL | CU |
|---|---|---|---|---|---|
| `crear()` | `array $datos` (nombre, descripcion, beneficio, indicaciones, url_video) | `int` | INSERT | `INSERT INTO EJERCICIO (nombre, descripcion, beneficio, indicaciones, url_video) VALUES (...) RETURNING id_ejercicio` | CU04 |
| `actualizar()` | `int $id, array $datos` | `void` | UPDATE | `UPDATE EJERCICIO SET nombre=..., descripcion=..., beneficio=..., indicaciones=..., url_video=... WHERE id_ejercicio=:id` | CU04 |
| `eliminar()` | `int $id` | `bool` | DELETE (con `try/catch PDOException`) | `DELETE FROM EJERCICIO WHERE id_ejercicio=:id` | CU04 |
| `buscarPorId()` | `int $id` | `?array` | SELECT | `SELECT * FROM EJERCICIO WHERE id_ejercicio=:id` | CU04, CU06 |
| `buscarPorNombre()` | `string $nombre` | `?array` | SELECT | `SELECT * FROM EJERCICIO WHERE nombre=:nombre` | CU04 |
| `listarTodos()` | — | `array` | SELECT | `SELECT * FROM EJERCICIO ORDER BY nombre` | CU04, CU06 |

#### `EjercicioGrupoMuscular` (`app/models/EjercicioGrupoMuscular.php`)

| Método | Parámetros | Retorno | Operación | Consulta SQL | CU |
|---|---|---|---|---|---|
| `asociar()` | `int $idEjercicio, array $idsGrupoMuscular` | `void` | DELETE + INSERT en bucle | `DELETE FROM EJERCICIO_GRUPO_MUSCULAR WHERE id_ejercicio=:id_ejercicio` seguido de `INSERT INTO EJERCICIO_GRUPO_MUSCULAR (id_ejercicio, id_grupo_muscular) VALUES (...)` por cada id recibido | CU04 |
| `listarGruposPorEjercicio()` | `int $idEjercicio` | `array` | SELECT (JOIN) | `SELECT gm.* FROM EJERCICIO_GRUPO_MUSCULAR egm JOIN GRUPO_MUSCULAR gm ON gm.id_grupo_muscular=egm.id_grupo_muscular WHERE egm.id_ejercicio=:id_ejercicio ORDER BY gm.nombre` | CU04 |
| `listarEjerciciosPorGrupo()`\* | `int $idGrupoMuscular` | `array` | SELECT (JOIN) | `SELECT e.* FROM EJERCICIO_GRUPO_MUSCULAR egm JOIN EJERCICIO e ON e.id_ejercicio=egm.id_ejercicio WHERE egm.id_grupo_muscular=:id_grupo_muscular ORDER BY e.nombre` | No conectado a ningún controlador |

#### `EvaluacionFisica` (`app/models/EvaluacionFisica.php`)

| Método | Parámetros | Retorno | Operación | Consulta SQL | CU |
|---|---|---|---|---|---|
| `crear()` | `array $datos` (peso, altura, objetivo, porcentaje_grasa, masa_muscular, flexibilidad, observaciones, id_cliente, id_instructor) | `int` | INSERT | `INSERT INTO EVALUACION_FISICA (peso, altura, objetivo, porcentaje_grasa, masa_muscular, flexibilidad, observaciones, id_cliente, id_instructor) VALUES (...) RETURNING id_evaluacion_fisica` | CU05 |
| `listarPorCliente()` | `int $idCliente` | `array` | SELECT (JOIN) | `SELECT ef.*, u.nombres AS instructor_nombres, u.apellidos AS instructor_apellidos FROM EVALUACION_FISICA ef JOIN USUARIO u ON u.id_usuario=ef.id_instructor WHERE ef.id_cliente=:id_cliente ORDER BY ef.fecha DESC, ef.id_evaluacion_fisica DESC` | CU05 |

#### `Rutina` (`app/models/Rutina.php`)

| Método | Parámetros | Retorno | Operación | Consulta SQL | CU |
|---|---|---|---|---|---|
| `crear()` | `array $datos` (nombre, tipo, fecha_inicio, fecha_fin, id_cliente, id_instructor) | `int` | INSERT | `INSERT INTO RUTINA (nombre, tipo, fecha_inicio, fecha_fin, id_cliente, id_instructor) VALUES (...) RETURNING id_rutina` | CU06 |
| `actualizar()` | `int $id, array $datos` (nombre, tipo, fecha_inicio, fecha_fin, estado) | `void` | UPDATE | `UPDATE RUTINA SET nombre=..., tipo=..., fecha_inicio=..., fecha_fin=..., estado=... WHERE id_rutina=:id` | CU06 |
| `buscarPorId()` | `int $id` | `?array` | SELECT (JOIN ×2) | `SELECT r.*, uc.nombres AS cliente_nombres, uc.apellidos AS cliente_apellidos, ui.nombres AS instructor_nombres, ui.apellidos AS instructor_apellidos FROM RUTINA r JOIN USUARIO uc ON uc.id_usuario=r.id_cliente JOIN USUARIO ui ON ui.id_usuario=r.id_instructor WHERE r.id_rutina=:id` | CU06 |
| `listarPorCliente()` | `int $idCliente` | `array` | SELECT (JOIN) | `SELECT r.*, ui.nombres AS instructor_nombres, ui.apellidos AS instructor_apellidos FROM RUTINA r JOIN USUARIO ui ON ui.id_usuario=r.id_instructor WHERE r.id_cliente=:id_cliente ORDER BY r.fecha_inicio DESC` | CU06 |
| `listarPorInstructor()` | `int $idInstructor` | `array` | SELECT (JOIN) | `SELECT r.*, uc.nombres AS cliente_nombres, uc.apellidos AS cliente_apellidos FROM RUTINA r JOIN USUARIO uc ON uc.id_usuario=r.id_cliente WHERE r.id_instructor=:id_instructor ORDER BY r.fecha_inicio DESC` | CU06 |

#### `DetalleRutina` (`app/models/DetalleRutina.php`)

| Método | Parámetros | Retorno | Operación | Consulta SQL | CU |
|---|---|---|---|---|---|
| `agregar()` | `int $idRutina, array $datos` (id_ejercicio, dia_semana, series, repeticiones, tiempo_descanso, orden) | `int` | INSERT | `INSERT INTO DETALLE_RUTINA (id_rutina, id_ejercicio, dia_semana, series, repeticiones, tiempo_descanso, orden) VALUES (...) RETURNING id_detalle` | CU06 |
| `eliminar()` | `int $idDetalle, int $idRutina` | `void` | DELETE | `DELETE FROM DETALLE_RUTINA WHERE id_detalle=:id_detalle AND id_rutina=:id_rutina` | CU06 |
| `listarPorRutina()` | `int $idRutina` | `array` | SELECT (JOIN) | `SELECT dr.*, e.nombre AS ejercicio_nombre FROM DETALLE_RUTINA dr JOIN EJERCICIO e ON e.id_ejercicio=dr.id_ejercicio WHERE dr.id_rutina=:id_rutina ORDER BY dr.dia_semana, dr.orden` | CU06 |

### 4.3 Controladores — orquestación (vista origen → vista destino)

#### `AuthController`

| Método | Parámetros | Validaciones | Modelo(s) invocados | Vista origen | Vista/Acción destino | CU |
|---|---|---|---|---|---|---|
| `login()` | `Request $request` | — (si ya hay sesión, redirige) | — | (URL directa) | `auth/login`, o `redirect('home')` | CU01 |
| `autenticar()` | `Request $request` | `esPost()`; `password_verify()`; `$usuario['estado']` | `Usuario::buscarPorCorreo()` | `auth/login` (formulario) | `auth/login` (con error) o `redirect('home')` | CU01 |
| `logout()` | — | — | — | (cualquier vista con sesión) | `redirect('home')` | CU01 |
| `register()` | `Request $request` | — (si ya hay sesión, redirige) | — | (URL directa) | `auth/register`, o `redirect('home')` | CU02 |
| `crearCuenta()` | `Request $request` | `validarRegistro()` (obligatorios, email válido, contraseña ≥6, confirmación, correo/CI únicos) | `Usuario::crear()`, `Usuario::buscarPorCorreo()`, `Usuario::buscarPorCi()`, `Cliente::crear()` | `auth/register` | `auth/register` (con error) o `redirect('auth','login')` | CU02 |

#### `UsuarioController`

| Método | Parámetros | Validaciones | Modelo(s) invocados | Vista origen | Vista/Acción destino | CU |
|---|---|---|---|---|---|---|
| `index()` | — | Middlewares (`administrador`) | `Usuario::listarTodos()` | (URL directa) | `usuario/index` | CU02 |
| `crear()` | — | Middlewares | — | (URL directa) | `usuario/crear` | CU02 |
| `guardar()` | `Request $request` | `validarDatosBasicos()`; especialidad obligatoria si rol=instructor | `Usuario::crear()`, `Instructor::crear()` o `Cliente::crear()` según rol | `usuario/crear` | `usuario/crear` (con error) o `redirect('usuario','index')` | CU02 |
| `editar()` | `Request $request` | Existencia del usuario (`buscarPorId`) | `Usuario::buscarPorId()`, `Instructor::buscarPorId()` | (URL directa) | `usuario/editar` o `errors/404` | CU02 |
| `actualizar()` | `Request $request` | `validarDatosBasicos()`; especialidad obligatoria si rol=instructor | `Usuario::buscarPorId()`, `Usuario::actualizar()`, `Instructor::actualizar()` | `usuario/editar` | `usuario/editar` (con error) o `redirect('usuario','index')` | CU02 |
| `cambiarEstado()` | `Request $request` | `esPost()`; no permite auto‑desactivarse | `Usuario::cambiarEstado()` | `usuario/index` (formulario embebido) | `redirect('usuario','index')` | CU02 |
| `perfil()` | — | Middlewares (solo `AuthMiddleware`) | `datosPerfil()` → `Usuario::buscarPorId()`, `Cliente::buscarPorId()`/`Instructor::buscarPorId()` | (URL directa) | `usuario/perfil` | CU02 |
| `actualizarPerfil()` | `Request $request` | `validarDatosBasicos()` | `Usuario::buscarPorId()`, `Usuario::actualizar()`, `Cliente::actualizar()` o `Instructor::actualizar()` | `usuario/perfil` | `usuario/perfil` (con error o éxito) | CU02 |

#### `GrupoMuscularController`

| Método | Parámetros | Validaciones | Modelo(s) invocados | Vista origen | Vista/Acción destino | CU |
|---|---|---|---|---|---|---|
| `index()` | — | Middlewares (`administrador`,`instructor`) | `GrupoMuscular::listarTodos()` | (URL directa) | `grupo_muscular/index` | CU03 |
| `crear()` | — | Middlewares | — | (URL directa) | `grupo_muscular/crear` | CU03 |
| `guardar()` | `Request $request` | `validar()` (obligatorio + único) | `GrupoMuscular::buscarPorNombre()`, `GrupoMuscular::crear()` | `grupo_muscular/crear` | `grupo_muscular/crear` (con error) o `redirect('grupoMuscular','index')` | CU03 |
| `editar()` | `Request $request` | Existencia (`buscarPorId`) | `GrupoMuscular::buscarPorId()` | (URL directa) | `grupo_muscular/editar` o `errors/404` | CU03 |
| `actualizar()` | `Request $request` | `validar()` | `GrupoMuscular::buscarPorId()`, `GrupoMuscular::actualizar()` | `grupo_muscular/editar` | `grupo_muscular/editar` (con error) o `redirect('grupoMuscular','index')` | CU03 |
| `eliminar()` | `Request $request` | `esPost()` | `GrupoMuscular::eliminar()` | `grupo_muscular/index` (formulario embebido) | `redirect('grupoMuscular','index')` | CU03 |

#### `EjercicioController`

| Método | Parámetros | Validaciones | Modelo(s) invocados | Vista origen | Vista/Acción destino | CU |
|---|---|---|---|---|---|---|
| `index()` | — | Middlewares (`administrador`,`instructor`) | `Ejercicio::listarTodos()` | (URL directa) | `ejercicio/index` | CU04 |
| `ver()` | `Request $request` | Existencia (`buscarPorId`) | `Ejercicio::buscarPorId()`, `EjercicioGrupoMuscular::listarGruposPorEjercicio()` | (URL directa) | `ejercicio/ver` o `errors/404` | CU04 |
| `crear()` | — | Middlewares | `GrupoMuscular::listarTodos()` | (URL directa) | `ejercicio/crear` | CU04 |
| `guardar()` | `Request $request` | `validar()` (obligatorio + único) | `Ejercicio::buscarPorNombre()`, `Ejercicio::crear()`, `EjercicioGrupoMuscular::asociar()`, `GrupoMuscular::listarTodos()` | `ejercicio/crear` | `ejercicio/crear` (con error) o `redirect('ejercicio','index')` | CU04 |
| `editar()` | `Request $request` | Existencia | `Ejercicio::buscarPorId()`, `EjercicioGrupoMuscular::listarGruposPorEjercicio()`, `GrupoMuscular::listarTodos()` | (URL directa) | `ejercicio/editar` o `errors/404` | CU04 |
| `actualizar()` | `Request $request` | `validar()` | `Ejercicio::buscarPorId()`, `Ejercicio::actualizar()`, `EjercicioGrupoMuscular::asociar()`, `GrupoMuscular::listarTodos()` | `ejercicio/editar` | `ejercicio/editar` (con error) o `redirect('ejercicio','index')` | CU04 |
| `eliminar()` | `Request $request` | `esPost()`; captura `false` de `Ejercicio::eliminar()` | `Ejercicio::eliminar()` (con `try/catch` interno), `Ejercicio::listarTodos()` | `ejercicio/index` (formulario embebido) | `ejercicio/index` (con error) o `redirect('ejercicio','index')` | CU04 |

#### `EvaluacionFisicaController`

| Método | Parámetros | Validaciones | Modelo(s) invocados | Vista origen | Vista/Acción destino | CU |
|---|---|---|---|---|---|---|
| `registrar()` | — | Middlewares (`instructor`) | `Cliente::listarTodos()` | (URL directa) | `evaluacion_fisica/registrar` | CU05 |
| `guardar()` | `Request $request` | `validar()` (cliente seleccionado, peso/altura numéricos >0) | `EvaluacionFisica::crear()`, `Cliente::actualizar()`, `Cliente::listarTodos()` | `evaluacion_fisica/registrar` | `evaluacion_fisica/registrar` (con error) o `redirect('evaluacionFisica','historial', ['id'=>...])` | CU05 |
| `historial()` | `Request $request` | Middlewares (`instructor`,`cliente`); rama por rol | `EvaluacionFisica::listarPorCliente()`, `Cliente::listarTodos()`, `Cliente::buscarPorId()` | (URL directa) | `evaluacion_fisica/historial` | CU05 |

#### `RutinaController`

| Método | Parámetros | Validaciones | Modelo(s) invocados | Vista origen | Vista/Acción destino | CU |
|---|---|---|---|---|---|---|
| `index()` | — | Middlewares (`instructor`,`cliente`) | `Rutina::listarPorInstructor()` o `Rutina::listarPorCliente()` | (URL directa) | `rutina/index` | CU06 |
| `ver()` | `Request $request` | Existencia + `tieneAcceso()` | `Rutina::buscarPorId()`, `DetalleRutina::listarPorRutina()` | (URL directa) | `rutina/ver` o `errors/404` | CU06 |
| `crear()` | — | Middlewares (`instructor`) | `Cliente::listarTodos()` | (URL directa) | `rutina/crear` | CU06 |
| `guardar()` | `Request $request` | `validarDatosGenerales()` | `Rutina::crear()`, `Cliente::listarTodos()` | `rutina/crear` | `rutina/crear` (con error) o `redirect('rutina','asignar', ['id'=>...])` | CU06 |
| `editar()` | `Request $request` | Existencia + `esPropietario()` | `Rutina::buscarPorId()` | (URL directa) | `rutina/editar` o `errors/404` | CU06 |
| `actualizar()` | `Request $request` | `validarDatosGenerales()`; estado válido | `Rutina::buscarPorId()`, `Rutina::actualizar()` | `rutina/editar` | `rutina/editar` (con error) o `redirect('rutina','ver', ['id'=>...])` | CU06 |
| `asignar()` | `Request $request` | Existencia + `esPropietario()` | `Rutina::buscarPorId()`, `DetalleRutina::listarPorRutina()`, `Ejercicio::listarTodos()` | (URL directa) | `rutina/asignar` o `errors/404` | CU06 |
| `agregarEjercicio()` | `Request $request` | `esPropietario()`; `validarDetalle()` | `Rutina::buscarPorId()`, `DetalleRutina::agregar()`, `DetalleRutina::listarPorRutina()`, `Ejercicio::listarTodos()` | `rutina/asignar` | `rutina/asignar` (con error) o `redirect('rutina','asignar', ['id'=>...])` | CU06 |
| `quitarEjercicio()` | `Request $request` | `esPost()`; `esPropietario()` | `Rutina::buscarPorId()`, `DetalleRutina::eliminar()` | `rutina/asignar` (formulario embebido) | `redirect('rutina','asignar', ['id'=>...])` | CU06 |

#### `HomeController`

| Método | Parámetros | Validaciones | Modelo(s) invocados | Vista origen | Vista destino | CU |
|---|---|---|---|---|---|---|
| `index()` | — | Ninguna (público) | — | (URL directa) | `home/index` | CU01–CU06 |

### 4.4 Clasificación CRUD — resumen

| Operación | Métodos de modelo que la implementan |
|---|---|
| **CREATE** (`INSERT`) | `Usuario::crear`, `Cliente::crear`, `Instructor::crear`, `GrupoMuscular::crear`, `Ejercicio::crear`, `EjercicioGrupoMuscular::asociar` (parte INSERT), `EvaluacionFisica::crear`, `Rutina::crear`, `DetalleRutina::agregar` |
| **READ** (`SELECT`) | `Usuario::buscarPorId/buscarPorCorreo/buscarPorCi/listarTodos`, `Cliente::buscarPorId/listarTodos`, `Instructor::buscarPorId/listarTodos`, `GrupoMuscular::buscarPorId/buscarPorNombre/listarTodos`, `Ejercicio::buscarPorId/buscarPorNombre/listarTodos`, `EjercicioGrupoMuscular::listarGruposPorEjercicio/listarEjerciciosPorGrupo`, `EvaluacionFisica::listarPorCliente`, `Rutina::buscarPorId/listarPorCliente/listarPorInstructor`, `DetalleRutina::listarPorRutina` |
| **UPDATE** | `Usuario::actualizar/actualizarPassword/cambiarEstado`, `Cliente::actualizar`, `Instructor::actualizar`, `GrupoMuscular::actualizar`, `Ejercicio::actualizar`, `Rutina::actualizar` |
| **DELETE** | `GrupoMuscular::eliminar`, `Ejercicio::eliminar`, `EjercicioGrupoMuscular::asociar` (parte DELETE), `DetalleRutina::eliminar` |

No existe ningún `DELETE` sobre `USUARIO`, `CLIENTE`, `INSTRUCTOR`, `EVALUACION_FISICA` ni `RUTINA`: para `USUARIO` se usa `cambiarEstado()` (baja lógica) en su lugar; las demás tablas no tienen ninguna operación de borrado implementada.

---

## 5. Relaciones con la Base de Datos

### 5.1 Tablas, claves primarias y claves foráneas

| Tabla | PK | FK | Referencia | Acción `ON DELETE` |
|---|---|---|---|---|
| `USUARIO` | `id_usuario` (SERIAL) | — | — | — |
| `INSTRUCTOR` | `id_usuario` (INTEGER, también FK) | `fk_instructor_usuario` | `USUARIO(id_usuario)` | `RESTRICT` |
| `CLIENTE` | `id_usuario` (INTEGER, también FK) | `fk_cliente_usuario` | `USUARIO(id_usuario)` | `RESTRICT` |
| `EVALUACION_FISICA` | `id_evaluacion_fisica` (SERIAL) | `fk_eval_cliente` | `CLIENTE(id_usuario)` | (no definido → `NO ACTION`) |
| | | `fk_eval_instructor` | `INSTRUCTOR(id_usuario)` | (no definido → `NO ACTION`) |
| `RUTINA` | `id_rutina` (SERIAL) | `fk_rutina_cliente` | `CLIENTE(id_usuario)` | (no definido → `NO ACTION`) |
| | | `fk_rutina_instructor` | `INSTRUCTOR(id_usuario)` | (no definido → `NO ACTION`) |
| `GRUPO_MUSCULAR` | `id_grupo_muscular` (SERIAL) | — | — | — |
| `EJERCICIO` | `id_ejercicio` (SERIAL) | — | — | — |
| `EJERCICIO_GRUPO_MUSCULAR` | **compuesta** `(id_ejercicio, id_grupo_muscular)` | `fk_egm_ejercicio` | `EJERCICIO(id_ejercicio)` | `CASCADE` |
| | | `fk_egm_grupo` | `GRUPO_MUSCULAR(id_grupo_muscular)` | `CASCADE` |
| `DETALLE_RUTINA` | **compuesta** `(id_detalle, id_rutina)` | `fk_detalle_rutina` | `RUTINA(id_rutina)` | `CASCADE` |
| | | `fk_detalle_ejercicio` | `EJERCICIO(id_ejercicio)` | `RESTRICT` |

### 5.2 Tipo de relación entre tablas

| Relación | Tipo | Detalle |
|---|---|---|
| `USUARIO` ↔ `INSTRUCTOR` | **1:1 (herencia por tabla / opcional)** | `INSTRUCTOR.id_usuario` es simultáneamente PK y FK a `USUARIO`. Solo existe fila si `rol = 'instructor'`. |
| `USUARIO` ↔ `CLIENTE` | **1:1 (herencia por tabla / opcional)** | Igual que arriba, para `rol = 'cliente'`. |
| `CLIENTE` (1) ↔ `EVALUACION_FISICA` (N) | **1:N** | Un cliente tiene muchas evaluaciones; cada evaluación pertenece a un único cliente (`id_cliente`). |
| `INSTRUCTOR` (1) ↔ `EVALUACION_FISICA` (N) | **1:N** | Un instructor registra muchas evaluaciones; cada evaluación fue registrada por un único instructor (`id_instructor`). |
| `CLIENTE` (1) ↔ `RUTINA` (N) | **1:N** | Un cliente tiene muchas rutinas; cada rutina pertenece a un único cliente (`id_cliente`, `NOT NULL`). |
| `INSTRUCTOR` (1) ↔ `RUTINA` (N) | **1:N** | Un instructor crea muchas rutinas; cada rutina fue creada por un único instructor (`id_instructor`). |
| `RUTINA` (1) ↔ `DETALLE_RUTINA` (N) | **1:N** | Una rutina tiene muchos detalles (ejercicios asignados); cada detalle pertenece a una única rutina. |
| `EJERCICIO` (1) ↔ `DETALLE_RUTINA` (N) | **1:N** | Un ejercicio puede aparecer en muchos detalles de rutina (de distintas rutinas); cada detalle referencia a un único ejercicio. |
| `EJERCICIO` (N) ↔ `GRUPO_MUSCULAR` (N) | **N:M** | A través de la tabla intermedia `EJERCICIO_GRUPO_MUSCULAR`. |

### 5.3 Tabla intermedia N:M — `EJERCICIO_GRUPO_MUSCULAR`

Representa la relación N:M entre `EJERCICIO` y `GRUPO_MUSCULAR`. Su PK es **compuesta**: `(id_ejercicio, id_grupo_muscular)`, sin columna `id` propia. Ambas FK tienen `ON DELETE CASCADE`: borrar un ejercicio o un grupo muscular limpia automáticamente sus asociaciones, sin afectar a la otra entidad. En código, `EjercicioGrupoMuscular::asociar()` nunca hace `UPDATE`: reemplaza el conjunto completo de asociaciones de un ejercicio con `DELETE` + `INSERT` en bucle.

### 5.4 Verificación de `DETALLE_RUTINA`

`DETALLE_RUTINA` tiene **clave primaria compuesta** `(id_detalle, id_rutina)`, aunque `id_detalle` es `SERIAL` (autoincremental). Esto significa que `id_detalle` **no es único por sí solo** en toda la tabla: dos filas de rutinas distintas podrían compartir el mismo valor de `id_detalle` si el `SERIAL` se reinicia o si se insertan filas manualmente para otra rutina fuera de este flujo (en la práctica, como `id_detalle` es `SERIAL` global, sus valores nunca se repiten entre rutinas, pero el diseño de la tabla exige de todas formas ambas columnas para identificar una fila de forma inequívoca según el modelo relacional). Por eso:

- `DetalleRutina::eliminar(int $idDetalle, int $idRutina)` filtra **por ambas columnas** (`WHERE id_detalle = :id_detalle AND id_rutina = :id_rutina`), tal como está comentado en el propio código: evita que, manipulando el formulario, alguien borre un detalle de una rutina que no le pertenece.
- Restricciones `CHECK`: `chk_detalle_series` (`series > 0`), `chk_detalle_repeticiones` (`repeticiones > 0`), `chk_detalle_descanso` (`tiempo_descanso >= 0`), `chk_detalle_orden` (`orden >= 0`). Todas estas se replican en `RutinaController::validarDetalle()`.
- `dia_semana` es `VARCHAR(10)` **sin** `CHECK` a nivel de base de datos; la validación de que el valor sea uno de los siete días es exclusivamente de PHP, contra la constante `RutinaController::DIAS_SEMANA`.
- `fk_detalle_rutina` con `ON DELETE CASCADE`: al borrar una rutina, PostgreSQL borra automáticamente todos sus detalles (aunque, según el código, **no existe ninguna acción de borrado de rutinas** implementada en `RutinaController`/`Rutina`, así que esta cascada nunca se dispara en el flujo actual).
- `fk_detalle_ejercicio` con `ON DELETE RESTRICT`: por eso `Ejercicio::eliminar()` puede fallar (y de hecho está preparado con `try/catch` para ese caso) cuando el ejercicio ya está en uso en alguna rutina.

### 5.5 Otras restricciones (`CHECK`, `UNIQUE`)

| Tabla | Restricción | Detalle |
|---|---|---|
| `USUARIO` | `UNIQUE (ci)`, `UNIQUE (correo)` | Replicadas en PHP con `buscarPorCi()`/`buscarPorCorreo()` antes de insertar/actualizar. |
| `USUARIO` | `CHECK (rol IN ('administrador','instructor','cliente'))` | Replicada en `UsuarioController::validarDatosBasicos()`. |
| `RUTINA` | `CHECK (estado IN ('activa','completada','cancelada'))` | Replicada en `RutinaController::actualizar()`. |
| `RUTINA` | `chk_rutina_fechas`: `fecha_fin IS NULL OR fecha_fin >= fecha_inicio` | Replicada en `RutinaController::validarDatosGenerales()`. |
| `GRUPO_MUSCULAR` | `UNIQUE (nombre)` | Replicada en `GrupoMuscularController::validar()`. |
| `EJERCICIO` | `UNIQUE (nombre)` | Replicada en `EjercicioController::validar()`. |

### 5.6 Índices

`idx_rutina_cliente`, `idx_rutina_instructor` (sobre `RUTINA`), `idx_evaluacion_cliente`, `idx_evaluacion_instructor` (sobre `EVALUACION_FISICA`), `idx_detalle_rutina`, `idx_detalle_ejercicio` (sobre `DETALLE_RUTINA`). Todos están declarados en `database/schema.sql` sobre las columnas de clave foránea más consultadas.

---

## 6. Diseño Procedimental

### 6.1 Convenciones comunes a los seis diagramas

- **Tipo de diagrama en Enterprise Architect:** crear, dentro de un paquete "Diseño Procedimental", un diagrama de tipo **Class**. Al nombrarlo (por ejemplo, `Diseño Procedimental - CU01`), la pestaña del diagrama en EA mostrará automáticamente el prefijo de tipo: **"class Diseño Procedimental - CU01"**, que es el formato pedido.
- **Notación de las cajas:** cada participante es un elemento `Class` (o `Actor`/`Boundary` para el actor y las vistas). En el compartimento superior va el **nombre real** (clase, actor o vista); en el compartimento de **Operations** van únicamente los métodos reales que ese participante ejecuta dentro del CU documentado (con su visibilidad `+`/`-` tal como en el código).
- **Relaciones:** se dibujan como conectores **Dependency** (línea discontinua, flecha abierta) con el nombre del método o mensaje escrito sobre la línea. Es la única relación usada salvo dos excepciones puntuales, ambas reales en el código:
  - **Generalización** (línea sólida, triángulo hueco) entre cada `XController` y la clase base `Controller`, porque literalmente hace `class XController extends Controller` y usa sus métodos heredados `render()`/`redirect()`.
  - **Composición** (línea sólida, **rombo negro** en el extremo del controlador) entre cada `XController` y los modelos que instancia con `new` dentro de su propio `__construct()` (ej. `private Usuario $usuarioModelo; ... $this->usuarioModelo = new Usuario();`). Esa relación es una composición real: el modelo vive y muere con la instancia del controlador, no se comparte ni se inyecta desde fuera.
- **Procedimiento general en EA** (igual para los seis CU, solo cambian los nombres/métodos de cada tabla):
  1. Crear el diagrama de tipo Class y nombrarlo `Diseño Procedimental - CUxx`.
  2. Arrastrar un elemento `Class` por cada fila de la tabla "Participantes"; renombrarlo con el nombre real de la columna "Archivo o clase".
  3. Abrir cada elemento → pestaña *Operations* → agregar cada método de la columna "Métodos que deben aparecer" (respetando `+`/`-`).
  4. Usar el conector `Dependency` del toolbox UML → Class para cada fila de la tabla "Relaciones", uniendo Origen→Destino según la columna "Dirección" y escribiendo el texto de "Mensaje o método" como *Name* del conector (doble clic sobre la línea).
  5. Para la relación Controlador→Controller (base), usar el conector `Generalization`.
  6. Para la relación Controlador→Modelo instanciado en el constructor, usar el conector `Aggregation` y marcar la casilla **Composite** en su cuadro de propiedades (esto dibuja el rombo relleno).
  7. Ubicar las cajas según la "Distribución recomendada" de cada CU y alinear con las herramientas de cuadrícula de EA.
- **Actores:** se muestran como `Actor` UML con el nombre del rol que ejecuta el CU (Administrador, Instructor, Cliente o Visitante sin sesión, según corresponda).
- **PostgreSQL** se representa como una única caja `«boundary» PostgreSQL (PDO)` que recibe, como mensaje, el texto literal de la sentencia SQL ejecutada por el modelo (columna `Consulta SQL` de la Sección 4.2).

---

### 6.2 CU01 — Gestionar acceso al sistema

**Nombre del diagrama:** `Diseño Procedimental - CU01 - Gestionar acceso al sistema`
**Alcance:** `AuthController::login()`, `autenticar()`, `logout()` (el auto‑registro se documenta en CU02, según el propio comentario del código fuente).

**Participantes**

| Participante | Tipo UML | Archivo o clase | Métodos que deben aparecer | Responsabilidad |
|---|---|---|---|---|
| Actor (Visitante / Administrador / Instructor / Cliente) | «actor» | — | — | Envía credenciales o solicita cerrar sesión. |
| `AuthController` | «control» (Class) | `app/controllers/AuthController.php` | `+login()`, `+autenticar()`, `+logout()` | Orquesta el inicio y cierre de sesión. |
| `Controller` | «control abstract» (Class) | `app/core/Controller.php` | `#render()`, `#redirect()` | Renderiza vistas y redirige; heredada por `AuthController`. |
| `Request` | «boundary» (Class) | `app/core/Request.php` | `+esPost()`, `+input()` | Envuelve la petición HTTP actual. |
| `Usuario` | «entity» (Class) | `app/models/Usuario.php` | `+buscarPorCorreo()` | Consulta credenciales en `USUARIO`. |
| PostgreSQL (PDO) | «boundary» | `config/Database.php` | — | Ejecuta el `SELECT` sobre `USUARIO`. |
| Vista `auth/login` | «boundary» | `app/views/auth/login.php` | — | Formulario de credenciales / mensaje de error. |
| Vista `home/index` | «boundary» | `app/views/home/index.php` | — | Panel mostrado tras iniciar sesión. |

**Relaciones**

| Origen | Destino | Relación | Mensaje o método | Dirección |
|---|---|---|---|---|
| Actor | `AuthController` | Dependencia | `login(Request)` / `autenticar(Request)` / `logout()` | Actor → `AuthController` |
| `AuthController` | `Controller` | Generalización (herencia) | `render()` / `redirect()` (heredados) | `AuthController` → `Controller` |
| `AuthController` | `Usuario` | **Composición** (`new Usuario()` en el constructor) | — | `AuthController` ◆→ `Usuario` |
| `AuthController` | `Request` | Dependencia | `esPost()`, `input('correo')`, `input('password')` | `AuthController` → `Request` |
| `AuthController` | `Usuario` | Dependencia | `buscarPorCorreo(correo)` | `AuthController` → `Usuario` |
| `Usuario` | PostgreSQL | Dependencia | `SELECT * FROM USUARIO WHERE correo=:correo` | `Usuario` → PostgreSQL |
| `AuthController` | Vista `auth/login` | Dependencia | `render('auth/login', ['error'=>...])` | `AuthController` → Vista |
| `AuthController` | Vista `home/index` | Dependencia | `redirect('home')` | `AuthController` → Vista |

**Distribución recomendada:** Actor arriba a la izquierda; `AuthController` al centro; `Controller` arriba a la derecha de `AuthController` (unido por generalización); `Usuario` a la derecha del centro (unida por el rombo de composición); PostgreSQL más a la derecha, colgando de `Usuario`; `Request` debajo de `AuthController`; las dos vistas en la fila inferior, ambas apuntadas desde `AuthController`.

> Nota: el constructor de `AuthController` también instancia `Cliente` (`new Cliente()`), pero ese modelo solo participa en `crearCuenta()` (CU02), por lo que no se incluye en este diagrama.

---

### 6.3 CU02 — Gestionar cuentas de usuario

**Nombre del diagrama:** `Diseño Procedimental - CU02 - Gestionar cuentas de usuario`
**Alcance:** `AuthController::register()`/`crearCuenta()` (auto‑registro) + todos los métodos de `UsuarioController`.

**Participantes**

| Participante | Tipo UML | Archivo o clase | Métodos que deben aparecer | Responsabilidad |
|---|---|---|---|---|
| Actor (Visitante) | «actor» | — | — | Se auto‑registra como cliente. |
| Actor (Administrador) | «actor» | — | — | Administra todas las cuentas. |
| Actor (Cualquier rol autenticado) | «actor» | — | — | Ve/edita su propio perfil. |
| `AuthController` | «control» | `app/controllers/AuthController.php` | `+register()`, `+crearCuenta()`, `-validarRegistro()` | Auto‑registro público (siempre rol `cliente`). |
| `UsuarioController` | «control» | `app/controllers/UsuarioController.php` | `+index()`, `+crear()`, `+guardar()`, `+editar()`, `+actualizar()`, `+cambiarEstado()`, `+perfil()`, `+actualizarPerfil()`, `-datosPerfil()`, `-validarDatosBasicos()`, `-renderNoEncontrado()` | Administración de cuentas + perfil propio. |
| `Controller` | «control abstract» | `app/core/Controller.php` | `#render()`, `#redirect()` | Heredada por ambos controladores. |
| `AuthMiddleware` | «control» | `app/core/middlewares/AuthMiddleware.php` | `+handle()` | Exige sesión activa. |
| `RoleMiddleware` | «control» | `app/core/middlewares/RoleMiddleware.php` | `+handle()` | Exige rol `administrador` en las acciones de gestión. |
| `Usuario` | «entity» | `app/models/Usuario.php` | `+crear()`, `+actualizar()`, `+cambiarEstado()`, `+buscarPorId()`, `+buscarPorCorreo()`, `+buscarPorCi()`, `+listarTodos()` | Acceso a `USUARIO`. |
| `Cliente` | «entity» | `app/models/Cliente.php` | `+crear()`, `+actualizar()`, `+buscarPorId()` | Acceso a `CLIENTE`. |
| `Instructor` | «entity» | `app/models/Instructor.php` | `+crear()`, `+actualizar()`, `+buscarPorId()` | Acceso a `INSTRUCTOR`. |
| PostgreSQL (PDO) | «boundary» | `config/Database.php` | — | Ejecuta INSERT/UPDATE/SELECT sobre `USUARIO`, `CLIENTE`, `INSTRUCTOR`. |
| Vistas `auth/register`, `usuario/index`, `usuario/crear`, `usuario/editar`, `usuario/perfil` | «boundary» | `app/views/...` | — | Formularios y listados. |

**Relaciones**

| Origen | Destino | Relación | Mensaje o método | Dirección |
|---|---|---|---|---|
| Actor Visitante | `AuthController` | Dependencia | `register()` / `crearCuenta(Request)` | Actor → `AuthController` |
| Actor Administrador | `UsuarioController` | Dependencia | `index()` / `crear()` / `guardar()` / `editar()` / `actualizar()` / `cambiarEstado()` | Actor → `UsuarioController` |
| Actor (cualquier rol) | `UsuarioController` | Dependencia | `perfil()` / `actualizarPerfil()` | Actor → `UsuarioController` |
| `UsuarioController` | `AuthMiddleware` | Dependencia | `handle()` | `UsuarioController` → `AuthMiddleware` |
| `UsuarioController` | `RoleMiddleware` | Dependencia | `handle(['administrador'])` | `UsuarioController` → `RoleMiddleware` |
| `AuthController` / `UsuarioController` | `Controller` | Generalización | `render()` / `redirect()` | Cada controlador → `Controller` |
| `AuthController` | `Usuario` | **Composición** | — | `AuthController` ◆→ `Usuario` |
| `AuthController` | `Cliente` | **Composición** | — | `AuthController` ◆→ `Cliente` |
| `UsuarioController` | `Usuario` | **Composición** | — | `UsuarioController` ◆→ `Usuario` |
| `UsuarioController` | `Cliente` | **Composición** | — | `UsuarioController` ◆→ `Cliente` |
| `UsuarioController` | `Instructor` | **Composición** | — | `UsuarioController` ◆→ `Instructor` |
| `AuthController` | `Usuario` | Dependencia | `crear()`, `buscarPorCorreo()`, `buscarPorCi()` | `AuthController` → `Usuario` |
| `AuthController` | `Cliente` | Dependencia | `crear(idUsuario, null, null)` | `AuthController` → `Cliente` |
| `UsuarioController` | `Usuario` | Dependencia | `listarTodos()`, `crear()`, `buscarPorId()`, `actualizar()`, `cambiarEstado()` | `UsuarioController` → `Usuario` |
| `UsuarioController` | `Cliente` | Dependencia | `crear()`, `buscarPorId()`, `actualizar()` | `UsuarioController` → `Cliente` |
| `UsuarioController` | `Instructor` | Dependencia | `crear()`, `buscarPorId()`, `actualizar()` | `UsuarioController` → `Instructor` |
| `Usuario`/`Cliente`/`Instructor` | PostgreSQL | Dependencia | Consultas de la Sección 4.2 | Modelo → PostgreSQL |
| `AuthController` | Vista `auth/register` | Dependencia | `render('auth/register', ...)` | `AuthController` → Vista |
| `UsuarioController` | Vistas `usuario/*` | Dependencia | `render('usuario/index'|'crear'|'editar'|'perfil', ...)` | `UsuarioController` → Vistas |

**Distribución recomendada:** dos controladores lado a lado en la fila superior (`AuthController` a la izquierda, `UsuarioController` a la derecha), ambos conectados hacia abajo con `Controller` (centrado) por generalización; los middlewares a la derecha de `UsuarioController`; los tres modelos (`Usuario`, `Cliente`, `Instructor`) en una fila intermedia, cada uno con su rombo de composición hacia el/los controlador(es) que lo instancian; PostgreSQL a la derecha de los modelos; las vistas en la fila inferior, agrupadas bajo el controlador que las renderiza.

---

### 6.4 CU03 — Administrar catálogo de grupos musculares

**Nombre del diagrama:** `Diseño Procedimental - CU03 - Administrar catálogo de grupos musculares`

**Participantes**

| Participante | Tipo UML | Archivo o clase | Métodos que deben aparecer | Responsabilidad |
|---|---|---|---|---|
| Actor (Administrador / Instructor) | «actor» | — | — | Mantiene el catálogo. |
| `GrupoMuscularController` | «control» | `app/controllers/GrupoMuscularController.php` | `+index()`, `+crear()`, `+guardar()`, `+editar()`, `+actualizar()`, `+eliminar()`, `-verificarAcceso()`, `-validar()` | CRUD del catálogo de grupos musculares. |
| `Controller` | «control abstract» | `app/core/Controller.php` | `#render()`, `#redirect()` | Heredada. |
| `AuthMiddleware` | «control» | `app/core/middlewares/AuthMiddleware.php` | `+handle()` | Exige sesión. |
| `RoleMiddleware` | «control» | `app/core/middlewares/RoleMiddleware.php` | `+handle()` | Exige rol `administrador` o `instructor`. |
| `GrupoMuscular` | «entity» | `app/models/GrupoMuscular.php` | `+crear()`, `+actualizar()`, `+eliminar()`, `+buscarPorId()`, `+buscarPorNombre()`, `+listarTodos()` | Acceso a `GRUPO_MUSCULAR`. |
| PostgreSQL (PDO) | «boundary» | `config/Database.php` | — | Ejecuta INSERT/UPDATE/DELETE/SELECT sobre `GRUPO_MUSCULAR`. |
| Vistas `grupo_muscular/index`, `crear`, `editar` | «boundary» | `app/views/grupo_muscular/*.php` | — | Listado y formularios. |

**Relaciones**

| Origen | Destino | Relación | Mensaje o método | Dirección |
|---|---|---|---|---|
| Actor | `GrupoMuscularController` | Dependencia | `index()`/`crear()`/`guardar()`/`editar()`/`actualizar()`/`eliminar()` | Actor → Controlador |
| `GrupoMuscularController` | `AuthMiddleware` | Dependencia | `handle()` (dentro de `verificarAcceso()`) | Controlador → Middleware |
| `GrupoMuscularController` | `RoleMiddleware` | Dependencia | `handle(['administrador','instructor'])` | Controlador → Middleware |
| `GrupoMuscularController` | `Controller` | Generalización | `render()`/`redirect()` | Controlador → `Controller` |
| `GrupoMuscularController` | `GrupoMuscular` | **Composición** | — | Controlador ◆→ Modelo |
| `GrupoMuscularController` | `GrupoMuscular` | Dependencia | `listarTodos()`, `buscarPorNombre()`, `crear()`, `buscarPorId()`, `actualizar()`, `eliminar()` | Controlador → Modelo |
| `GrupoMuscular` | PostgreSQL | Dependencia | INSERT/UPDATE/DELETE/SELECT sobre `GRUPO_MUSCULAR` | Modelo → PostgreSQL |
| `GrupoMuscularController` | Vistas `grupo_muscular/*` | Dependencia | `render(...)` | Controlador → Vistas |

**Distribución recomendada:** Actor arriba; `GrupoMuscularController` al centro con `Controller` a su derecha (generalización) y los dos middlewares apilados encima; `GrupoMuscular` debajo del controlador (rombo de composición); PostgreSQL a la derecha del modelo; las tres vistas en fila inferior.

---

### 6.5 CU04 — Administrar catálogo de ejercicios

**Nombre del diagrama:** `Diseño Procedimental - CU04 - Administrar catálogo de ejercicios`

**Participantes**

| Participante | Tipo UML | Archivo o clase | Métodos que deben aparecer | Responsabilidad |
|---|---|---|---|---|
| Actor (Administrador / Instructor) | «actor» | — | — | Mantiene el catálogo de ejercicios y su relación con grupos musculares. |
| `EjercicioController` | «control» | `app/controllers/EjercicioController.php` | `+index()`, `+ver()`, `+crear()`, `+guardar()`, `+editar()`, `+actualizar()`, `+eliminar()`, `-verificarAcceso()`, `-datosFormulario()`, `-validar()` | CRUD de ejercicios + asociación N:M. |
| `Controller` | «control abstract» | `app/core/Controller.php` | `#render()`, `#redirect()` | Heredada. |
| `AuthMiddleware` / `RoleMiddleware` | «control» | `app/core/middlewares/*.php` | `+handle()` | Control de acceso. |
| `Ejercicio` | «entity» | `app/models/Ejercicio.php` | `+crear()`, `+actualizar()`, `+eliminar()`, `+buscarPorId()`, `+buscarPorNombre()`, `+listarTodos()` | Acceso a `EJERCICIO`. |
| `GrupoMuscular` | «entity» | `app/models/GrupoMuscular.php` | `+listarTodos()` | Opciones de checkboxes de grupos musculares. |
| `EjercicioGrupoMuscular` | «entity» | `app/models/EjercicioGrupoMuscular.php` | `+asociar()`, `+listarGruposPorEjercicio()` | Acceso a la tabla pivote. |
| PostgreSQL (PDO) | «boundary» | `config/Database.php` | — | Ejecuta las consultas sobre `EJERCICIO`, `GRUPO_MUSCULAR`, `EJERCICIO_GRUPO_MUSCULAR`. |
| Vistas `ejercicio/index`, `ver`, `crear`, `editar` | «boundary» | `app/views/ejercicio/*.php` | — | Listado, detalle y formularios. |

**Relaciones**

| Origen | Destino | Relación | Mensaje o método | Dirección |
|---|---|---|---|---|
| Actor | `EjercicioController` | Dependencia | `index()`/`ver()`/`crear()`/`guardar()`/`editar()`/`actualizar()`/`eliminar()` | Actor → Controlador |
| `EjercicioController` | `AuthMiddleware` / `RoleMiddleware` | Dependencia | `handle()` | Controlador → Middlewares |
| `EjercicioController` | `Controller` | Generalización | `render()`/`redirect()` | Controlador → `Controller` |
| `EjercicioController` | `Ejercicio` | **Composición** | — | Controlador ◆→ `Ejercicio` |
| `EjercicioController` | `GrupoMuscular` | **Composición** | — | Controlador ◆→ `GrupoMuscular` |
| `EjercicioController` | `EjercicioGrupoMuscular` | **Composición** | — | Controlador ◆→ `EjercicioGrupoMuscular` |
| `EjercicioController` | `Ejercicio` | Dependencia | `listarTodos()`, `buscarPorId()`, `buscarPorNombre()`, `crear()`, `actualizar()`, `eliminar()` | Controlador → `Ejercicio` |
| `EjercicioController` | `GrupoMuscular` | Dependencia | `listarTodos()` | Controlador → `GrupoMuscular` |
| `EjercicioController` | `EjercicioGrupoMuscular` | Dependencia | `asociar(idEjercicio, grupos)`, `listarGruposPorEjercicio(id)` | Controlador → `EjercicioGrupoMuscular` |
| `Ejercicio` / `GrupoMuscular` / `EjercicioGrupoMuscular` | PostgreSQL | Dependencia | Consultas de la Sección 4.2 | Modelo → PostgreSQL |
| `EjercicioController` | Vistas `ejercicio/*` | Dependencia | `render(...)` | Controlador → Vistas |

**Distribución recomendada:** Actor arriba; `EjercicioController` al centro; `Controller` y middlewares a su derecha; los tres modelos (`Ejercicio`, `GrupoMuscular`, `EjercicioGrupoMuscular`) en fila debajo del controlador, cada uno con su rombo de composición; PostgreSQL a la derecha de esa fila; las cuatro vistas abajo.

---

### 6.6 CU05 — Gestionar evaluación física

**Nombre del diagrama:** `Diseño Procedimental - CU05 - Gestionar evaluación física`

**Participantes**

| Participante | Tipo UML | Archivo o clase | Métodos que deben aparecer | Responsabilidad |
|---|---|---|---|---|
| Actor (Instructor) | «actor» | — | — | Registra evaluaciones y consulta cualquier historial. |
| Actor (Cliente) | «actor» | — | — | Consulta su propio historial. |
| `EvaluacionFisicaController` | «control» | `app/controllers/EvaluacionFisicaController.php` | `+registrar()`, `+guardar()`, `+historial()`, `-validar()` | Registro y consulta de evaluaciones. |
| `Controller` | «control abstract» | `app/core/Controller.php` | `#render()`, `#redirect()` | Heredada. |
| `AuthMiddleware` / `RoleMiddleware` | «control» | `app/core/middlewares/*.php` | `+handle()` | Control de acceso (`instructor` para registrar; `instructor`/`cliente` para historial). |
| `EvaluacionFisica` | «entity» | `app/models/EvaluacionFisica.php` | `+crear()`, `+listarPorCliente()` | Acceso a `EVALUACION_FISICA`. |
| `Cliente` | «entity» | `app/models/Cliente.php` | `+listarTodos()`, `+actualizar()`, `+buscarPorId()` | Selector de clientes y sincronización de altura/peso. |
| PostgreSQL (PDO) | «boundary» | `config/Database.php` | — | Ejecuta las consultas sobre `EVALUACION_FISICA` y `CLIENTE`. |
| Vistas `evaluacion_fisica/registrar`, `historial` | «boundary» | `app/views/evaluacion_fisica/*.php` | — | Formulario de registro y tabla de historial. |

**Relaciones**

| Origen | Destino | Relación | Mensaje o método | Dirección |
|---|---|---|---|---|
| Actor Instructor | `EvaluacionFisicaController` | Dependencia | `registrar()`/`guardar(Request)` | Actor → Controlador |
| Actor Instructor / Cliente | `EvaluacionFisicaController` | Dependencia | `historial(Request)` | Actor → Controlador |
| `EvaluacionFisicaController` | `AuthMiddleware` / `RoleMiddleware` | Dependencia | `handle()` | Controlador → Middlewares |
| `EvaluacionFisicaController` | `Controller` | Generalización | `render()`/`redirect()` | Controlador → `Controller` |
| `EvaluacionFisicaController` | `EvaluacionFisica` | **Composición** | — | Controlador ◆→ Modelo |
| `EvaluacionFisicaController` | `Cliente` | **Composición** | — | Controlador ◆→ Modelo |
| `EvaluacionFisicaController` | `EvaluacionFisica` | Dependencia | `crear(datos)`, `listarPorCliente(idCliente)` | Controlador → Modelo |
| `EvaluacionFisicaController` | `Cliente` | Dependencia | `listarTodos()`, `actualizar(id,altura,peso)`, `buscarPorId(id)` | Controlador → Modelo |
| `EvaluacionFisica` / `Cliente` | PostgreSQL | Dependencia | Consultas de la Sección 4.2 | Modelo → PostgreSQL |
| `EvaluacionFisicaController` | Vistas `evaluacion_fisica/*` | Dependencia | `render(...)` | Controlador → Vistas |

**Distribución recomendada:** los dos actores arriba (Instructor a la izquierda, Cliente a la derecha), ambos apuntando al `EvaluacionFisicaController` central; `Controller`/middlewares a la derecha; `EvaluacionFisica` y `Cliente` debajo del controlador con sus rombos de composición; PostgreSQL a la derecha; las dos vistas abajo.

---

### 6.7 CU06 — Gestionar rutinas de entrenamiento

**Nombre del diagrama:** `Diseño Procedimental - CU06 - Gestionar rutinas de entrenamiento`

**Participantes**

| Participante | Tipo UML | Archivo o clase | Métodos que deben aparecer | Responsabilidad |
|---|---|---|---|---|
| Actor (Instructor) | «actor» | — | — | Crea rutinas, gestiona ejercicios y las visualiza. |
| Actor (Cliente) | «actor» | — | — | Visualiza sus propias rutinas. |
| `RutinaController` | «control» | `app/controllers/RutinaController.php` | `+index()`, `+ver()`, `+crear()`, `+guardar()`, `+editar()`, `+actualizar()`, `+asignar()`, `+agregarEjercicio()`, `+quitarEjercicio()`, `-esPropietario()`, `-tieneAcceso()`, `-agruparPorDia()`, `-validarDatosGenerales()`, `-validarDetalle()` | Orquesta todo el CU06. |
| `Controller` | «control abstract» | `app/core/Controller.php` | `#render()`, `#redirect()` | Heredada. |
| `AuthMiddleware` / `RoleMiddleware` | «control» | `app/core/middlewares/*.php` | `+handle()` | Control de acceso. |
| `Rutina` | «entity» | `app/models/Rutina.php` | `+crear()`, `+actualizar()`, `+buscarPorId()`, `+listarPorCliente()`, `+listarPorInstructor()` | Acceso a `RUTINA`. |
| `DetalleRutina` | «entity» | `app/models/DetalleRutina.php` | `+agregar()`, `+eliminar()`, `+listarPorRutina()` | Acceso a `DETALLE_RUTINA`. |
| `Cliente` | «entity» | `app/models/Cliente.php` | `+listarTodos()` | Selector de clientes al crear rutina. |
| `Ejercicio` | «entity» | `app/models/Ejercicio.php` | `+listarTodos()` | Selector de ejercicios al asignar. |
| PostgreSQL (PDO) | «boundary» | `config/Database.php` | — | Ejecuta las consultas sobre `RUTINA`, `DETALLE_RUTINA`, `CLIENTE`, `EJERCICIO`. |
| Vistas `rutina/index`, `ver`, `crear`, `editar`, `asignar` | «boundary» | `app/views/rutina/*.php` | — | Listado, detalle y formularios. |

**Relaciones**

| Origen | Destino | Relación | Mensaje o método | Dirección |
|---|---|---|---|---|
| Actor Instructor | `RutinaController` | Dependencia | `crear()`/`guardar()`/`editar()`/`actualizar()`/`asignar()`/`agregarEjercicio()`/`quitarEjercicio()` | Actor → Controlador |
| Actor Instructor / Cliente | `RutinaController` | Dependencia | `index()`/`ver(Request)` | Actor → Controlador |
| `RutinaController` | `AuthMiddleware` / `RoleMiddleware` | Dependencia | `handle()` | Controlador → Middlewares |
| `RutinaController` | `Controller` | Generalización | `render()`/`redirect()` | Controlador → `Controller` |
| `RutinaController` | `Rutina` | **Composición** | — | Controlador ◆→ `Rutina` |
| `RutinaController` | `DetalleRutina` | **Composición** | — | Controlador ◆→ `DetalleRutina` |
| `RutinaController` | `Cliente` | **Composición** | — | Controlador ◆→ `Cliente` |
| `RutinaController` | `Ejercicio` | **Composición** | — | Controlador ◆→ `Ejercicio` |
| `RutinaController` | `Rutina` | Dependencia | `crear()`, `buscarPorId()`, `actualizar()`, `listarPorCliente()`, `listarPorInstructor()` | Controlador → `Rutina` |
| `RutinaController` | `DetalleRutina` | Dependencia | `agregar()`, `eliminar()`, `listarPorRutina()` | Controlador → `DetalleRutina` |
| `RutinaController` | `Cliente` | Dependencia | `listarTodos()` | Controlador → `Cliente` |
| `RutinaController` | `Ejercicio` | Dependencia | `listarTodos()` | Controlador → `Ejercicio` |
| `Rutina`/`DetalleRutina`/`Cliente`/`Ejercicio` | PostgreSQL | Dependencia | Consultas de la Sección 4.2 | Modelo → PostgreSQL |
| `RutinaController` | Vistas `rutina/*` | Dependencia | `render(...)` | Controlador → Vistas |

**Distribución recomendada:** los dos actores arriba; `RutinaController` grande al centro; `Controller`/middlewares a su derecha; los cuatro modelos (`Rutina`, `DetalleRutina`, `Cliente`, `Ejercicio`) en una fila debajo, cada uno con su rombo de composición hacia el controlador; PostgreSQL a la derecha de esa fila; las cinco vistas en la fila inferior. Nota: no existe una relación directa `Rutina` ↔ `DetalleRutina` a nivel de objetos PHP (ambos modelos son independientes y solo se relacionan a través de las columnas de la base de datos); por eso el diagrama no dibuja ningún conector entre esas dos cajas.

---

## 7. Diagramas de Secuencia

### 7.1 Convenciones comunes a los seis diagramas

- **Tipo de diagrama en EA:** `Sequence` (UML → Interaction → Sequence Diagram).
- **Líneas de vida estándar** que reaparecen en varios CU: `:Actor` (el rol correspondiente), `:Request` (instanciado por el front controller), `:XController` (el controlador del CU), `:XModelo` (cada modelo invocado) y `:PostgreSQL` (representa `$this->db`, la conexión PDO). El objeto `Controller` base **no** se dibuja como línea de vida aparte: `render()`/`redirect()` se representan como **auto‑mensajes** (self‑message) sobre la línea de vida del propio controlador, porque en tiempo de ejecución es la misma instancia (herencia, no composición).
- **Activaciones:** se activa la línea de vida del receptor mientras procesa un mensaje síncrono, y se desactiva al enviar el retorno (flecha discontinua) a quien lo llamó.
- **Fragmentos combinados:** se usa `alt` para ramas mutuamente excluyentes (ej. credenciales válidas/ inválidas), `opt` para una rama condicional sin alternativa (ej. "si `id_cliente == 0`, mostrar selector"), y `loop` para repeticiones sobre una colección (ej. insertar cada grupo muscular seleccionado).
- **Pasos en EA para cada diagrama:**
  1. Crear un diagrama `Sequence` dentro del paquete correspondiente al CU.
  2. Arrastrar un elemento `Actor` y tantos elementos `Lifeline`/`Object` como participantes tenga la tabla de pasos.
  3. Usar el conector `Message` (síncrono, flecha rellena) para cada fila de la tabla "Emisor→Receptor", escribiendo `método(parámetros)` como texto del mensaje.
  4. Agregar el mensaje de retorno correspondiente (flecha discontinua) con el valor de la columna "Retorno" cuando el método no sea `void`.
  5. Para las ramas condicionales, usar el ícono `Combined Fragment` del toolbox de Sequence y seleccionar el operador `alt`, `opt` o `loop`; escribir la guarda (condición) entre corchetes en cada operando.
  6. Numerar los mensajes en el orden de la tabla (EA lo hace automáticamente si se activa "Sequence numbering" en las opciones del diagrama).

---

### 7.2 CU01 — Gestionar acceso al sistema

**Actor:** Visitante / Administrador / Instructor / Cliente.
**Participantes (líneas de vida):** `:Request`, `:AuthController`, `:Usuario`, `:PostgreSQL`.

**Flujo: `autenticar()`**

| Número | Emisor | Receptor | Método real | Parámetros | Retorno | Descripción |
|---|---|---|---|---|---|---|
| 1 | Actor | `:AuthController` | `autenticar(Request $request)` | Petición POST con `correo`, `password` | `void` | Envía el formulario de login. |
| 2 | `:AuthController` | `:Request` | `esPost()` | — | `true` | Verifica que la petición sea POST. |
| 3 | `:AuthController` | `:Request` | `input('correo', '')` | `'correo'` | `string $correo` | Obtiene el correo enviado. |
| 4 | `:AuthController` | `:Request` | `input('password', '')` | `'password'` | `string $password` | Obtiene la contraseña enviada. |
| 5 | `:AuthController` | `:Usuario` | `buscarPorCorreo($correo)` | `string $correo` | `?array $usuario` | Busca la cuenta por correo. |
| 6 | `:Usuario` | `:PostgreSQL` | `execute()` sobre `SELECT * FROM USUARIO WHERE correo=:correo` | `correo` | Fila o vacío | Consulta preparada. |
| 7 | `:PostgreSQL` | `:Usuario` | (retorno) | — | Fila / null | Resultado de la consulta. |
| 8 | `:Usuario` | `:AuthController` | (retorno) | — | `?array` | Devuelve el usuario o `null`. |
| **alt** [`!$usuario` o `!password_verify($password, $usuario['password_hash'])`] |
| 9 | `:AuthController` | `:AuthController` (self) | `render('auth/login', ['error'=>'Correo o contraseña incorrectos.'])` | — | `void` | Reincidencia con mensaje de error. |
| **else alt** [`!$usuario['estado']`] |
| 10 | `:AuthController` | `:AuthController` (self) | `render('auth/login', ['error'=>'Esta cuenta está inactiva...'])` | — | `void` | Bloquea cuentas desactivadas. |
| **else** |
| 11 | `:AuthController` | `:AuthController` (self) | `session_regenerate_id(true)` | — | `void` | Previene *session fixation*. |
| 12 | `:AuthController` | `:AuthController` (self) | Asigna `$_SESSION['user'] = [id, nombre, correo, rol]` | — | `void` | Arranca la sesión autenticada. |
| 13 | `:AuthController` | `:AuthController` (self) | `redirect('home')` | — | `void` | Redirige al panel principal. |
| **end** |

**Flujo: `logout()`** — un solo mensaje: Actor → `:AuthController` : `logout()`; `:AuthController` (self) → `$_SESSION = []; session_destroy();` → `redirect('home')`. No tiene excepciones ni fragmentos.

No hay excepciones capturadas en este flujo (ninguna llamada dentro de `autenticar()`/`logout()` está envuelta en `try/catch`).

---

### 7.3 CU02 — Gestionar cuentas de usuario

Por la cantidad de operaciones de este CU, se recomienda un diagrama de secuencia por operación (todas dentro del mismo paquete `CU02`).

**7.3.1 Auto‑registro público — `AuthController::crearCuenta()`**

Participantes: `:Actor(Visitante)`, `:Request`, `:AuthController`, `:Usuario`, `:Cliente`, `:PostgreSQL`.

| Número | Emisor | Receptor | Método real | Parámetros | Retorno | Descripción |
|---|---|---|---|---|---|---|
| 1 | Actor | `:AuthController` | `crearCuenta(Request $request)` | POST: ci, nombres, apellidos, fecha_nacimiento, correo, password, password_confirmacion | `void` | Envía el formulario. |
| 2 | `:AuthController` | `:Request` | `esPost()`, `input()` ×7 | — | valores | Extrae los campos. |
| 3 | `:AuthController` | `:AuthController` (self) | `validarRegistro($datos, $password, $passwordConfirmacion)` | — | `?string $error` | Valida obligatorios, formato de correo, longitud de contraseña, coincidencia, unicidad. |
| 3a | `:AuthController` | `:Usuario` | `buscarPorCorreo()` / `buscarPorCi()` (dentro de la validación) | — | `?array` | Verifica unicidad. |
| 3b | `:Usuario` | `:PostgreSQL` | `SELECT ... WHERE correo=:correo` / `WHERE ci=:ci` | — | Fila/null | Consultas de unicidad. |
| **opt** [`$error !== null`] |
| 4 | `:AuthController` | `:AuthController` (self) | `render('auth/register', ['error'=>$error, 'datos'=>$datos])` | — | `void` | Muestra el error y detiene el flujo. |
| **end** |
| 5 | `:AuthController` | `:Usuario` | `crear([...datos, password_hash, rol=>'cliente'])` | array | `int $idUsuario` | Inserta en `USUARIO`. |
| 6 | `:Usuario` | `:PostgreSQL` | `INSERT INTO USUARIO (...) RETURNING id_usuario` | — | `id_usuario` | INSERT. |
| 7 | `:AuthController` | `:Cliente` | `crear($idUsuario, null, null)` | `int, null, null` | `void` | Inserta la fila hija en `CLIENTE`. |
| 8 | `:Cliente` | `:PostgreSQL` | `INSERT INTO CLIENTE (id_usuario, altura, peso) VALUES (...)` | — | — | INSERT. |
| 9 | `:AuthController` | `:AuthController` (self) | `redirect('auth','login')` | — | `void` | Redirige al login. |

**7.3.2 Alta de cuenta por administrador — `UsuarioController::guardar()`**

Participantes: `:Actor(Administrador)`, `:AuthMiddleware`, `:RoleMiddleware`, `:Request`, `:UsuarioController`, `:Usuario`, `:Instructor`, `:Cliente`, `:PostgreSQL`.

| Número | Emisor | Receptor | Método real | Parámetros | Retorno | Descripción |
|---|---|---|---|---|---|---|
| 1 | Actor | `:UsuarioController` | `guardar(Request $request)` | POST: ci, nombres, apellidos, fecha_nacimiento, correo, rol, password, especialidad | `void` | Envía el formulario de alta. |
| 2 | `:UsuarioController` | `:AuthMiddleware` | `handle()` | — | `void` | Exige sesión. |
| 3 | `:UsuarioController` | `:RoleMiddleware` | `handle(['administrador'])` | — | `void` | Exige rol administrador. |
| 4 | `:UsuarioController` | `:Request` | `esPost()`, `input()` ×7 | — | valores | Extrae datos del formulario. |
| 5 | `:UsuarioController` | `:UsuarioController` (self) | `validarDatosBasicos($datos, $password, null)` | — | `?string` | Incluye consultas de unicidad vía `Usuario`. |
| **opt** [`$error === null && rol === 'instructor' && especialidad === ''`] |
| 6 | `:UsuarioController` | `:UsuarioController` (self) | Asigna error "La especialidad es obligatoria..." | — | — | Regla adicional para instructores. |
| **end** |
| **alt** [`$error !== null`] |
| 7 | `:UsuarioController` | `:UsuarioController` (self) | `render('usuario/crear', ['error'=>..., 'datos'=>...])` | — | `void` | Vuelve a mostrar el formulario. |
| **else** |
| 8 | `:UsuarioController` | `:Usuario` | `crear([...datos, password_hash])` | array | `int $idUsuario` | INSERT en `USUARIO`. |
| 9 | `:Usuario` | `:PostgreSQL` | `INSERT INTO USUARIO (...) RETURNING id_usuario` | — | id | INSERT. |
| **alt** [`rol === 'instructor'`] |
| 10 | `:UsuarioController` | `:Instructor` | `crear($idUsuario, $especialidad)` | — | `void` | INSERT en `INSTRUCTOR`. |
| **else alt** [`rol === 'cliente'`] |
| 11 | `:UsuarioController` | `:Cliente` | `crear($idUsuario, null, null)` | — | `void` | INSERT en `CLIENTE`. |
| **else** (`rol === 'administrador'`) |
| — | — | — | (no se crea fila hija) | — | — | El rol administrador no tiene tabla hija. |
| **end** |
| 12 | `:UsuarioController` | `:UsuarioController` (self) | `redirect('usuario','index')` | — | `void` | Vuelve al listado. |
| **end** |

**7.3.3 Cambiar estado — `UsuarioController::cambiarEstado()`**

| Número | Emisor | Receptor | Método real | Parámetros | Retorno | Descripción |
|---|---|---|---|---|---|---|
| 1 | Actor(Administrador) | `:UsuarioController` | `cambiarEstado(Request $request)` | POST: id, estado | `void` | Botón activar/desactivar. |
| 2 | `:UsuarioController` | `:Request` | `esPost()`, `input('id')`, `input('estado','0')` | — | valores | Extrae datos. |
| **opt** [`$id !== $_SESSION['user']['id']`] |
| 3 | `:UsuarioController` | `:Usuario` | `cambiarEstado($id, $nuevoEstado)` | — | `void` | `UPDATE USUARIO SET estado=...`. |
| 4 | `:Usuario` | `:PostgreSQL` | `UPDATE USUARIO SET estado=:estado WHERE id_usuario=:id` | — | — | UPDATE. |
| **end** |
| 5 | `:UsuarioController` | `:UsuarioController` (self) | `redirect('usuario','index')` | — | `void` | Un administrador nunca se desactiva a sí mismo (si `$id === session id`, el `UPDATE` simplemente no se ejecuta). |

**7.3.4 Actualizar perfil propio — `UsuarioController::actualizarPerfil()`**

Participantes: `:Actor(cualquier rol)`, `:Request`, `:UsuarioController`, `:Usuario`, `:Cliente`/`:Instructor`, `:PostgreSQL`.

| Número | Emisor | Receptor | Método real | Parámetros | Retorno | Descripción |
|---|---|---|---|---|---|---|
| 1 | Actor | `:UsuarioController` | `actualizarPerfil(Request $request)` | POST: ci, nombres, apellidos, fecha_nacimiento, correo (+ altura/peso o especialidad) | `void` | Envía el formulario de perfil. |
| 2 | `:UsuarioController` | `:Usuario` | `buscarPorId($id)` | `int` | `array $usuario` | Obtiene el rol actual. |
| 3 | `:UsuarioController` | `:UsuarioController` (self) | `validarDatosBasicos($datos, null, $id)` | — | `?string` | Valida (incluye unicidad excluyendo el propio id). |
| **alt** [`$error !== null`] |
| 4 | `:UsuarioController` | `:UsuarioController` (self) | `render('usuario/perfil', [...])` | — | `void` | Muestra error. |
| **else** |
| 5 | `:UsuarioController` | `:Usuario` | `actualizar($id, $datos)` | — | `void` | `UPDATE USUARIO`. |
| **alt** [`$usuario['rol'] === 'cliente'`] |
| 6 | `:UsuarioController` | `:Cliente` | `actualizar($id, $altura, $peso)` | — | `void` | `UPDATE CLIENTE`. |
| **else alt** [`$usuario['rol'] === 'instructor'`] |
| 7 | `:UsuarioController` | `:Instructor` | `actualizar($id, $especialidad)` | — | `void` | `UPDATE INSTRUCTOR`. |
| **end** |
| 8 | `:UsuarioController` | `:UsuarioController` (self) | Actualiza `$_SESSION['user']['nombre']`/`['correo']` | — | — | Refresca la barra de navegación sin re‑loguearse. |
| 9 | `:UsuarioController` | `:UsuarioController` (self) | `render('usuario/perfil', ['exito'=>...])` | — | `void` | Confirma el cambio. |
| **end** |

---

### 7.4 CU03 — Administrar catálogo de grupos musculares

**Actor:** Administrador / Instructor.
**Participantes:** `:Request`, `:GrupoMuscularController`, `:GrupoMuscular`, `:PostgreSQL`.

**Flujo: `guardar()`**

| Número | Emisor | Receptor | Método real | Parámetros | Retorno | Descripción |
|---|---|---|---|---|---|---|
| 1 | Actor | `:GrupoMuscularController` | `guardar(Request $request)` | POST: nombre, descripcion | `void` | Envía el formulario. |
| 2 | `:GrupoMuscularController` | `:Request` | `esPost()`, `input('nombre')`, `input('descripcion')` | — | valores | Extrae datos. |
| 3 | `:GrupoMuscularController` | `:GrupoMuscularController` (self) | `validar($nombre, null)` | — | `?string` | Verifica obligatorio + unicidad. |
| 3a | `:GrupoMuscularController` | `:GrupoMuscular` | `buscarPorNombre($nombre)` | — | `?array` | Consulta de unicidad. |
| 3b | `:GrupoMuscular` | `:PostgreSQL` | `SELECT * FROM GRUPO_MUSCULAR WHERE nombre=:nombre` | — | Fila/null | SELECT. |
| **alt** [`$error !== null`] |
| 4 | `:GrupoMuscularController` | `:GrupoMuscularController` (self) | `render('grupo_muscular/crear', [...])` | — | `void` | Muestra error. |
| **else** |
| 5 | `:GrupoMuscularController` | `:GrupoMuscular` | `crear($nombre, $descripcion)` | — | `int` | INSERT. |
| 6 | `:GrupoMuscular` | `:PostgreSQL` | `INSERT INTO GRUPO_MUSCULAR (...) RETURNING id_grupo_muscular` | — | id | INSERT. |
| 7 | `:GrupoMuscularController` | `:GrupoMuscularController` (self) | `redirect('grupoMuscular','index')` | — | `void` | Vuelve al listado. |
| **end** |

**Flujo: `eliminar()`**

| Número | Emisor | Receptor | Método real | Parámetros | Retorno | Descripción |
|---|---|---|---|---|---|---|
| 1 | Actor | `:GrupoMuscularController` | `eliminar(Request $request)` | POST: id | `void` | Confirmación (`onsubmit="return confirm(...)"` en la vista, no en el servidor). |
| 2 | `:GrupoMuscularController` | `:GrupoMuscular` | `eliminar($id)` | `int` | `void` | `DELETE FROM GRUPO_MUSCULAR WHERE id_grupo_muscular=:id`. |
| 3 | `:GrupoMuscular` | `:PostgreSQL` | `DELETE ...` (dispara `ON DELETE CASCADE` en `EJERCICIO_GRUPO_MUSCULAR`) | — | — | DELETE. |
| 4 | `:GrupoMuscularController` | `:GrupoMuscularController` (self) | `redirect('grupoMuscular','index')` | — | `void` | Vuelve al listado. |

Este flujo **no tiene manejo de excepciones**: a diferencia de `Ejercicio::eliminar()`, `GrupoMuscular::eliminar()` no envuelve el `DELETE` en `try/catch` (no lo necesita, porque la única FK que apunta a `GRUPO_MUSCULAR` tiene `ON DELETE CASCADE`, nunca `RESTRICT`).

---

### 7.5 CU04 — Administrar catálogo de ejercicios

**Actor:** Administrador / Instructor.
**Participantes:** `:Request`, `:EjercicioController`, `:Ejercicio`, `:EjercicioGrupoMuscular`, `:PostgreSQL`.

**Flujo: `guardar()` (crear ejercicio con grupos musculares asociados)**

| Número | Emisor | Receptor | Método real | Parámetros | Retorno | Descripción |
|---|---|---|---|---|---|---|
| 1 | Actor | `:EjercicioController` | `guardar(Request $request)` | POST: nombre, descripcion, beneficio, indicaciones, url_video, `grupos[]` | `void` | Envía el formulario con checkboxes. |
| 2 | `:EjercicioController` | `:Request` | `todoPost()['grupos'] ?? []` | — | `array` | Obtiene los ids de grupo muscular marcados. |
| 3 | `:EjercicioController` | `:EjercicioController` (self) | `validar($nombre, null)` | — | `?string` | Obligatorio + único. |
| **alt** [`$error !== null`] |
| 4 | `:EjercicioController` | `:EjercicioController` (self) | `render('ejercicio/crear', [...])` | — | `void` | Muestra error. |
| **else** |
| 5 | `:EjercicioController` | `:Ejercicio` | `crear($datos)` | array | `int $idEjercicio` | `INSERT INTO EJERCICIO (...) RETURNING id_ejercicio`. |
| 6 | `:Ejercicio` | `:PostgreSQL` | INSERT | — | id | INSERT. |
| 7 | `:EjercicioController` | `:EjercicioGrupoMuscular` | `asociar($idEjercicio, $gruposSeleccionados)` | array | `void` | Reemplaza asociaciones. |
| 8 | `:EjercicioGrupoMuscular` | `:PostgreSQL` | `DELETE FROM EJERCICIO_GRUPO_MUSCULAR WHERE id_ejercicio=:id` | — | — | DELETE previo (sin efecto si es un ejercicio nuevo). |
| **loop** [por cada `$idGrupoMuscular` en `$idsGrupoMuscular`] |
| 9 | `:EjercicioGrupoMuscular` | `:PostgreSQL` | `INSERT INTO EJERCICIO_GRUPO_MUSCULAR (id_ejercicio, id_grupo_muscular) VALUES (...)` | — | — | Un INSERT por cada grupo marcado. |
| **end** |
| 10 | `:EjercicioController` | `:EjercicioController` (self) | `redirect('ejercicio','index')` | — | `void` | Vuelve al listado. |
| **end** |

**Flujo: `eliminar()` (con excepción de integridad referencial)**

| Número | Emisor | Receptor | Método real | Parámetros | Retorno | Descripción |
|---|---|---|---|---|---|---|
| 1 | Actor | `:EjercicioController` | `eliminar(Request $request)` | POST: id | `void` | Solicita borrar. |
| 2 | `:EjercicioController` | `:Ejercicio` | `eliminar($id)` | `int` | `bool` | Intenta el `DELETE`. |
| 3 | `:Ejercicio` | `:PostgreSQL` | `DELETE FROM EJERCICIO WHERE id_ejercicio=:id` | — | — | DELETE dentro de `try{}`. |
| **alt** [PostgreSQL lanza `PDOException` por `fk_detalle_ejercicio` (`ON DELETE RESTRICT`)] |
| 4 | `:PostgreSQL` | `:Ejercicio` | (excepción `PDOException`) | — | — | El ejercicio está en uso en `DETALLE_RUTINA`. |
| 5 | `:Ejercicio` | `:Ejercicio` (self, `catch`) | `return false` | — | `bool` | Captura la excepción y devuelve `false`. |
| 6 | `:EjercicioController` | `:EjercicioController` (self) | `render('ejercicio/index', ['error'=>'No se puede eliminar...'])` | — | `void` | Informa el error sin un 500. |
| **else** [DELETE exitoso] |
| 7 | `:Ejercicio` | `:EjercicioController` | (retorno) | — | `true` | Borrado correcto. |
| 8 | `:EjercicioController` | `:EjercicioController` (self) | `redirect('ejercicio','index')` | — | `void` | Vuelve al listado. |
| **end** |

---

### 7.6 CU05 — Gestionar evaluación física

**Actor:** Instructor (registrar), Instructor/Cliente (historial).
**Participantes:** `:Request`, `:EvaluacionFisicaController`, `:EvaluacionFisica`, `:Cliente`, `:PostgreSQL`.

**Flujo: `guardar()`**

| Número | Emisor | Receptor | Método real | Parámetros | Retorno | Descripción |
|---|---|---|---|---|---|---|
| 1 | Actor(Instructor) | `:EvaluacionFisicaController` | `guardar(Request $request)` | POST: id_cliente, peso, altura, objetivo, porcentaje_grasa, masa_muscular, flexibilidad, observaciones | `void` | Envía el formulario. |
| 2 | `:EvaluacionFisicaController` | `:EvaluacionFisicaController` (self) | `validar($datos)` | — | `?string` | Cliente seleccionado; peso/altura numéricos > 0. |
| **alt** [`$error !== null`] |
| 3 | `:EvaluacionFisicaController` | `:EvaluacionFisicaController` (self) | `render('evaluacion_fisica/registrar', [...])` | — | `void` | Muestra error. |
| **else** |
| 4 | `:EvaluacionFisicaController` | `:EvaluacionFisica` | `crear([...datos, id_instructor=>session id])` | array | `int` | `INSERT INTO EVALUACION_FISICA (...) RETURNING id_evaluacion_fisica`. |
| 5 | `:EvaluacionFisica` | `:PostgreSQL` | INSERT | — | id | INSERT. |
| 6 | `:EvaluacionFisicaController` | `:Cliente` | `actualizar($id_cliente, $altura, $peso)` | — | `void` | Sincroniza altura/peso actuales. |
| 7 | `:Cliente` | `:PostgreSQL` | `UPDATE CLIENTE SET altura=..., peso=... WHERE id_usuario=:id_usuario` | — | — | UPDATE. |
| 8 | `:EvaluacionFisicaController` | `:EvaluacionFisicaController` (self) | `redirect('evaluacionFisica','historial', ['id'=>id_cliente])` | — | `void` | Muestra el historial recién actualizado. |
| **end** |

**Flujo: `historial()`**

| Número | Emisor | Receptor | Método real | Parámetros | Retorno | Descripción |
|---|---|---|---|---|---|---|
| 1 | Actor | `:EvaluacionFisicaController` | `historial(Request $request)` | GET: id (opcional) | `void` | Solicita ver el historial. |
| **alt** [`$_SESSION['user']['rol'] === 'cliente'`] |
| 2 | `:EvaluacionFisicaController` | `:EvaluacionFisica` | `listarPorCliente($_SESSION['user']['id'])` | — | `array` | Ignora cualquier `id` de la URL. |
| 3 | `:EvaluacionFisica` | `:PostgreSQL` | `SELECT ... JOIN USUARIO ... WHERE ef.id_cliente=:id_cliente ORDER BY fecha DESC` | — | filas | SELECT. |
| 4 | `:EvaluacionFisicaController` | `:EvaluacionFisicaController` (self) | `render('evaluacion_fisica/historial', [...])` | — | `void` | Muestra su propio historial. |
| **else** (rol instructor) |
| 5 | `:EvaluacionFisicaController` | `:Cliente` | `listarTodos()` | — | `array` | Lista de clientes para el selector. |
| **opt** [`$idCliente === 0`] |
| 6 | `:EvaluacionFisicaController` | `:EvaluacionFisicaController` (self) | `render('evaluacion_fisica/historial', ['evaluaciones'=>[], 'clientes'=>...])` | — | `void` | Muestra el selector de clientes en vez de una tabla vacía. |
| **end** |
| **opt** [`$idCliente !== 0`] |
| 7 | `:EvaluacionFisicaController` | `:Cliente` | `buscarPorId($idCliente)` | — | `?array` | Verifica que el cliente exista. |
| 8 | `:EvaluacionFisicaController` | `:EvaluacionFisica` | `listarPorCliente($idCliente)` | — | `array` | Solo si el cliente existe. |
| 9 | `:EvaluacionFisicaController` | `:EvaluacionFisicaController` (self) | `render('evaluacion_fisica/historial', [...])` | — | `void` | Muestra el historial del cliente elegido. |
| **end** |
| **end** |

---

### 7.7 CU06 — Gestionar rutinas de entrenamiento

**Actor:** Instructor (crear/gestionar), Instructor/Cliente (visualizar).
**Participantes:** `:Request`, `:RutinaController`, `:Rutina`, `:DetalleRutina`, `:Cliente`, `:Ejercicio`, `:PostgreSQL`.

**7.7.1 Crear rutina y redirigir a asignación — `guardar()`**

| Número | Emisor | Receptor | Método real | Parámetros | Retorno | Descripción |
|---|---|---|---|---|---|---|
| 1 | Actor(Instructor) | `:RutinaController` | `guardar(Request $request)` | POST: nombre, tipo, fecha_inicio, fecha_fin, id_cliente | `void` | Envía el formulario de creación. |
| 2 | `:RutinaController` | `:RutinaController` (self) | `validarDatosGenerales($datos)` | — | `?string` | Nombre y fecha de inicio obligatorios; `fecha_fin >= fecha_inicio`; cliente seleccionado. |
| **alt** [`$error !== null`] |
| 3 | `:RutinaController` | `:RutinaController` (self) | `render('rutina/crear', [...])` | — | `void` | Muestra error. |
| **else** |
| 4 | `:RutinaController` | `:Rutina` | `crear([...datos, id_instructor=>session id])` | array | `int $idRutina` | `INSERT INTO RUTINA (...) RETURNING id_rutina`. |
| 5 | `:Rutina` | `:PostgreSQL` | INSERT | — | id | INSERT. |
| 6 | `:RutinaController` | `:RutinaController` (self) | `redirect('rutina','asignar', ['id'=>idRutina])` | — | `void` | Pasa directo a agregar ejercicios. |
| **end** |

**7.7.2 Agregar ejercicio a la rutina — `agregarEjercicio()`**

| Número | Emisor | Receptor | Método real | Parámetros | Retorno | Descripción |
|---|---|---|---|---|---|---|
| 1 | Actor(Instructor) | `:RutinaController` | `agregarEjercicio(Request $request)` | POST: id_rutina, id_ejercicio, dia_semana, series, repeticiones, tiempo_descanso, orden | `void` | Envía el formulario de la pantalla "asignar". |
| 2 | `:RutinaController` | `:Rutina` | `buscarPorId($id)` | — | `?array` | Recupera la rutina. |
| **alt** [`!$rutina || !esPropietario($rutina)`] |
| 3 | `:RutinaController` | `:RutinaController` (self) | `http_response_code(404); render('errors/404')` | — | `void` | La rutina no existe o no pertenece al instructor en sesión. |
| **else** |
| 4 | `:RutinaController` | `:RutinaController` (self) | `validarDetalle($datos)` | — | `?string` | Ejercicio y día seleccionados; series/repeticiones > 0; descanso ≥ 0. |
| **alt** [`$error !== null`] |
| 5 | `:RutinaController` | `:RutinaController` (self) | `render('rutina/asignar', [...])` | — | `void` | Muestra error, recarga el detalle actual. |
| **else** |
| 6 | `:RutinaController` | `:DetalleRutina` | `agregar($idRutina, $datos)` | array | `int` | `INSERT INTO DETALLE_RUTINA (...) RETURNING id_detalle`. |
| 7 | `:DetalleRutina` | `:PostgreSQL` | INSERT | — | id | INSERT. |
| 8 | `:RutinaController` | `:RutinaController` (self) | `redirect('rutina','asignar', ['id'=>idRutina])` | — | `void` | Vuelve a la pantalla de asignación. |
| **end** |
| **end** |

**7.7.3 Ver rutina — `ver()`**

| Número | Emisor | Receptor | Método real | Parámetros | Retorno | Descripción |
|---|---|---|---|---|---|---|
| 1 | Actor(Instructor o Cliente) | `:RutinaController` | `ver(Request $request)` | GET: id | `void` | Solicita ver el detalle. |
| 2 | `:RutinaController` | `:Rutina` | `buscarPorId($id)` | — | `?array` | Recupera la rutina con nombres de cliente/instructor. |
| 3 | `:Rutina` | `:PostgreSQL` | `SELECT ... JOIN USUARIO uc ... JOIN USUARIO ui ... WHERE r.id_rutina=:id` | — | fila/null | SELECT. |
| **alt** [`!$rutina || !tieneAcceso($rutina)`] |
| 4 | `:RutinaController` | `:RutinaController` (self) | `http_response_code(404); render('errors/404')` | — | `void` | No existe, o el usuario en sesión no es ni el instructor dueño ni el cliente asignado (404 en vez de 403 para no revelar que el id existe). |
| **else** |
| 5 | `:RutinaController` | `:DetalleRutina` | `listarPorRutina($id)` | — | `array` | Ejercicios de la rutina, ordenados por día y orden. |
| 6 | `:DetalleRutina` | `:PostgreSQL` | `SELECT dr.*, e.nombre ... JOIN EJERCICIO ... WHERE dr.id_rutina=:id_rutina ORDER BY dr.dia_semana, dr.orden` | — | filas | SELECT. |
| 7 | `:RutinaController` | `:RutinaController` (self) | `agruparPorDia($detalle)` | — | `array` | Agrupa por día en el orden fijo `DIAS_SEMANA`. |
| 8 | `:RutinaController` | `:RutinaController` (self) | `render('rutina/ver', ['rutina'=>..., 'detallePorDia'=>...])` | — | `void` | Muestra la rutina; la vista oculta el botón "Gestionar ejercicios" si el usuario no es el instructor dueño. |
| **end** |

No se identificaron bucles (`loop`) en los flujos de `RutinaController` salvo, indirectamente, el que ya se documentó para `EjercicioGrupoMuscular::asociar()` en CU04; `RutinaController::agregarEjercicio()` inserta un único detalle por envío de formulario (no hay inserción múltiple en una sola petición).

---

## 8. Resumen General

### 8.1 Vista – Controlador – Modelos – Métodos – Tablas por CU

| Caso de uso | Vista(s) | Controlador | Modelos | Métodos principales | Tablas utilizadas |
|---|---|---|---|---|---|
| CU01 | `auth/login`, `home/index` | `AuthController` | `Usuario` | `login()`, `autenticar()`, `logout()` | `USUARIO` |
| CU02 | `auth/register`, `usuario/index`, `crear`, `editar`, `perfil` | `AuthController`, `UsuarioController` | `Usuario`, `Cliente`, `Instructor` | `register()`, `crearCuenta()`, `index()`, `guardar()`, `editar()`, `actualizar()`, `cambiarEstado()`, `perfil()`, `actualizarPerfil()` | `USUARIO`, `CLIENTE`, `INSTRUCTOR` |
| CU03 | `grupo_muscular/index`, `crear`, `editar` | `GrupoMuscularController` | `GrupoMuscular` | `index()`, `guardar()`, `actualizar()`, `eliminar()` | `GRUPO_MUSCULAR` |
| CU04 | `ejercicio/index`, `ver`, `crear`, `editar` | `EjercicioController` | `Ejercicio`, `GrupoMuscular`, `EjercicioGrupoMuscular` | `index()`, `ver()`, `guardar()`, `actualizar()`, `eliminar()` | `EJERCICIO`, `GRUPO_MUSCULAR`, `EJERCICIO_GRUPO_MUSCULAR` |
| CU05 | `evaluacion_fisica/registrar`, `historial` | `EvaluacionFisicaController` | `EvaluacionFisica`, `Cliente` | `registrar()`, `guardar()`, `historial()` | `EVALUACION_FISICA`, `CLIENTE` |
| CU06 | `rutina/index`, `ver`, `crear`, `editar`, `asignar` | `RutinaController` | `Rutina`, `DetalleRutina`, `Cliente`, `Ejercicio` | `index()`, `ver()`, `guardar()`, `actualizar()`, `asignar()`, `agregarEjercicio()`, `quitarEjercicio()` | `RUTINA`, `DETALLE_RUTINA`, `CLIENTE`, `EJERCICIO` |

### 8.2 Operaciones CRUD por CU

| Caso de uso | Operación CRUD | Método del controlador | Método del modelo | Consulta SQL |
|---|---|---|---|---|
| CU01 | READ | `AuthController::autenticar()` | `Usuario::buscarPorCorreo()` | `SELECT * FROM USUARIO WHERE correo=:correo` |
| CU02 | CREATE | `AuthController::crearCuenta()` / `UsuarioController::guardar()` | `Usuario::crear()`, `Cliente::crear()`, `Instructor::crear()` | `INSERT INTO USUARIO/CLIENTE/INSTRUCTOR (...) [RETURNING ...]` |
| CU02 | UPDATE | `UsuarioController::actualizar()` / `actualizarPerfil()` / `cambiarEstado()` | `Usuario::actualizar()`, `Usuario::cambiarEstado()`, `Cliente::actualizar()`, `Instructor::actualizar()` | `UPDATE USUARIO/CLIENTE/INSTRUCTOR SET ... WHERE id_usuario=:id` |
| CU02 | READ | `UsuarioController::index()` / `editar()` / `perfil()` | `Usuario::listarTodos()`, `buscarPorId()`, `Cliente::buscarPorId()`, `Instructor::buscarPorId()` | `SELECT * FROM USUARIO ...` / `SELECT ... JOIN ...` |
| CU02 | DELETE | *No implementado* | — | — (se usa `cambiarEstado()` como baja lógica, ver 2.9 y 5) |
| CU03 | CREATE | `GrupoMuscularController::guardar()` | `GrupoMuscular::crear()` | `INSERT INTO GRUPO_MUSCULAR (...) RETURNING id_grupo_muscular` |
| CU03 | READ | `GrupoMuscularController::index()` / `editar()` | `GrupoMuscular::listarTodos()`, `buscarPorId()`, `buscarPorNombre()` | `SELECT * FROM GRUPO_MUSCULAR ...` |
| CU03 | UPDATE | `GrupoMuscularController::actualizar()` | `GrupoMuscular::actualizar()` | `UPDATE GRUPO_MUSCULAR SET ... WHERE id_grupo_muscular=:id` |
| CU03 | DELETE | `GrupoMuscularController::eliminar()` | `GrupoMuscular::eliminar()` | `DELETE FROM GRUPO_MUSCULAR WHERE id_grupo_muscular=:id` |
| CU04 | CREATE | `EjercicioController::guardar()` | `Ejercicio::crear()`, `EjercicioGrupoMuscular::asociar()` (INSERT) | `INSERT INTO EJERCICIO (...) RETURNING id_ejercicio` + `INSERT INTO EJERCICIO_GRUPO_MUSCULAR (...)` |
| CU04 | READ | `EjercicioController::index()` / `ver()` / `editar()` | `Ejercicio::listarTodos()`, `buscarPorId()`, `buscarPorNombre()`, `EjercicioGrupoMuscular::listarGruposPorEjercicio()` | `SELECT * FROM EJERCICIO ...` / `SELECT ... JOIN ...` |
| CU04 | UPDATE | `EjercicioController::actualizar()` | `Ejercicio::actualizar()`, `EjercicioGrupoMuscular::asociar()` | `UPDATE EJERCICIO SET ... WHERE id_ejercicio=:id` |
| CU04 | DELETE | `EjercicioController::eliminar()` | `Ejercicio::eliminar()`, `EjercicioGrupoMuscular::asociar()` (DELETE) | `DELETE FROM EJERCICIO WHERE id_ejercicio=:id` (con `try/catch`) |
| CU05 | CREATE | `EvaluacionFisicaController::guardar()` | `EvaluacionFisica::crear()` | `INSERT INTO EVALUACION_FISICA (...) RETURNING id_evaluacion_fisica` |
| CU05 | UPDATE | `EvaluacionFisicaController::guardar()` | `Cliente::actualizar()` | `UPDATE CLIENTE SET altura=..., peso=... WHERE id_usuario=:id_usuario` |
| CU05 | READ | `EvaluacionFisicaController::historial()` | `EvaluacionFisica::listarPorCliente()`, `Cliente::listarTodos()`, `buscarPorId()` | `SELECT ... JOIN USUARIO ... WHERE ef.id_cliente=:id_cliente ORDER BY fecha DESC` |
| CU05 | DELETE | *No implementado* | — | — |
| CU06 | CREATE | `RutinaController::guardar()` / `agregarEjercicio()` | `Rutina::crear()`, `DetalleRutina::agregar()` | `INSERT INTO RUTINA (...) RETURNING id_rutina` / `INSERT INTO DETALLE_RUTINA (...) RETURNING id_detalle` |
| CU06 | READ | `RutinaController::index()` / `ver()` / `asignar()` | `Rutina::listarPorCliente()`, `listarPorInstructor()`, `buscarPorId()`, `DetalleRutina::listarPorRutina()` | `SELECT ... JOIN USUARIO ...` |
| CU06 | UPDATE | `RutinaController::actualizar()` | `Rutina::actualizar()` | `UPDATE RUTINA SET ... WHERE id_rutina=:id` |
| CU06 | DELETE | `RutinaController::quitarEjercicio()` | `DetalleRutina::eliminar()` | `DELETE FROM DETALLE_RUTINA WHERE id_detalle=:id_detalle AND id_rutina=:id_rutina` |

### 8.3 Flujos principales, alternativos y excepciones

| Caso de uso | Flujo principal | Flujo alternativo | Excepción |
|---|---|---|---|
| CU01 | Login con credenciales correctas y cuenta activa → sesión iniciada → `home`. | Credenciales incorrectas o cuenta inactiva → se reincide en `auth/login` con mensaje de error. | Ninguna capturada explícitamente en código. |
| CU02 | Administrador crea/edita una cuenta con datos válidos → tabla hija creada/actualizada según el rol → listado. | Datos inválidos, correo/CI duplicado, especialidad faltante para instructor → se reincide en el formulario con error. | Ninguna capturada explícitamente (una violación `UNIQUE` de PostgreSQL no atrapada por la validación en PHP se propagaría sin manejo). |
| CU03 | Nombre válido y único → alta/edición en el catálogo → listado. | Nombre vacío o duplicado → error en el formulario. | Ninguna (el `DELETE` no está protegido por `try/catch`, pero tampoco puede fallar por FK gracias a `ON DELETE CASCADE`). |
| CU04 | Nombre válido y único → alta/edición del ejercicio + reemplazo de sus grupos musculares → listado. | Nombre vacío o duplicado → error en el formulario. | `PDOException` al intentar `DELETE` un ejercicio ya usado en `DETALLE_RUTINA` (`ON DELETE RESTRICT`), capturada en `Ejercicio::eliminar()` y mostrada como mensaje de error en `ejercicio/index`. |
| CU05 | Instructor registra peso/altura válidos para un cliente → se guarda la evaluación y se sincroniza `CLIENTE` → historial del cliente. | Cliente no seleccionado o peso/altura inválidos → error en el formulario; en el historial, instructor sin `id` en la URL → se muestra selector de clientes en vez de tabla vacía. | Ninguna capturada explícitamente. |
| CU06 | Instructor crea la rutina para un cliente → agrega ejercicios (día, series, repeticiones, descanso, orden) → cliente y/o instructor la visualizan agrupada por día. | Datos generales o de detalle inválidos → error en el formulario correspondiente; usuario sin acceso a una rutina (`tieneAcceso()`/`esPropietario()` en `false`) → `404`. | Ninguna capturada explícitamente (las restricciones `CHECK` de `DETALLE_RUTINA` están duplicadas en `validarDetalle()`, por lo que en la práctica no deberían dispararse desde la aplicación). |

---

## 9. Verificación Final

Lista de comprobación para validar este documento contra el código antes de construir los diagramas en Enterprise Architect:

- [x] Cada uno de los seis casos de uso (CU01–CU06) tiene su propio diagrama procedimental documentado en la Sección 6, con tablas de participantes y relaciones.
- [x] Cada uno de los seis casos de uso tiene su propio diagrama de secuencia (o conjunto de sub‑diagramas, cuando el CU agrupa varias operaciones) documentado en la Sección 7, con tabla numerada de mensajes.
- [x] Todos los participantes citados (`AuthController`, `UsuarioController`, `GrupoMuscularController`, `EjercicioController`, `EvaluacionFisicaController`, `RutinaController`, `HomeController`, `Controller`, `Model`, `Request`, `AuthMiddleware`, `RoleMiddleware`, `Config`, `Database`, `Usuario`, `Cliente`, `Instructor`, `GrupoMuscular`, `Ejercicio`, `EjercicioGrupoMuscular`, `EvaluacionFisica`, `Rutina`, `DetalleRutina`) existen como archivos reales en `app/` o `config/`, verificados por lectura directa del código fuente.
- [x] Todos los métodos usados en los diagramas (procedimental y de secuencia) existen literalmente en el código, con la misma firma documentada en la Sección 4.
- [x] Los mensajes de las tablas de relaciones y de las tablas de secuencia coinciden exactamente con el nombre del método invocado en el código (no se parafrasean como "gestiona usuario" sino como `guardar(Request $request)`, `buscarPorCorreo($correo)`, etc.).
- [x] Las nueve tablas documentadas en la Sección 5 (`USUARIO`, `INSTRUCTOR`, `CLIENTE`, `EVALUACION_FISICA`, `RUTINA`, `GRUPO_MUSCULAR`, `EJERCICIO`, `EJERCICIO_GRUPO_MUSCULAR`, `DETALLE_RUTINA`) coinciden con las definidas en `database/schema.sql`, incluyendo tipos de columna, `CHECK`, `UNIQUE` y `ON DELETE`.
- [x] Las relaciones 1:1 (herencia por tabla), 1:N y N:M, así como las claves compuestas (`EJERCICIO_GRUPO_MUSCULAR`, `DETALLE_RUTINA`), coinciden con las restricciones `FOREIGN KEY`/`PRIMARY KEY` reales del script SQL.
- [x] Para cada CU, el diagrama procedimental (Sección 6) y el de secuencia (Sección 7) representan el mismo conjunto de clases y el mismo conjunto de métodos — solo cambia la notación (colaboración sin eje temporal vs. secuencia con líneas de vida).
- [x] No se inventó ningún componente: se señalaron explícitamente dos métodos implementados pero no conectados a ningún controlador (`Usuario::actualizarPassword()`, `EjercicioGrupoMuscular::listarEjerciciosPorGrupo()`), la ausencia total de operación `DELETE` sobre `USUARIO`/`CLIENTE`/`INSTRUCTOR`/`EVALUACION_FISICA`/`RUTINA`, y la ausencia de manejo de excepciones fuera de `Ejercicio::eliminar()`.
- [x] La documentación (nombres de clase, archivos, parámetros, SQL, vistas de origen/destino) coincide con la implementación real leída directamente de `app/controllers/`, `app/models/`, `app/core/`, `config/`, `public/index.php` y `database/schema.sql`.
