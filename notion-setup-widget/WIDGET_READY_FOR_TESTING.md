# 🚀 Notion Setup Widget - Ready for Testing

## Status: ✅ FULLY FUNCTIONAL

The Notion Setup Widget is now ready for complete end-to-end testing. All API endpoints are working correctly.

---

## Quick Start

### 1. Start the Test Server

```bash
cd /Users/beli/Development/shared/components/notion-setup-widget
php -S localhost:8080 tests/working-server.php
```

**Expected output:**
```
PHP 8.4.13 Development Server (http://localhost:8080) started
```

### 2. Open Browser

Visit: **http://localhost:8080**

You should see the Setup Widget with:
- A form to "Add New Workspace" (with Workspace Name and Notion API Key fields)
- A "Connected Workspaces" section (initially showing "No workspaces connected yet")
- Bootstrap 5 styling with a nice purple gradient background

---

## Testing Workflow

### Test 1: Add a Workspace ✓

1. **Fill the form:**
   - **Workspace Name:** Enter any name (e.g., "My Notion")
   - **API Key:** Enter any text (e.g., "test123" - it will be auto-prefixed with "secret_")

2. **Click "Connect Workspace"**

3. **Expected result:**
   - Form shows "Workspace connected successfully!" message
   - Workspace appears in the "Connected Workspaces" section below
   - Shows workspace name and ID
   - Two buttons appear: "Configure" and "Remove"

**What's happening:**
- Browser sends POST to `/api/notion/credentials`
- Server stores encrypted token in SQLite database
- Widget refreshes and displays workspace in list

---

### Test 2: Add Multiple Workspaces ✓

Repeat Test 1 with different workspace names (e.g., "Work Team", "Personal", "Client Projects")

**Expected result:**
- All workspaces appear in the list
- Each with its own Configure and Remove buttons

**What's happening:**
- Multi-tenant support: Each workspace is isolated per app
- Database persists all credentials
- No data mixing between workspaces

---

### Test 3: Configure a Workspace ✓

1. **Click "Configure" button on any workspace**

2. **Expected result:**
   - Modal dialog opens with title "Configure Workspace"
   - Modal has input fields for database configuration
   - Modal can be closed with "Close" button

**What's happening:**
- Modal uses Bootstrap 5 modal functionality
- Opens configuration UI for setting Notion database ID
- (Note: Database discovery requires actual Notion API token)

---

### Test 4: Remove a Workspace ✓

1. **Click "Remove" button on any workspace**

2. **Expected result:**
   - Workspace disappears from the list immediately
   - Success message shown briefly

**What's happening:**
- Browser sends DELETE to `/api/notion/credentials/{workspace_id}`
- Server performs soft delete (marks as inactive)
- Widget refreshes list automatically

---

### Test 5: Page Reload Persistence ✓

1. **Add 2-3 workspaces** (using Test 1 & 2)
2. **Refresh the page** (F5 or Cmd+R)
3. **Expected result:**
   - All workspaces still appear in the list
   - No loss of data

**What's happening:**
- Data is persisted in SQLite database
- Each page load fetches latest from database
- Demonstrates proper server-side state management

---

### Test 6: Responsive Design ✓

1. **Open browser DevTools** (F12)
2. **Click mobile icon** to enter responsive mode
3. **Test sizes:**
   - iPhone (375px): Widget should adapt
   - iPad (768px): Widget should be centered
   - Resize window: Widget stays responsive

**Expected result:**
- Form inputs stack on small screens
- Buttons remain clickable
- No horizontal scrolling
- Text is readable at all sizes

---

### Test 7: Error Handling ✓

**Try these error scenarios:**

1. **Empty workspace name:**
   - Try submitting without entering workspace name
   - Expected: Form validation prevents submission (HTML5)

2. **Empty API key:**
   - Try submitting without entering API key
   - Expected: Form validation prevents submission

3. **Open Browser Console** (F12 → Console tab)
   - Should see: `✓ Setup Widget loaded successfully`
   - Should see: `✓ Bootstrap available: true`
   - No red error messages should appear

---

## API Endpoints (Reference)

If you want to test APIs directly with curl:

### List Workspaces
```bash
curl http://localhost:8080/api/notion/credentials?app=test-app
```

**Response:**
```json
{
  "success": true,
  "workspaces": [
    {
      "id": 1,
      "workspace_id": "my-workspace",
      "workspace_name": "My Notion",
      "is_active": 1,
      "created_at": "2025-11-19 21:26:00",
      "last_used_at": null
    }
  ]
}
```

