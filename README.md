# Proyecto-Final-DS4

Plataforma de gestión ambiental y forestal. Esta entrega implementa **solo el backend** del rol de Antony; frontend web y Android corresponden al compañero.

## Estructura conservada de CitasMedicas

```text
ProyectoFinal/
├── Business/
│   ├── public/                 index.php, .htaccess, openapi.json
│   ├── RestRequest/            ejemplos HTTP
│   ├── src/
│   │   ├── app/                App.php, config.php, routes.php
│   │   ├── controllers/        Auth, Usuario, Cliente, Servicio, Solicitud,
│   │   │                      Cotizacion, Proyecto, Pago, Visita, Evidencia, Reporte, Sistema
│   │   ├── Services/           DataService.php: Guzzle entre las dos APIs
│   │   └── Validators/         validación por entidad con Respect Validation
│   ├── tests/
│   ├── composer.json y composer.lock
│   ├── .env.example
│   └── Dockerfile
├── Data/
│   ├── public/                 index.php y .htaccess
│   ├── RestRequest/
│   ├── src/
│   │   ├── app/                App.php, config.php, conexion.php, eloquent.php, routes.php
│   │   ├── controllers/        controladores por entidad
│   │   ├── Models/             User, Cliente, Servicio, Solicitud, Cotizacion,
│   │   │                      Proyecto, Pago, Visita, Evidencia
│   │   ├── Repositories/       consultas específicas de cada módulo
│   │   ├── Services/           permisos y reglas de cotizaciones, proyectos, pagos y visitas
│   │   └── Validators/         defensa de la entrada de la API interna
│   ├── tests/                  semillas, sintaxis y pruebas unitarias
│   ├── composer.json y composer.lock
│   ├── .env.example
│   └── Dockerfile
├── db_dumb/scriptsv.sql         esquema, relaciones y catálogo inicial
├── mysql_data/                 persistencia local; no se versiona
├── db_password.txt             secreto local; no se versiona
├── configurar.ps1
├── docker-compose.yml
└── BACKEND.md                  decisiones, permisos, integración y modelo E-R
```

Se mantienen los puntos de entrada `public/index.php → src/app/App.php`, PHP-DI, archivos `config.php`/`routes.php`, Eloquent y los métodos `read`, `filter`, `create`, `update`, `delete` por entidad. Se conserva `DataService` para comunicar Business y Data. Los servicios y repositorios adicionales implementan las reglas del nuevo dominio; no hay un controlador general ni se fusionó Data con Business.

No se creó ni modificó `Presentation/` en este repositorio. La implementación anterior quedó respaldada localmente fuera del repositorio compartido; sus volúmenes de Docker se conservaron.

## Arranque desde un clon

Requisitos: Git y Docker Desktop con contenedores Linux. No necesita PHP ni Composer instalados en Windows. Ejecutar en PowerShell desde la raíz de **ProyectoFinal**, no desde la de CitasMedicas:

```powershell
.\configurar.ps1
docker compose up -d --build --wait
docker compose exec -T webdatos php tests/actualizar.php
docker compose exec -T webdatos php tests/seed.php
```

`configurar.ps1` crea claves nuevas en `Business/.env`, `Data/.env` y `db_password.txt`. No sobrescribe configuración existente y no copia los secretos de CitasMedicas. Ambas APIs reciben sus `.env` por volumen de solo lectura. SQL se inicializa automáticamente **solo cuando el directorio mysql_data está vacío**.

Los dos `composer.lock` fijan las dependencias. Las imágenes se construyen con PHP 8.5/Apache y Composer 2; MySQL usa la misma familia 9.7 de la base. No se utiliza una imagen local de CitasMedicas como dependencia.

| Servicio | Acceso local | Uso |
| --- | --- | --- |
| Business / `webnegocio` | `http://localhost:9091/api` | API que consume frontend/Android. |
| Data / `webdatos` | `http://127.0.0.1:8081/api` | API interna; requiere `X-Internal-Key` y, salvo autenticación/públicos internos, JWT. |
| MySQL / `db` | `127.0.0.1:3309` | Base `gestion_ambiental`, usuario `ambiental`. |
| Swagger UI | `http://localhost:9091/api/docs` | Explorar y ejecutar operaciones. Los recursos de Swagger UI se cargan desde CDN. |
| OpenAPI | `http://localhost:9091/api/openapi.json` | Contrato descargable e importable sin depender del CDN. |
| Salud | `http://localhost:9091/api/health` | Recorre Business → Data → MySQL. |

Los puertos son distintos de CitasMedicas para poder mantener ambos proyectos. Desde el emulador Android, Business es `http://10.0.2.2:9091/api`; en dispositivo físico se utiliza la IP local de la computadora. CORS permite localhost de Angular y los esquemas de Capacitor; se configura en `Business/.env`.

## Cuentas ficticias locales

