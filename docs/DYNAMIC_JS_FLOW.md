# Dynamic JavaScript Flow Documentation

This document explains how dynamic behavior works in the frontend: which script boots each page, how data is loaded, how UI is updated, and how state moves between pages.

## 1. Frontend Script Map

Page -> script:

- `pages/index.html` -> `scripts/events.js`
- `pages/clubs.html` -> `scripts/clubs.js`
- `pages/club-dashboard.html` -> `scripts/club-dashboard.js` (ES module)
- `pages/calendar.html` -> `scripts/calendar.js`
- `pages/event.html` -> inline script in the HTML page

## 2. Shared Dynamic Patterns Used in Project

Most pages follow this lifecycle:

1. Wait for `DOMContentLoaded`.
2. Fetch data (or load from storage).
3. Transform/filter/sort data.
4. Render DOM by creating/replacing HTML blocks.
5. Attach event listeners for user actions.
6. Trigger updates (re-render or navigation) when state changes.

## 3. Home/Events Page Flow (`scripts/events.js`)

### 3.1 Boot flow

On page load:

- Calls `loadEvents()`.
- `loadEvents()` sends `GET ../backend/events.php?action=getAll`.
- Stores returned list in in-memory `eventsData` array.
- Calls:
  - `renderFeaturedEvents()`
  - `renderUpcomingEvents()`

### 3.2 Rendering behavior

`renderFeaturedEvents()`:

- Filters by `event.featured === true`.
- Builds clickable cards inside `.featured-grid`.
- Click action -> `viewEvent(event.id)`.

`renderUpcomingEvents()`:

- Keeps events with date >= today.
- Sorts by nearest date.
- Builds cards in `.events-container`.
- Click action -> `viewEvent(event.id)`.

### 3.3 Navigation + transient state

`viewEvent(event.id)`:

1. Finds selected event in `eventsData`.
2. Saves full object to `sessionStorage` under `currentEvent`.
3. Navigates to `event.html`.

This is the bridge between the list page and event detail page.

### 3.4 API write helpers present in script

The file also includes:

- `addEvent(eventObj)` -> POST `events.php`
- `removeEvent(eventId)` -> DELETE `events.php?id=...`
- `updateEvent(eventId, updates)` -> PUT `events.php?id=...`

Each operation refreshes local view by calling `loadEvents()` again.

## 4. Event Detail Page Flow (`pages/event.html` inline script)

On `DOMContentLoaded`:

1. Read `sessionStorage.getItem("currentEvent")`.
2. Parse JSON if present.
3. Fill title/club/date/time/location/description DOM nodes.
4. Compute participation progress:
   - `progressPercent = participants / maxParticipants * 100`
5. Set progress bar width/text.
6. Set background image from `event.image`.

Example behavior:

- Clicking any event card on homepage immediately opens detail view with the same object payload, without re-fetching from backend.

## 5. Clubs Listing Flow (`scripts/clubs.js`)

### 5.1 Data load

- `loadClubs()` fetches `GET ../backend/clubs.php?action=getAll`.
- Saves response in `allClubs`.
- Calls `renderClubs(allClubs)`.
- Initializes category filters via `setupFilterButtons()`.

### 5.2 Dynamic filtering

Each `.filter-btn` click:

1. Updates active button classes.
2. Reads `data-category`.
3. Filters `allClubs` unless category is `All`.
4. Re-renders list with selected subset.

### 5.3 Deep-link transition

Each card contains:

- `club-dashboard.html?club=<clubId>`

This query parameter drives the dashboard content for the selected club.

## 6. Club Dashboard Modular Flow (`scripts/club-dashboard.js` + modules)

This is the most structured dynamic flow in the project.

### 6.1 Modules and responsibilities

- `club-dashboard/api.js`: backend fetch/upload/create wrappers.
- `club-dashboard/constants.js`: club ID/name mappings + default club.
- `club-dashboard/utils.js`: normalization, escaping, date labeling, split logic.
- `club-dashboard/render.js`: all DOM rendering and sidebar panel switching.
- `club-dashboard.js`: orchestration (load, filter, submit handling, refresh).

