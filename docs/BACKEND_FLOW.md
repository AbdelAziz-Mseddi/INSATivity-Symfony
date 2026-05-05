# Backend Flow Documentation

This document describes the current PHP backend flow, request lifecycle, storage model, and response contracts used by the frontend.

## 1. Backend Structure

Main backend files:

- `backend/events.php`: Event API router/controller.
- `backend/EventManager.php`: Event domain logic backed by Supabase Postgres.
- `backend/clubs.php`: Club API router/controller.
- `backend/ClubManager.php`: Club domain logic backed by Supabase Postgres.
- `backend/media.php`: Media upload/delete router.
- `backend/MediaManager.php`: Upload validation + filesystem operations.
- `backend/register.php`: Form POST endpoint for registration.
- `backend/login.php`: Form POST endpoint for login.
- `backend/Database.php`: Shared PDO connection + `.env` loading.

## 2. Data and Persistence

Current storage model:

- Clubs are stored in `public.clubs` (Supabase Postgres).
- Events are stored in `public.events` (Supabase Postgres).
- Users are stored in `public.users` (Supabase Postgres).
- Uploaded media files are stored on disk in `assets/uploads/`.

Important:

- Events and clubs are no longer persisted in JSON files.
- Event `status` is computed dynamically in `EventManager` from date/time and not stored in DB.

## 3. Database Connection Flow (`backend/Database.php`)

1. Load `.env` through `vlucas/phpdotenv`.
2. Validate required env vars (`DATABASE_HOST`, `DATABASE_USER`, `DATABASE_PASSWORD`).
3. Build PDO DSN: `pgsql:host=...;port=...;dbname=...;sslmode=...`.
4. Create PDO connection with:
   - exceptions enabled
   - associative fetch mode
   - emulated prepares disabled
5. Throw exception if PDO `pgsql` driver is missing or connection fails.

## 4. Common JSON Response Shapes

### 4.1 Events API (`events.php`)

```json
{
  "status": "success|error",
  "message": "",
  "data": null,
  "errors": []
}
```

### 4.2 Clubs and Media APIs (`clubs.php`, `media.php`)

```json
{
  "status": "success|error",
  "data": null,
  "errors": []
}
```

Error behavior:

- Exceptions are caught and returned as HTTP 400 with `status: "error"`.
- Validation/routing messages are exposed via `errors[0]`.

## 5. Events API Flow (`backend/events.php`)

### 5.1 Request lifecycle

1. Set JSON response header.
2. Read HTTP method and query `action`.
3. Validate that `action` exists.
4. Instantiate `EventManager`.
5. Parse JSON body for write methods (`POST`, `PUT`, `PATCH`).
6. Dispatch to matching `EventManager` method.
7. Return structured JSON response.

### 5.2 Supported actions

`GET`:

- `action=get&id=<eventId>`
- `action=getAll`
- `action=getByClub&club=<clubName>`
- `action=getByClubAndStatus&club=<clubName>&status=<published|finished>`
- `action=getByStatus&status=<published|finished>`
- `action=getFeatured`

`POST`:

- `action=create`

`PUT`/`PATCH`:

- `action=update&id=<eventId>`

`DELETE`:

- `action=delete&id=<eventId>`

### 5.3 EventManager behavior (`backend/EventManager.php`)

Read flow:

- Joins `public.events` with `public.clubs` to expose club display data.
- Maps DB columns to frontend fields (`clubLogo`, `maxParticipants`, etc.).
- Computes `status` dynamically:
  - future datetime -> `published`
  - past datetime -> `finished`

Write flow:

- `createEvent(payload)` validates required fields and resolves `club` (name) -> `club_id`.
- `updateEvent(id, payload)` merges payload with existing event, protects `id/status/clubLogo`.
- `deleteEvent(id)` removes by ID and throws if not found.

## 6. Clubs API Flow (`backend/clubs.php`)

### 6.1 Supported operations

`GET` actions:

- `action=get&id=<clubId>`
- `action=getAll`
- `action=getByCategory&category=<category>`
- `action=getCategories`

Write methods:

- `POST` (no action required) -> create club
- `PUT/PATCH&id=<clubId>` -> update club
- `DELETE&id=<clubId>` -> delete club

### 6.2 ClubManager behavior (`backend/ClubManager.php`)

- Reads from `public.clubs`.
- Returns mapped fields: `id`, `name`, `category`, `logo`, `banner`, `description`.
- Handles duplicate conflicts on create/update (Postgres code `23505`).
- Uses `updated_at = NOW()` on updates.

## 7. Media Flow (`backend/media.php` + `MediaManager.php`)

### 7.1 Upload

Request:

```http
POST /backend/media.php?action=upload&prefix=<clubId>
Content-Type: multipart/form-data
```

Runtime:

1. Validate uploaded file exists and has no upload error.
2. Enforce max size = 5 MB.
3. Validate MIME type (jpeg/png/gif/webp).
4. Build unique filename: `<prefix>_<timestamp>_<random>.ext`.
5. Move file into `assets/uploads/`.
6. Return relative public path.

Success response:

```json
{
  "status": "success",
  "data": {
    "path": "../assets/uploads/cine_radio_1743070000_ab12cd34.webp"
  },
  "errors": []
}
```

### 7.2 Delete

Request:

```http
DELETE /backend/media.php?action=delete&filePath=assets/uploads/file.webp
```

Safety checks:

- Removes `..` and backslashes.
- Validates resolved path is inside uploads directory.
- Deletes only if file exists and guard passes.

## 8. Form Endpoints (`register.php`, `login.php`)

These endpoints process HTML form POST requests, not JSON APIs.

`register.php`:

- Validates all registration fields.
- Builds full email as `<local_part>@insat.ucar.tn`.
- Calls `AuthManager::registerUser`.
- Starts session and stores `$_SESSION['user']`.
- Returns HTML success or HTML error block.

`login.php`:

- Validates identifier/password presence.
- Calls `AuthManager::loginUser`.
- Starts session and stores `$_SESSION['user']`.
- Redirects to `../pages/index.html` on success.
- Returns HTML error block with HTTP 401 on auth failure.

## 9. End-to-End Example (Club Dashboard Event Creation)

1. Frontend uploads image to `POST /backend/media.php?action=upload&prefix=<clubId>`.
2. Backend validates and stores file in `assets/uploads/`.
3. Frontend sends event payload to `POST /backend/events.php?action=create`.
4. Backend inserts into `public.events` using resolved `club_id`.
5. Frontend refreshes via `GET /backend/events.php?action=getAll`.

## 10. Operational Notes

- No authorization layer currently protects write APIs.
- `events.php` requires `action` for all methods.
- `clubs.php` requires `action` for GET only; write methods can work without it.
- Client code should always handle `errors[0]` safely.
