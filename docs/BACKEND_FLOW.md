# Backend Flow Documentation

This document explains how the PHP backend is structured, how requests flow through each endpoint, how data is persisted, and what JSON contracts the frontend depends on.

## 1. Backend Structure

Main backend files:

- `backend/events.php`: HTTP router/controller for event APIs.
- `backend/EventManager.php`: Event domain logic + JSON file persistence.
- `backend/clubs.php`: HTTP router/controller for club APIs.
- `backend/ClubManager.php`: Club domain logic + JSON file persistence.
- `backend/media.php`: HTTP router/controller for media upload/delete.
- `backend/MediaManager.php`: File validation + filesystem writes/deletes.
- `backend/register.php`: Form POST validation endpoint for registration page.
- `backend/login.php`: Form POST validation endpoint for login page.

Data storage:

- Clubs are stored in `data/clubs.json`.
- Events are split by club in `data/<club_id>_events.json`.
- Uploaded images are stored in `assets/uploads/`.

## 2. Common API Response Shape

Most JSON endpoints return:

```json
{
  "status": "success|error",
  "data": null,
  "errors": []
}
```

`events.php` also includes a `message` field for some operations:

```json
{
  "status": "success|error",
  "message": "...",
  "data": null,
  "errors": []
}
```

Error behavior:

- Validation or routing errors throw exceptions.
- Catch blocks return HTTP 400 with `status: "error"` and `errors` populated.

## 3. Events API Flow (`backend/events.php`)

### 3.1 Request lifecycle

1. Set response header to JSON.
2. Read HTTP method and query `action`.
3. Validate required action/method combination.
4. Instantiate `EventManager`.
5. For write methods, parse JSON body from `php://input`.
6. Delegate to the matching `EventManager` method.
7. Return structured JSON response.

### 3.2 Supported operations

GET actions:

- `action=get&id=<eventId>` -> single event
- `action=getAll` -> all events across all clubs
- `action=getByClub&club=<clubName>` -> all events for one club
- `action=getByClubAndStatus&club=<clubName>&status=<status>` -> filtered subset
- `action=getByStatus&status=<status>` -> global status filter
- `action=getFeatured` -> events where `featured === true`

Write operations:

- `POST ?action=create` (or POST with no action) -> create event
- `PUT/PATCH ?action=update&id=<eventId>` (action optional) -> update
- `DELETE ?action=delete&id=<eventId>` (action optional) -> delete

### 3.3 Example: get all events

Request:

```http
GET /backend/events.php?action=getAll
```

Typical success response:

```json
{
  "status": "success",
  "message": "",
  "data": [
    {
      "id": 1,
      "title": "Hack Night",
      "club": "ACM",
      "date": "2026-04-10",
      "time": "18:00",
      "status": "published"
    }
  ],
  "errors": []
}
```

### 3.4 Example: create event

Request:

```http
POST /backend/events.php?action=create
Content-Type: application/json
```

Body:

```json
{
  "title": "AI Workshop",
  "club": "Cine Radio",
  "clubLogo": "../assets/images/cine_radio/profile.jpg",
  "image": "../assets/uploads/cine_radio_1743070000_ab12cd34.webp",
  "date": "2026-04-22",
  "time": "14:00",
  "location": "Main Auditorium",
  "description": "Hands-on beginner AI session",
  "participants": 0,
  "maxParticipants": 120,
  "featured": false
}
```

Success response (201):

```json
{
  "status": "success",
  "message": "Event created successfully",
  "data": {
    "id": 57,
    "title": "AI Workshop",
    "club": "Cine Radio",
    "date": "2026-04-22",
    "time": "14:00",
    "status": "published"
  },
  "errors": []
}
```

## 4. EventManager Domain + Persistence (`backend/EventManager.php`)

### 4.1 Initialization flow

When `EventManager` is created:

1. Builds a club ID <-> display name map.
2. Scans all known `*_events.json` files using the static club list.
3. Loads each event into one in-memory `events` array.
4. Computes `status` dynamically from date/time:
   - future -> `published`
   - past -> `finished`

