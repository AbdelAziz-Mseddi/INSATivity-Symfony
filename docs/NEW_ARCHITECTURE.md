# INSATivity New Architecture Guide

Welcome to the newly refactored INSATivity architecture! This project has been transitioned from a messy, session-based architecture to a clean, scalable **REST API pattern**.

## 1. Directory Structure

The project strictly separates the **Frontend** (UI and API clients) from the **Backend** (API endpoints, Models, and Database connections).

```text
INSATivity/
├── .env                          # Environment variables (Supabase credentials & JWT secret)
├── pages/                        # HTML pages (UI)
├── styles/                       # CSS files
├── scripts/                      # Frontend JavaScript
│   ├── api.js                    # The centralized API client (handles JWTs, headers, fetches)
│   ├── config.js                 # Configuration file (holds API_BASE_URL)
│   ├── events.js                 # UI logic for Events page
│   ├── clubs.js                  # UI logic for Clubs page
│   └── ...
└── backend/                      # PHP Backend
    ├── api/                      # REST API Endpoints (Controllers)
    │   ├── auth.php              # Handles /login, /register, and /me
    │   ├── events.php            # Handles fetching, creating, updating events
    │   ├── clubs.php             # Handles fetching clubs
    │   └── media.php             # Handles image uploads
    ├── config/
    │   └── Database.php          # Database Singleton (connects to Supabase Postgres via PDO)
    ├── middleware/
    │   └── AuthMiddleware.php    # Intercepts requests, validates JWT Bearer tokens, checks roles
    ├── models/                   # Data Layer (Direct DB interactions)
    │   ├── UserModel.php         # Queries for public.users
    │   ├── EventModel.php        # Queries for public.events
    │   ├── ClubModel.php         # Queries for public.clubs
    │   └── MediaModel.php        # Handles file system storage
    └── utils/
        ├── JWT.php               # JSON Web Token encoder/decoder
        └── Response.php          # Standardizes `{success: bool, data: ...}` outputs
```

## 2. Core Concepts

### A. Separation of Concerns
- **Endpoints (`backend/api/`)** do NOT contain database logic. They only receive the request, validate it via Middleware, pass it to a Model, and format the response using `Response::success()`.
- **Models (`backend/models/`)** do NOT handle HTTP requests or sessions. They only write raw SQL queries, execute them via PDO, and return raw arrays/objects.
- **Frontend Scripts (`scripts/`)** do NOT use `fetch()` randomly. They all import the centralized `API` object from `api.js` to ensure consistent headers and error handling.

### B. Stateless Authentication (JWT)
Instead of relying on PHP `$_SESSION` cookies (which are stateful and don't scale well across mobile apps or cross-domain APIs), we use **JSON Web Tokens (JWT)**.
When a user logs in, the backend creates an encrypted JWT string and sends it to the frontend. The frontend saves it in `localStorage` and attaches it to every future API request as an `Authorization: Bearer <token>` header.

### C. Standardized Responses
Every API endpoint uses `backend/utils/Response.php` to guarantee a consistent JSON output format:
**Success:**
```json
{
  "success": true,
  "data": { "id": 1, "title": "Example Event" },
  "message": "Operation successful"
}
```
**Error:**
```json
{
  "success": false,
  "error": "Authentication required. Missing Bearer token.",
  "code": "AUTH_MISSING"
}
```

---
**See the scenario files for deep-dives into specific flows:**
- [Authentication Flow (`SCENARIO_AUTH_FLOW.md`)](SCENARIO_AUTH_FLOW.md)
- [Data Fetching Flow (`SCENARIO_DATA_FETCH.md`)](SCENARIO_DATA_FETCH.md)