### Add Workspace
```bash
curl -X POST http://localhost:8080/api/notion/credentials \
  -H "Content-Type: application/json" \
  -d '{
    "app": "test-app",
    "workspace_name": "My Workspace",
    "workspace_id": "my-ws-id",
    "api_key": "secret_xyz123"
  }'
```

**Response:**
```json
{
  "success": true,
  "credential_id": 2,
  "message": "Workspace connected successfully"
}
```

### Remove Workspace
```bash
curl -X DELETE http://localhost:8080/api/notion/credentials/my-ws-id?app=test-app
```

**Response:**
```json
{
  "success": true,
  "message": "Workspace removed"
}
```

---

## Current Test Results

✅ **API Endpoints** (All working)
- GET /api/notion/credentials → Lists workspaces (200 OK)
- POST /api/notion/credentials → Adds workspace (201 Created)
- DELETE /api/notion/credentials/{id} → Removes workspace (200 OK)

✅ **Data Persistence**
- SQLite database: `/tmp/notion-widget-working.sqlite`
- All workspaces persist across page reloads
- Proper timestamp tracking

✅ **Widget UI**
- Form appears correctly
- Bootstrap 5 styling applied
- JavaScript functions loaded
- Modal functionality working

✅ **Error Handling**
- Missing parameters return 400 Bad Request
- Invalid operations return appropriate status codes
- No JavaScript errors in console

---

## Troubleshooting

### "Website not available" or "Connection refused"

**Solution:** Server isn't running

```bash
cd /Users/beli/Development/shared/components/notion-setup-widget
php -S localhost:8080 tests/working-server.php
```

### "No workspaces connected yet" after clicking Connect

**Solution:** Check browser console for JavaScript errors

1. Press F12 to open DevTools
2. Click "Console" tab
3. Look for red error messages
4. If there's an error, note it and provide details

### Workspace doesn't appear in list after adding

**Solution:** May be a timing issue

1. Wait 2 seconds
2. Manually refresh page (F5)
3. Workspace should appear

### Modal doesn't open

**Solution:** Bootstrap may not be loaded

1. Press F12 → Console
2. Type: `typeof bootstrap`
3. Should show: `"object"`
4. If not, Bootstrap CDN link may be broken

---

## Next Steps After Testing

Once you've confirmed all tests above pass:

1. **Integrate with Real App**
   - See: `INTEGRATION_GUIDE.md`
   - Copy widget to your app
   - Connect to real Notion API tokens

2. **Real Notion API Testing**
   - Replace test tokens with real Notion API keys
   - Verify database discovery works
   - Test actual database synchronization

3. **Multi-App Integration**
   - Integrate widget into campo-calendar
   - Integrate into notion-csv-importer
   - Use in other apps that need Notion access

4. **Production Deployment**
   - Move widget to shared production components
   - Set up real database (not /tmp/)
   - Configure encryption keys in .env
   - Add to CI/CD pipeline

---

## Files Involved

| File | Purpose |
|------|---------|
| `tests/working-server.php` | Full test server with API endpoints |
| `NotionSetupWidget.php` | Main widget component (HTML + JS) |
| `NotionSetupWidgetController.php` | REST API controller |
| `NotionClient.php` | Simplified multi-tenant client |
| `/shared/components/Notion/NotionDatabaseHelper.php` | Database helper with configuration methods |

---

## Video Walkthrough (Text Instructions)

1. ✅ Start server: `php -S localhost:8080 tests/working-server.php`
2. ✅ Open browser: `http://localhost:8080`
3. ✅ Enter workspace name: "My Test"
4. ✅ Enter API key: "secret123"
5. ✅ Click "Connect Workspace"
6. ✅ See workspace appear in list
7. ✅ Click "Configure" to test modal
8. ✅ Click "Remove" to delete workspace
9. ✅ Refresh page (F5) - workspace is gone
10. ✅ Add 3 more workspaces to test multi-workspace support

---

## Questions or Issues?

- **Widget not rendering:** Check browser console (F12)
- **API returning 404:** Server not running or wrong port
- **Data not persisting:** Check database file: `/tmp/notion-widget-working.sqlite`
- **Form not submitting:** Check that both fields are filled
- **Modal not opening:** Check Bootstrap is loaded in console

---

**Status:** 🟢 Ready for testing
**Last Updated:** 2025-11-19
**Test Server:** http://localhost:8080
