# Plataforma de Eventos — API REST (Back End I)

API REST para una plataforma donde **organizadores** crean eventos por categoría
(conciertos, talleres, meetups, conferencias, jornadas deportivas) y los **asistentes**
los descubren, se inscriben, reciben recordatorios y los valoran.

Hecha con **Laravel 12 + Sanctum**. Usa controladores API con validación, **API Resources**,
**local scopes** (SearchTrait), **Services**, **Mail**, **Notifications**, un **Command**
programado, **Policies**, **Docker** y tests con **PHPUnit**.

## Roles
- **Visitante**: consulta el catálogo público y el detalle de eventos.
- **Usuario registrado (asistente)**: se autentica, se inscribe, ve sus tickets y valora eventos.
- **Organizador**: rol que cualquier usuario puede activar; crea/edita/cancela sus eventos,
  gestiona inscritos y hace check-in.

## Puesta en marcha (rápida, local con SQLite)
```bash
composer install
cp .env.example .env        # ya viene configurado a sqlite + mail=log
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve            # http://127.0.0.1:8000
```

## Puesta en marcha con Docker
El `docker-compose.yml` levanta **app (PHP-FPM) + nginx + MySQL 8.4 + Redis**.
```bash
docker compose up -d --build
# Cambia en .env la conexión a MySQL:
#   DB_CONNECTION=mysql
#   DB_HOST=mysql
#   DB_PORT=3306
#   DB_DATABASE=eventos_api
#   DB_USERNAME=laravel_user
#   DB_PASSWORD=laravel_password
docker compose exec app php artisan migrate:fresh --seed
# La API queda en http://localhost:8180
```

## Usuarios de demo (seeder)
| Email | Password | Rol |
|---|---|---|
| organizador@eventos.test | password | Organizador |
| asistente1@eventos.test | password | Asistente |
| asistente2@eventos.test | password | Asistente |

## Endpoints

### Públicos (visitante)
| Método | Ruta | Descripción |
|---|---|---|
| POST | `/api/register` | Registro |
| POST | `/api/login` | Login |
| GET | `/api/categories` | Lista de categorías |
| GET | `/api/events` | Catálogo + filtros (`titulo`, `categoria`, `ciudad`, `fecha`, `estado`) |
| GET | `/api/events/{id}` | Detalle de un evento |
| GET | `/api/events/{id}/reviews` | Reseñas de un evento |

### Protegidos (token Sanctum: `Authorization: Bearer ...`)
| Método | Ruta | Descripción |
|---|---|---|
| POST | `/api/logout` | Cerrar sesión |
| GET | `/api/me` | Perfil |
| POST | `/api/me/organizer` | Activar rol organizador |
| POST | `/api/events` | Crear evento (organizador) |
| PUT | `/api/events/{id}` | Editar evento propio |
| DELETE | `/api/events/{id}` | Eliminar evento propio |
| POST | `/api/events/{id}/cancel` | Cancelar evento (avisa a inscritos) |
| POST | `/api/events/{id}/cover` | Subir imagen de portada |
| GET | `/api/events/{id}/attendees` | Lista de inscritos (organizador) |
| POST | `/api/events/{id}/check-in` | Check-in por `ticket_code` (organizador) |
| GET | `/api/my-tickets` | Mis inscripciones / tickets |
| POST | `/api/events/{id}/register` | Inscribirse (genera ticket + email) |
| DELETE | `/api/events/{id}/register` | Cancelar inscripción |
| POST | `/api/events/{id}/reviews` | Valorar (sólo si asististe) |

### Filtros del catálogo (local scopes)
`GET /api/events?categoria=2&ciudad=Madrid&estado=proximos`
Estados: `proximos`, `pasados`, `agotados`, `disponibles`.

## Estructura JSON unificada
Todas las respuestas (éxito y error) tienen la misma forma:
```json
{ "success": true, "message": "...", "data": { ... }, "errors": null }
```

## Recordatorios automáticos (24h antes)
Comando programado cada hora en `routes/console.php`:
```bash
php artisan app:send-event-reminders   # ejecución manual
php artisan schedule:run               # lo lanza el cron en producción
```

## Tests
```bash
php artisan test
```
