# traveler-app

# Traveler App

Aplicación web para consulta de información de viajes, desarrollada con **Angular** en el frontend y un **backend PHP con arquitectura MVC personalizada**.

El sistema permite registrar y autenticar usuarios, consultar países y ciudades, obtener información climática y realizar conversiones de moneda mediante APIs externas.

---

## Tecnologías utilizadas

### Frontend

- Angular 22
- TypeScript
- Bootstrap 5
- RxJS
- Angular Router
- HttpClient

### Backend

- PHP 8.5+
- Arquitectura MVC personalizada
- PostgreSQL
- PDO
- Composer
- JWT
- JWE
- cURL

### APIs externas

- OpenWeather
- ExchangeRate-API

---

## Requisitos previos

Antes de instalar el proyecto es necesario contar con:

- PHP 8.2 o superior
- Composer
- Node.js
- npm
- Angular CLI
- PostgreSQL
- Git

Versiones utilizadas durante el desarrollo:

```text
PHP       8.5.11
Composer  2.10.3
Node.js   24.21.0
npm       11.19.0
Angular   22.2.0
PostgreSQL 18
```

Las versiones pueden variar mientras sean compatibles con las tecnologías utilizadas.

---

# Instalación

## 1. Clonar el repositorio

```bash
git clone <URL_DEL_REPOSITORIO>
```

Entrar al proyecto:

```bash
cd traveler-app
```

La estructura principal es:

```text
traveler-app/
├── backend/
├── frontend/
├── .gitignore
└── README.md
```

---

# Backend

## 2. Entrar al backend

```bash
cd backend
```

## 3. Instalar dependencias PHP

```bash
composer install
```

Esto instalará las dependencias definidas en:

```text
backend/composer.json
```

y utilizará las versiones registradas en:

```text
backend/composer.lock
```

---

## 4. Configurar variables de entorno

Crear una copia de `.env.example` llamada:

```text
.env
```

En Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

El archivo debe contener las variables necesarias:

```env
APP_TOKEN_SECRET=
WEATHER_API_KEY=
EXCHANGE_API_KEY=
APP_DEBUG=false

DB_HOST=localhost
DB_PORT=5432
DB_NAME=traveler_bd
DB_USER=
DB_PASSWORD=
```

### Importante

El archivo `.env` **no debe subirse a GitHub**.

Nunca colocar en el código fuente:

- Contraseñas
- API Keys
- Secretos JWT
- Credenciales de PostgreSQL
- Tokens

Para generar un secreto seguro:

```bash
php -r "echo base64_encode(random_bytes(32));"
```

El resultado puede utilizarse como valor de:

```env
APP_TOKEN_SECRET=
```

---

# Base de datos

## 5. Crear la base de datos PostgreSQL

Crear una base de datos llamada:

```text
traveler_bd
```

Por ejemplo, desde PostgreSQL:

```sql
CREATE DATABASE traveler_bd;
```

Después configurar las credenciales correspondientes en `.env`.

---

## 6. Ejecutar las migraciones

Desde:

```text
backend/
```

ejecutar:

```bash
php database/migrate.php
```

Las migraciones crean las tablas necesarias para el funcionamiento de la aplicación.

Entre ellas:

```text
monedas
paises
ciudades
usuarios
historial
tokens_revocados
refresh_tokens
tasas_cambio
```

---

## 7. Ejecutar los seeders

Después de ejecutar las migraciones:

```bash
php database/seed.php
```

Los seeders agregan información inicial para las monedas, países y ciudades.

Actualmente se incluyen:

### Monedas

- GBP — Libra esterlina
- JPY — Yen japonés
- INR — Rupia india
- DKK — Corona danesa

### Países

- Reino Unido
- Japón
- India
- Dinamarca

### Ciudades

- Londres
- Manchester
- Tokio
- Osaka
- Nueva Delhi
- Mumbai
- Copenhague
- Aarhus

---

# APIs externas

El proyecto utiliza dos APIs externas.

## OpenWeather

Se utiliza para obtener información meteorológica de las ciudades.

La API Key debe configurarse en:

```env
WEATHER_API_KEY=
```

## ExchangeRate-API

Se utiliza para obtener tasas de conversión de moneda.

La API Key debe configurarse en:

```env
EXCHANGE_API_KEY=
```

Las claves no deben incluirse directamente en el código fuente.

---

# Ejecutar el backend

Desde la carpeta:

```text
backend/
```

ejecutar:

```bash
php -S localhost:8000 -t public
```

El backend estará disponible en:

```text
http://localhost:8000
```

La API utiliza rutas bajo:

```text
/api/
```

---

# Frontend

## 8. Instalar dependencias

Abrir otra terminal y entrar en:

```bash
cd frontend
```

Instalar las dependencias:

```bash
npm install
```

Esto utilizará el archivo:

```text
package-lock.json
```

para instalar las versiones correspondientes.

---

## 9. Ejecutar Angular

Desde:

```text
frontend/
```

ejecutar:

```bash
ng serve
```

También puede utilizarse:

```bash
npm start
```

si el proyecto tiene configurado el script correspondiente.

La aplicación estará disponible en:

```text
http://localhost:4200
```

---

# Flujo de ejecución

Para trabajar con el proyecto deben estar ejecutándose ambos servidores.

### Terminal 1 — Backend

```bash
cd backend
php -S localhost:8000 -t public
```

### Terminal 2 — Frontend

```bash
cd frontend
ng serve
```

Después abrir:

```text
http://localhost:4200
```

---

# Funcionalidades implementadas

## Autenticación

- Registro de usuarios
- Validación de datos
- Contraseñas almacenadas mediante `password_hash`
- Login
- JWT
- JWE
- Access Token
- Refresh Token
- Logout
- Revocación de tokens
- Middleware de autenticación

