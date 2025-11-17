# Automated Price Update Scheduling Guide

This guide explains how automated price updates are configured and how to start the scheduler.

## Overview

Your diet plan application is configured to automatically update food prices on a schedule:

- **Woolworths**: Daily at 2:00 AM (traditional scraping)
- **Checkers**: Daily at 3:00 AM (browser automation)
- **Crowd-sourced**: Every 6 hours
- **Full update**: Weekly on Sundays at 1:00 AM (forced)

## Schedule Configuration

Located in `bootstrap/app.php`:

```php
->withSchedule(function ($schedule): void {
    // Update Woolworths prices daily at 2:00 AM
    $schedule->command('foods:update-prices --source=woolworths --method=scraper')
        ->dailyAt('02:00')
        ->withoutOverlapping()
        ->runInBackground();

    // Update Checkers prices daily at 3:00 AM (with browser automation)
    $schedule->command('foods:update-prices --source=checkers --method=dusk')
        ->dailyAt('03:00')
        ->withoutOverlapping()
        ->runInBackground();

    // Update from crowd-sourced data every 6 hours
    $schedule->command('foods:update-from-crowdsource')
        ->everySixHours()
        ->withoutOverlapping();

    // Weekly forced update (bypass cache) - Sundays at 1:00 AM
    $schedule->command('foods:update-prices --source=all --method=scraper --force')
        ->weekly()
        ->sundays()
        ->at('01:00')
        ->withoutOverlapping()
        ->runInBackground();
})
```

## Starting the Scheduler

Laravel's scheduler needs a cron job or continuous process to run.

### Option 1: Schedule Worker (Development)

For development/testing, use the schedule worker:

```bash
php artisan schedule:work
```

This runs continuously and executes scheduled tasks at the right time.

**Pros**:
- Easy to start
- See real-time output
- Good for development

**Cons**:
- Stops when terminal closes
- Needs to run continuously

### Option 2: Windows Task Scheduler (Production on Windows)

#### Step 1: Create Batch File

Create `run-schedule.bat` in your project root:

```batch
@echo off
cd C:\Users\simon\Herd\diet-plan
"C:\Users\simon\.config\herd\bin\php.bat" artisan schedule:run >> storage\logs\schedule.log 2>&1
```

#### Step 2: Configure Task Scheduler

1. Open **Task Scheduler**
2. Click **Create Basic Task**
3. Name: "Diet Plan Price Updater"
4. Trigger: **Daily**
5. Start time: **00:00** (midnight)
6. Action: **Start a program**
7. Program: `C:\Users\simon\Herd\diet-plan\run-schedule.bat`
8. In **Settings**:
   - ✅ Run task every: **1 minute**
   - ✅ Stop task if runs longer than: **3 hours**
   - ✅ Run whether user is logged in or not

#### Step 3: Advanced Settings

In the task's properties:
- **Triggers** → Edit → Repeat task every: **1 minute**
- **Duration**: Indefinitely
- **Stop task if it runs longer than**: 3 hours
- **Settings** → ✅ Run task as soon as possible after a scheduled start is missed

### Option 3: Cron (Linux/Mac)

Edit crontab:

```bash
crontab -e
```

Add this line:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

This runs every minute and Laravel decides which tasks to execute.

## Scheduler Options Explained

### withoutOverlapping()

Prevents the same task from running twice simultaneously.

```php
$schedule->command('foods:update-prices')
    ->withoutOverlapping();
```

If a task is still running when the next execution time arrives, it will be skipped.

### runInBackground()

Runs the task in the background, allowing other tasks to run concurrently.

```php
$schedule->command('foods:update-prices')
    ->runInBackground();
```

Without this, tasks run sequentially.

### Scheduling Methods

| Method | Description | Example |
|--------|-------------|---------|
| `dailyAt('02:00')` | Run daily at 2:00 AM | `->dailyAt('02:00')` |
| `everyMinute()` | Run every minute | `->everyMinute()` |
| `everyFiveMinutes()` | Run every 5 minutes | `->everyFiveMinutes()` |
| `hourly()` | Run every hour | `->hourly()` |
| `everyTwoHours()` | Run every 2 hours | `->everyTwoHours()` |
| `everySixHours()` | Run every 6 hours | `->everySixHours()` |
| `daily()` | Run daily at midnight | `->daily()` |
| `weekly()` | Run weekly | `->weekly()` |
| `monthly()` | Run monthly | `->monthly()` |
| `weekdays()` | Run on weekdays only | `->weekdays()` |
| `weekends()` | Run on weekends only | `->weekends()` |
| `sundays()` | Run on Sundays | `->sundays()` |

## Testing the Schedule

### List Scheduled Tasks

```bash
php artisan schedule:list
```

This shows all scheduled tasks and their next run time.

### Run Schedule Immediately (Testing)

```bash
php artisan schedule:run
```

