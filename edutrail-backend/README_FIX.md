# EduTrail Backend - Project Tracker Fix

## Quick Overview

**Problem Fixed**: Projects added via UI showed "Success" but tracker remained at 0, and image uploads sometimes failed.

**Solution Implemented**:
- ✅ Complete CRUD for projects with `user_id` association (fixes tracker count)
- ✅ File upload in project creation (single request with image)
- ✅ Standardized JSON responses for frontend compatibility (fixes undefined `publicUrl`)
- ✅ Debug endpoints for troubleshooting (no auth required)
- ✅ Comprehensive logging on key operations

**Status**: Ready to test and deploy

---

## Quick Start (3 Steps)

### 1. Verify Your Setup
**Windows (PowerShell)**:
```powershell
.\verify.ps1
```

**Linux/Mac (Bash)**:
```bash
bash verify.sh
```

### 2. Run Migrations & Start Server
```bash
php artisan migrate --force
php artisan storage:link
php artisan serve
```

### 3. Test One Endpoint
```bash
curl http://localhost:8000/api/debug/logs | head -5
```

Expected: JSON with `data.lines` array containing log entries.

---

## What Was Changed

### Files Created
| File | Purpose |
|------|---------|
| `app/Http/Controllers/DebugController.php` | Debug endpoints (local-only): logs, create project, list projects |
| `DEBUGGING_GUIDE.md` | Full cURL examples and endpoint reference |
| `SETUP_VERIFICATION.md` | Environment setup checklist |
| `FIX_SUMMARY.md` | Complete verification steps (8-step detailed guide) |
| `tests/Unit/ProjectControllerTest.php` | Unit tests for CRUD operations |
| `verify.sh` / `verify.ps1` | Quick verification scripts |

### Files Modified
| File | Changes |
|------|---------|
| `app/Http/Controllers/ProjectController.php` | Implemented full CRUD + helpers (count, latest, summary) |
| `app/Http/Controllers/StorageController.php` | Enhanced upload response; added logging |
| `app/Models/Project.php` | Added `$fillable` and `$casts` for mass assignment |
| `routes/api.php` | Added project helpers + debug routes |
| `config/cors.php` | Allow local dev origins + credentials |

---

## Testing Your Fix

### Easiest: Run the Full Verification (FIX_SUMMARY.md)
See `FIX_SUMMARY.md` for step-by-step 5-step verification that takes ~10 minutes.

### Quick: Test Tracker Count Fix
```bash
# 1. Start server
php artisan serve &

# 2. Authenticate
curl -b cookies.txt -c cookies.txt \
  -X POST -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}' \
  http://localhost:8000/api/auth/sign-in

# 3. Get initial count (should be 0 or current)
curl -b cookies.txt http://localhost:8000/api/projects/count

# 4. Create a project
curl -b cookies.txt -X POST -H "Content-Type: application/json" \
  -d '{"description":"Test","due_date":"2026-02-20"}' \
  http://localhost:8000/api/projects

# 5. Check count again (should increment!)
curl -b cookies.txt http://localhost:8000/api/projects/count
# Expected: {"data":{"count":1},"error":null}
```

### With File: Test Upload Fix
```bash
# Create a test image (or use a real one)
dd if=/dev/zero of=test.png bs=1024 count=10  # 10KB file

# Upload with project
curl -b cookies.txt -F "description=Test" -F "file=@test.png" \
  http://localhost:8000/api/projects

# Expected: Response includes "image_public_url" and "imagePublicUrl"
```

---

## Endpoint Reference

### Projects CRUD
- `GET /api/projects` — List all user's projects
- `POST /api/projects` — Create project (can include `file`)
- `GET /api/projects/{id}` — Get one project
- `PUT /api/projects/{id}` — Update project
- `DELETE /api/projects/{id}` — Delete project

### Project Helpers
- `GET /api/projects/count` — **Returns `{count: N}` for tracker**
- `GET /api/projects/latest` — Latest 5 projects
- `GET /api/projects/summary` — Counts + latest

### File Upload
- `POST /api/storage/edutrail/upload` — Upload image (returns `publicUrl`)

### Debug (Local Only, No Auth)
- `GET /api/debug/logs` — Tail of laravel.log
- `POST /api/debug/projects/create-for-user` — Create project for user_id
- `GET /api/debug/projects/list-for-user` — List projects for user_id

See `DEBUGGING_GUIDE.md` for full cURL examples.

---

## Frontend Integration

### 1. Send Cookies
```javascript
// Axios
axios.defaults.withCredentials = true;

// or Fetch
fetch('/api/projects', {credentials: 'include'})
```

