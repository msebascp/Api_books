# Api_books

API REST construida con **Laravel 13** y **PHP 8.4** para la catalogación de libros, gestión de biblioteca personal, diario de lecturas y relecturas, listas personalizadas y comunidad lectora (al estilo de **Letterboxd** y **Trakt TV**), diseñada para alimentar clientes **Android** y **Web**.

---

## Stack tecnológico

| Componente | Versión / Especificación |
| :--- | :--- |
| **PHP** | `^8.4` (`declare(strict_types=1);` en todo el código de dominio) |
| **Framework** | `laravel/framework` `^13.0` |
| **Autenticación API** | `laravel/sanctum` `^4.3` (Bearer Tokens) |
| **Roles y Permisos** | `spatie/laravel-permission` `^8.5` |
| **Base de datos** | MariaDB / MySQL (`InnoDB`, charset `utf8mb4`, colación `utf8mb4_unicode_ci`) |
| **Testing** | `phpunit/phpunit` `^13.4` (SQLite `:memory:` aislado) |
| **Estilo de código** | `laravel/pint` `^1.32` |

---

## Funcionalidades principales

- **Autenticación y control de acceso por roles:**
  - Registro, inicio de sesión unificado por `email` o `username`, perfil autenticado (`/api/me`) y cierre de sesión por token mediante **Laravel Sanctum**.
  - Autorización basada en roles con **Spatie Permission** (`user`, `admin`) y `IsAdminMiddleware` con soporte para roles configurables (`middleware('admin')` o `middleware('admin:admin,editor')`), diferenciando respuestas `401 Unauthorized` y `403 Forbidden`.
- **Catálogo editorial con archivado seguro (`SoftDeletes`):**
  - Gestión de libros, autores y géneros con búsqueda por título/ISBN (normalizado sin guiones ni espacios), filtrado por autor/género y ordenación (`latest`, `avg_rating`, `title`, `publication_date`).
  - Los libros utilizan `SoftDeletes` (`deleted_at` + flag `is_archived` en `BookResource`) y endpoint de restauración para administradores (`POST /api/books/{book}/restore`), preservando intactos el historial, reseñas y listas de los lectores cuando un libro se descataloga.
- **Biblioteca personal (`library_entries`) y puntuaciones desacopladas:**
  - Estados de lectura respaldados por el Enum `ReadingStatus` (`to_read`, `reading`, `read`, `dnf`), indicadores `is_owned` e `is_favorite`, y filtro `?has_been_read=1` (`read_count > 0`) para recuperar libros ya leídos aunque su estado actual sea `to_read`.
  - Endpoint dedicado `PUT /api/books/{book}/rating` con escala de medias estrellas (`0.5` a `5.0`, `multiple_of:0.5`). Enviar `0` o `null` elimina la puntuación sin borrar la entrada ni las reseñas escritas; puntuar un libro aún no leído (`read_count === 0`) registra automáticamente su lectura con fecha de hoy.
- **Diario de lecturas y relecturas (`reading_logs`):**
  - Registro cronológico de cada sesión de lectura (`started_at`, `finished_at`, `is_reread`) filtrable por año (`?year=YYYY`) y libro (`?book_id=X`).
  - Detección automática de relecturas (`is_reread = true` a partir de la segunda lectura) y sincronización mediante `ReadingLogObserver` de `read_count` y `last_read_at` en `library_entries`.
- **Reacciones con emojis estilo Trakt TV (`book_reactions`):**
  - Hasta 3 reacciones distintas por usuario y libro sincronizadas de forma atómica e idempotente (`PUT /api/books/{book}/reactions`) sobre el Enum `ReactionType`: `mind_blown` (🤯), `thumbs_up` (👍), `laugh` (🤣), `heart` (❤️), `fire` (🔥) y `cry` (😭).
  - `BookResource` expone el conteo `total`, el `top_reactions` (top 3 global) y `user_reactions` del usuario autenticado.
- **Comunidad, reseñas escritas (`reviews`), comentarios y seguimiento (`follows`):**
  - Reseñas de texto obligatorio (`content NOT NULL`) separadas de la calificación con estrellas, con indicador `has_spoilers`, subconsulta escalar `rating` del autor de la reseña, likes (`review_likes`) y comentarios (`comments`).
  - Grafo social de seguidores/seguidos (`follows`) y flags de estado para la interfaz (`viewer_rating`, `liked_by_viewer`, `followed_by_viewer`) resueltos también en rutas públicas mediante el guard de Sanctum.