### 4.2 Create/update/delete logic

Create:

- Validates required fields.
- Resolves club display name to club ID (e.g., `Cine Radio` -> `cine_radio`).
- Generates next numeric event ID by scanning all loaded events.
- Appends event to the target club file.
- Reloads all events in memory.

Update:

- Finds existing event by ID.
- Merges mutable fields (`id` and computed `status` are protected).
- If club changed, moves the event between club files.
- Persists and reloads all events.

Delete:

- Finds event by ID and owning club.
- Removes from the right club file.
- Persists and reloads all events.

### 4.3 Important design detail

Events are physically partitioned by club JSON file, but read APIs expose a unified view by aggregating all files at load time.

## 5. Clubs API Flow (`backend/clubs.php`)

### 5.1 Supported operations

GET actions:

- `action=get&id=<clubId>` -> single club
- `action=getAll` -> all clubs
- `action=getByCategory&category=<category>` -> category filter
- `action=getCategories` -> unique categories

Write methods:

- `POST` -> create club
- `PUT/PATCH&id=<clubId>` -> update club
- `DELETE&id=<clubId>` -> delete club

### 5.2 Example: fetch one club

Request:

```http
GET /backend/clubs.php?action=get&id=acm
```

Response:

```json
{
  "status": "success",
  "data": {
    "id": "acm",
    "name": "ACM",
    "category": "Academic",
    "banner": "../assets/images/acm/banner.jpg",
    "description": "Computing and programming activities"
  },
  "errors": []
}
```

## 6. ClubManager Domain + Persistence (`backend/ClubManager.php`)

Flow:

1. Load all clubs from `data/clubs.json` at construction.
2. Serve reads from in-memory array.
3. Validate required fields on create.
4. Reject duplicate IDs.
5. Write updated array back to `clubs.json` with `JSON_PRETTY_PRINT`.

## 7. Media Upload/Delete Flow (`backend/media.php` + `MediaManager.php`)

### 7.1 Upload flow

Request:

```http
POST /backend/media.php?action=upload&prefix=cine_radio
Content-Type: multipart/form-data
```

Runtime steps:

1. Validate upload error code.
2. Validate max size (5 MB).
3. Detect MIME type via `finfo`.
4. Allow only: jpeg/png/gif/webp.
5. Build unique filename: `<prefix>_<timestamp>_<random>.ext`.
6. Move file to `assets/uploads/`.
7. Return public relative path.

Typical response:

```json
{
  "status": "success",
  "data": {
    "path": "../assets/uploads/cine_radio_1743070000_ab12cd34.webp"
  },
  "errors": []
}
```

### 7.2 Delete flow

Request:

```http
DELETE /backend/media.php?action=delete&filePath=assets/uploads/cine_radio_....webp
```

Safety checks:

- Sanitizes `..` and backslashes.
- Verifies target exists and resolves inside uploads root.
- Deletes only if path passes guard.

## 8. Form Endpoints (`register.php`, `login.php`)

These endpoints process HTML form POSTs (not JSON APIs).

`register.php`:

- Validates full name, username length, major, password length, confirm password, terms checkbox.
- Appends `@insat.ucar.tn` to submitted email local-part.
- Returns inline HTML with either validation errors or success summary.

`login.php`:

- Validates username + password presence and minimum username length.
- Returns inline HTML with errors or a success message.

## 9. End-to-End Example (Club Dashboard Create Event)

1. Frontend uploads image using `POST /backend/media.php?action=upload&prefix=<clubId>`.
2. Backend validates and stores file in `assets/uploads/`, returns `path`.
3. Frontend sends event JSON with that image path to `POST /backend/events.php?action=create`.
4. Backend resolves club name to club ID, writes event into that club JSON file.
5. Frontend refreshes by calling `GET /backend/events.php?action=getAll`.

## 10. Operational Notes

- There is no authentication/authorization layer protecting write operations yet.
- Data consistency depends on JSON file integrity and valid club name mapping.
- Status is computed from current date; it is not stored as a source-of-truth field.
- Error payloads are exception-message based; client code should always read `errors[0]` fallback-safe.
