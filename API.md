# Savings Tracker API

A RESTful JSON API for managing savings goals and deposits. Each authenticated user can only see and modify their own data.

- **Base URL:** `https://savings-tracker-main-szr80r.laravel.cloud/api` (replace with your host)
- **Auth:** Bearer tokens (Laravel Sanctum personal access tokens)
- **Content-Type:** `application/json`
- **Accept:** `application/json` (required — without it, validation errors render as HTML)

## Table of contents

1. [Authentication flow](#authentication-flow)
2. [Authorization & data isolation](#authorization--data-isolation)
3. [Conventions](#conventions)
4. [Errors](#errors)
5. [Rate limits](#rate-limits)
6. [CORS](#cors)
7. [Auth endpoints](#auth-endpoints)
8. [Dashboard endpoint](#dashboard-endpoint)
9. [Goal endpoints](#goal-endpoints)
10. [Deposit endpoints](#deposit-endpoints)
11. [Object reference](#object-reference)

---

## Authentication flow

1. **Register** (`POST /api/register`) or **log in** (`POST /api/login`) to get a bearer token.
2. Send the token on every protected request:

    ```http
    Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz...
    ```

3. **Log out** (`POST /api/logout`) to revoke the token used on that request.

Tokens never expire automatically — they only become invalid after `POST /api/logout` or by deleting the row from `personal_access_tokens` directly.

## Authorization & data isolation

**Every authenticated endpoint is scoped to the user the token belongs to.** A token can only ever read or modify that user's own goals and deposits — there is no admin endpoint and no way to enumerate other accounts.

How this is enforced:

- **List & create** operations query through the user's relation (`$user->goals()->...`), so the underlying SQL is always `WHERE user_id = ?`. A goal that doesn't belong to you can't appear in `GET /api/goals`, `GET /api/dashboard`, or `GET /api/goals/{goal}/deposits`.
- **Show, update, and delete** operations resolve the model by ID via implicit route binding, then check `goal.user_id` (and `deposit.goal_id`) against the authenticated user before doing anything. A mismatch returns `403 Forbidden` immediately — no data is read, written, or leaked in the response body.

Concrete examples of what happens with a cross-user request:

| Request                           | Token belongs to | Goal owner | Response                                                             |
| --------------------------------- | ---------------- | ---------- | -------------------------------------------------------------------- |
| `GET /api/goals/42`               | user A           | user B     | `403 Forbidden`                                                      |
| `PUT /api/goals/42`               | user A           | user B     | `403 Forbidden` (validation never runs)                              |
| `DELETE /api/goals/42`            | user A           | user B     | `403 Forbidden`, goal untouched                                      |
| `POST /api/goals/42/deposits`     | user A           | user B     | `403 Forbidden`, no deposit created                                  |
| `GET /api/goals/42/deposits`      | user A           | user B     | `403 Forbidden`                                                      |
| `DELETE /api/goals/42/deposits/9` | user A           | user B     | `403 Forbidden`, deposit untouched                                   |
| `GET /api/dashboard`              | user A           | —          | Only user A's goals and only user A's deposits in `monthly_deposits` |

These boundaries are covered by a dedicated set of feature tests (`tests/Feature/Api/*ApiTest.php`) that assert `403` for every cross-user mutation and that the list/dashboard payloads include only the caller's data.

Note on `403` vs `404`: a missing ID returns `404 Not Found`, while an existing ID owned by another user returns `403 Forbidden`. The status code itself reveals whether a given numeric ID is in use by _someone_, but never which account owns it or any of its contents. If you'd prefer to mask even that (return `404` in both cases), open an issue and we'll switch the controllers to use a `findOrFail` scoped through the user's relation.

## Conventions

- All timestamps are ISO 8601 in UTC, e.g. `2026-05-13T11:30:00+00:00`.
- All amounts are decimal numbers in dollars (no cents-as-integer encoding).
- Dates (deadlines) use `YYYY-MM-DD`.
- Resources are returned wrapped in a `data` key. Collections paginate and include a `meta` and `links` block (Laravel's standard pagination shape).
- Successful mutations return the affected resource (`201 Created` on create, `200 OK` on update). Deletes return `204 No Content`.

## Errors

| Status                     | Meaning                                                   | Example body                                                       |
| -------------------------- | --------------------------------------------------------- | ------------------------------------------------------------------ |
| `401 Unauthorized`         | Missing or invalid token                                  | `{ "message": "Unauthenticated." }`                                |
| `403 Forbidden`            | The token is valid but the resource doesn't belong to you | `{ "message": "This action is unauthorized." }`                    |
| `404 Not Found`            | Goal or deposit ID doesn't exist                          | `{ "message": "No query results for model [App\\Models\\Goal]." }` |
| `422 Unprocessable Entity` | Validation failed                                         | see below                                                          |
| `429 Too Many Requests`    | Rate limit hit                                            | `{ "message": "Too Many Attempts." }`                              |

Validation errors (`422`) look like:

```json
{
    "message": "The name field is required.",
    "errors": {
        "name": ["The name field is required."],
        "target": ["The target field must be at least 0.01."]
    }
}
```

## Rate limits

- `POST /api/register` and `POST /api/login` — **6 requests per minute** per IP.
- All other endpoints — Laravel's default authenticated API throttle (60/min per user).

## Auth endpoints

### `POST /api/register`

Create an account and receive a token.

**Body**

| Field                   | Type   | Required | Notes                                                               |
| ----------------------- | ------ | -------- | ------------------------------------------------------------------- |
| `name`                  | string | yes      | max 255                                                             |
| `email`                 | string | yes      | must be a valid, unused email                                       |
| `password`              | string | yes      | follows Laravel's default password rules (min 8 chars)              |
| `password_confirmation` | string | yes      | must match `password`                                               |
| `device_name`           | string | no       | labels the token in `personal_access_tokens.name`, e.g. `iphone-15` |

**Request**

```bash
curl -X POST https://savings-tracker-main-szr80r.laravel.cloud/api/register \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Ada Lovelace",
    "email": "ada@example.com",
    "password": "correct-horse-battery",
    "password_confirmation": "correct-horse-battery",
    "device_name": "macbook"
  }'
```

**Response — `201 Created`**

```json
{
    "token": "1|aBcD3FgH...",
    "user": {
        "id": 7,
        "name": "Ada Lovelace",
        "email": "ada@example.com"
    }
}
```

### `POST /api/login`

Exchange credentials for a token.

**Body**

| Field         | Type   | Required |
| ------------- | ------ | -------- |
| `email`       | string | yes      |
| `password`    | string | yes      |
| `device_name` | string | no       |

**Response — `200 OK`** (same shape as register, minus the `201` status).

On invalid credentials returns `422` with:

```json
{
    "message": "The provided credentials are incorrect.",
    "errors": { "email": ["The provided credentials are incorrect."] }
}
```

### `GET /api/me`

Return the user the token belongs to. Useful for verifying a token is still good.

**Response — `200 OK`**

```json
{
    "user": {
        "id": 7,
        "name": "Ada Lovelace",
        "email": "ada@example.com"
    }
}
```

### `POST /api/logout`

Revoke the token used to make this request.

**Response — `200 OK`**

```json
{ "message": "Token revoked." }
```

---

## Dashboard endpoint

### `GET /api/dashboard`

One-shot endpoint that returns everything needed to render a dashboard: summary stats, monthly deposit totals (across all goals), and the full goals list. Mirrors the data the web dashboard renders.

**Response — `200 OK`**

```json
{
    "data": {
        "summary": {
            "total_savings": 11249,
            "active_goals": 7,
            "completed_goals": 1
        },
        "monthly_deposits": [
            { "month": "2026-03", "total": 700 },
            { "month": "2026-04", "total": 1850 },
            { "month": "2026-05", "total": 1200 }
        ],
        "goals": [
            {
                "id": 12,
                "name": "MacBook Pro M4",
                "target": 2499,
                "deadline": "2026-08-01",
                "saved": 1900,
                "progress": 76,
                "is_completed": false,
                "is_not_started": false,
                "created_at": "2026-01-15T09:00:00+00:00",
                "updated_at": "2026-05-01T09:00:00+00:00"
            }
        ]
    }
}
```

Fields:

| Field                     | Description                                                                                                             |
| ------------------------- | ----------------------------------------------------------------------------------------------------------------------- |
| `summary.total_savings`   | Sum of every deposit across all of the user's goals                                                                     |
| `summary.active_goals`    | Count of goals where `saved < target`                                                                                   |
| `summary.completed_goals` | Count of goals where `saved >= target`                                                                                  |
| `monthly_deposits`        | Array of `{ month: "YYYY-MM", total: number }`, ordered oldest → newest. Empty array when the user has no deposits      |
| `goals`                   | Array of [Goal](#goal-object) objects (without their full `deposits` arrays — call `GET /api/goals/{goal}` to drill in) |

---

## Goal endpoints

### `GET /api/goals`

List the authenticated user's goals, ordered by most recently created. Paginated.

**Query parameters**

| Name       | Default | Notes |
| ---------- | ------- | ----- |
| `page`     | 1       |       |
| `per_page` | 25      |       |

**Response — `200 OK`**

```json
{
    "data": [
        {
            "id": 12,
            "name": "MacBook Pro M4",
            "target": 2499,
            "deadline": "2026-08-01",
            "saved": 1900,
            "progress": 76,
            "is_completed": false,
            "is_not_started": false,
            "deposit_count": 7,
            "created_at": "2026-01-15T09:00:00+00:00",
            "updated_at": "2026-01-15T09:00:00+00:00"
        }
    ],
    "links": { "first": "...", "last": "...", "prev": null, "next": null },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "per_page": 25,
        "to": 1,
        "total": 1
    }
}
```

> Note: `deposits` and `deposit_count` are only populated for the show endpoint. The list endpoint omits the full deposit array to keep responses light.

### `POST /api/goals`

Create a goal.

**Body**

| Field      | Type              | Required | Notes                  |
| ---------- | ----------------- | -------- | ---------------------- |
| `name`     | string            | yes      | max 120                |
| `target`   | number            | yes      | `> 0`                  |
| `deadline` | date `YYYY-MM-DD` | no       | must be today or later |

**Response — `201 Created`** with a single goal in `data`.

### `GET /api/goals/{goal}`

Fetch a single goal including its full deposit history (ordered newest first).

**Response — `200 OK`**

```json
{
    "data": {
        "id": 12,
        "name": "MacBook Pro M4",
        "target": 2499,
        "deadline": "2026-08-01",
        "saved": 1900,
        "progress": 76,
        "is_completed": false,
        "is_not_started": false,
        "deposit_count": 7,
        "deposits": [
            {
                "id": 88,
                "goal_id": 12,
                "amount": 200,
                "note": "Monthly savings",
                "created_at": "2026-05-01T09:00:00+00:00"
            }
        ],
        "created_at": "2026-01-15T09:00:00+00:00",
        "updated_at": "2026-05-01T09:00:00+00:00"
    }
}
```

### `PUT /api/goals/{goal}` _(also accepts `PATCH`)_

Update a goal. Same body as `POST /api/goals`, but `deadline` has no `after_or_equal:today` constraint (you may push a missed deadline forward or backward).

**Response — `200 OK`** with the updated goal in `data`.

### `DELETE /api/goals/{goal}`

Permanently delete the goal **and all of its deposits**. There is no soft-delete or undo.

**Response — `204 No Content`**

---

## Deposit endpoints

### `GET /api/goals/{goal}/deposits`

List deposits for a goal, newest first. Paginated.

**Query parameters**

| Name       | Default |
| ---------- | ------- |
| `page`     | 1       |
| `per_page` | 50      |

**Response — `200 OK`** with an array of [Deposit](#deposit-object) objects in `data`.

### `POST /api/goals/{goal}/deposits`

Add a deposit to a goal.

**Body**

| Field    | Type   | Required | Notes         |
| -------- | ------ | -------- | ------------- |
| `amount` | number | yes      | must be `> 0` |
| `note`   | string | no       | max 255       |

**Response — `201 Created`**

```json
{
    "data": {
        "id": 142,
        "goal_id": 12,
        "amount": 250,
        "note": "Freelance bonus",
        "created_at": "2026-05-13T14:30:00+00:00"
    }
}
```

### `DELETE /api/goals/{goal}/deposits/{deposit}`

Delete a deposit. The goal's `saved` and `progress` values will recalculate on the next read.

**Response — `204 No Content`**

---

## Object reference

### Goal object

| Field                       | Type           | Description                                                                   |
| --------------------------- | -------------- | ----------------------------------------------------------------------------- |
| `id`                        | integer        | Unique goal ID                                                                |
| `name`                      | string         | Display name                                                                  |
| `target`                    | number         | Target dollar amount                                                          |
| `deadline`                  | string \| null | `YYYY-MM-DD`, or `null` for open-ended goals                                  |
| `saved`                     | number         | Sum of all deposits for this goal                                             |
| `progress`                  | number         | Percentage `0`–`100` (capped at 100), one decimal of precision                |
| `is_completed`              | boolean        | `true` once `saved >= target`                                                 |
| `is_not_started`            | boolean        | `true` when the goal has zero deposits                                        |
| `deposit_count`             | integer        | Number of deposits (only on `GET /api/goals/{goal}`)                          |
| `deposits`                  | array          | Array of [Deposit](#deposit-object) objects (only on `GET /api/goals/{goal}`) |
| `created_at` / `updated_at` | string         | ISO 8601 timestamp                                                            |

### Deposit object

| Field        | Type           | Description                   |
| ------------ | -------------- | ----------------------------- |
| `id`         | integer        | Unique deposit ID             |
| `goal_id`    | integer        | Parent goal                   |
| `amount`     | number         | Dollar amount (always `> 0`)  |
| `note`       | string \| null | Optional note (max 255 chars) |
| `created_at` | string         | ISO 8601 timestamp            |

---

## End-to-end example

```bash
# 1. Register
TOKEN=$(curl -s -X POST https://savings-tracker-main-szr80r.laravel.cloud/api/register \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"name":"Ada","email":"ada@example.com","password":"correct-horse","password_confirmation":"correct-horse"}' \
  | jq -r .token)

# 2. Create a goal
GOAL_ID=$(curl -s -X POST https://savings-tracker-main-szr80r.laravel.cloud/api/goals \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"name":"New laptop","target":2000,"deadline":"2026-12-31"}' \
  | jq -r .data.id)

# 3. Add a deposit
curl -s -X POST "https://savings-tracker-main-szr80r.laravel.cloud/api/goals/$GOAL_ID/deposits" \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"amount":500,"note":"Starting fund"}'

# 4. Read the goal back with its deposits
curl -s "https://savings-tracker-main-szr80r.laravel.cloud/api/goals/$GOAL_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Accept: application/json' | jq

# 5. Log out
curl -s -X POST https://savings-tracker-main-szr80r.laravel.cloud/api/logout \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Accept: application/json'
```