## Países y ciudades

- Consulta de países
- Consulta de ciudades por país
- Relación entre países, monedas y ciudades

## Información de viaje

- Consulta de clima
- Consulta de moneda
- Conversión de presupuesto
- Persistencia de tasas de cambio
- Fallback a la última tasa disponible cuando la API de conversión falla

## Historial

- Registro de consultas realizadas
- Historial asociado al usuario autenticado
- Consulta de las últimas consultas del usuario
- Separación del historial entre usuarios

## Manejo de errores

La API utiliza respuestas JSON estandarizadas con:

```text
success
data
error
trace_id
```

Los errores cuentan con códigos específicos, por ejemplo:

```text
BAD_REQUEST
AUTH_TOKEN_MISSING
AUTH_TOKEN_INVALID
AUTH_TOKEN_EXPIRED
AUTH_TOKEN_REVOKED
NOT_FOUND
USER_ALREADY_EXISTS
VALIDATION_ERROR
EXTERNAL_API_ERROR
EXTERNAL_API_TIMEOUT
INTERNAL_ERROR
```

---

# Principales endpoints

## Autenticación

### Registro

```http
POST /api/auth/register
```

### Login

```http
POST /api/auth/login
```

### Usuario autenticado

```http
GET /api/auth/me
```

### Logout

```http
POST /api/auth/logout
```

---

## Países y ciudades

### Obtener países

```http
GET /api/paises
```

### Obtener ciudades de un país

```http
GET /api/paises/{id}/ciudades
```

---

## Información de viaje

### Obtener datos de una ciudad

```http
POST /api/travel-data
```

Este endpoint requiere autenticación.

---

## Consultas

### Crear consulta

```http
POST /api/consultas
```

### Obtener historial

```http
GET /api/consultas/historial
```

Los endpoints protegidos requieren:

```http
Authorization: Bearer <access_token>
```

---

# Estructura del proyecto

```text
traveler-app/
│
├── backend/
│   ├── app/
│   │   ├── controllers/
│   │   ├── middleware/
│   │   ├── models/
│   │   ├── services/
│   │   ├── views/
│   │   └── Router.php
│   │
│   ├── config/
│   │   ├── config.php
│   │   ├── cors.php
│   │   ├── database.php
│   │   └── env.php
│   │
│   ├── database/
│   │   ├── migrations/
│   │   ├── seeders/
│   │   ├── migrate.php
│   │   ├── seed.php
│   │   └── sql/
│   │
│   ├── exceptions/
│   │   ├── ApiException.php
│   │   └── ExceptionHandler.php
│   │
│   ├── public/
│   │   └── index.php
│   │
│   ├── routes/
│   │   └── web.php
│   │
│   ├── storage/
│   │   └── logs/
│   │
│   ├── .env
│   ├── .env.example
│   ├── composer.json
│   └── composer.lock
│
├── frontend/
│   ├── src/
│   │   ├── app/
│   │   │   ├── components/
│   │   │   └── services/
│   │   └── styles.css
│   │
│   ├── angular.json
│   ├── package.json
│   └── package-lock.json
│
├── .gitignore
└── README.md
```

---

# CORS

El backend permite solicitudes provenientes del frontend local:

```text
http://localhost:4200
```

La configuración se encuentra en:

```text
backend/config/cors.php
```

Esto permite que Angular pueda comunicarse con la API PHP durante el desarrollo.

---

# Seguridad

El proyecto utiliza variables de entorno para información sensible.

El `.gitignore` excluye:

```text
.env
vendor/
node_modules/
```

Por lo tanto, después de clonar el proyecto es necesario crear el `.env` localmente.

No utilizar valores reales de producción dentro del repositorio.

---

# Flujo básico de la aplicación

```text
Usuario
   │
   ▼
Angular
   │
   ├── Registro
   │
   ├── Login
   │      │
   │      ▼
   │   Access Token
   │
   ▼
PHP MVC
   │
   ├── Autenticación
   ├── Países
   ├── Ciudades
   ├── Clima
   ├── Conversión de moneda
   └── Historial
          │
          ▼
      PostgreSQL
```

---

# Desarrollo

Para realizar cambios en el proyecto:

```bash
git pull origin main
```

Después de modificar el código:

```bash
git status
```

Revisar los archivos modificados antes de realizar el commit.

Crear un commit descriptivo:

```bash
git add .
git commit -m "Descripción del cambio"
```

Enviar los cambios:

```bash
git push origin main
```

Ejemplo:

```bash
git commit -m "Paso 13: registro de usuarios en frontend"
```

---

# Orden recomendado para levantar el proyecto desde cero

```text
1. Clonar repositorio
        ↓
2. Instalar dependencias PHP
        ↓
3. Crear .env
        ↓
4. Configurar PostgreSQL
        ↓
5. Ejecutar migraciones
        ↓
6. Ejecutar seeders
        ↓
7. Instalar dependencias Angular
        ↓
8. Levantar backend
        ↓
9. Levantar frontend
        ↓
10. Abrir http://localhost:4200
```

---

# Estado del proyecto

El proyecto se encuentra actualmente en desarrollo y cuenta con:

- Backend PHP MVC personalizado
- PostgreSQL
- Autenticación mediante JWT/JWE
- Registro de usuarios
- Gestión de países y ciudades
- Integración con APIs externas
- Consulta de clima
- Conversión de moneda
- Historial de consultas
- Frontend Angular
- Bootstrap 5
- Configuración CORS
- Manejo centralizado de errores
- Variables de entorno para información sensible

El desarrollo se organiza mediante commits correspondientes a cada paso de implementación.