- **Listas personalizadas de libros (`book_lists` y `book_list_items`):**
  - Listas públicas o privadas, numerables (`is_ranked`), con generación automática de `slug` único por usuario.
  - Reordenación manual atómica (`PUT /api/lists/{bookList}/books/reorder`), recompactación automática `1..N` al retirar libros (incluidos libros archivados) y 5 modos de ordenación en lectura: `custom`, `added_desc`, `added_asc`, `rating_desc` y `rating_asc` (dejando los libros sin votos al final).

---

## Diseño de base de datos y arquitectura

### Tablas de dominio (14 tablas)

| Tabla | Propósito y claves principales |
| :--- | :--- |
| `users` | Usuarios registrados (`UNIQUE username` máx. 50, `UNIQUE email`). |
| `authors` | Autores del catálogo (`INDEX name`). |
| `genres` | Géneros literarios (`UNIQUE name`, `UNIQUE slug`). |
| `books` | Catálogo de obras (`UNIQUE isbn` normalizado máx. 20, `avg_rating`, `ratings_count`, `deleted_at` con `SoftDeletes`). |
| `author_book` | Pivote N:M entre autores y libros (`PRIMARY KEY (author_id, book_id)`). |
| `book_genre` | Pivote N:M entre libros y géneros (`PRIMARY KEY (book_id, genre_id)`). |
| `library_entries` | Estado actual del libro en la biblioteca del usuario (`UNIQUE (user_id, book_id)`, `status`, `rating` con `CHECK 0.5..5.0`, `read_count`, `last_read_at`). |
| `reading_logs` | Historial de sesiones de lectura y relecturas (`started_at`, `finished_at`, `is_reread`, `INDEX (user_id, finished_at, id)`). |
| `book_reactions` | Reacciones con emojis (`PRIMARY KEY (user_id, book_id, reaction)`, `INDEX (book_id, reaction)`). |
| `reviews` | Reseñas escritas (`UNIQUE (user_id, book_id)`, `content NOT NULL`, `has_spoilers`). |
| `review_likes` | Likes sobre reseñas (`PRIMARY KEY (user_id, review_id)`). |
| `comments` | Comentarios en reseñas (`INDEX (review_id, created_at)`). |
| `follows` | Relaciones de seguimiento entre usuarios (`PRIMARY KEY (follower_id, following_id)`). |
| `book_lists` / `book_list_items` | Listas personalizadas (`UNIQUE (user_id, slug)`) y sus libros ordenados (`PRIMARY KEY (book_list_id, book_id)`, `INDEX (book_list_id, position)`). |

### Garantías técnicas

- **Compatibilidad `utf8mb4_unicode_ci`:** En `book_reactions` se persiste el slug ASCII del `ReactionType` (`mind_blown`, `fire`, etc.) en lugar del carácter emoji para evitar colisiones de pesos Unicode (UCA 4.0.0) en la clave primaria compuesta.
- **Consistencia desnormalizada mediante Observers:**
  - `LibraryEntryObserver`: recalcula `books.avg_rating` y `books.ratings_count` directamente sobre `library_entries WHERE rating IS NOT NULL` (protegiendo ambas columnas fuera de `Book::$fillable`).
  - `ReadingLogObserver`: sincroniza `library_entries.read_count` (`COUNT(*)`) y `last_read_at` (`MAX(finished_at)`) al crear, editar o borrar registros de lectura.
  - `UserObserver`: antes de eliminar un usuario, recopila los libros que había puntuado, revoca sus tokens de Sanctum y, tras el `ON DELETE CASCADE`, recalcula las medias de los libros afectados.
- **Prevención de consultas N+1:** Precarga sistemática mediante `Book::reactionEagerLoads()` (`reactionCounts` agrupado + `viewerReactions` + `viewerLibraryEntry`), `withExists()` para `liked_by_viewer` y `followed_by_viewer`, y `scopeWithReviewerRating()` mediante subconsulta escalar en `Review`.
- **Privacidad por diseño:** `UserResource` oculta `email` en todos los endpoints públicos y listados sociales, exponiéndolo únicamente cuando el usuario autenticado por Sanctum consulta su propio perfil o `/api/me`.