### 6.2 Dashboard boot flow

On `DOMContentLoaded` in `club-dashboard.js`:

1. Collect DOM references via `getDashboardDom()`.
2. Bind sidebar navigation with `bindSidebar(dom)`.
3. Execute `loadDashboardData()`.

`loadDashboardData()` runtime:

1. Read `club` query param using `getRequestedClubId()`.
2. Fetch selected club with `fetchClubById(...)`.
3. If invalid club in URL, fallback to default club.
4. Fetch all events with `fetchAllEvents()`.
5. Keep only events relevant to active club (`getClubEvents`).
6. Split into upcoming vs finished (`splitEventsByDate`).
7. Render profile + pending + history + done + feedback options.

### 6.3 Club event matching strategy

`getClubEvents(allEvents, club)` matches by:

- normalized event `club` name == normalized club display name, or
- normalized event `club` name == mapped manager name, or
- `event.clubLogo` path includes `/assets/images/<clubId>/`

This makes matching robust when data naming varies slightly.

### 6.4 Create Event dynamic flow

When `.event-create-form` is submitted:

1. Validate that a cover image exists.
2. Determine active club context (ID + display name).
3. Upload image via `uploadCoverImage(file, clubId)`.
4. Build event payload from form fields.
5. Send payload to `createEvent(payload)`.
6. Reset form + show success status.
7. Reload dashboard data to display new event.

Important payload details:

- `clubLogo` is derived from club ID: `../assets/images/<clubId>/profile.jpg`.
- `description` appends end-time metadata when provided.
- `featured` defaults to `false` from dashboard creation flow.

## 7. Calendar Page Flow (`scripts/calendar.js`)

This page is fully client-side and currently independent from backend APIs.

### 7.1 State model

- Events are stored in `localStorage` under key `insativity_events`.
- If empty/invalid, fallback to hardcoded `DEFAULT_EVENTS`.
- In-memory state:
  - `events`
  - current viewed month/year
  - selected day

### 7.2 Dynamic rendering cycle

`renderCalendar()`:

1. Compute month boundaries and grid size.
2. Build day cells (including muted previous/next month cells).
3. Mark styles for current day/weekends/selected/has-event.
4. Inject up to 2 event pills per day + `+N` overflow marker.
5. Attach click listeners to valid current-month day cells.

`openDayPanel(...)`:

- Shows per-day agenda list.
- Binds delete handlers per event.
- Enables quick add-event modal prefilled with selected date.

### 7.3 Mutation actions

- Add event -> push to `events`, save to localStorage, re-render.
- Delete event -> filter array, save, re-render.
- Search bar -> debounced filter for featured cards (`200ms`).

## 8. Dynamic JS Examples

### Example A: Homepage to detail page transition

1. User clicks featured card in homepage.
2. `viewEvent(id)` stores object in sessionStorage.
3. Browser navigates to `event.html`.
4. Detail page inline script reads object and paints UI.

### Example B: Club dashboard create event

1. User submits create form with image.
2. Frontend uploads image file first (`media.php`).
3. Frontend sends event JSON (`events.php?action=create`).
4. UI reloads dashboard and newly created event appears in proper section after date-based split.

### Example C: Club filtering without extra API calls

1. `clubs.js` loads all clubs once.
2. Filter button clicks only operate on in-memory `allClubs`.
3. DOM is rebuilt for each selected category.

## 9. Current Architectural Notes

- The app mixes backend-driven pages (`events.js`, `clubs.js`, dashboard) and local-only page state (`calendar.js`).
- `sessionStorage` is used as a lightweight cross-page transport for selected event details.
- Rendering is imperative (manual `innerHTML`/`createElement`) rather than framework-based reactive rendering.
- Error handling is mostly console-based except dashboard create form, which exposes inline status messages to users.
