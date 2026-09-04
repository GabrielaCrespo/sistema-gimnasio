# Base de Datos

El sistema utilizará **PostgreSQL** como sistema gestor de base de datos.

## Entidades principales

* Usuario
* Cliente
* Instructor
* GrupoMuscular
* Ejercicio
* EvaluacionFisica
* Rutina
* DetalleRutina
* Asignacion

## Relaciones principales

* Usuario se relaciona con Cliente o Instructor.
* Cliente puede tener múltiples evaluaciones físicas.
* Instructor registra evaluaciones físicas.
* Instructor puede crear múltiples rutinas.
* Una rutina puede contener múltiples ejercicios.
* Un ejercicio puede pertenecer a múltiples rutinas mediante DetalleRutina.
* Un cliente puede tener rutinas asignadas mediante Asignacion.

Las relaciones serán implementadas mediante claves primarias y claves foráneas para garantizar la integridad de los datos.