---

## Instalación y configuración local

1. **Clonar el repositorio e instalar dependencias:**
   ```bash
   git clone https://github.com/msebascp/Api_books.git
   cd Api_books
   composer install
   ```

2. **Configurar variables de entorno:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Asegúrate de configurar la conexión a MariaDB/MySQL en `.env`:
   ```dotenv
   DB_CONNECTION=mariadb
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=api_books
   DB_USERNAME=root
   DB_PASSWORD=
   DB_CHARSET=utf8mb4
   DB_COLLATION=utf8mb4_unicode_ci
   ```

3. **Ejecutar migraciones y seeders:**
   ```bash
   php artisan migrate --seed
   ```

4. **Iniciar el servidor de desarrollo:**
   ```bash
   php artisan serve
   ```

---

## Referencia de Endpoints

Todas las rutas están prefijadas bajo `/api`. En las rutas con acceso **Público**, si el cliente envía un encabezado `Authorization: Bearer <token>` válido, la API resuelve igualmente los campos contextuales del usuario (`viewer_rating`, `user_reactions`, `liked_by_viewer`, `followed_by_viewer`).

### 1. Autenticación

| Método | Ruta | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/register` | Público | Registra un usuario (rol `user`) y devuelve su token Sanctum. |
| `POST` | `/api/login` | Público | Inicia sesión con `login` (`email` o `username`) y `password`. |
| `POST` | `/api/logout` | Auth | Revoca el token de acceso utilizado en la petición actual. |
| `GET` | `/api/me` | Auth | Devuelve el perfil del usuario autenticado con sus contadores. |

### 2. Catálogo (Libros, Autores y Géneros)

| Método | Ruta | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/books` | Público | Listado paginado (`?search=`, `?genre=`, `?author=`, `?sort=latest\|avg_rating\|title\|publication_date`). |
| `GET` | `/api/books/{book}` | Público | Detalle de un libro con autores, géneros, reacciones y `viewer_rating`. |
| `POST` | `/api/books` | Admin | Crea un libro y sincroniza sus autores y géneros. |
| `PUT` | `/api/books/{book}` | Admin | Actualiza los datos y relaciones de un libro. |
| `DELETE` | `/api/books/{book}` | Admin | Archiva un libro mediante `SoftDeletes`. |
| `POST` | `/api/books/{book}/restore` | Admin | Restaura un libro previamente archivado. |
| `GET` | `/api/authors` | Público | Listado paginado de autores (`?search=`). |
| `GET` | `/api/authors/{author}` | Público | Detalle de un autor con sus libros. |
| `POST` | `/api/authors` | Admin | Crea un autor. |
| `PUT` | `/api/authors/{author}` | Admin | Actualiza un autor. |
| `DELETE` | `/api/authors/{author}` | Admin | Elimina un autor. |
| `GET` | `/api/genres` | Público | Listado paginado de géneros (`?search=`). |
| `GET` | `/api/genres/{genre}` | Público | Detalle de un género con sus libros. |
| `POST` | `/api/genres` | Admin | Crea un género (autocalcula `slug` si no se envía). |
| `PUT` | `/api/genres/{genre}` | Admin | Actualiza un género. |
| `DELETE` | `/api/genres/{genre}` | Admin | Elimina un género. |

### 3. Biblioteca Personal y Puntuaciones

| Método | Ruta | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/library-entries` | Auth | Biblioteca del usuario (`?status=`, `?is_owned=`, `?is_favorite=`, `?has_been_read=`). |
| `POST` | `/api/library-entries` | Auth | Añade o actualiza un libro en la biblioteca personal (`book_id`, `status`, `is_owned`, `is_favorite`). |
| `DELETE` | `/api/library-entries/{book}` | Auth | Elimina un libro de la biblioteca personal. |
| `PUT` | `/api/books/{book}/rating` | Auth | Puntúa un libro (`0.5`..`5.0` en pasos de `0.5`) o borra la nota (`0` / `null`). |

### 4. Historial de Lecturas y Relecturas

| Método | Ruta | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/reading-logs` | Auth | Historial paginado del usuario autenticado (`?year=YYYY`, `?book_id=X`). |
| `GET` | `/api/users/{username}/reading-logs` | Público | Historial paginado público de un usuario (`?year=YYYY`, `?book_id=X`). |
| `POST` | `/api/books/{book}/reading-logs` | Auth | Registra una lectura o relectura (`started_at`, `finished_at`, `is_reread`) y marca `status = 'read'`. |
| `PUT` | `/api/reading-logs/{readingLog}` | Auth | Edita las fechas o flag `is_reread` de un registro propio. |
| `DELETE` | `/api/reading-logs/{readingLog}` | Auth | Elimina un registro de lectura propio y recalcula `read_count` y `last_read_at`. |

