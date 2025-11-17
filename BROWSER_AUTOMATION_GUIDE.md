# Browser Automation Guide (Laravel Dusk)

This guide explains how to use browser automation with Laravel Dusk to scrape food prices from Checkers and bypass anti-bot protection.

## Overview

**Problem**: Checkers.co.za uses anti-bot protection (Cloudflare) which blocks simple HTTP requests with 403 Forbidden errors.

**Solution**: Laravel Dusk uses headless Chrome browser automation to:
- Execute JavaScript like a real browser
- Bypass anti-bot detection
- Wait for dynamic content to load
- Extract prices more reliably

## Requirements

✅ All requirements are already installed!

- Laravel Dusk: `composer require --dev laravel/dusk` ✅ Installed
- ChromeDriver: Auto-downloaded by Dusk ✅ Installed
- Chrome/Chromium browser: Should be on your system

## How It Works

### 1. Traditional Scraping (Fails)
```
Your App → HTTP Request → Checkers.co.za
                         ↓
                      403 Forbidden (Anti-bot)
```

### 2. Browser Automation (Success)
```
Your App → Dusk → ChromeDriver → Headless Chrome → Checkers.co.za
                                                    ↓
                                                 Success! ✓
```

## Commands

### Test Dusk Scraper (Single Product)

```bash
php artisan checkers:test-dusk "product name"
```

**Examples:**
```bash
php artisan checkers:test-dusk eggs
php artisan checkers:test-dusk "brown bread"
php artisan checkers:test-dusk milk
```

### Update Prices with Browser Automation

```bash
# Use Dusk for Checkers (recommended)
php artisan foods:update-prices --source=checkers --method=dusk

# Force update (bypass cache)
php artisan foods:update-prices --source=checkers --method=dusk --force
```

### Compare Methods

```bash
# Traditional scraper (will likely fail with 403)
php artisan checkers:test eggs

# Browser automation (bypasses anti-bot)
php artisan checkers:test-dusk eggs
```

## Configuration

### DuskCheckersScraper Settings

Located in `app/Services/DuskCheckersScraper.php`:

```php
protected $headless = true;  // Run browser without GUI
```

**Headless Mode** (default):
- Runs Chrome without visible window
- Faster, uses less resources
- Good for production

**Visible Mode** (for debugging):
```php
protected $headless = false;
```
- Shows Chrome window
- Good for debugging
- Can see what's happening

### Browser Options

The scraper is configured to:
- Use realistic User-Agent
- Window size: 1920x1080
- Disable automation flags
- No sandbox mode (for server compatibility)

## Scheduled Automation

Browser automation is already configured to run automatically!

**Schedule** (in `bootstrap/app.php`):

```php
// Daily at 3:00 AM - Update Checkers with browser automation
$schedule->command('foods:update-prices --source=checkers --method=dusk')
    ->dailyAt('03:00');
```

### Start the Scheduler

To enable automated updates, run the Laravel scheduler:

```bash
# Windows (using Task Scheduler)
php artisan schedule:work

# Or add to crontab (Linux/Mac)
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### All Scheduled Tasks

1. **Woolworths** - Daily 2:00 AM (traditional scraping)
2. **Checkers** - Daily 3:00 AM (browser automation)
3. **Crowd-sourced** - Every 6 hours
4. **Weekly forced update** - Sundays 1:00 AM (all sources)

## Advantages Over Traditional Scraping

| Feature | Traditional Scraper | Browser Automation (Dusk) |
|---------|-------------------|--------------------------|
| **Speed** | ⚡ Fast (1-2 sec) | 🐢 Slower (3-5 sec) |
| **Reliability** | ❌ Blocked by anti-bot | ✅ Bypasses anti-bot |
| **Resources** | 💚 Low CPU/Memory | 💛 Higher CPU/Memory |
| **JavaScript** | ❌ Can't execute | ✅ Executes JS |
| **Success Rate** | 📉 Low on Checkers | 📈 High on Checkers |
| **Detection** | 🚨 Easily detected | 🥷 Harder to detect |

## Troubleshooting

### ChromeDriver Issues

**Problem**: "ChromeDriver not found" or version mismatch

**Solution**:
```bash
# Download/update ChromeDriver to match your Chrome version
php artisan dusk:chrome-driver

# Check ChromeDriver version
php artisan dusk:chrome-driver --detect
```

### Timeout Errors

**Problem**: Browser automation times out

**Increase timeout in `DuskCheckersScraper.php`:**
```php
return RemoteWebDriver::create(
    'http://localhost:9515',
    $capabilities,
    120000,  // Increase connection timeout (2 minutes)
    120000   // Increase request timeout (2 minutes)
);
```

### Price Not Found

**Problem**: Browser loads but can't find price

**Debug steps:**
1. Set headless mode to `false` to see what's happening
2. Check if product exists on Checkers.co.za
3. Update price selectors in `extractFromProductElements()` method
4. Check logs: `storage/logs/laravel.log`

### Port Already in Use

**Problem**: "Port 9515 already in use"

**Solution**:
```bash
# Windows
netstat -ano | findstr :9515
taskkill /PID [PID_NUMBER] /F

