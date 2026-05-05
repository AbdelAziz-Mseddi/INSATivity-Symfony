# How Frontend Actions Trigger Backend PHP

This file explains the exact bridge between your UI and PHP backend in this project.

## The Short Answer

Clicking a button does not run PHP directly.

The browser runs JavaScript (or submits an HTML form), and that sends an HTTP request to a PHP endpoint in `backend/`. The web server executes that PHP file, then returns a response to the browser.

## Main Trigger Patterns Used Here

### 1) HTML Form Submit -> PHP Script

Used by login and register pages:

- `pages/login.html` form has `action="../backend/login.php"` and `method="POST"`.
- `pages/register.html` form has `action="../backend/register.php"` and `method="POST"`.

What happens:

1. User clicks the submit button.
2. Browser sends POST request to the form action URL.
3. PHP script (`login.php` or `register.php`) runs on server.
4. PHP validates input, calls `AuthManager`, then returns HTML or redirect.

### 2) JavaScript Event Listener -> fetch(...) -> PHP API

Used by clubs/events/dashboard pages.

What happens:

1. A listener is attached in JS (`click`, `submit`, `DOMContentLoaded`, etc.).
2. Listener callback calls `fetch("../backend/...php?...", { method: ... })`.
3. Browser sends HTTP request.
4. Target PHP router (`events.php`, `clubs.php`, `media.php`) runs.
5. Router calls manager class (`EventManager`, `ClubManager`, `MediaManager`).
6. Manager talks to DB or filesystem.
7. JSON is returned.
8. JS updates DOM.

## Real Examples From This Codebase

## A) Clubs Page Auto-Load

Files:

- `scripts/clubs.js`
- `backend/clubs.php`
- `backend/ClubManager.php`

Flow:

1. `DOMContentLoaded` in `scripts/clubs.js` calls `loadClubs()`.
2. `loadClubs()` does `fetch("../backend/clubs.php?action=getAll", { method: "GET" })`.
3. `backend/clubs.php` sees `GET + action=getAll`.
4. It calls `ClubManager->getAllClubs()`.
5. JSON response comes back.
6. `renderClubs(...)` builds cards in the page.

## B) Home Events Auto-Load

Files:

- `scripts/events.js`
- `backend/events.php`
- `backend/EventManager.php`

Flow:

1. `DOMContentLoaded` in `scripts/events.js` calls `loadEvents()`.
2. `loadEvents()` does `fetch("../backend/events.php?action=getAll", { method: "GET" })`.
3. `backend/events.php` routes to `EventManager->getAllEvents()`.
4. JSON result returns.
5. JS renders featured and upcoming cards.

When a user clicks an event card:

1. Click listener runs `viewEvent(event.id)`.
2. Event object is written to `sessionStorage`.
3. Browser navigates to `event.html`.

Note: this click does not call backend; it changes page using already-loaded data.

## C) Club Dashboard Create Event (best full chain)

Files:

- `scripts/club-dashboard.js`
- `scripts/club-dashboard/api.js`
- `backend/media.php` + `backend/MediaManager.php`
- `backend/events.php` + `backend/EventManager.php`

Flow:

1. User clicks submit on `.event-create-form`.
2. Submit listener in `scripts/club-dashboard.js` intercepts (`event.preventDefault()`).
3. JS uploads cover file:
   - `fetch("../backend/media.php?action=upload&prefix=...", { method: "POST", body: FormData })`
4. `backend/media.php` validates action/method and calls `MediaManager->upload(...)`.
5. Upload returns JSON path to stored image.
6. JS builds event payload and sends:
   - `fetch("../backend/events.php?action=create", { method: "POST", headers: {"Content-Type":"application/json"}, body: JSON.stringify(payload) })`
7. `backend/events.php` routes to `EventManager->createEvent(...)`.
8. Event is inserted into DB.
9. JS reloads dashboard data via `fetchAllEvents()` and re-renders.

## D) Login Button -> Backend Auth

Files:

- `pages/login.html`
- `backend/login.php`
- `backend/AuthManager.php`

Flow:

1. User clicks Login submit button.
2. Browser POSTs form fields to `../backend/login.php`.
3. `login.php` validates fields and calls `AuthManager->loginUser(...)`.
4. On success: starts session and redirects to `../pages/index.html`.
5. On failure: returns error HTML.

## E) Register Button -> Backend Auth

Files:

- `pages/register.html`
- `backend/register.php`
- `backend/AuthManager.php`

Flow:

1. User clicks Create Account submit button.
2. Browser POSTs form fields to `../backend/register.php`.
3. `register.php` validates and calls `AuthManager->registerUser(...)`.
4. On success: session is initialized and success HTML is returned.
5. On failure: error HTML is returned.

## Request Routing Logic In PHP

### `backend/events.php`

- Reads `$_SERVER['REQUEST_METHOD']` and `$_GET['action']`.
- Uses switch on method + action to call manager methods.
- Returns JSON (`status`, `data`, `errors`, etc.).

### `backend/clubs.php`

- Similar method/action router for clubs.
- Returns JSON.

### `backend/media.php`

- Accepts upload/delete actions.
- Handles files, returns JSON.

## Sequence Diagram

```text
User click/submit
    -> Browser (JS listener or HTML form)
        -> HTTP request to /backend/*.php
            -> PHP router (events.php/clubs.php/media.php/login.php/register.php)
                -> Manager/Auth class
                    -> Database or uploads folder
                <- data/result
            <- JSON or HTML/redirect
        <- JS updates DOM or browser navigates
```

## Practical Rule Of Thumb

If you want a UI button to trigger backend logic, you need one of these:

- A form with `action="../backend/your-script.php"` and submit button, or
- A JS click/submit handler that calls `fetch("../backend/your-script.php?...", ...)`.

Without one of those two, clicking only changes frontend state and never reaches PHP.

## Important Contract Note

In this project, `backend/events.php` expects an `action` query parameter. So requests should include it (for example `action=getAll`, `action=create`, `action=update`, `action=delete`).

The helper functions `addEvent`, `removeEvent`, and `updateEvent` in `scripts/events.js` currently call `events.php` without `action`, so those helper paths are not aligned with the router contract as written.