`seed.php` crea las cuentas si faltan y exige `APP_ENV=local` en Data. La contraseña de demostración de las tres es `Forestal2026!`. Estas cuentas son exclusivamente para desarrollo.

| Usuario | Rol |
| --- | --- |
| `adminforestal` | Admin = 1 |
| `tecnicocampo` | Tecnico = 2 |
| `clientefinca` | Cliente = 3 |

La semilla vincula `clientefinca` a una finca ficticia. El SQL incluye tres servicios iniciales. La prueba de integración crea su propio servicio, usuarios, clientes, cotización, proyecto, pagos, visita y foto ficticios; deja esos registros disponibles para inspección y no borra datos anteriores.

## Comprobaciones reproducibles

```powershell
docker compose exec -T webdatos php tests/sintaxis.php
docker compose exec -T webnegocio php tests/sintaxis.php
docker compose exec -T webdatos php tests/unit.php
docker compose exec -T webnegocio php -d display_errors=1 tests/integracion.php
```

La prueba de integración debe ejecutarse después de las semillas y usa el administrador ficticio. Incluye sesión, roles, alcance por registro, CRUD, filtros, aprobación idempotente, transiciones, anticipo/final, anulación, visita y evidencia reintentables, descarga protegida y los cuatro reportes. Al iniciar/refrescar/cerrar la sesión de demostración invalida los tokens anteriores de esa misma cuenta.

### Resultado verificado el 22 de noviembre de 2026

| Comprobación | Resultado |
| --- | --- |
| Construcción desde los dos lockfiles y MySQL vacío | Correcta, en proyecto Compose aislado. |
| Sintaxis PHP | 53 archivos Data y 27 Business correctos. |
| Referencias de rutas a controladores/métodos públicos | 51 Data y 52 Business correctas. |
| Pruebas unitarias de servicios | 29 comprobaciones correctas. |
| Integración HTTP Business → Data → MySQL | 139 comprobaciones correctas. |
| Swagger Parser sobre OpenAPI 3.0.3 | 58 operaciones válidas. |
| Archivo `flujo.http`, ejecutado dos veces en Docker aislado | 81 solicitudes por recorrido; 162 respuestas correctas y cobertura de las 58 operaciones. |
| Salud del entorno principal después de las pruebas | Business, Data y MySQL responden `ok`. |

La verificación aislada utiliza `Business/tests/docker-compose.verificacion.yml`: volúmenes propios y sin puertos publicados. Puede repetirse conservando el entorno principal:

```powershell
docker compose -p ambiental-verificacion -f docker-compose.yml -f Business/tests/docker-compose.verificacion.yml up -d --build --wait
docker compose -p ambiental-verificacion -f docker-compose.yml -f Business/tests/docker-compose.verificacion.yml exec -T webdatos php tests/actualizar.php
docker compose -p ambiental-verificacion -f docker-compose.yml -f Business/tests/docker-compose.verificacion.yml exec -T webdatos php tests/seed.php
docker compose -p ambiental-verificacion -f docker-compose.yml -f Business/tests/docker-compose.verificacion.yml exec -T webdatos php tests/unit.php
docker compose -p ambiental-verificacion -f docker-compose.yml -f Business/tests/docker-compose.verificacion.yml exec -T webnegocio php -d display_errors=1 tests/integracion.php
docker compose -p ambiental-verificacion -f docker-compose.yml -f Business/tests/docker-compose.verificacion.yml down -v
```

El último comando elimina exclusivamente los volúmenes ficticios de **ambiental-verificacion**. Mantener ese nombre y ambos archivos al ejecutarlo.

Para detener sin borrar persistencia:

```powershell
docker compose down
```

No usar `down -v` para reiniciar pruebas: eliminaría el volumen de evidencias. La base de datos está en `mysql_data/`, que tampoco se debe borrar para una actualización ordinaria. Cambios futuros del esquema deben aplicarse mediante scripts de actualización, no eliminando la base existente.

## Trabajo en equipo

El compañero consume **Business** y el contrato OpenAPI; no debe poner la clave interna de Data en Angular/Android. Se preservan los campos de sesión de la base: `token`, `tkRef`, `expires_in`, `user` y los claims `username`, `rol_id`.

Antes de desarrollar una tarea: actualizar `main`, crear una rama propia y enviar el cambio a revisión mediante PR. Mantener las credenciales y los datos físicos fuera de Git; cada integrante genera su propia configuración local. La base del backend se versiona en `backend/h0-base` para integrarla en `main` y permitir que ambos roles trabajen desde el mismo punto de partida.

## Estado de integración

Este backend no sustituye F01–F09. Falta conectar la SPA y la app móvil, probar la captura en modo avión y su cola persistente, completar los manuales/video y desplegar en Cloud con HTTPS. Los endpoints de fotos/GPS/sincronización están preparados; no se afirma que la captura Android ya exista. El requisito final de contenedor web se completa cuando el compañero integre `Presentation/`, añadiendo `webpresentacion` a los tres servicios backend actuales.
