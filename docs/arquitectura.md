# Arquitectura del Sistema

El sistema utilizará la arquitectura **Modelo-Vista-Controlador (MVC)**, separando los datos, la lógica de procesamiento y la interfaz de usuario.

```text
Usuario
   ↓
Vista
   ↓
Controlador
   ↓
Modelo
   ↓
PostgreSQL
```

## Modelo

Gestiona los datos y las operaciones relacionadas con la base de datos.

Principales modelos:

* Usuario
* Cliente
* Instructor
* GrupoMuscular
* Ejercicio
* EvaluacionFisica
* Rutina
* DetalleRutina
* Asignacion

## Vista

Contiene las interfaces mediante las cuales los usuarios interactúan con el sistema.

## Controlador

Recibe las solicitudes de los usuarios, procesa las operaciones y coordina la comunicación entre las Vistas y los Modelos.
