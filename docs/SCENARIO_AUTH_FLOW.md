# Scenario: Authentication Flow (Login & JWT)

This document traces exactly what happens when a user attempts to log in and subsequently accesses a protected resource.

## Phase 1: The Login Request

1. **User Submits Form (`pages/login.html`)**
   - The user types their username and password and hits "Login".
2. **Frontend Interception (`scripts/login.js`)**
   - The script prevents the default HTML form submission.
   - It reads the input values and calls `API.login(username, password)`.
3. **API Client (`scripts/api.js`)**
   - `api.js` executes a `fetch` request to `../backend/api/auth.php?action=login`.
   - Payload: `{"username": "roua", "password": "roua123"}`.
4. **Backend Endpoint (`backend/api/auth.php`)**
   - The endpoint detects `POST` and `action=login`.
   - It instantiates `UserModel` and calls `$model->login(...)`.
5. **Database Query (`backend/models/UserModel.php`)**
   - The Model executes a PDO prepared statement to fetch the user by username/email.
   - It verifies the password using `password_verify()`.
   - It removes the password hash from the result and returns the user object to the endpoint.
6. **Token Generation (`backend/api/auth.php` & `backend/utils/JWT.php`)**
   - The endpoint uses the `JWT` utility to encode the user's ID, role, and a 24-hour expiration time into a signed token using `JWT_SECRET`.
   - The endpoint responds with:
     ```json
     {
       "success": true,
       "data": { "token": "eyJhbG...", "user": { "id": 1, "username": "roua" } }
     }
     ```
7. **Frontend State Saving (`scripts/api.js` & `scripts/login.js`)**
   - The `api.js` client automatically saves the `token` and `user` object into `localStorage`.
   - `login.js` detects success, shows a green message, and redirects the user to `index.html`.

---

## Phase 2: Accessing a Protected Route (e.g., Creating an Event)

1. **User Action (`scripts/club-dashboard/api.js`)**
   - A club admin submits a new event form. The script calls `API.createEvent(payload)`.
2. **API Client Injects Token (`scripts/api.js`)**
   - `api.js` looks in `localStorage` for `jwt_token`.
   - It automatically injects the header: `Authorization: Bearer eyJhbG...`.
3. **Backend Middleware Validation (`backend/api/events.php` -> `AuthMiddleware.php`)**
   - `events.php` hits the line: `AuthMiddleware::requireRole(['admin', 'student']);`.
   - The `AuthMiddleware` extracts the `Bearer` token from the HTTP headers.
   - It decodes the token using the `JWT_SECRET` and checks if it's expired.
   - It verifies that the user's role allows them to perform this action.
   - If invalid or expired, it throws a JSON error, and the endpoint stops execution.
4. **Execution & Success**
   - If the token is valid, `events.php` continues, passes the payload to `EventModel`, inserts it into the database, and returns the new event data.
