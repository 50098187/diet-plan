# Diet Plan Optimizer

A Laravel-based diet plan optimization application that uses mathematical programming to generate optimal monthly meal plans based on user preferences and nutritional requirements.

**Version 2.0.0** - Local Hosting Only

## Features

- **Personalized Monthly Diet Plans**: Calculate optimal meal plans based on:
  - Weight, height, age, gender
  - Activity level (sedentary to extremely active)
  - Goals (lose fat, maintain weight, gain muscle)
  - Diet preferences (normal, vegetarian, vegan)

- **Mathematical Optimization**: Uses Elytica Compute Platform with Hybrid Integer Goal Programming (Formulation 4)

- **NAMC Food Basket**: Real South African food prices and nutritional data from the official NAMC food basket survey

- **Database-Driven**: SQLite database with easy CSV import for food data

- **Budget-Constrained**: Optimizes for R2,619/month (50% of SA minimum wage)

## Quick Start

### For Laravel Herd Users (You!)

Your application is already set up and running! 🎉

1. **Access your application:**
   - Visit `http://diet-plan.test` (or check your Herd dashboard for the URL)

2. **Setup the database:**
   ```bash
   php artisan migrate:fresh
   php artisan db:seed
   ```

3. **Done!** Your application is ready to use.

### For Other Users

See [LOCAL_SETUP.md](LOCAL_SETUP.md) for detailed installation instructions including XAMPP setup.

## Architecture

### Tech Stack

- **Backend**: Laravel 11
- **Frontend**: Tailwind CSS, Alpine.js
- **Database**: SQLite (no server needed!)
- **Optimization**: Elytica HLPL (Hybrid Logic Programming Language)
- **Model**: Hybrid Integer Goal Programming with weighted deviations

### Food Data Source

All food data is stored in a **SQLite database** and loaded from CSV via seeder:
- **Database table**: `foods`
- **CSV Source**: NAMC food basket survey data
- **Seeder**: `FoodSeeder.php` imports from CSV
- **Fields**: category, product, price, protein, carbs, fat, energy_kj

**Benefits:**
- ✓ Easy to query and filter
- ✓ Fast performance with database indexes
- ✓ Simple updates via CSV reimport
- ✓ No cloud database needed - SQLite runs locally

## Installation

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- npm

### Setup Steps

1. **Clone the repository:**
   ```bash
   git clone https://github.com/50098187/diet-plan.git
   cd diet-plan
   ```

2. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure Elytica credentials in `.env`:**
   ```env
   ELYTICA_TOKEN=your_token_here
   ELYTICA_APPLICATION_ID=14
   ELYTICA_MODEL_PATH=app/Services/model.hlpl
   ```

   **Important:** You also need to create a project on Elytica:
   - Go to https://elytica.com and create a new project
   - Name it `namc-diet-plan`
   - Copy the Project ID and add to `.env`:
   ```env
   ELYTICA_PROJECT_ID=your_project_id
   ```
   See `QUICK_FIX.md` for detailed instructions.

5. **Update CSV path in `database/seeders/FoodSeeder.php` (line 20):**
   ```php
   $csvPath = 'path/to/your/NAMC foodbasket data and nutritional info.csv';
   ```

6. **Setup database:**
   ```bash
   php artisan migrate:fresh
   php artisan db:seed
   ```

7. **Build frontend assets:**
   ```bash
   npm run build
   # or for development:
   npm run dev
   ```

8. **Start the server:**
   ```bash
   php artisan serve
   ```

Visit `http://localhost:8000` to use the application.

## Food Data Management

### Viewing Food Data

```bash
php artisan tinker
>>> App\Models\Food::count()
>>> App\Models\Food::all()
```

### Updating Food Data

1. Edit your CSV file with updated prices/nutritional info
2. Re-run the seeder:
   ```bash
   php artisan db:seed --class=FoodSeeder
   ```

### Adding New Foods

Add new rows to your CSV file, then re-run the seeder. The CSV format:
```csv
Category;Product;Price;Protein;Carbs;Fat;Energy_kJ
Beans;Baked beans - tinned 410g;15.43;24.72;67.90;1.19;1562.10
```

## How It Works

1. **User Input**: User enters physical stats, activity level, goal, and diet preference
2. **Data Preparation**: System loads foods from database and filters by diet type
3. **Monthly Calculation**: Calculates 30-day nutritional requirements based on TDEE
4. **Optimization**: Elytica runs Hybrid Integer Goal Programming using HLPL model
5. **Result**: Returns optimal monthly meal plan that:
   - Meets nutritional targets (energy, protein, carbs, fat)
   - Minimizes cost (heavily weighted)
   - Stays within R2,619 budget
   - Respects dietary restrictions
   - Ensures variety (minimum 5 different foods)

## Project Structure

```
diet-plan/
├── app/
│   ├── Http/Controllers/
│   │   └── DietPlanController.php      # Main controller
│   ├── Models/
│   │   └── Food.php                    # Food model
│   └── Services/
│       ├── ElyticaService.php          # Elytica integration
│       └── model.hlpl                  # HLPL optimization model
├── database/
│   ├── migrations/
│   │   └── 2026_08_10_000001_create_foods_table.php
│   ├── seeders/
│   │   ├── DatabaseSeeder.php
│   │   └── FoodSeeder.php             # CSV import
│   └── database.sqlite                 # SQLite database (auto-created)
├── resources/
│   └── views/
│       └── diet-calculator.blade.php   # Main view
├── LOCAL_SETUP.md                       # Detailed setup guide
└── README.md                            # This file
```

## The Optimization Model

The application implements **Formulation 4** from the thesis:

- **Objective**: Minimize weighted sum of deviations
- **Primary Goal**: Minimize cost (ωC ≫ other weights)
- **Constraints**:
  - Budget: ≤ R2,619/month
  - Energy: Monthly energy requirement (kJ)
  - Protein: Monthly protein target
  - Carbs: Monthly carbohydrate target
  - Fat: Monthly fat target
  - Variety: Minimum 5 different foods
  - Servings: Maximum per food item

## Development

### Frontend Development

```bash
npm run dev
```

This starts Vite dev server with hot module replacement.

### Running Tests

```bash
php artisan test
```

## What's New in v2.0.0

🎉 **Major Changes:**

- ✅ **Removed Render deployment** - Local hosting only
- ✅ **Database-driven architecture** - Foods stored in SQLite
- ✅ **Monthly optimization** - Changed from 7-day to 30-day planning
- ✅ **Simplified model** - Hybrid Integer Goal Programming (Formulation 4)
- ✅ **CSV import** - Easy food data management
- ✅ **Budget-constrained** - R2,619/month limit
- ✅ **Diet filtering** - Vegan/vegetarian/normal support

## Troubleshooting

### "No food data found" error:
- Ensure CSV path is correct in `FoodSeeder.php`
- Run: `php artisan db:seed --class=FoodSeeder`

### Database errors:
```bash
# Reset database:
php artisan migrate:fresh
php artisan db:seed
```

### Port already in use:
```bash
php artisan serve --port=8001
```

## License

This project is open-sourced software licensed under the MIT license.

## Credits

- Built with [Laravel](https://laravel.com)
- Optimization powered by [Elytica](https://elytica.com)
- Based on NAMC food basket survey data
- UI styled with [Tailwind CSS](https://tailwindcss.com)

## Support

For issues or questions:
- Check [LOCAL_SETUP.md](LOCAL_SETUP.md) for detailed instructions
- Open an issue on GitHub
- Review Laravel docs: https://laravel.com/docs

---

**Version 2.0.0** | Local Hosting Only | Built for INDE 471 Research Project
