# Frontend Fixes - Supabase → Laravel REST API

## Problem Identified
Your frontend was written for **Supabase** but the backend is **Laravel REST API**. They're completely incompatible:

| What Frontend Was Trying | What It Found | Result |
|---|---|---|
| `api.from('projects').select()` | Not a function (Supabase syntax) | ❌ `api.from(...).select(...).eq is not a function` |
| `api.auth.getSession()` | Not a function (Supabase auth) | ❌ `Cannot read properties of undefined (reading 'email')` |
| `api.storage.from('edutrail').upload()` | Not a function (Supabase storage) | ❌ `Cannot read properties of undefined (reading 'publicUrl')` |

## What I Fixed

### 1. `src/stores/projects.js` ✅
Changed from Supabase to REST API:

**Before (Supabase)**:
```javascript
const { data, error } = await api.from('projects').select('*').eq('user_id', userId)
```

**After (REST API)**:
```javascript
const response = await apiClient.get('/projects')
```

**Key Changes**:
- Replaced all `api.from('projects').select()...` with REST GET/POST/PUT/DELETE
- Image upload now uses `POST /api/storage/edutrail/upload`
- Reads response from `response.data.data.projects` (JSON structure from backend)
- **CRITICAL**: Added `withCredentials: true` to axios config so session cookies are sent

### 2. `src/stores/authUser.js` ✅
Changed from Supabase Auth to Laravel Session:

**Before (Supabase)**:
```javascript
const { data, error } = await api.auth.getSession()
```

**After (Laravel REST API)**:
```javascript
const response = await apiClient.get('/auth/user')
```

**Key Changes**:
- `isAuthenticated()` now calls `GET /api/auth/user` (checks session cookie)
- `login()` now calls `POST /api/auth/sign-in`
- `logout()` now calls `POST /api/auth/sign-out`
- `updateUserInformation()` now calls `POST /api/auth/update`
- **CRITICAL**: Added `withCredentials: true` so cookies are sent with every request

### 3. Configuration
Set up axios to always send cookies:
```javascript
const apiClient = axios.create({
  baseURL: API_BASE,
  withCredentials: true,  // THIS IS CRITICAL!
})
```

This allows Laravel to:
1. Receive the session cookie
2. Identify which user is making the request
3. Return user-specific data (projects, user info, etc.)

## Next Steps

1. **Verify .env has correct API_BASE**:
   ```
   VITE_API_BASE=http://localhost:8000/api
   ```
   (Check your `.env` file in the frontend root)

2. **Stop and restart your frontend dev server**:
   ```bash
   npm run dev  # or: npm run build
   ```

3. **Test the flow**:
   - Try to login
   - Create a project
   - Check if tracker shows the count
   - Upload an image with project

## If Still Having Issues

1. **Check browser cookies** (F12 → Application → Cookies):
   - Should see `XSRF-TOKEN` and `laravel_session` cookies
   - If not, the backend isn't setting them → session problem

2. **Check Network tab** (F12 → Network):
   - Request should have `Cookie:` header
   - Response should have session cookies
   - Upload response should include `publicUrl` field

3. **Check backend logs**:
   ```bash
   curl http://localhost:8000/api/debug/logs
   ```

4. **Test backend directly** (to isolate frontend issues):
   ```bash
   curl -b cookies.txt -c cookies.txt \
     -X POST -H "Content-Type: application/json" \
     -d '{"email":"test@example.com","password":"password"}' \
     http://localhost:8000/api/auth/sign-in
   
   curl -b cookies.txt http://localhost:8000/api/projects/count
   ```

## API Endpoints Your Frontend Now Uses

| Operation | Endpoint | Method |
|-----------|----------|--------|
| Get projects | `/api/projects` | GET |
| Create project | `/api/projects` | POST |
| Update project | `/api/projects/{id}` | PUT |
| Delete project | `/api/projects/{id}` | DELETE |
| Upload image | `/api/storage/edutrail/upload` | POST |
| Get user | `/api/auth/user` | GET |
| Login | `/api/auth/sign-in` | POST |
| Logout | `/api/auth/sign-out` | POST |
| Update user | `/api/auth/update` | POST |

All require `withCredentials: true` (already configured in the axios client).

## What's Still Needed (Frontend)

If you have other Vue components using Supabase API (assignments, subjects, etc.), they will need similar fixes:

```bash
# Search for remaining Supabase calls
grep -r "api.from\|api.auth\|api.storage" src/
```

If you find any, share them and I'll fix those too.

---

**The tracker should now work!** Test by:
1. Login
2. Create project
3. Check tracker count increments

If still failing, I'll diagnose using network data and server logs.
