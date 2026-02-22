# EduTrail Backend Debugging Guide

## Problem Summary
**Issue**: Projects added from the UI show "Success" but:
1. Tracker count remains 0
2. Image uploads occasionally fail with 500 or frontend receives undefined `publicUrl`
3. Network requests fail sporadically

## Root Causes Fixed
✅ **Backend CRUD was missing** – Now implemented with `user_id` association  
✅ **Projects not linked to user** – `ProjectController@store` now sets `user_id` from auth  
✅ **Upload response shape incompatible** – Standardized response includes `publicUrl` at top-level AND `data.publicUrl`  
✅ **Missing file handling in project store** – Can now accept file + project creation in single request  
✅ **CORS blocking local frontend** – Allowed local origins and credentials forwarding  
✅ **Session/auth not forwarded** – Added debug endpoints for local-only testing  

---

## Endpoint Quick Reference

### Project CRUD
- `GET /projects` – List all projects for authenticated user
- `POST /projects` – Create project (optionally with `file` attached)
- `GET /projects/{id}` – Get single project
- `PUT /projects/{id}` – Update project
- `DELETE /projects/{id}` – Delete project

### Project Helpers  
- `GET /projects/count` – Returns `{count: N}` for tracker UI
- `GET /projects/latest` – Returns latest 5 projects for authenticated user
- `GET /projects/summary` – Returns `{total, completed, pending}` stats

### File Upload
- `POST /storage/edutrail/upload` – Upload image file
  - **Returns**: `{path, publicUrl, data: {path, publicUrl}, error}`
  - **Note**: Always checks both top-level and `data.publicUrl` to avoid undefined

### Debug Endpoints (LOCAL ONLY)
- `GET /debug/logs` – Tail of `storage/logs/laravel.log` (last 50 lines)
- `POST /debug/projects/create-for-user` – Create project for any user (local dev only)
  - Body: `{user_id: N, description, additional_notes, due_date, due_time, steps[]}`
- `GET /debug/projects/list-for-user` – List projects for user (local dev only)
  - Query: `?user_id=N`

---

## Testing with cURL

### 1. Check Session / Auth Status
```bash
curl -b cookies.txt -c cookies.txt \
  -H "Content-Type: application/json" \
  http://localhost:8000/api/auth/session
```

### 2. Authenticate (Create Session)
```bash
curl -b cookies.txt -c cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}' \
  http://localhost:8000/api/auth/sign-in
```
**Expected**: User object + session cookie stored in `cookies.txt`

### 3. Create Project with File
```bash
curl -b cookies.txt \
  -F "description=CSC120 Project" \
  -F "due_date=2026-02-20" \
  -F "due_time=19:30" \
  -F "file=@/path/to/image.png" \
  http://localhost:8000/api/projects
```
**Expected**: `{data: {project: {...}, image_public_url: "..."}}`

### 4. Check Project Count
```bash
curl -b cookies.txt \
  http://localhost:8000/api/projects/count
```
**Expected**: `{count: N}` where N matches created projects

### 5. Get Project Summary
```bash
curl -b cookies.txt \
  http://localhost:8000/api/projects/summary
```
**Expected**: `{total: N, completed: M, pending: X}`

### 6. Upload Image Only
```bash
curl -b cookies.txt \
  -F "file=@/path/to/image.png" \
  -F "bucket=edutrail" \
  http://localhost:8000/api/storage/edutrail/upload
```
**Expected**: 
```json
{
  "path": "edutrail/UUID.png",
  "publicUrl": "http://localhost:8000/storage/edutrail/UUID.png",
  "data": {
    "path": "edutrail/UUID.png",
    "publicUrl": "http://localhost:8000/storage/edutrail/UUID.png"
  },
  "error": null
}
```

---

## Debug Endpoints (for Local Testing Only)

### View Server Logs
```bash
curl http://localhost:8000/api/debug/logs
```
Returns last 50 lines of `storage/logs/laravel.log`. Useful for:
- Checking if DB is accessible
- Verifying file saves
- Seeing auth/session errors

### Create Project via Debug
```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 9,
    "description": "Test Project",
    "due_date": "2026-02-20",
    "due_time": "14:00",
    "steps": []
  }' \
  http://localhost:8000/api/debug/projects/create-for-user
```
**Note**: This endpoint **only works in `local` environment** and does NOT require auth. Use to verify DB and project model work.

### List Projects for User
```bash
curl http://localhost:8000/api/debug/projects/list-for-user?user_id=9
```

---

## Frontend Integration Checklist

