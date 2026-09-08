# Laravel Auth API

API RESTful completa para la autenticación de usuarios construida con **Laravel**.

Esta API proporciona todas las funcionalidades necesarias para gestionar usuarios, autenticación basada en tokens JWT, verificación por correo electrónico, recuperación de contraseñas y Autenticación 2FA.

## Características

- **Autenticación JWT:** Inicio y cierre de sesión utilizando JSON Web Tokens.
- **Registro y Verificación de Email:** Registro de usuarios con confirmación de correo electrónico.
- **Gestión de Contraseñas:** Flujos para actualizar y restablecer la contraseña.
- **Autenticación 2FA:** Integración con Google Authenticator usando códigos QR y códigos de recuperación.
- **Gestión de Usuarios (CRUD):** Endpoints para listar, ver, actualizar y eliminar usuarios.
- **Control de Acceso basado en Roles (RBAC):** Restricción de endpoints solo para administradores.

## Tecnologías y Paquetes Principales

- **PHP 8.2+**
- **Laravel 11.x**
- [tymon/jwt-auth](https://github.com/tymondesigns/jwt-auth) - Para la autenticación JWT.
- [pragmarx/google2fa-laravel](https://github.com/antonioribeiro/google2fa-laravel) - Para el manejo de 2FA.
- [bacon/bacon-qr-code](https://github.com/Bacon/BaconQrCode) - Para generar los códigos QR de 2FA.

## Instalación

1. **Clonar el repositorio** (o descargar el código fuente):

    ```bash
    git clone https://github.com/wesvm/auth-api.git
    cd auth-api
    ```

2. **Instalar las dependencias de PHP:**

    ```bash
    composer install
    ```

3. **Configurar las variables de entorno:**
   Copia el archivo de ejemplo para crear tu propio `.env`:

    ```bash
    cp .env.example .env
    ```

    Asegúrate de configurar los datos de conexión a la base de datos y la configuración del servidor de correo (`MAIL_*`) para que funcione la verificación de email y recuperación de contraseñas.

4. **Generar la clave de la aplicación:**

    ```bash
    php artisan key:generate
    ```

5. **Generar la clave secreta para JWT:**

    ```bash
    php artisan jwt:secret
    ```

6. **Ejecutar las migraciones:**

    ```bash
    php artisan migrate
    ```

7. **Iniciar el servidor local:**
    ```bash
    php artisan serve
    ```

## Documentación de la API (Endpoints)

Todas las rutas están bajo el prefijo `/api`.

### Autenticación (`/api/auth/*`)

- `POST /auth/register` - Registrar un nuevo usuario.
- `POST /auth/login` - Iniciar sesión y obtener el token JWT.
- `POST /auth/2fa/verify` - Autenticar usando el código 2FA.
- `GET /auth/verify-email/{token}` - Verificar el correo electrónico.
- `POST /auth/resend-verification` - Reenviar el correo de verificación.
- `GET /auth/me` - Obtener los datos del usuario autenticado. (Requiere Token)
- `POST /auth/logout` - Cerrar sesión e invalidar el token. (Requiere Token)
- `POST /auth/refresh` - Refrescar el token JWT. (Requiere Token)

### Contraseña (`/api/auth/password/*`)

- `POST /auth/password/forgot` - Enviar enlace de restablecimiento de contraseña.
- `POST /auth/password/reset` - Restablecer la contraseña con el token.
- `POST /auth/password/update` - Actualizar la contraseña estando autenticado. (Requiere Token)

### Autenticación 2FA (`/api/auth/2fa/*`) _Requiere Token_

- `GET /auth/2fa/status` - Comprobar el estado del 2FA (activado/desactivado).
- `POST /auth/2fa/generate` - Generar el secreto y código QR para configurar 2FA.
- `POST /auth/2fa/enable` - Activar el 2FA tras verificar el primer código.
- `POST /auth/2fa/disable` - Desactivar el 2FA.
- `POST /auth/2fa/recovery-codes/regenerate` - Generar nuevos códigos de recuperación.

### Usuarios (`/api/users/*`) _Requiere Token_

- `GET /users` - Listar todos los usuarios.
- `GET /users/{user}` - Obtener detalles de un usuario específico.
- `PUT /users/{user}` o `PATCH /users/{user}` - Actualizar datos de un usuario.
- `DELETE /users/{user}` - Eliminar un usuario. _(Requiere rol: admin)_.
