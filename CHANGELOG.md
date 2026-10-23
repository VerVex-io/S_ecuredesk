# Changelog

## v1.1.0 - 2026-10-23
Security patch.

### Fixed
- PHP core RCE (CVE-2026-109618 / CVE-2026-109619): the Docker build now fails on PHP < 8.3.36
- No login or register rate limiting
- Login timing leak that revealed which emails exist
- Non-strict Content-Type check; missing input length limits
- Session role not refreshed after a role change or account deletion
- vite/esbuild dev-server advisory (vite upgraded to 7.3.7)

### Hardened
- Production php.ini, `session.upload_progress` and `expose_php` off
- Apache rejects non-JSON write requests before PHP parses them
- `Secure` cookie and HSTS when `APP_ENV=production`
- Idle (30 min) and absolute (8 h) session timeouts
- `seed.php` refuses to run in production
- Dev ports bind to 127.0.0.1; CI runs `npm audit` and `composer audit`