### 1. Send Cookies with Requests
Ensure your Axios / Fetch calls include credentials:
```javascript
// Axios
axios.defaults.withCredentials = true;
// or per-request:
axios.post('/api/projects', {...}, {withCredentials: true});

// Fetch
fetch('/api/projects', {credentials: 'include', ...})
```

### 2. Handle Upload Response Shape
```javascript
// The upload endpoint now returns BOTH top-level and data-wrapped fields
const response = await fetch('/api/storage/edutrail/upload', {
  method: 'POST',
  body: formData,
  credentials: 'include'
});
const json = await response.json();

// Safe way to get publicUrl (works with both old and new response shapes):
const publicUrl = json.publicUrl ?? json.data?.publicUrl ?? null;
```

### 3. Send Project + Image in One Request
```javascript
const formData = new FormData();
formData.append('description', 'CSC120');
formData.append('additional_notes', 'PASSDUE');
formData.append('due_date', '2026-02-06');
formData.append('due_time', '19:30');
formData.append('steps', JSON.stringify([])); // if needed
formData.append('file', fileInput.files[0]); // optional image

const response = await fetch('/api/projects', {
  method: 'POST',
  body: formData,
  credentials: 'include'
});
const result = await response.json();
console.log(result.data.project);      // The created project
console.log(result.data.image_public_url);  // Image URL (if file was sent)
```

### 4. Refresh Tracker Count
```javascript
const response = await fetch('/api/projects/count', {credentials: 'include'});
const {count} = await response.json();
document.querySelector('[data-tracker-count]').textContent = count;
```

---

## Environment Checklist

- [ ] **MySQL Running** – Verify DB is accessible: `mysql -u root` (or configured user)
- [ ] **Storage Link Created** – Run: `php artisan storage:link` (creates public/storage → storage/app/public symlink)
- [ ] **App Key Set** – Check `.env` has `APP_KEY=base64:...`
- [ ] **APP_ENV** – Set to `local` for debug endpoints; change to `production` to disable them
- [ ] **Session Store** – Set to `cookie` or ensure Redis/Memcached running if using those
- [ ] **.env DB Credentials** – Verify `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

---

## Common Errors & Solutions

| Error | Cause | Solution |
|-------|-------|----------|
| `Cannot read properties of undefined (reading 'publicUrl')` | Frontend expects old response shape | Use `json.publicUrl ?? json.data?.publicUrl` |
| `500 on /storage/edutrail/upload` | File validation or storage path issue | Check logs with `/debug/logs`; verify `storage/app/public` writable |
| `SQLSTATE[HY000] [1045] Access denied` | DB credentials wrong | Check `.env` DB_USERNAME, DB_PASSWORD |
| `Session store not set on request` | Middleware/session config issue | Ensure `web` middleware applies; check session driver in `.env` |
| `0 projects in tracker` | Projects not linked to user | Check `/projects/count` with auth cookies; use `/debug/logs` to see store payload |
| POST `/projects` returns 401 | Not authenticated | Ensure session/token forwarded; test with curl `-b cookies.txt` |

---

## Next Steps

1. **Verify Backend Routes Are Active**
   ```bash
   php artisan route:list --name=projects
   ```

2. **Check Storage Link**
   ```bash
   ls -la public/storage  # or dir public\storage on Windows
   ```

3. **Test Single Endpoint**
   Use the cURL commands above to test one endpoint at a time and check response and logs.

4. **Monitor Server Logs**
   Keep `/debug/logs` endpoint open or tail `storage/logs/laravel.log` to watch for errors in real time.

5. **Run Migrations (if not done)**
   ```bash
   php artisan migrate
   ```

6. **Seed Test Data (optional)**
   ```bash
   php artisan tinker
   >>> App\Models\Project::create([
         'user_id' => 1,
         'description' => 'Test',
         'due_date' => '2026-02-20',
         'due_time' => '14:00',
         'image_url' => null,
         'steps' => []
       ]);
   >>> exit
   ```

---

## Logs to Watch

Location: `storage/logs/laravel.log`

**Success Indicators:**
```
[2026-02-22 11:48:26] storage: StorageController@upload saved file path=edutrail/UUID.png publicUrl=http://localhost:8000/storage/...
[2026-02-22 11:48:31] storage: ProjectController@store called payload={...} has_file=true
```

**Error Indicators:**
```
[2026-02-22 11:42:00] error: SQLSTATE[HY000] [1045] Access denied
[2026-02-22 11:42:10] warning: Session store not set on request
```

If you see these, refer to "Common Errors & Solutions" above.

---

## Support

If issues persist after following this guide:
1. Share the output of `/debug/logs`
2. Share the request/response from the Network tab (F12 → Network)
3. Share the exact steps to reproduce the issue