# Linux/Mac
lsof -ti:9515 | xargs kill -9
```

### Memory Issues

**Problem**: Browser uses too much RAM

**Solutions**:
1. Reduce concurrent browsers
2. Add memory limits
3. Restart browser between products
4. Use more efficient selectors

## Performance Optimization

### 1. Caching

Results are cached for 6 hours by default:

```php
$cacheKey = 'dusk_checkers_' . md5($productName);
Cache::put($cacheKey, $result, now()->addHours(6));
```

### 2. Rate Limiting

3 seconds between requests to avoid detection:

```php
sleep(3);  // Wait 3 seconds between products
```

### 3. Browser Reuse

The service creates a new browser for each product. For better performance, you could reuse the browser instance, but this increases complexity.

## Legal & Ethical Considerations

⚠️ **IMPORTANT**: Browser automation to bypass anti-bot protection may violate Terms of Service

**Risks**:
- Terms of Service violations
- IP banning
- Legal action (unlikely but possible)
- Ethical concerns

**Recommendations**:
1. **Primary**: Use crowd-sourced pricing (legal, reliable)
2. **Secondary**: Use browser automation sparingly
3. **Rate limit**: Don't overwhelm servers
4. **Respect**: Be a good internet citizen

## Advanced Usage

### Custom Selectors

If price extraction fails, update selectors in `DuskCheckersScraper.php`:

```php
protected function extractFromProductElements($driver, $searchTerm)
{
    $selectors = [
        '.product-price',        // Add Checkers-specific selectors
        '.price',
        '[data-testid="product-price"]',
        '.product-card__price',
        '.item-price',
        '.your-custom-selector',  // Add here
    ];
    // ...
}
```

### Headful Mode for Debugging

See what the browser is doing:

```php
// In DuskCheckersScraper.php
protected $headless = false;

// Then run:
php artisan checkers:test-dusk eggs
```

You'll see Chrome open and navigate to Checkers.

### Screenshots for Debugging

Add to `scrapWithBrowser()` method:

```php
// Take screenshot for debugging
$driver->takeScreenshot(storage_path('app/debug_screenshot.png'));
```

## Comparison: All Scraping Methods

### Method 1: Traditional Scraping (CheckersScraperService)
- ❌ Blocked by anti-bot (403 errors)
- ✅ Fast
- ✅ Low resources
- ❌ Low success rate

### Method 2: Browser Automation (DuskCheckersScraper)
- ✅ Bypasses anti-bot
- ⚠️ Slower
- ⚠️ Higher resources
- ✅ High success rate
- **Recommended for Checkers**

### Method 3: Crowd-Sourced Pricing
- ✅ Legal
- ✅ Reliable
- ✅ Location-specific
- ✅ Community-driven
- **Recommended as primary source**

## Integration with Your App

The Dusk scraper integrates seamlessly:

```php
use App\Services\DuskCheckersScraper;

$scraper = app(DuskCheckersScraper::class);

// Search single product
$result = $scraper->searchProduct('eggs');

// Update all foods
$stats = $scraper->updateAllFoodPrices($forceUpdate = false);
```

## Maintenance

### Update ChromeDriver

When Chrome updates, you may need to update ChromeDriver:

```bash
php artisan dusk:chrome-driver
```

### Monitor Logs

Check for errors:

```bash
tail -f storage/logs/laravel.log
```

### Test Regularly

Run test command to ensure it's working:

```bash
php artisan checkers:test-dusk eggs
```

## Security Best Practices

1. **Don't commit credentials**: No API keys needed (it's scraping)
2. **Use .env for config**: Store settings in environment variables
3. **Limit access**: Don't expose scraper endpoints publicly
4. **Monitor usage**: Track requests to avoid abuse
5. **Respect ToS**: Be aware you may be violating Terms of Service

## Future Improvements

Potential enhancements:

1. **Proxy rotation**: Use rotating proxies for IP diversity
2. **CAPTCHA solving**: Integrate 2captcha or similar
3. **Browser fingerprinting**: More realistic browser profiles
4. **Multi-threading**: Parallel browser instances
5. **Smart retry**: Exponential backoff on failures
6. **Price history**: Track price changes over time

## Support

For issues:

1. Check logs: `storage/logs/laravel.log`
2. Test ChromeDriver: `php artisan dusk:chrome-driver`
3. Verify Chrome installed: `google-chrome --version` or `chrome --version`
4. Try non-headless mode for visual debugging
5. Update all dependencies: `composer update`

## Resources

- [Laravel Dusk Documentation](https://laravel.com/docs/dusk)
- [WebDriver Documentation](https://www.selenium.dev/documentation/webdriver/)
- [ChromeDriver Downloads](https://chromedriver.chromium.org/downloads)

---

**Remember**: Use browser automation responsibly and ethically. When possible, prefer official APIs or crowd-sourced data.