This runs all tasks that are due right now (won't wait for scheduled time).

### Test Specific Task

```bash
# Test Woolworths update manually
php artisan foods:update-prices --source=woolworths --method=scraper

# Test Checkers update manually
php artisan foods:update-prices --source=checkers --method=dusk

# Test crowd-sourced update manually
php artisan foods:update-from-crowdsource
```

## Monitoring

### View Logs

Check scheduler logs:

```bash
# On Windows
type storage\logs\schedule.log

# On Linux/Mac
tail -f storage/logs/laravel.log
```

### Add Logging to Scheduled Tasks

Update schedule to send output to log:

```php
$schedule->command('foods:update-prices --source=woolworths')
    ->dailyAt('02:00')
    ->appendOutputTo(storage_path('logs/scheduler-woolworths.log'));
```

### Email Notifications on Failure

```php
$schedule->command('foods:update-prices')
    ->dailyAt('02:00')
    ->emailOutputOnFailure('your-email@example.com');
```

Requires mail configuration in `.env`.

## Customizing the Schedule

### Change Update Times

Edit `bootstrap/app.php`:

```php
// Update Woolworths at 6:00 AM instead
$schedule->command('foods:update-prices --source=woolworths --method=scraper')
    ->dailyAt('06:00');

// Update Checkers every 12 hours
$schedule->command('foods:update-prices --source=checkers --method=dusk')
    ->everyTwelveHours();
```

### Add More Frequent Updates

```php
// Update every 2 hours
$schedule->command('foods:update-from-crowdsource')
    ->everyTwoHours();
```

### Disable Automatic Updates

Comment out unwanted tasks:

```php
// Disable Checkers automation
// $schedule->command('foods:update-prices --source=checkers --method=dusk')
//     ->dailyAt('03:00');
```

## Troubleshooting

### Schedule Not Running

**Check if scheduler is active:**

```bash
# Windows
tasklist | findstr php

# Linux/Mac
ps aux | grep schedule:work
```

**Verify cron/Task Scheduler:**
- Windows: Check Task Scheduler app
- Linux/Mac: Check `crontab -l`

### Tasks Not Executing

**Check overlapping:**
- If `withoutOverlapping()` is set and task is already running, it will be skipped

**Check time zone:**
- Laravel uses `config/app.php` timezone
- Make sure it matches your local timezone

**View schedule list:**
```bash
php artisan schedule:list
```

### Permission Errors

**Storage directory:**
```bash
# Make sure storage is writable
chmod -R 775 storage  # Linux/Mac
```

**ChromeDriver:**
```bash
# Make sure ChromeDriver is executable
chmod +x vendor/laravel/dusk/bin/chromedriver-*  # Linux/Mac
```

### Memory Issues

If browser automation uses too much memory:

```php
// Reduce frequency
$schedule->command('foods:update-prices --source=checkers --method=dusk')
    ->weekly()  // Instead of daily
    ->sundays();
```

## Best Practices

### 1. Stagger Update Times

Don't run all tasks at the same time:
- Woolworths: 2:00 AM ✓
- Checkers: 3:00 AM ✓
- (Not 2:00 AM for both)

### 2. Use Background Execution

For long-running tasks:
```php
->runInBackground()
```

### 3. Prevent Overlapping

Always add for long tasks:
```php
->withoutOverlapping()
```

### 4. Monitor Logs

Regularly check:
```bash
tail -f storage/logs/laravel.log
```

### 5. Test Before Scheduling

Always test commands manually before scheduling:
```bash
php artisan foods:update-prices --source=checkers --method=dusk
```

## Advanced Configuration

### Different Schedules for Different Environments

```php
if (app()->environment('production')) {
    $schedule->command('foods:update-prices --source=all')
        ->dailyAt('02:00');
} else {
    $schedule->command('foods:update-prices --source=all')
        ->hourly(); // More frequent in development
}
```

### Maintenance Mode

Skip scheduled tasks during maintenance:

```php
$schedule->command('foods:update-prices')
    ->dailyAt('02:00')
    ->when(function () {
        return !app()->isDownForMaintenance();
    });
```

### Conditional Scheduling

Only run if certain conditions are met:

```php
$schedule->command('foods:update-prices')
    ->dailyAt('02:00')
    ->when(function () {
        // Only run if we have internet
        return @fopen('https://www.google.com', 'r');
    });
```

## Security Considerations

### Rate Limiting

Current configuration respects rate limits:
- Woolworths: 2 seconds between requests
- Checkers (Dusk): 3 seconds between requests

### IP Rotation

If you get blocked:
1. Reduce frequency
2. Use VPN/proxy (check legality)
3. Rely more on crowd-sourced data

### Respect ToS

Remember: Automated scraping may violate Terms of Service
- Use responsibly
- Consider crowd-sourced data as primary source
- Scraping as backup only

## Summary of Scheduled Tasks

| Task | Frequency | Time | Method | Purpose |
|------|-----------|------|--------|---------|
| Woolworths | Daily | 2:00 AM | Scraper | Update Woolworths prices |
| Checkers | Daily | 3:00 AM | Dusk | Update Checkers prices |
| Crowd-sourced | Every 6 hours | - | Database | Update from user reports |
| Full update | Weekly | Sun 1:00 AM | Scraper | Forced refresh all prices |

## Quick Start

**Windows (Recommended for your setup):**

```bash
# Option 1: Run continuously in terminal (development)
php artisan schedule:work

# Option 2: Set up Windows Task Scheduler (production)
# Follow "Windows Task Scheduler" section above
```

**Linux/Mac:**

```bash
# Add to crontab
crontab -e

# Add this line:
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Next Steps

1. Start the scheduler: `php artisan schedule:work`
2. Monitor logs: `tail -f storage/logs/laravel.log`
3. Test tasks manually first
4. Set up Windows Task Scheduler for production
5. Monitor resource usage (CPU, memory)
6. Adjust frequencies as needed

---

Your automated price updates are ready to go! 🚀