### 2. Read Response Safely
```javascript
// Handle both old and new response shapes
const publicUrl = response.publicUrl ?? response.data?.publicUrl;
```

### 3. Get Tracker Count
```javascript
const response = await fetch('/api/projects/count', {credentials: 'include'});
const {count} = await response.json().data;
document.querySelector('[tracker]').textContent = count;
```

---

## Troubleshooting

| Error | Check |
|-------|-------|
| `Cannot read properties of undefined (reading 'publicUrl')` | Use safe access: `response.publicUrl ?? response.data?.publicUrl` |
| Tracker shows 0 | Send cookies; check `/auth/user` returns authenticated user |
| Upload returns 500 | Check `/debug/logs` for errors; ensure `storage/app/public` writable |
| SQLSTATE[1045] Access denied | Update `.env` with correct `DB_USERNAME` and `DB_PASSWORD` |
| Projects not showing up | Authenticate first; verify `user_id` is set when you create a project |

See `DEBUGGING_GUIDE.md` → "Common Errors & Solutions" for detailed troubleshooting.

---

## Verification Checklist

Before deploying, verify:

- [ ] All PHP files lint without errors: `php -l app/Http/Controllers/ProjectController.php`
- [ ] Routes include `/api/projects/count` and project helpers
- [ ] Projects table has `user_id`, `image_url`, `steps` columns
- [ ] `POST /api/projects` accepts `file` parameter
- [ ] `/api/projects/count` returns `{count: N}` when authenticated
- [ ] Tracker increments after creating a project
- [ ] File uploads store to `storage/app/public/edutrail/`
- [ ] Response includes `publicUrl` (both top-level and nested)
- [ ] Frontend sends cookies (axios `withCredentials` or fetch `credentials: 'include'`)
- [ ] Unit tests pass: `php artisan test tests/Unit/ProjectControllerTest.php`

---

## Files to Read

1. **`FIX_SUMMARY.md`** ← Start here! Complete 8-step verification guide
2. **`DEBUGGING_GUIDE.md`** — Full API reference with cURL examples
3. **`SETUP_VERIFICATION.md`** — Environment setup and troubleshooting
4. **`verify.ps1`** or **`verify.sh`** — Quick automated checks

---

## What Changed & Why

| Component | Before | After | Why |
|-----------|--------|-------|-----|
| Project creation | No `user_id` set | `user_id = Auth::user()->id` | Fixes tracker count = 0 |
| File upload | Separate request | Inline in project POST | Fixes upload timing issues |
| Upload response | Missing `publicUrl` | Includes both top-level + nested | Fixes undefined frontend errors |
| Logging | Minimal | Full payload logging | Enables debugging |
| CORS | Strict | Allow local dev | Fixes browser cors blocks |
| Debug endpoints | None | 3 new local endpoints | Enables troubleshooting |

---

## Common Questions

**Q: Why do I need to send cookies?**  
A: Projects must be linked to the authenticated user. This requires the session cookie.

**Q: Can I create projects without authentication?**  
A: No, production endpoints require auth. Use `/debug/projects/create-for-user` for local testing (only in `APP_ENV=local`).

**Q: Why is the response shape different now?**  
A: To handle both old frontend expectations (top-level fields) and new (nested in `data`). **Always check for `data` wrapper first.**

**Q: How do I disable debug endpoints?**  
A: Change `.env` to `APP_ENV=production` or modify `DebugController` to add auth checks.

**Q: Where are uploaded images stored?**  
A: `storage/app/public/edutrail/` (public disk). Served as `http://localhost:8000/storage/edutrail/...` after running `php artisan storage:link`.

---

## Support

If issues persist:

1. Run `verify.ps1` or `verify.sh` to check setup
2. Check `/debug/logs` for server errors
3. Run `php artisan route:list --path=api/projects` to verify routes
4. Share the output of `/debug/logs` endpoint
5. Share Network tab (F12) request/response from the failing operation

---

## Deployment Checklist

- [ ] `.env` has correct `DB_*` credentials and `APP_KEY`
- [ ] `php artisan migrate` completed without errors
- [ ] `php artisan storage:link` created symlink
- [ ] `public/storage/` is writable (or `storage/app/public/`)
- [ ] Frontend sends cookies: `axios.defaults.withCredentials = true;`
- [ ] Frontend reads response safely: `response.publicUrl ?? response.data?.publicUrl`
- [ ] Tracker calls `/api/projects/count` after project creation
- [ ] Tests pass: `php artisan test`
- [ ] Server logs monitored: `tail -f storage/logs/laravel.log`

---

**Status**: ✅ Ready for testing. Follow FIX_SUMMARY.md for step-by-step verification.

