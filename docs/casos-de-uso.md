# Casos de Uso

## Actores

* Administrador
* Instructor
* Cliente

## Casos de Uso

| Código | Caso de Uso                                | Actor                              |
| ------ | ------------------------------------------- | ----------------------------------- |
| CU01   | Gestionar acceso al sistema                 | Administrador, Instructor, Cliente |
| CU02   | Gestionar cuentas de usuario                | Administrador, Instructor, Cliente |
| CU03   | Administrar catálogo de grupos musculares   | Administrador, Instructor          |
| CU04   | Administrar catálogo de ejercicios          | Administrador, Instructor          |
| CU05   | Gestionar evaluación física                 | Instructor, Cliente                |
| CU06   | Gestionar rutinas de entrenamiento          | Instructor, Cliente                |

## Descripción

* **CU01 — Gestionar acceso al sistema:** Permite iniciar y cerrar sesión de Administrador, Instructor y Cliente, validando las credenciales y el estado de la cuenta.
* **CU02 — Gestionar cuentas de usuario:** Permite crear cuentas, consultar, modificar datos, gestionar el estado (activo/inactivo) y el perfil de los usuarios. Incluye tanto la administración de cuentas por parte de un administrador como el auto-registro público de cuentas de rol cliente y la edición del perfil propio de cualquier rol.
* **CU03 — Administrar catálogo de grupos musculares:** Permite crear, consultar, modificar y eliminar grupos musculares.
* **CU04 — Administrar catálogo de ejercicios:** Permite crear, consultar, modificar y eliminar ejercicios, y asociarlos a uno o varios grupos musculares.
* **CU05 — Gestionar evaluación física:** Permite registrar evaluaciones físicas de los clientes (instructor) y consultar el historial de evaluaciones de un cliente (instructor para cualquier cliente, cliente solo el propio).
* **CU06 — Gestionar rutinas de entrenamiento:** Permite crear rutinas, modificarlas, asignarlas a un cliente, agregar y quitar ejercicios de una rutina, y visualizar las rutinas (instructor sobre las que creó, cliente sobre las que tiene asignadas).
