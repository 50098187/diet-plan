# Quick Fix Guide - Elytica Project Setup

## Issue: 500 Error When Creating Diet Plan

**Error:** "Failed to create Elytica project - invalid response from server"

**Root Cause:** The Elytica API's `createNewProject()` returns NULL, likely because your account requires manual project creation through the web interface.

## ✅ SOLUTION (2 minutes)

### Step 1: Create Project Manually on Elytica

1. **Go to:** https://elytica.com
2. **Log in** with your account
3. **Create a new project:**
   - Click "New Project" or "+" button
   - **Name:** `namc-diet-plan` (exactly this)
   - **Description:** NAMC Diet Optimization Project
   - **Application:** Select "HLPL Optimization" or Application ID: 14
   - Click "Create"

4. **Copy the Project ID:**
   - After creation, you'll see the project
   - The Project ID will be visible (e.g., `12345`)
   - Copy this number

### Step 2: Add Project ID to .env

1. **Open:** `C:\Users\simon\Herd\diet-plan\.env`

2. **Find the line:**
   ```
   #ELYTICA_PROJECT_ID=11625
   ```

3. **Replace with:**
   ```
   ELYTICA_PROJECT_ID=<your_project_id>
   ```
   (Replace `<your_project_id>` with the actual number from Step 1)

4. **Save the file**

### Step 3: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
```

### Step 4: Test Again

1. Visit: http://diet-plan.test
2. Fill in your details
3. Click "Optimise My Diet Plan"
4. ✅ Should work now!

---

## Alternative: Use Existing Project

If you already have a project on Elytica:

```bash
# Run this to see your projects:
php artisan tinker
>>> $client = new \Elytica\ComputeClient\ComputeService(env('ELYTICA_TOKEN'));
>>> $projects = $client->getProjects();
>>> foreach($projects as $p) { echo $p->id . " - " . $p->name . "\n"; }
```

Then add any project ID to your `.env`:
```
ELYTICA_PROJECT_ID=<project_id>
```

---

## Why This Happens

Some Elytica accounts require projects to be created through the web interface for security/quota reasons. Once you have a project ID in your `.env`, the app will use it directly without trying to create a new one.

This is actually **better** because:
- ✅ Faster (no API calls to find/create project)
- ✅ More reliable
- ✅ You have full control on Elytica's dashboard

---

## What About Those 28 Foods?

Don't worry! All 28 foods are loaded in your database and ready to use. Once you add the project ID, the optimization will use all of them! 🎉

Check with:
```bash
php artisan tinker
>>> App\Models\Food::count()
```

Should show: **28**
