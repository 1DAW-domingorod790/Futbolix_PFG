# Futbolix - Documentación del Proyecto

## Tecnologías utilizadas

| Capa | Tecnología |
|------|-----------|
| Backend | Laravel 12 (PHP 8.5) |
| Frontend | Vue 3 + Inertia.js |
| Base de datos | SQLite |
| CSS | Tailwind CSS |
| Build tool | Vite |
| Autenticación | Laravel Breeze |

---

## Cómo arrancar el proyecto

Abrir **2 terminales** en la carpeta del proyecto:

**Terminal 1 — Servidor Laravel (backend):**
```bash
C:\Users\Practicas\scoop\shims\php.exe artisan serve
```

**Terminal 2 — Vite (assets CSS/JS en tiempo real):**
```bash
npm run dev
```

Acceder en el navegador: **http://localhost:8000**

> Solo es necesario ejecutar `php artisan migrate` la primera vez para crear la base de datos.

---

## Funcionalidades implementadas

### 1. Registro de usuarios
- Ruta: `/register`
- El usuario introduce nombre, email, contraseña y confirmación de contraseña.
- **Captcha de verificación**: se muestra una imagen con caracteres aleatorios para verificar que el usuario es humano. Si se introduce mal, el registro no se completa. Haciendo clic en la imagen se genera un nuevo captcha.
- Si todos los datos son correctos, el usuario se registra y se redirige al dashboard.

### 2. Inicio de sesión
- Ruta: `/login`
- El usuario introduce email y contraseña.
- Si las credenciales son correctas, accede al dashboard.
- Si son incorrectas, se muestra un mensaje de error.
- Opción "Remember me" para mantener la sesión.

### 3. Recuperación de contraseña por email
- Ruta: `/forgot-password`
- El usuario introduce su email.
- El sistema envía un email real con un enlace de recuperación al correo registrado (configurado con SMTP: `smtp.serviciodecorreo.es`).
- El enlace lleva a un formulario donde el usuario puede establecer una nueva contraseña.
- Ruta del formulario: `/reset-password/{token}?email=...`

### 4. Cerrar sesión (Logout)
- Disponible desde el dashboard.
- Destruye la sesión del usuario y redirige a la página de inicio.

### 5. Dashboard (área privada)
- Ruta: `/dashboard`
- Solo accesible para usuarios autenticados.
- Si un usuario no autenticado intenta acceder, es redirigido al login.

### 6. Página de inicio
- Ruta: `/`
- Página pública de bienvenida.
- Muestra enlaces a Login y Register si el usuario no está autenticado.
- Si el usuario ya está autenticado, muestra enlace al Dashboard.

---

## Seguridad implementada

- **Captcha en el registro**: evita registros automáticos de bots.
- **Validación de formularios**: todos los campos se validan en el servidor (Laravel).
- **Contraseñas cifradas**: las contraseñas se guardan con bcrypt (nunca en texto plano).
- **Protección CSRF**: todos los formularios incluyen token CSRF automáticamente.
- **Rutas protegidas**: el middleware `auth` protege las rutas privadas.
- **Sesiones en base de datos**: las sesiones se almacenan en SQLite, no en el servidor.
- **Tokens de reset seguros**: los tokens de recuperación de contraseña expiran y son de un solo uso.

---

## Estructura de la base de datos (SQLite)

### Tabla `users`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | integer | Identificador único |
| name | string | Nombre del usuario |
| email | string | Email (único) |
| password | string | Contraseña cifrada (bcrypt) |
| email_verified_at | timestamp | Fecha de verificación de email |
| two_factor_secret | text | Secreto 2FA (opcional) |
| two_factor_recovery_codes | text | Códigos de recuperación 2FA |
| remember_token | string | Token para "recordarme" |
| created_at / updated_at | timestamp | Fechas de creación/modificación |

### Tabla `password_reset_tokens`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| email | string | Email del usuario |
| token | string | Token de recuperación (cifrado) |
| created_at | timestamp | Fecha de creación |

### Tabla `sessions`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | string | ID de sesión |
| user_id | integer | Usuario asociado (nullable) |
| ip_address | string | IP del cliente |
| user_agent | string | Navegador del cliente |
| payload | text | Datos de la sesión |
| last_activity | integer | Última actividad |

---

## Archivos clave del proyecto

| Archivo | Descripción |
|---------|-------------|
| `routes/web.php` | Define todas las rutas de la aplicación |
| `app/Http/Controllers/Auth/` | Controladores de autenticación |
| `resources/js/Pages/Auth/` | Vistas Vue de login, registro, etc. |
| `resources/js/Pages/Dashboard.vue` | Vista del dashboard |
| `resources/js/Pages/Welcome.vue` | Página de inicio |
| `resources/js/Layouts/` | Layouts reutilizables (autenticado / invitado) |
| `database/database.sqlite` | Base de datos SQLite |
| `.env` | Configuración del entorno (base de datos, email, etc.) |
| `config/captcha.php` | Configuración del captcha |

---

## Configuración de email

El sistema de email está configurado con SMTP real:

- **Host**: smtp.serviciodecorreo.es
- **Puerto**: 465 (SSL)
- **Cuenta**: cvilpiz939@g.educaand.es

Los emails se envían realmente al correo del usuario para la recuperación de contraseña.

---

## Diagrama de flujo de autenticación

```
Usuario no autenticado
        │
        ├── /register ──► Formulario registro + Captcha ──► Dashboard
        │
        ├── /login ──► Formulario login ──► Dashboard
        │
        └── /forgot-password ──► Email con enlace ──► /reset-password ──► Login

Usuario autenticado
        │
        ├── /dashboard ──► Área privada
        │
        └── Logout ──► /
```