### 5. Reacciones con Emojis

| Método | Ruta | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `PUT` | `/api/books/{book}/reactions` | Auth | Reemplaza atómicamente las reacciones del usuario en el libro (`reactions: []`, máx. 3 valores distintos de `ReactionType`). |

### 6. Reseñas y Comentarios

| Método | Ruta | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/reviews` | Público | Feed global paginado de reseñas escritas. |
| `GET` | `/api/reviews/{review}` | Público | Detalle de una reseña con sus comentarios. |
| `GET` | `/api/books/{book}/reviews` | Público | Reseñas paginadas de un libro concreto. |
| `POST` | `/api/books/{book}/reviews` | Auth | Publica una reseña escrita sobre un libro (`content`, `has_spoilers`). |
| `PUT` | `/api/reviews/{review}` | Auth | Actualiza una reseña propia. |
| `DELETE` | `/api/reviews/{review}` | Auth | Elimina una reseña propia. |
| `POST` | `/api/reviews/{review}/like` | Auth | Alterna (toggle) el like del usuario sobre una reseña. |
| `GET` | `/api/reviews/{review}/comments` | Público | Comentarios paginados de una reseña en orden cronológico. |
| `POST` | `/api/reviews/{review}/comments` | Auth | Publica un comentario en una reseña (`content` máx. 2000 caracteres). |
| `DELETE` | `/api/comments/{comment}` | Auth | Elimina un comentario propio. |

### 7. Usuarios y Social

| Método | Ruta | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/users/{username}` | Público | Perfil público de un usuario por `username` o `id`. |
| `GET` | `/api/users/{user}/followers` | Público | Listado paginado de seguidores del usuario. |
| `GET` | `/api/users/{user}/following` | Público | Listado paginado de usuarios seguidos. |
| `POST` | `/api/users/{user}/follow` | Auth | Alterna (toggle) el seguimiento hacia otro usuario. |

### 8. Listas Personalizadas

| Método | Ruta | Acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/users/{username}/lists` | Público | Listas públicas paginadas de un usuario. |
| `GET` | `/api/lists/{bookList}` | Público* | Detalle de una lista (*requiere ser el dueño si `is_public = false`). |
| `GET` | `/api/lists/{bookList}/books` | Público* | Libros paginados de la lista (`?sort=custom\|added_desc\|added_asc\|rating_desc\|rating_asc`). |
| `GET` | `/api/lists` | Auth | Todas las listas (públicas y privadas) del usuario autenticado. |
| `POST` | `/api/lists` | Auth | Crea una lista (`title`, `description`, `is_public`, `is_ranked`). |
| `PUT` | `/api/lists/{bookList}` | Auth | Actualiza una lista propia. |
| `DELETE` | `/api/lists/{bookList}` | Auth | Elimina una lista propia. |
| `POST` | `/api/lists/{bookList}/books` | Auth | Añade un libro activo al final de una lista propia (`book_id`). |
| `PUT` | `/api/lists/{bookList}/books/reorder` | Auth | Reordena atómicamente los libros de la lista (`book_ids`). |
| `DELETE` | `/api/lists/{bookList}/books/{book}` | Auth | Quita un libro de la lista y recompacta las posiciones `1..N`. |

---

## Tests y calidad de código

La suite de tests se ejecuta íntegramente sobre **SQLite en memoria** (configurada en `phpunit.xml`), por lo que no altera los datos de la base de datos local en MariaDB:

```bash
php artisan test
```

Para verificar o aplicar el estándar de estilo de código con **Laravel Pint**:

```bash
./vendor/bin/pint --test
./vendor/bin/pint
```
