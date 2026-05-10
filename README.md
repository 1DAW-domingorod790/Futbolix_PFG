# Documentacion Futbolix

## 1. Descripcion funcional del sistema

### Vision general

Futbolix es una plataforma web de gestion de futbol que combina la administracion de torneos personalizados, la consulta de competiciones reales de ligas europeas, un sistema completo de equipos, jugadores y partidos, y funcionalidades avanzadas de inteligencia artificial.

La aplicacion esta pensada para ofrecer una experiencia completa de gestion deportiva tanto a usuarios normales como a administradores. Permite crear torneos propios, gestionar equipos y plantillas, registrar resultados, generar fases eliminatorias, consultar competiciones reales y usar IA para predicciones y asistencia conversacional.

### Funcionalidades principales

#### 1. Gestion de torneos personalizados

El nucleo del sistema permite crear torneos personalizados, anadir equipos, gestionar partidos, registrar resultados, generar playoffs de forma automatica e importar o exportar datos mediante archivos CSV.

Rutas principales:

- `/tournaments`
- `/tournaments/{id}`
- `/tournaments/{id}/matches`
- `/tournaments/{id}/playoffs`

#### 2. Gestion de equipos y jugadores

Cada equipo dispone de informacion como nombre, escudo, posicion en la clasificacion y estadisticas completas: partidos jugados, victorias, empates, derrotas, goles a favor, goles en contra, diferencia de goles y puntos.

Dentro de cada equipo se gestiona una plantilla de jugadores con datos como nombre, DNI, dorsal, fecha de nacimiento, numero de goles y fotografia.

Funcionalidades destacadas:

- Subida de imagenes mediante Vue e Inertia.
- Gestion dinamica de jugadores.
- Control de permisos a nivel de torneo.
- Actualizacion automatica de estadisticas segun resultados.

#### 3. Sistema de partidos

Cada partido incluye equipo local, equipo visitante, fecha y hora, estado del partido, resultado y goleadores.

El sistema permite visualizar partidos, actualizar resultados e integrar automaticamente esos resultados con la clasificacion del torneo.

#### 4. Inteligencia artificial

Futbolix incorpora un modulo de inteligencia artificial con chat conversacional, prediccion de partidos y sistema de creditos.

El chat IA permite mantener conversaciones persistentes asociadas a cada usuario, con historial de mensajes y control de consumo mediante creditos.

Rutas principales:

- `/futbolix-ai`
- `/api/futbolix-ai/conversations`
- `/api/predictions/match`

Tambien existe un sistema de planes de IA que permite gestionar creditos, planes gratuitos o de pago, y mejoras de plan de usuario.

#### 5. Ligas reales

El sistema integra datos de competiciones reales de futbol, como LaLiga, Premier League, Bundesliga, Serie A, Ligue 1 y otras competiciones soportadas por la API externa.

Rutas principales:

- `/competitions`
- `/matches`
- `/teams`

La sincronizacion se realiza mediante comandos Artisan y tareas programadas.

#### 6. Autenticacion y usuarios

El sistema de autenticacion esta basado en Laravel Breeze/Fortify e incluye:

- Registro de usuarios.
- Inicio de sesion.
- Verificacion de correo electronico.
- Recuperacion de contrasena.
- Edicion del perfil.
- Soporte para autenticacion en dos pasos.

#### 7. Panel de administracion

Existe un panel de administracion exclusivo para usuarios con permisos de administrador. Desde este panel se pueden gestionar usuarios del sistema, incluyendo creacion, edicion, eliminacion y control de acceso.

Ruta principal:

- `/admin/users`

### Caracteristicas mas innovadoras

Una de las principales innovaciones del proyecto es el uso de Vue con Inertia.js. Esto permite desarrollar una aplicacion con comportamiento similar a una SPA sin necesidad de construir una API REST tradicional para todas las vistas. Laravel sigue controlando las rutas y controladores, mientras que Vue se encarga de la interfaz.

Otra caracteristica destacada es la integracion de inteligencia artificial dentro del flujo real de la aplicacion. Futbolix incluye chat conversacional, prediccion de partidos, sistema de creditos y gestion de conversaciones.

El motor de torneos tambien es una parte importante del proyecto, ya que permite generar clasificaciones automaticas, crear playoffs, importar datos desde CSV y gestionar competiciones completas de forma dinamica.

Finalmente, el sistema deportivo es bastante completo, ya que incluye equipos, jugadores, estadisticas avanzadas, escudos, fotografias, historial de partidos y resultados.

## 2. Modelo de datos

### Entidades principales

#### User

Contiene los datos basicos del usuario: id, nombre, email, contrasena, indicador de administrador y avatar. Tambien se relaciona con conversaciones de IA y con los torneos creados por el usuario.

#### Tournament

Representa un torneo personalizado. Incluye datos como id, nombre, codigo, propietario, formato y configuracion de playoffs. Tiene relaciones uno a muchos con equipos, partidos y rondas eliminatorias.

