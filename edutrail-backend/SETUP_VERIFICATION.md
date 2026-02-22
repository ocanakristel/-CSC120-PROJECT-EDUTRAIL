# Backend Setup & Verification Checklist

## Pre-flight Checks

Run these commands to verify your environment before testing:

```bash
cd c:\CSC\ 120\ PROJECT\edutrail-backend

# 1. Check PHP version (needs 8.0+)
php -v

# 2. Check if composer dependencies are installed
composer --version

# 3. Check if database is connected
php artisan tinker
>>> DB::connection()->getPdo();  # Should execute without error
>>> exit

# 4. Run migrations to ensure projects table has all fields
php artisan migrate --force

# 5. Create storage symlink (if not already done)
php artisan storage:link

# 6. Verify .env is set up
php artisan key:generate  # Only if APP_KEY is missing
```

## .env File Requirements

Ensure your `.env` file (in project root) contains:

```
APP_NAME=EduTrail
APP_ENV=local              # Use 'local' to enable debug endpoints
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_KEY=base64:...         # Should be auto-generated

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=edutrail       # Match your DB name
DB_USERNAME=root           # Match your DB user
DB_PASSWORD=               # Set your DB password if needed

SESSION_DRIVER=cookie      # Or 'database'/'redis'
CACHE_DRIVER=file

MAIL_MAILER=log
```

## File Structure Verification

After applying patches, verify these files exist and are not empty:

```
✓ app/Http/Controllers/ProjectController.php      (>150 lines, has CRUD + helpers)
✓ app/Http/Controllers/StorageController.php      (>100 lines, has upload + logging)
✓ app/Http/Controllers/DebugController.php        (NEW, >100 lines)
✓ app/Models/Project.php                          (has $fillable and $casts)
✓ routes/api.php                                  (has project routes + debug routes)
✓ config/cors.php                                 (supports_credentials = true, allowed origins include localhost)
✓ database/migrations/*projects*                  (2 migrations for projects table)
✓ DEBUGGING_GUIDE.md                             (NEW, comprehensive guide)
```

Verify with:
```bash
wc -l app/Http/Controllers/ProjectController.php
grep -c "public function" app/Http/Controllers/ProjectController.php  # Should be >= 8
grep "fillable" app/Models/Project.php
```

## Database Verification

Connect to MySQL and verify the `projects` table schema:

```bash
mysql -u root
> USE edutrail;
> DESCRIBE projects;
```

Expected columns:
- id, created_at, updated_at (auto)
- user_id, description, additional_notes
- due_date, due_time, steps (JSON), image_url

## Route Verification

List all registered routes:

```bash
php artisan route:list --name=projects
php artisan route:list --name=debug
php artisan route:list --path=api/projects
```

Expected output should include:
```
POST      api/projects
GET       api/projects
GET       api/projects/count
GET       api/projects/latest
GET       api/projects/summary
GET       api/debug/logs
POST      api/debug/projects/create-for-user
GET       api/debug/projects/list-for-user
```

## Run Tests

```bash
# Unit tests for Project controller
php artisan test tests/Unit/ProjectControllerTest.php

# All tests in Feature
php artisan test tests/Feature
```

## Start Development Server

```bash
# Terminal 1: Run Laravel dev server
php artisan serve

# Terminal 2 (optional): Watch logs in real-time
tail -f storage/logs/laravel.log
```

Server will be at: `http://localhost:8000`

## Quick Smoke Test

After server is running, test one endpoint:

```bash
# Create a session first
curl -b cookies.txt -c cookies.txt \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}' \
  http://localhost:8000/api/auth/sign-in

# Then get project count (should work with session)
curl -b cookies.txt http://localhost:8000/api/projects/count
```

Or use the debug endpoint (no auth needed):

```bash
curl http://localhost:8000/api/debug/logs
```

Should return the last 50 lines of `storage/logs/laravel.log`.

## Common Issues & Fixes

| Issue | Fix |
|-------|-----|
| `SQLSTATE[HY000] [1045] Access denied` | Update `.env` DB_USERNAME and DB_PASSWORD |
| `No such table: projects` | Run `php artisan migrate` |
| `storage/app/public` doesn't exist | Run `php artisan storage:link` |
| `public/storage` is not a symlink | Run `php artisan storage:link --force` |
| Routes not loading | Run `php artisan route:cache` then `route:clear` |
| Can't connect to MySQL | Start MySQL: `mysqld` or `services.msc` (Windows) |

## Success Criteria

Your setup is ready when:

- [ ] All PHP files lint without errors
- [ ] Routes include `GET /api/projects/count`, `POST /api/projects` with file support
- [ ] Projects table has all columns (user_id, image_url, steps, etc.)
- [ ] `/debug/logs` returns server logs
- [ ] `/api/projects/count` returns JSON (e.g., `{count: 5}`) when authenticated
- [ ] POST `/api/projects` with file creates project AND stores image
- [ ] Request/response headers include session cookie

## Next: Frontend Integration

Once backend passes all checks, ensure frontend:

1. Sends cookies: `axios.defaults.withCredentials = true;`
2. Reads response shape: `json.publicUrl ?? json.data?.publicUrl`
3. Refreshes tracker: `GET /api/projects/count` after creating project

See `DEBUGGING_GUIDE.md` for full frontend integration steps.

