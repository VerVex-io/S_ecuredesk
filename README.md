# SecureDesk

A support-ticket platform built with security and quality in mind.
Users open tickets, agents handle them, admins oversee everything.

<!-- Add a screenshot: ![SecureDesk](docs/screenshot.png) -->

## Stack
React (Vite), PHP 8.3 REST API, MySQL 8, Docker Compose, PHPUnit, GitHub Actions, Apache JMeter

## Quick start
```bash
cp .env.example .env
docker compose up --build
docker compose exec backend php bin/seed.php   # optional demo users
```
- App: http://localhost:5173
- API: http://localhost:8080/api

Demo accounts (dev only, password `Password123!`): `admin@securedesk.test`, `agent@securedesk.test`, `alice@securedesk.test`

## API
| Method | Route | Description | Access |
|---|---|---|---|
| POST | /api/register | Create account | public |
| POST | /api/login | Start session | public |
| POST | /api/logout | End session | auth |
| GET | /api/me | Current user | auth |
| GET | /api/tickets?q=&status= | List / search tickets | auth (users see own) |
| POST | /api/tickets | Create ticket | auth |
| GET | /api/tickets/{id} | Ticket and comments | owner or staff |
| PATCH | /api/tickets/{id} | Change status | staff |
| POST | /api/tickets/{id}/comments | Add comment | owner or staff |

## Security measures
- Prepared statements everywhere (SQL injection)
- `password_hash` / `password_verify`, session ID regenerated on login (session fixation)
- HttpOnly and SameSite cookies, JSON-only writes (CSRF)
- Object-level authorization on every ticket route (IDOR); role is never accepted from the client
- Generic auth errors and 500s (no user enumeration or info leaks)
- Security headers on every response; React output escaping (XSS)

See the companion repo **securedesk-security-audit** for the pentest of a deliberately vulnerable version.

## Tests
```bash
cd backend && composer install && vendor/bin/phpunit
```
CI runs the backend tests and the frontend build on every push.

## Roadmap
- [ ] File attachments (type/size whitelist, stored outside the web root)
- [ ] Assign tickets to agents, admin dashboard
- [ ] Rate limiting on login
- [ ] JMeter load-test results (see `jmeter/`)
- [ ] Integration tests against a MySQL service in CI

## What I learned
Write 3 to 4 sentences here once you finish.
