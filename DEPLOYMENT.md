# Deploy Diet Calculator to Render.com (FREE)

This guide will help you deploy your diet calculator application to Render.com for free using SQLite (no database expiration issues!).

## Prerequisites

1. GitHub account
2. Render.com account (free)
3. Your Elytica API token

## Why SQLite?

This deployment uses SQLite instead of PostgreSQL because:
- ✅ No 30-day expiration (Render's free PostgreSQL expires monthly)
- ✅ Simpler setup - no separate database service needed
- ✅ Sufficient for this application's needs
- ✅ Truly free forever

## Step 1: Prepare Your Application

### 1.1 Create a build script

Create `build.sh` in your project root:

```bash
#!/usr/bin/env bash

# Exit on error
set -o errexit

# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies and build assets
npm ci
npm run build

# Clear and cache Laravel config
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Run migrations
php artisan migrate --force

# Seed the database with real food prices
php artisan db:seed --class=RealFoodPricesSeeder --force
```

Make it executable:
```bash
chmod +x build.sh
```

### 1.2 Create Render configuration

Create `render.yaml` in your project root:

```yaml
services:
  - type: web
    name: diet-calculator
    env: php
    region: oregon # or 'frankfurt' for EU
    plan: free
    buildCommand: "./build.sh"
    startCommand: "php artisan serve --host=0.0.0.0 --port=$PORT"
    envVars:
      - key: APP_NAME
        value: DietCalculator
      - key: APP_ENV
        value: production
      - key: APP_DEBUG
        value: false
      - key: APP_KEY
        generateValue: true
      - key: ELYTICA_TOKEN
        sync: false # Will set manually in Render dashboard
      - key: ELYTICA_APPLICATION_ID
        value: 14
      - key: DB_CONNECTION
        value: sqlite
      - key: DB_DATABASE
        value: /opt/render/project/src/database/database.sqlite
      - key: SESSION_DRIVER
        value: file
      - key: CACHE_DRIVER
        value: file
      - key: QUEUE_CONNECTION
        value: sync
```

**Note:** No database service needed - SQLite runs in the same container!

### 1.3 Update .gitignore

Make sure these are NOT ignored (comment them out if present):

```gitignore
# /vendor  # Comment this out - Render needs to see composer.lock
# /node_modules  # Keep this ignored, will install on Render
```

### 1.4 Database configuration

No changes needed! Your app already supports SQLite. The `config/database.php` file automatically uses SQLite when `DB_CONNECTION=sqlite` is set.

## Step 2: Push to GitHub

1. Initialize git repository (if not already):
```bash
git init
git add .
git commit -m "Prepare for Render deployment"
```

2. Create a new repository on GitHub

3. Push your code:
```bash
git remote add origin https://github.com/YOUR_USERNAME/diet-plan.git
git branch -M main
git push -u origin main
```

## Step 3: Deploy on Render

1. **Sign up at [render.com](https://render.com)** (free account)

2. **Create New Web Service:**
   - Click "New +" → "Web Service"
   - Connect your GitHub account
   - Select your `diet-plan` repository

3. **Configure the service:**
   - **Name:** `diet-calculator` (or your choice)
   - **Region:** Select closest to your users
   - **Branch:** `main`
   - **Build Command:** `./build.sh`
   - **Start Command:** `php artisan serve --host=0.0.0.0 --port=$PORT`
   - **Plan:** Select **"Free"**

4. **Add Environment Variables:**
   Click "Advanced" → "Add Environment Variable":

   ```
   APP_NAME=DietCalculator
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://YOUR-APP-NAME.onrender.com

   ELYTICA_TOKEN=your_actual_token_here
   ELYTICA_APPLICATION_ID=14

   SESSION_DRIVER=file
   CACHE_DRIVER=file
   QUEUE_CONNECTION=sync
   ```

5. **Generate APP_KEY:**
   - Render will auto-generate this, OR
   - Generate locally: `php artisan key:generate --show`
   - Add as environment variable

6. **Deploy:**
   - Click "Create Web Service"
   - Wait for build (5-10 minutes first time)
   - SQLite database will be created automatically during build!

## Step 4: Verify Deployment

1. Once deployed, visit your app URL: `https://your-app-name.onrender.com`

2. Test the diet calculator with all three diet types

3. Monitor logs in Render dashboard if any issues occur

## Important Notes

### Free Tier Limitations:
- ⚠️ **Spins down after 15 min of inactivity** (first request after inactivity takes 15-30 seconds)
- 512MB RAM limit
- 750 hours/month free (sufficient for personal projects)

### Keep Your App Awake (Optional):
Use a free service like [UptimeRobot](https://uptimerobot.com) to ping your app every 5 minutes:
- Sign up at uptimerobot.com
- Add new monitor
- URL: `https://your-app-name.onrender.com`
- Interval: 5 minutes

### Auto-Deploy:
- Every git push to `main` branch will auto-deploy
- Great for continuous updates!

## Troubleshooting

### Build fails:
- Check Render logs
- Ensure `composer.lock` is committed
- Verify `build.sh` is executable

### Database errors:
- Check that SQLite database was created during build
- Ensure migrations ran successfully in build logs
- Verify `DB_CONNECTION=sqlite` is set

### Asset loading issues:
- Run `npm run build` before committing
- Ensure Vite builds correctly locally first

### Sessions not persisting:
- Change `SESSION_DRIVER=database` in .env
- Run: `php artisan session:table`
- Run migrations again

## Alternative: If Render doesn't work

If you encounter issues, try **Fly.io**:
- More reliable for always-on apps
- Requires credit card but won't charge in free tier
- Guide: https://fly.io/docs/laravel/

---

**Need help?** Check Render's Laravel documentation: https://render.com/docs/deploy-laravel
