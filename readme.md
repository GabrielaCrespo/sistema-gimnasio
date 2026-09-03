
# Sistema de Gestión de Gimnasio

Sistema web para la gestión de clientes, instructores, usuarios, ejercicios, grupos musculares, evaluaciones físicas y rutinas de entrenamiento.

El proyecto es desarrollado como una aplicación web monolítica, utilizando el patrón arquitectónico Modelo–Vista–Controlador (MVC) de forma interna, sin utilizar frameworks ni mecanismos de enrutamiento.


## 1. Descripción del proyecto

El Sistema de Gestión de Gimnasio tiene como objetivo centralizar y organizar la información relacionada con la gestión de usuarios, clientes, instructores y planificación del entrenamiento.

El sistema permitirá administrar los usuarios según su rol, mantener un catálogo de ejercicios y grupos musculares, registrar evaluaciones físicas y gestionar rutinas de entrenamiento personalizadas para los clientes.


## 2. Objetivo general

Desarrollar un sistema web para la gestión básica de un gimnasio que permita administrar usuarios, clientes, instructores, ejercicios, grupos musculares, evaluaciones físicas y rutinas de entrenamiento mediante una arquitectura MVC implementada en PHP puro.


## 3. Tecnologías utilizadas

* PHP 8.5
* PostgreSQL
* HTML5
* CSS3
* JavaScript
* Git
* GitHub

### 4. Restricciones tecnológicas

El proyecto se desarrolla bajo las siguientes restricciones:

* No se utilizarán frameworks de desarrollo web.
* No se utilizará Laravel, Symfony, CodeIgniter u otros frameworks.
* No se utilizará una API REST.
* No se utilizará JSON como mecanismo de comunicación entre las capas.
* No se utilizará un sistema de enrutamiento.
* La aplicación será monolítica y se ejecutará en un único servidor web.
* La comunicación interna seguirá el patrón MVC.


## 5. Arquitectura

El sistema utiliza el patrón **Modelo–Vista–Controlador (MVC)** de forma interna.

### Modelo

Se encarga de gestionar los datos y la comunicación con PostgreSQL.

Responsabilidades:

* Ejecutar consultas SQL.
* Registrar información.
* Actualizar información.
* Eliminar información.
* Consultar información.
* Mantener la lógica relacionada con los datos.

### Vista

Se encarga de presentar la información al usuario mediante HTML, CSS y JavaScript.

Las vistas no realizan consultas directamente a la base de datos.

### Controlador

Actúa como intermediario entre el usuario, los modelos y las vistas.

Responsabilidades:

* Recibir las solicitudes.
* Validar y procesar los datos recibidos.
* Solicitar operaciones a los modelos.
* Enviar los resultados a las vistas.
* Coordinar el flujo de cada caso de uso.


## 6. Casos de uso

El sistema contempla los siguientes casos de uso principales:

| Código | Caso de uso                                  |
| ------ | -------------------------------------------- |
| CU01   | Autenticar usuario en el sistema             |
| CU02   | Gestionar cuentas de usuario                 |
| CU03   | Administrar catálogo de grupos musculares    |
| CU04   | Administrar catálogo de ejercicios           |
| CU05   | Registrar evaluación física                  |
| CU06   | Consultar historial de evaluación física     |
| CU07   | Gestionar y asignar rutinas de entrenamiento |
| CU08   | Visualizar rutinas asignadas                 |


## 7. Actores

### Administrador

Puede gestionar las cuentas de usuario y administrar los catálogos del sistema.

### Instructor

Puede gestionar ejercicios y grupos musculares, registrar evaluaciones físicas y crear y asignar rutinas de entrenamiento a los clientes.

### Cliente

Puede consultar sus rutinas asignadas, visualizar los detalles de sus entrenamientos y consultar su historial de evaluaciones físicas.

## 8. Base de datos

El sistema utiliza PostgreSQL como sistema gestor de base de datos.

Las principales entidades son:

* Usuario
* Instructor
* Cliente
* Evaluación Física
* Rutina
* Detalle de Rutina
* Ejercicio
* Grupo Muscular
* Ejercicio – Grupo Muscular

El archivo `database/schema.sql` contiene la estructura de la base de datos y sus restricciones.


## 9. Seguridad y control de acceso

El sistema utilizará sesiones de PHP para controlar la autenticación de los usuarios.

El acceso a las funcionalidades estará determinado por el rol del usuario:

Administrador
      │
      ├── Gestión de usuarios
      └── Gestión de catálogos

Instructor
      │
      ├── Ejercicios
      ├── Grupos musculares
      ├── Evaluaciones físicas
      └── Rutinas

Cliente
      │
      ├── Rutinas asignadas
      └── Historial de evaluaciones


Se utilizarán middlewares internos para verificar:

* Existencia de una sesión activa.
* Rol del usuario.
* Permisos de acceso a determinadas funcionalidades.


## 10. Comunicación entre componentes

La comunicación entre los componentes del sistema seguirá el patrón MVC.

Las capas estarán separadas para evitar que:

* Las vistas accedan directamente a PostgreSQL.
* Los controladores ejecuten consultas SQL directamente.
* Los modelos generen HTML.
* La lógica de presentación se mezcle con la lógica de acceso a datos.


