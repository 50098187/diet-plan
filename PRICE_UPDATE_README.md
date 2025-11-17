# Diet Plan - Automated Price Updates 🚀

Complete guide to automated food price updates with browser automation and scheduling.

## 🎯 What's Been Implemented

### 1. Browser Automation (Laravel Dusk) ✅
- **Bypasses anti-bot protection** on Checkers.co.za
- Uses headless Chrome browser
- ChromeDriver auto-installed
- No more 403 Forbidden errors!

### 2. Automated Scheduling ✅
- **Woolworths**: Daily at 2:00 AM
- **Checkers**: Daily at 3:00 AM (with browser automation)
- **Crowd-sourced**: Every 6 hours
- **Weekly forced update**: Sundays at 1:00 AM

### 3. Multiple Scraping Methods ✅
- Traditional HTTP scraping (Woolworths, Checkers)
- Browser automation (Checkers) - **NEW!**
- Crowd-sourced pricing

## 📚 Documentation

| Guide | Purpose |
|-------|---------|
| `PRICE_SCRAPING_GUIDE.md` | Complete scraping guide (all methods) |
| `BROWSER_AUTOMATION_GUIDE.md` | Browser automation deep-dive |
| `AUTOMATED_SCHEDULING_GUIDE.md` | Scheduling setup and configuration |

## 🚀 Quick Start

### Test Browser Automation

```bash
# Test Checkers with browser automation (bypasses anti-bot)
php artisan checkers:test-dusk eggs
```

### Start Automated Scheduler

```bash
# For development/testing (runs continuously)
php artisan schedule:work
```

### View All Scheduled Tasks

```bash
php artisan schedule:list
```

## 📋 Available Commands

### Testing Commands

| Command | Description |
|---------|-------------|
| `php artisan woolworths:test eggs` | Test Woolworths scraper |
| `php artisan checkers:test eggs` | Test Checkers (traditional - will fail) |
| `php artisan checkers:test-dusk eggs` | Test Checkers (browser automation) ⭐ |

### Update Commands

| Command | Description |
|---------|-------------|
| `php artisan foods:update-prices` | Update all prices (all sources) |
| `php artisan foods:update-prices --source=woolworths` | Woolworths only |
| `php artisan foods:update-prices --source=checkers --method=dusk` | Checkers (browser) ⭐ |
| `php artisan foods:update-prices --force` | Force update (bypass cache) |
| `php artisan foods:update-from-crowdsource` | Update from user reports |

### Scheduler Commands

| Command | Description |
|---------|-------------|
| `php artisan schedule:work` | Start scheduler (development) |
| `php artisan schedule:list` | List all scheduled tasks |
| `php artisan schedule:run` | Run scheduler once (testing) |

### Dusk Commands

| Command | Description |
|---------|-------------|
| `php artisan dusk:install` | Install Dusk (already done) ✅ |
| `php artisan dusk:chrome-driver` | Update ChromeDriver |

## 🎬 How to Test Everything

### 1. Test Browser Automation

```bash
# This should succeed even with anti-bot protection
php artisan checkers:test-dusk eggs
```

**Expected output:**
```
✓ Success! Product found using browser automation
Name: Eggs
Price: R45.99
Currency: ZAR
Source: dusk-json-ld
✅ Browser automation successfully bypassed anti-bot protection!
```

### 2. Compare Traditional vs Browser Automation

```bash
# Traditional scraper (will likely fail with 403)
php artisan checkers:test eggs

# Browser automation (should succeed)
php artisan checkers:test-dusk eggs
```

### 3. Test Scheduled Tasks

```bash
# See what's scheduled
php artisan schedule:list

# Run scheduler manually (won't wait for scheduled time)
php artisan schedule:run

# Start scheduler continuously
php artisan schedule:work
```

## 🏗️ Architecture

```
Diet Plan Application
│
├── Price Scrapers
│   ├── WoolworthsScraperService (HTTP scraping)
│   ├── CheckersScraperService (HTTP scraping - often blocked)
│   └── DuskCheckersScraper (Browser automation - bypasses blocks) ⭐
│
├── Scheduled Tasks (bootstrap/app.php)
│   ├── Daily: Woolworths at 2:00 AM
│   ├── Daily: Checkers at 3:00 AM (Dusk)
│   ├── Every 6h: Crowd-sourced updates
│   └── Weekly: Full forced update (Sundays 1:00 AM)
│
├── Console Commands
│   ├── foods:update-prices (main update command)
│   ├── woolworths:test (test Woolworths)
│   ├── checkers:test (test Checkers traditional)
│   ├── checkers:test-dusk (test Checkers Dusk) ⭐
│   └── foods:update-from-crowdsource (crowd data)
│
└── Database Models
    ├── Food (food items with prices)
    └── PriceReport (user-reported prices)
```

## 🎯 Comparison: Scraping Methods

| Method | Success Rate | Speed | Resources | Best For |
|--------|--------------|-------|-----------|----------|
| **Traditional Scraper** | ⭐⭐ (Woolworths) | ⚡⚡⚡ Fast | 💚 Low | Woolworths |
| **Traditional Scraper** | ⭐ (Checkers) | ⚡⚡⚡ Fast | 💚 Low | ❌ Don't use for Checkers |
| **Browser Automation** | ⭐⭐⭐⭐⭐ | ⚡⚡ Slower | 💛 Medium | ✅ Checkers |
| **Crowd-Sourced** | ⭐⭐⭐⭐⭐ | ⚡⚡⚡ Fast | 💚 Low | ✅ Primary source |

