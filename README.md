# NewCareer

A PHP + MySQL **Resume and Admin Backend** REST API.

---

## Requirements

| Tool | Version |
|------|---------|
| PHP  | 8.0+    |
| MySQL | 5.7+ / 8.0+ |
| Apache / Nginx | with `mod_rewrite` / `try_files` |

---

## Setup

### 1. Database

```bash
mysql -u root -p < database/schema.sql
```

The schema creates the `newcareer` database and all tables.  
A default admin user is seeded:

| Field    | Value                |
|----------|----------------------|
| username | `admin`              |
| email    | `admin@newcareer.com` |
| password | `Admin@1234`         |

> **Change the admin password immediately after first login.**

### 2. Environment

Copy `.env.example` to `.env` and fill in your database credentials:

```bash
cp .env.example .env
```

The application reads these environment variables (set them via `.env`, Apache `SetEnv`, or Nginx `fastcgi_param`):

```
DB_HOST=localhost
DB_NAME=newcareer
DB_USER=root
DB_PASSWORD=your_password
```

### 3. Web Server

**Apache** — enable `mod_rewrite`; the included `.htaccess` handles routing.

**Nginx** example:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

---

## Running Tests

Tests use an in-memory SQLite database — no MySQL connection required.

```bash
php tests/test_models.php
```

---

## API Reference

All responses are JSON. Authenticated endpoints require:

```
Authorization: Bearer <token>
```

### Auth

| Method | Endpoint         | Auth | Description        |
|--------|------------------|------|--------------------|
| POST   | `/auth/register` | —    | Register new user  |
| POST   | `/auth/login`    | —    | Login              |
| POST   | `/auth/logout`   | ✓    | Logout             |

**Register / Login body:**
```json
{ "username": "johndoe", "email": "john@example.com", "password": "Secret123" }
```

**Response:**
```json
{ "success": true, "token": "<bearer_token>", "user": { "id": 1, "username": "...", "role": "user" } }
```

---

### Resumes

| Method | Endpoint                                | Auth | Description               |
|--------|-----------------------------------------|------|---------------------------|
| GET    | `/resumes`                              | ✓    | List your resumes         |
| POST   | `/resumes`                              | ✓    | Create resume             |
| GET    | `/resumes/{id}`                         | ✓    | Get resume (with all sections) |
| PUT    | `/resumes/{id}`                         | ✓    | Update resume             |
| DELETE | `/resumes/{id}`                         | ✓    | Delete resume             |

**Create / Update resume body fields:**
`title`, `summary`, `first_name`*, `last_name`*, `email`*, `phone`, `address`, `city`, `state`, `country`, `postal_code`, `linkedin_url`, `github_url`, `website_url`, `is_public`

> Fields marked * are required on create.

---

### Resume Sections

Each section follows the same pattern under `/resumes/{id}/{section}`:

| Section          | Required fields on create       |
|------------------|---------------------------------|
| `education`      | `institution`                   |
| `experience`     | `company`, `position`           |
| `skills`         | `name`                          |
| `projects`       | `name`                          |
| `certifications` | `name`                          |

| Method | Endpoint                             | Description     |
|--------|--------------------------------------|-----------------|
| POST   | `/resumes/{id}/{section}`            | Add entry       |
| PUT    | `/resumes/{id}/{section}/{entry_id}` | Update entry    |
| DELETE | `/resumes/{id}/{section}/{entry_id}` | Delete entry    |

---

### Admin Endpoints

> Require `admin` role.

| Method | Endpoint                       | Description           |
|--------|--------------------------------|-----------------------|
| GET    | `/admin/stats`                 | Dashboard statistics  |
| GET    | `/admin/users`                 | List all users        |
| POST   | `/admin/users`                 | Create user (any role)|
| GET    | `/admin/users/{id}`            | Get user              |
| PUT    | `/admin/users/{id}`            | Update user           |
| DELETE | `/admin/users/{id}`            | Delete user           |
| POST   | `/admin/users/{id}/toggle`     | Toggle active status  |
| GET    | `/admin/resumes`               | List all resumes      |
| GET    | `/admin/resumes/{id}`          | Get any resume        |
| DELETE | `/admin/resumes/{id}`          | Delete any resume     |

---

## Project Structure

```
newcareer/
├── index.php              # Front controller / router
├── .htaccess              # Apache URL rewriting
├── .env.example           # Environment variable template
├── config/
│   └── database.php       # PDO connection factory
├── middleware/
│   └── auth.php           # Bearer-token authentication
├── models/
│   ├── User.php           # User CRUD + validation
│   └── Resume.php         # Resume + all sections CRUD
├── api/
│   ├── auth.php           # Auth endpoints
│   ├── resumes.php        # Resume endpoints
│   └── admin.php          # Admin endpoints
├── database/
│   └── schema.sql         # MySQL schema + seed
└── tests/
    └── test_models.php    # Unit tests (SQLite in-memory)
```

