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
- Login rate limiting (5 failures / 15 min per IP+email, 30 per IP) and constant-time login (no timing-based user enumeration)
- Sessions: strict mode, idle (30 min) and absolute (8 h) timeouts, role re-read from the DB on every request, `Secure` cookie + HSTS when `APP_ENV=production`
- Strict `application/json` check in PHP and in Apache (`public/.htaccess`); input length caps
- PHP hardening in `backend/docker/` (production php.ini, `session.upload_progress` off, `expose_php` off) and a build-time guard that refuses PHP older than 8.3.36 (CVE-2026-109618/109619). Rebuild with `docker compose build --pull backend`
- `bin/seed.php` refuses to run when `APP_ENV=production`; dev ports bind to 127.0.0.1 only

See the companion repo **securedesk-security-audit** for the pentest of a deliberately vulnerable version.

See [CHANGELOG.md](CHANGELOG.md) for security fixes by version.

## Tests
```bash
cd backend && composer install && vendor/bin/phpunit
```
CI runs the backend tests and the frontend build on every push.

## Roadmap
- [ ] File attachments (type/size whitelist, stored outside the web root)
- [ ] Assign tickets to agents, admin dashboard
- [x] Rate limiting on login
- [ ] Run containers as non-root; add a `composer.lock` (run `composer install` once and commit it)
- [ ] Production CSP headers for the built SPA (set on the web server that serves `frontend/dist`)
- [ ] JMeter load-test results (see `jmeter/`)
- [ ] Integration tests against a MySQL service in CI