#### TournamentTeam

Representa un equipo dentro de un torneo. Incluye nombre, escudo, posicion en la clasificacion y estadisticas deportivas. Se relaciona con jugadores y partidos.

#### TournamentPlayer

Contiene informacion del jugador: nombre, DNI, dorsal, fecha de nacimiento, goles, fotografia y equipo asociado.

#### TournamentMatch

Representa un partido dentro de un torneo. Incluye equipo local, equipo visitante, resultado, fecha, hora, jornada, estado y datos de goleadores.

#### PlayoffMatch

Define los partidos de fase eliminatoria, con torneo asociado, ronda, posicion en el cuadro, equipos, estado, resultado, ganador y siguiente partido.

#### Competition

Representa una competicion real obtenida desde la API externa de futbol. Incluye nombre, codigo, tipo, emblema, temporada actual y jornada actual.

#### Team

Representa un equipo real de la API externa. Incluye nombre, escudo, nombre corto, estadio, ano de fundacion y relacion con competiciones.

#### Game

Representa un partido real sincronizado desde la API externa. Incluye competicion, equipos, jornada, fecha, estado y resultado.

#### AIConversation

Representa una conversacion de IA asociada a un usuario.

#### AIMessage

Representa los mensajes individuales dentro de una conversacion, con rol de usuario o asistente y contenido.

#### AIPlan / Creditos

Gestiona los planes de inteligencia artificial y el consumo de creditos por usuario.

## 3. Stack tecnologico

### Backend

El backend esta desarrollado con Laravel 12 y PHP 8.4 en el entorno Docker del proyecto. Se utilizan controladores, modelos Eloquent, migraciones, seeders, comandos Artisan personalizados y el Scheduler de Laravel.

### Frontend

El frontend esta construido con Vue 3, Inertia.js, TailwindCSS y Vite. La aplicacion usa componentes Vue para las vistas principales y Laravel para el enrutado del lado servidor.

### Inteligencia artificial

El proyecto incluye un sistema propio de chat con IA, prediccion de partidos y gestion de creditos. La integracion con el modelo de IA se configura mediante claves API en el archivo `.env`.

### Base de datos

Se utiliza MariaDB/MySQL como sistema de base de datos relacional. En Docker se levanta un servicio MariaDB con la base de datos `futbolix`.

### Arquitectura de sincronizacion

El sistema tiene comandos Artisan para sincronizar competiciones, equipos, partidos y clasificaciones desde la API externa de futbol.

Comando principal:

```bash
php artisan app:sync-all
```

Este comando ejecuta la sincronizacion completa en orden:

1. Competiciones.
2. Equipos.
3. Partidos.
4. Clasificaciones.

Ademas, el Scheduler ejecuta esta sincronizacion cada 10 minutos.

### Arquitectura general

```text
Frontend Vue + Inertia
        ↓
Controladores Laravel
        ↓
Servicios, comandos Artisan y Scheduler
        ↓
Base de datos MariaDB/MySQL
        ↓
IA y APIs externas de futbol
```

## 4. Despliegue y puesta en marcha

### Requisitos previos

- Docker.
- Docker Compose.
- Conexion a internet para descargar imagenes y dependencias.
- Claves API necesarias para las integraciones externas.

### Variables de entorno

Primero se debe crear el archivo `.env` a partir del ejemplo:

```bash
cp .env.example .env
```

Despues hay que configurar las claves API necesarias:

```env
FOOTBALL_DATA_ORG_API_KEY=
GROQ_API_KEY=
MAIL_PASSWORD=
```

La clave `FOOTBALL_DATA_ORG_API_KEY` se utiliza para sincronizar competiciones, equipos, partidos y clasificaciones reales.

La clave `GROQ_API_KEY` se utiliza para las funcionalidades de inteligencia artificial.

La configuracion de correo se utiliza para recuperacion de contrasena y verificacion de email.

### Levantar la aplicacion con Docker

Desde la raiz del proyecto:

```bash
docker compose up -d --build
```

La aplicacion quedara disponible en:

```text
http://localhost:8000
```

PhpMyAdmin quedara disponible en:

```text
http://localhost:8080
```

### Preparar Laravel

Despues de levantar los contenedores por primera vez:

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
docker compose exec app php artisan optimize:clear
```

### Sincronizar datos reales de futbol

Para cargar o actualizar competiciones, equipos, partidos y clasificaciones desde la API externa:

```bash
docker compose exec app php artisan app:sync-all
```

Este paso requiere que `FOOTBALL_DATA_ORG_API_KEY` este configurada en `.env`.

### Comandos utiles

Ver contenedores:

```bash
docker compose ps
```

Ver logs:

```bash
docker compose logs -f
```

Parar la aplicacion:

```bash
docker compose down
```

Parar y eliminar contenedores huerfanos:

```bash
docker compose down --remove-orphans
```

Recompilar el frontend:

```bash
docker compose run --rm vite sh -c "npm install && npm run build"
```