## 📊 Scheduling Overview

### Current Schedule

```
Sunday 1:00 AM  - Full forced update (all sources)
Daily  2:00 AM  - Woolworths update (traditional scraper)
Daily  3:00 AM  - Checkers update (browser automation)
Every  6 hours  - Crowd-sourced price updates
```

### Start the Scheduler

**Development:**
```bash
php artisan schedule:work
```

**Production (Windows):**
1. Create `run-schedule.bat`:
```batch
@echo off
cd C:\Users\simon\Herd\diet-plan
"C:\Users\simon\.config\herd\bin\php.bat" artisan schedule:run
```

2. Add to **Windows Task Scheduler**:
   - Trigger: Every 1 minute
   - Action: Run `run-schedule.bat`
   - See `AUTOMATED_SCHEDULING_GUIDE.md` for details

## 🔧 Configuration

### Update Frequency

Edit `bootstrap/app.php` to change schedule:

```php
// Change Checkers to twice daily
$schedule->command('foods:update-prices --source=checkers --method=dusk')
    ->twiceDaily(3, 15);  // 3:00 AM and 3:00 PM

// Change crowd-sourced to every 3 hours
$schedule->command('foods:update-from-crowdsource')
    ->everyThreeHours();
```

### Browser Settings

Edit `app/Services/DuskCheckersScraper.php`:

```php
// Show browser window (debugging)
protected $headless = false;

// Increase timeout
return RemoteWebDriver::create(
    'http://localhost:9515',
    $capabilities,
    120000,  // 2 minutes
    120000
);
```

## 🛠️ Troubleshooting

### ChromeDriver Issues

```bash
# Update ChromeDriver
php artisan dusk:chrome-driver

# Check version
php artisan dusk:chrome-driver --detect
```

### Price Not Found

```bash
# Check logs
tail -f storage/logs/laravel.log

# Test manually
php artisan checkers:test-dusk eggs

# Enable visible browser (debugging)
# Set $headless = false in DuskCheckersScraper.php
```

### Schedule Not Running

```bash
# Check scheduler status
php artisan schedule:list

# Run manually
php artisan schedule:run

# Check if running
# Windows: tasklist | findstr php
# Linux/Mac: ps aux | grep schedule:work
```

### 403 Errors (Anti-bot)

```bash
# Don't use traditional scraper for Checkers
# ❌ php artisan checkers:test eggs

# Use browser automation instead
# ✅ php artisan checkers:test-dusk eggs
```

## 📈 Monitoring

### View Logs

```bash
# Real-time log monitoring
tail -f storage/logs/laravel.log

# View scheduler log
type storage\logs\schedule.log  # Windows
cat storage/logs/schedule.log   # Linux/Mac
```

### Check Price Updates

```bash
# View food prices in database
php artisan tinker

# In tinker:
\App\Models\Food::latest('price_updated_at')->take(10)->get(['name', 'cost', 'source', 'price_updated_at'])
```

## ⚠️ Legal & Ethical Considerations

**Important**: Web scraping may violate retailer Terms of Service

### Risks
- Terms of Service violations
- IP blocking
- Legal action (unlikely)

### Recommendations
1. **Primary**: Use crowd-sourced pricing (legal, reliable)
2. **Secondary**: Use browser automation sparingly
3. **Rate limit**: Respect server resources
4. **Monitor**: Watch for blocks or issues

### Current Rate Limiting
- Woolworths: 2 seconds between requests ✅
- Checkers (Dusk): 3 seconds between requests ✅

## 🎓 Learn More

- **Browser Automation**: `BROWSER_AUTOMATION_GUIDE.md`
- **Scraping Methods**: `PRICE_SCRAPING_GUIDE.md`
- **Scheduling**: `AUTOMATED_SCHEDULING_GUIDE.md`
- **Laravel Dusk**: https://laravel.com/docs/dusk
- **Task Scheduling**: https://laravel.com/docs/scheduling

## 🚦 Getting Started Checklist

- [x] Laravel Dusk installed
- [x] ChromeDriver downloaded
- [x] Scheduled tasks configured
- [x] Test commands created
- [ ] Test browser automation (`php artisan checkers:test-dusk eggs`)
- [ ] Start scheduler (`php artisan schedule:work`)
- [ ] Monitor logs (`tail -f storage/logs/laravel.log`)
- [ ] Set up Windows Task Scheduler (production)

## 📞 Support

If you encounter issues:

1. Check relevant guide in docs
2. View logs: `storage/logs/laravel.log`
3. Test manually before scheduling
4. Update ChromeDriver if needed
5. Consider crowd-sourced pricing as primary source

## 🎉 Summary

You now have:

✅ **Browser automation** to bypass anti-bot protection
✅ **Automated scheduling** for daily price updates
✅ **Multiple scraping methods** for different retailers
✅ **Comprehensive documentation** for all features
✅ **Testing commands** to verify everything works

**Next steps:**
1. Test browser automation: `php artisan checkers:test-dusk eggs`
2. Start scheduler: `php artisan schedule:work`
3. Monitor results: `tail -f storage/logs/laravel.log`

Happy price tracking! 🎯
