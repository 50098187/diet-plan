# Diet Plan Calculator - Local Setup Guide

This application is designed for **local hosting only**. No cloud deployment needed!

## Quick Start

You have **two options** for running this application locally:

### Option 1: Laravel Herd (Recommended - Already Installed!)
You're already using Laravel Herd, so your setup is complete!

1. **Your site is already running at:**
   - `http://diet-plan.test` (or similar)
   - Check your Herd dashboard for the exact URL

2. **Database setup:**
   ```bash
   php artisan migrate:fresh
   php artisan db:seed
   ```

3. **That's it!** Visit your site in the browser.

### Option 2: XAMPP

If you prefer XAMPP:

1. **Copy project to XAMPP:**
   ```bash
   # Copy this folder to:
   C:\xampp\htdocs\diet-plan
   ```

2. **Configure environment:**
   ```bash
   # Copy .env.example to .env
   copy .env.example .env

   # Generate app key
   php artisan key:generate
   ```

3. **Setup database:**
   ```bash
   # Database is SQLite - no configuration needed!
   php artisan migrate:fresh
   php artisan db:seed
   ```

4. **Start XAMPP:**
   - Start Apache from XAMPP Control Panel
   - Visit: `http://localhost/diet-plan/public`

## Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and NPM
- SQLite (usually included with PHP)

## Installation Steps (Fresh Setup)

### 1. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 2. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Configure Elytica API

Edit `.env` and add your Elytica token:

```env
ELYTICA_TOKEN=your_token_here
ELYTICA_APPLICATION_ID=14
ELYTICA_MODEL_PATH=app/Services/model.hlpl
```

### 4. Database Setup

The application uses **SQLite** - no database server needed!

```bash
# Run migrations
php artisan migrate:fresh

# Seed the database with NAMC food basket data
php artisan db:seed
```

**Important:** Update the CSV path in `database/seeders/FoodSeeder.php` (line 20):
```php
$csvPath = 'D:\4de jaar 1ste semester\INDE 471\Milestone 6\NAMC foodbasket data and nutritional info.csv';
```

### 5. Build Frontend Assets

```bash
npm run build
```

For development with hot reload:
```bash
npm run dev
```

### 6. Start the Application

#### With Laravel Herd:
Already running! Check your Herd dashboard.

#### With Laravel's built-in server:
```bash
php artisan serve
```
Visit: `http://localhost:8000`

#### With XAMPP:
- Start Apache in XAMPP
- Visit: `http://localhost/diet-plan/public`

## Features

- **Monthly Diet Optimization** - 30-day meal plans
- **Budget-Constrained** - R2,619/month maximum
- **NAMC Food Basket** - Real South African food prices
- **Diet Preferences** - Normal, Vegetarian, Vegan
- **Goal-Based** - Lose fat, maintain weight, or gain muscle
- **Hybrid Integer Goal Programming** - Academic formulation

## Directory Structure

```
diet-plan/
├── app/
│   ├── Http/Controllers/DietPlanController.php
│   ├── Models/Food.php
│   └── Services/
│       ├── ElyticaService.php
│       └── model.hlpl (HLPL optimization model)
├── database/
│   ├── migrations/
│   │   └── 2026_08_10_000001_create_foods_table.php
│   ├── seeders/
│   │   └── FoodSeeder.php
│   └── database.sqlite (created automatically)
├── resources/
│   └── views/diet-calculator.blade.php
└── .env (your configuration)
```

## Troubleshooting

### "No food data found" error:
```bash
# Make sure the CSV path is correct in FoodSeeder.php
# Then run:
php artisan db:seed --class=FoodSeeder
```

### SQLite database file missing:
```bash
# The file is auto-created, but if needed:
touch database/database.sqlite
php artisan migrate:fresh
```

### Assets not loading:
```bash
# Rebuild assets:
npm run build

# Or for development:
npm run dev
```

### Port already in use (Laravel serve):
```bash
# Use a different port:
php artisan serve --port=8001
```

### XAMPP "Access forbidden":
- Make sure your project is in `C:\xampp\htdocs\`
- Access via `http://localhost/diet-plan/public`

## Database Schema

**foods** table:
- `id` - Primary key
- `category` - Food category (Beans, Dairy, etc.)
- `product` - Product name
- `price_per_unit` - Price in Rands (R)
- `protein` - Protein content (g)
- `carbs` - Carbohydrate content (g)
- `fat` - Fat content (g)
- `energy_kj` - Energy in kilojoules (kJ)

## Usage

1. Visit the application in your browser
2. Enter your personal details:
   - Weight, height, age, gender
   - Activity level
   - Goal (lose/maintain/gain)
   - Diet preference
3. Click "Optimise My Diet Plan"
4. Wait for Elytica to compute the optimal monthly meal plan
5. View your personalized, budget-optimized results!

## Technical Details

- **Framework:** Laravel 11
- **Database:** SQLite (no server needed)
- **Frontend:** Tailwind CSS, Alpine.js
- **Optimization:** Elytica HLPL (Hybrid Logic Programming Language)
- **Model:** Goal Programming with weighted deviations

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review Laravel documentation: https://laravel.com/docs
3. Check Elytica documentation: https://elytica.com/docs

---

**Version 2.0.0** - Local Hosting Only
