# Pleat API

API de Pleat, una cadena de dumplings y bao. Está hecha con Laravel 13, PHP 8.5, Postgres, Sanctum (tokens Bearer) y Pest para las pruebas.

Este repo es de capacitación: el módulo **Categorías** está escrito para que lo leas y lo copies al construir productos, pedidos y lo demás.

## Requisitos

- Docker (Docker Desktop o equivalente). No necesitas PHP ni Postgres instalados en tu máquina: todo corre en contenedores con Laravel Sail.

## Cómo levantarlo

Primera vez, antes de que exista la carpeta `vendor/` (instala las dependencias dentro de un contenedor):

```sh
cp .env.example .env
docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html composer:latest composer install --ignore-platform-reqs
```

Luego, con Sail (usa `./vendor/bin/sail` hasta que definas el alias, más abajo):

```sh
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail test
```

La primera vez Postgres tarda unos segundos en estar listo; `sail up -d` espera a que responda antes de arrancar la app. Si `migrate` falla por conexión, espera un momento y repítelo.

Para volver a empezar con la base limpia: `./vendor/bin/sail artisan migrate:fresh --seed`.

Si ya tenías un `.env` de antes, revisa que tenga `APP_LOCALE=es` y `APP_FALLBACK_LOCALE=en`: con eso los errores de validación salen en español.

### El alias `sail`

Escribir `./vendor/bin/sail` cada vez cansa. Agrega esto a tu `~/.zshrc` (o `~/.bashrc`) y abre una terminal nueva:

```sh
alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
```

Desde ahí, `sail up -d`, `sail artisan ...`, `sail test`, etc. El resto de este README usa el alias.

## Puertos

| Qué | Puerto en tu máquina | Dónde se define |
| --- | --- | --- |
| API | `8091` (http://localhost:8091) | `APP_PORT` en `.env` |
| Postgres | `54331` | `FORWARD_DB_PORT` en `.env` |

## Postgres con psql

Abre la consola de Postgres:

```sh
sail psql
```

Dentro de `psql` escribe SQL. Por ejemplo:

```sql
\dt
\d categories
SELECT * FROM categories;
```

`\dt` lista las tablas, `\d categories` muestra las columnas. Para salir: `\q`.

Si prefieres un cliente gráfico, conéctate a `localhost:54331` con usuario `sail` y la contraseña de `DB_PASSWORD` en tu `.env`.

Las pruebas usan otra base, `testing`, para no tocar tus datos de desarrollo. Sail la crea la primera vez que se crea el volumen de Postgres. Si `sail test` dice que no existe, créala a mano: entra con `sail psql` y ejecuta

```sql
CREATE DATABASE testing;
```

## Usuarios de prueba

**Solo para desarrollo.** Los crea `sail artisan migrate --seed` y todos tienen la contraseña `password`. Nunca los uses fuera de tu máquina. El seeder se puede correr más de una vez sin duplicar usuarios.

| Nombre | Correo | Rol |
| --- | --- | --- |
| Mei Lin Zhou | mei@pleat.app | admin |
| Tomás Rivera | tomas@pleat.app | staff |
| Aisha Bello | aisha@pleat.app | staff |
| Daniel Kwon | daniel@pleat.app | staff |
| Priya Raghavan | priya@correo.com | customer |

Quién puede qué en Categorías: cualquier usuario autenticado las ve; solo `admin` crea, edita y borra.

## Reglas del proyecto

- **Autorización:** si la acción tiene FormRequest, se autoriza en su `authorize()`; si no, se usa `Gate::authorize()` en el controlador. En los dos casos la regla vive en la policy.
- **Respuestas:** siempre `{"data": ...}` (por los Resources). Register y login agregan además `"token"` al mismo nivel que `data`. Las listas traen `data`, `links` y `meta`.
- **Correos:** se guardan y se buscan en minúsculas (en Postgres `=` distingue mayúsculas).
- **Límite de intentos:** register y login aceptan 5 intentos por minuto por IP; el sexto responde 429.
- **Tokens:** expiran a los 30 días.

## Módulo Categorías: mapa de lectura

Léelo en este orden, de la base de datos hacia la respuesta HTTP:

1. `database/migrations/2026_10_04_130100_create_categories_table.php`: la tabla (nombre único sin importar mayúsculas, posición, activa).
2. `app/Models/Category.php`: el modelo, qué campos se pueden llenar en masa y sus tipos.
3. `database/factories/CategoryFactory.php` y `database/seeders/CategorySeeder.php`: datos falsos para pruebas y las 5 categorías reales.
4. `routes/api.php`: qué URL llama a qué método. `apiResource` genera las cinco rutas de un CRUD; todas van detrás de `auth:sanctum`.
5. `app/Http/Controllers/Api/CategoryController.php`: la lógica de cada ruta. Aquí están la paginación, la búsqueda con `ilike` y el orden.
6. `app/Http/Requests/IndexCategoryRequest.php`, `StoreCategoryRequest.php` y `UpdateCategoryRequest.php`: validan lo que llega y preguntan a la policy si el usuario puede hacer la acción. Los mensajes en español están en `lang/es/validation.php`.
7. `app/Policies/CategoryPolicy.php`: las reglas de quién puede qué (solo admin escribe).
8. `app/Http/Resources/CategoryResource.php`: define el JSON que sale, así no se filtra nada por accidente.
9. `tests/Feature/CategoryTest.php`: lo mismo visto desde afuera, como ejemplos de uso. `tests/Feature/AuthTest.php` hace lo propio con el login.

La autenticación (registro, login, logout, `/me`) vive en `app/Http/Controllers/Api/AuthController.php`.

Para crear un módulo nuevo (por ejemplo, productos) copia esos mismos pasos: migración, modelo, factory, requests, policy, resource, controlador, rutas y pruebas.

## Probar un endpoint a mano con curl

1. Inicia sesión y copia el valor de `token` de la respuesta (viene al lado de `data`):

```sh
curl -s -X POST http://localhost:8091/api/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email": "mei@pleat.app", "password": "password"}'
```

2. Pega el token en una variable de tu terminal:

```sh
TOKEN="pega-aqui-el-token"
```

3. Lista las categorías:

```sh
curl -s http://localhost:8091/api/categories \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
```

Con búsqueda y paginación: `.../api/categories?search=bao&per_page=10` (`per_page` de 1 a 50).

Siempre manda `Accept: application/json`; sin ese encabezado Laravel puede responder con HTML en vez de JSON.

## Flujo de ramas

1. Parte siempre de `dev` actualizada: `git checkout dev && git pull`.
2. Crea una rama para tu cambio: `git checkout -b feature/productos`.
3. Haz commits pequeños y corre `sail test` antes de subir.
4. Abre un Pull Request hacia `dev` (no hacia otra rama).
5. Espera la revisión, atiende los comentarios y haz merge solo cuando esté aprobado.

Nadie hace push directo a `dev`.
