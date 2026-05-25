# Dynamic JavaScript Flow Documentation

This document describes how dynamic frontend behavior currently works across pages, including data loading, UI rendering, and page-to-page state transfer.

## 1. Script Map by Page

- `pages/index.html` -> `scripts/events.js`
- `pages/clubs.html` -> `scripts/clubs.js`
- `pages/club-dashboard.html` -> `scripts/club-dashboard.js` (ES module + submodules)
- `pages/calendar.html` -> `scripts/calendar.js`
- `pages/event.html` -> inline script in the page

## 2. Global Runtime Pattern

Most pages follow this lifecycle:

1. Wait for `DOMContentLoaded`.
2. Fetch data from backend or load browser storage.
3. Normalize/filter/sort data in memory.
4. Render DOM with `innerHTML`/`createElement`.
5. Attach click/submit listeners.
6. Re-render after mutations.

## 3. Home Events Flow (`scripts/events.js`)

### 3.1 Boot

- On load, `loadEvents()` fetches `GET ../backend/events.php?action=getAll`.
- Response data is stored in local array `eventsData`.
- Then:
  - `renderFeaturedEvents()`
  - `renderUpcomingEvents()`

### 3.2 Rendering

`renderFeaturedEvents()`:

- Filters `event.featured === true`.
- Renders `.event-card-featured` cards into `.featured-grid`.
- Card click -> `viewEvent(event.id)`.

`renderUpcomingEvents()`:

- Keeps events with `event.date >= today`.
- Sorts by ascending date.
- Renders cards into `.events-container`.
- Card click -> `viewEvent(event.id)`.

### 3.3 Event detail transition

`viewEvent(eventId)`:

1. Finds event object in `eventsData`.
2. Saves object to `sessionStorage` under key `currentEvent`.
3. Navigates to `event.html`.

## 4. Event Detail Page (`pages/event.html` inline script)

On `DOMContentLoaded`:

1. Read `sessionStorage.getItem("currentEvent")`.
2. If available, parse JSON and fill event fields (title, club, date, time, location, description).
3. Compute and render progress bar from `participants/maxParticipants`.
4. Set hero background image from `event.image`.

Notes:

- Page currently relies on `sessionStorage` payload and does not fetch event details directly from backend.
- If storage is empty, page stays with placeholder/default content.

## 5. Clubs Listing Flow (`scripts/clubs.js`)

### 5.1 Load

- `loadClubs()` fetches `GET ../backend/clubs.php?action=getAll`.
- Saves result into `allClubs`.
- Calls `renderClubs(allClubs)` and `setupFilterButtons()`.

### 5.2 Render

- Builds club cards inside `#clubs-container`.
- Each card links to: `club-dashboard.html?club=<clubId>`.

### 5.3 Filters

- Filter buttons (`.filter-btn`) are wired once.
- Clicking a button toggles active classes and filters in-memory `allClubs` by exact `category`.

## 6. Club Dashboard Flow (`scripts/club-dashboard.js` + modules)

### 6.1 Modules and responsibilities

- `scripts/club-dashboard/api.js`: backend requests for club/event/media.
- `scripts/club-dashboard/constants.js`: default club + club name map.
- `scripts/club-dashboard/utils.js`: URL parsing, text normalization, date split/helpers.
- `scripts/club-dashboard/render.js`: all panel rendering + sidebar behavior.
- `scripts/club-dashboard.js`: orchestration and form submit flow.

### 6.2 Boot sequence

On `DOMContentLoaded`:

1. Build DOM references with `getDashboardDom()`.
2. Bind sidebar panel navigation with `bindSidebar(dom)`.
3. Run `loadDashboardData()`.

`loadDashboardData()` flow:

1. Read query param club id via `getRequestedClubId()`.
2. Fetch selected club with `fetchClubById(...)`.
3. If requested club is invalid, fallback to `DEFAULT_CLUB_ID`.
4. Fetch all events via `fetchAllEvents()`.
5. Keep only events matching active club through `getClubEvents(...)`.
6. Split club events into upcoming/finished via `splitEventsByDate(...)`.
7. Render profile, pending, history, done, and feedback selector.

### 6.3 Club-event matching strategy

`getClubEvents(...)` matches an event when one of these conditions passes:

- `event.club` normalized == club display name normalized
- `event.club` normalized == mapped manager name normalized
- `event.clubLogo` contains `/assets/images/<clubId>/`

This tolerates minor naming/path inconsistencies.

### 6.4 Create event flow

When `.event-create-form` is submitted:

1. Validate a cover file is provided.
2. Resolve active club context (`clubId`, `clubName`).
3. Upload image with `uploadCoverImage(file, clubId)`.
4. Build event payload from form fields.
5. Call `createEvent(payload)`.
6. Reset form + show status text.
7. Reload dashboard data to show latest state.

Payload fields include:

- `clubLogo`: `../assets/images/<clubId>/profile.jpg`
- `description`: appends `End time: ...` when provided
- `participants`: starts at `0`
- `featured`: forced to `false` from dashboard form

## 7. Calendar Flow (`scripts/calendar.js`)

This page is currently local-only and independent from backend events/clubs APIs.

### 7.1 State

- Storage key: `insativity_events` in `localStorage`.
- Fallback seed: `DEFAULT_EVENTS` when storage is missing/invalid.
- Runtime state:
  - `events`
  - `current` month/year
  - `selected` day

### 7.2 Rendering cycle

`renderCalendar()`:

1. Compute month boundaries and visible grid size.
2. Build cells for previous/current/next month overflow.
3. Mark classes: current day, weekend, selected day, has-event.
4. Render up to 2 event pills/day plus `+N` overflow indicator.
5. Bind click handlers on current-month cells.

`openDayPanel(...)`:

- Renders agenda entries for selected day.
- Wires per-event delete buttons.
- Wires add-event button with prefilled date.

### 7.3 Mutations

- Add event (modal submit) -> push to `events`, save, re-render.
- Delete event -> filter `events`, save, re-render.
- Search input -> debounced (`200ms`) filtering of featured cards.

## 8. API Helper Nuance in `events.js`

`events.js` contains helper functions:

- `addEvent(eventObj)` -> `POST ../backend/events.php` (without `action`)
- `removeEvent(eventId)` -> `DELETE ../backend/events.php?id=...` (without `action`)
- `updateEvent(eventId, updates)` -> `PUT ../backend/events.php?id=...` (without `action`)

Current backend `events.php` requires `action` for every method, so these helpers are not aligned with the router contract unless updated to include:

- `action=create`
- `action=delete`
- `action=update`

## 9. Current Architecture Notes

- Mixed data strategy:
  - Backend-driven pages: home, clubs, club dashboard
  - Local storage page: calendar
- Event detail page uses session storage as transport, not direct fetch.
- Rendering remains imperative (DOM APIs), no frontend framework.
- User-facing inline status messaging is strongest in club dashboard; other pages rely mainly on console error logs.
