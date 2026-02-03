<div align="center">

# 🏨 Sistema de Gestión Hotelera

### Plataforma moderna y completa para la administración de hoteles

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white)](https://vuejs.org)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com)

[Características](#-características) •
[Instalación](#-instalación) •
[Tecnologías](#-stack-tecnológico) •
[Documentación](#-documentación)

</div>

---

## 📋 Descripción

Sistema integral de gestión hotelera desarrollado con tecnologías modernas y escalables. Completamente dockerizado para facilitar el despliegue y desarrollo en cualquier entorno.

## ✨ Características

- 🔐 **Autenticación segura** - Sistema robusto de login y permisos
- 🏨 **Gestión de reservas** - Control total de bookings y disponibilidad
- 👥 **Administración de usuarios** - Roles y permisos personalizables
- 📊 **Dashboard interactivo** - Estadísticas en tiempo real
- 🎨 **Interfaz moderna** - UI/UX optimizada con PrimeVue
- 🐳 **100% Dockerizado** - Deploy rápido y consistente

## 🛠 Stack Tecnológico

<table>
<tr>
<td align="center" width="25%">
<img src="https://laravel.com/img/logomark.min.svg" width="60" height="60" alt="Laravel"/>
<br><strong>Laravel 12</strong>
<br>Backend Framework
</td>
<td align="center" width="25%">
<img src="https://upload.wikimedia.org/wikipedia/commons/9/95/Vue.js_Logo_2.svg" width="60" height="60" alt="Vue"/>
<br><strong>Vue 3</strong>
<br>Frontend Framework
</td>
<td align="center" width="25%">
<img src="https://upload.wikimedia.org/wikipedia/commons/2/29/Postgresql_elephant.svg" width="60" height="60" alt="PostgreSQL"/>
<br><strong>PostgreSQL 16</strong>
<br>Base de Datos
</td>
<td align="center" width="25%">
<img src="https://www.docker.com/wp-content/uploads/2022/03/Moby-logo.png" width="60" height="60" alt="Docker"/>
<br><strong>Docker</strong>
<br>Containerización
</td>
</tr>
</table>

### Tecnologías Adicionales

- **PHP** 8.3 FPM
- **Node.js** 22
- **Vite** - Build tool ultrarrápido
- **PrimeVue** - Componentes UI premium
- **Nginx** - Servidor web
- **Adminer** - Gestión de base de datos

## 🚀 Instalación

### Prerrequisitos

- Docker & Docker Compose instalados
- Git

### Pasos de instalación

1️⃣ **Clonar el repositorio**
```bash
git clone https://github.com/Jefferson0k/hoteles.git
cd hoteles
```

2️⃣ **Configurar variables de entorno**
```bash
cp .env.example .env
```

Edita el archivo `.env` con las credenciales de base de datos:
```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=hoteles
DB_USERNAME=hoteles_user
DB_PASSWORD=password
```

3️⃣ **Levantar los contenedores**
```bash
docker compose up --build -d
```

4️⃣ **Configurar Laravel**
```bash
# Generar key de aplicación
docker compose exec app php artisan key:generate

# Ejecutar migraciones
docker compose exec app php artisan migrate

# Iniciar servidor de desarrollo
docker compose exec app composer run dev
```

## 🌐 Accesos al Sistema

| Servicio | URL | Descripción |
|----------|-----|-------------|
| **Aplicación Principal** | [http://localhost](http://localhost) | Frontend + Backend |
| **Adminer (DB Manager)** | [http://localhost:8080](http://localhost:8080) | Gestión de base de datos |
| **Vite Dev Server** | [http://localhost:5173](http://localhost:5173) | Hot reload development |

## 🗄️ Configuración de Base de Datos

Para acceder a **Adminer** en `http://localhost:8080`:
```
Sistema:      PostgreSQL
Servidor:     postgres
Usuario:      hoteles_user
Contraseña:   password
Base de datos: hoteles
```

## ⚙️ Comandos Útiles

### Migraciones
```bash
# Ejecutar migraciones
docker compose exec app php artisan migrate

# Revertir última migración
docker compose exec app php artisan migrate:rollback

# Refrescar todas las migraciones
docker compose exec app php artisan migrate:fresh
```

### Cache y Configuración
```bash
# Limpiar cache de configuración
docker compose exec app php artisan config:clear

# Limpiar cache de aplicación
docker compose exec app php artisan cache:clear

# Optimizar aplicación para producción
docker compose exec app php artisan optimize
```

### Docker
```bash
# Ver logs
docker compose logs -f app

# Detener contenedores
docker compose down

# Reiniciar servicios
docker compose restart

# Reconstruir imágenes
docker compose up --build -d
```

## 📁 Estructura del Proyecto
```
hoteles/
├── app/                 # Lógica de aplicación Laravel
├── resources/
│   ├── js/             # Componentes Vue.js
│   └── views/          # Vistas Blade
├── public/             # Archivos públicos
├── database/
│   └── migrations/     # Migraciones de BD
├── docker/             # Configuración Docker
├── docker-compose.yml  # Orquestación de contenedores
└── .env.example        # Variables de entorno
```

## 🔧 Configuración de Nginx

El servidor Nginx está configurado para servir la aplicación Laravel:
```nginx
root /var/www/public;
index index.php index.html;
fastcgi_pass app:9000;
```

## 👨‍💻 Autor

<div align="center">

**Jeferson Coveñas**

[![GitHub](https://img.shields.io/badge/GitHub-Jefferson0k-181717?style=for-the-badge&logo=github)](https://github.com/Jefferson0k)

</div>

## 📄 Licencia

Este proyecto está bajo licencia de **uso educativo y personal**.

⚠️ **Prohibido el uso comercial sin autorización expresa del autor.**

---

<div align="center">

### ⭐ Si te ha sido útil, considera darle una estrella al proyecto

**Hecho con ❤️ usando Laravel 12 + Vue 3 + Docker**

</div>
