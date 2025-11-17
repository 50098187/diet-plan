# Woolworths Web Scraper - Usage Guide

## ⚠️ CRITICAL LEGAL WARNING

**THIS SCRAPER MAY VIOLATE WOOLWORTHS' TERMS OF SERVICE**

By using this scraper, you acknowledge and accept:

1. **Legal Risk**: Web scraping may be against Woolworths.co.za Terms of Service
2. **IP Blocking**: Your IP address may be banned from Woolworths website
3. **Legal Action**: Woolworths could pursue legal action against you
4. **Your Responsibility**: You bear all legal consequences of using this tool
5. **No Warranty**: This code is provided as-is without any guarantees

**USE AT YOUR OWN RISK**

---

## What This Scraper Does

- Searches Woolworths.co.za for product names
- Extracts product prices from HTML/JSON
- Caches results for 6 hours to minimize requests
- Rate limits to 1 request every 2 seconds
- Updates your food database with current prices

## How It Works

1. **Search**: Visits `woolworths.co.za/cat?Ntt={product}`
2. **Parse**: Extracts price using multiple methods:
   - JSON-LD structured data
   - HTML price tags
   - JavaScript state objects
3. **Validate**: Sanity checks (R1 - R1000 range)
4. **Cache**: Stores result for 6 hours
5. **Update**: Updates database if price changed >5% or >R0.50

## Ethical Features

✅ **Rate Limited**: 2 seconds between requests (respectful)
✅ **Caching**: Minimizes unnecessary requests
✅ **User-Agent**: Identifies as browser, not bot
✅ **Error Handling**: Fails gracefully
✅ **Logging**: Tracks all requests for transparency

---

## Commands

### 1. Test Single Product

Test if scraper works for a specific product:

```bash
# Test with "eggs"
php artisan woolworths:test eggs

# Test with "chicken breast"
php artisan woolworths:test "chicken breast"

# Test with "brown rice"
php artisan woolworths:test "brown rice"
```

**Example Output:**
```
⚠ This is a web scraper test - use responsibly

Testing Woolworths scraper...
Product: eggs
------------------------------------------------------------

Fetching from Woolworths.co.za...

✓ Success! Product found

+-----------+-------------+
| Field     | Value       |
+-----------+-------------+
| Name      | eggs        |
| Price     | R32.99      |
| Currency  | ZAR         |
| Source    | json-ld     |
+-----------+-------------+
```

### 2. Update All Food Prices

Update all foods in your database:

```bash
# Update with confirmation prompt
php artisan foods:update-prices --method=scraper

# Force update (skip cache)
php artisan foods:update-prices --method=scraper --force

# Update only Woolworths source foods
php artisan foods:update-prices --source=woolworths --method=scraper
```

**Example Output:**
```
Starting food price update...
Method: scraper

⚠ Using web scraper - this may violate Woolworths ToS
⚠ You are responsible for any legal consequences

 Do you accept responsibility and want to continue? (yes/no) [no]:
 > yes

Scraping Woolworths prices...
Rate limited: 2 seconds between requests

✓ Updated: 12 foods
⊘ Skipped: 7 foods (price unchanged)
✗ Failed: 2 foods

Price update completed!
```

---

## Configuration

### Environment Variables (Optional)

Add to `.env` if you want to customize:

```env
# Cache duration in minutes (default: 360 = 6 hours)
WOOLWORTHS_CACHE_MINUTES=360

# Rate limit delay in seconds (default: 2)
WOOLWORTHS_RATE_LIMIT=2

# Timeout for HTTP requests in seconds (default: 30)
WOOLWORTHS_TIMEOUT=30
```

### Marking Foods for Scraping

Update `source` field in database:

```php
// In tinker or migration
Food::where('name', 'Eggs')->update(['source' => 'woolworths']);
Food::where('name', 'Chicken breast')->update(['source' => 'woolworths']);
```

---

## Troubleshooting

### Problem: "Failed to find product"

**Causes:**
- Product name doesn't match Woolworths exactly
- Product not available online
- Website structure changed

**Solutions:**
1. Try different search terms:
   ```bash
   php artisan woolworths:test "free range eggs"
   php artisan woolworths:test "large eggs"
   ```

2. Check Woolworths website manually:
   - Visit woolworths.co.za
   - Search for your product
   - See exact name they use

3. Update food names in database to match Woolworths

### Problem: "All requests fail"

**Causes:**
- IP blocked by Woolworths
- Network issues
- Website down

**Solutions:**
1. Wait 24 hours (IP ban usually temporary)
2. Check logs: `tail -f storage/logs/laravel.log`
3. Try from different network/IP
4. Check if woolworths.co.za is accessible in browser

### Problem: "Prices not updating"

**Causes:**
- Cache is active (6 hour default)
- Price change < 5% and < R0.50
- Scraper extracted wrong price

**Solutions:**
1. Force cache refresh:
   ```bash
   php artisan foods:update-prices --method=scraper --force
   ```

2. Clear cache manually:
   ```bash
   php artisan cache:clear
   ```

3. Check scraped price vs actual:
   ```bash
   php artisan woolworths:test "eggs"
   ```

### Problem: "Scraper is slow"

**Expected:**
- 21 foods × 2 seconds = ~42 seconds minimum
- Rate limiting is intentional to be respectful

**Not a bug, it's a feature!** 🐢

---

## Monitoring

### Check Logs

```bash
# View real-time logs
tail -f storage/logs/laravel.log | grep "Woolworths scraper"

# Filter successful updates
tail -f storage/logs/laravel.log | grep "Price updated"

# Filter failures
tail -f storage/logs/laravel.log | grep "Failed to get price"
```

### Database Query

Check when prices were last updated:

```sql
SELECT
    name,
    cost,
    source,
    price_updated_at
FROM foods
WHERE source = 'woolworths'
ORDER BY price_updated_at DESC;
```

---

## Scheduling (Automated Updates)

### Option 1: Cron Job (Linux/Mac)

```bash
# Add to crontab
0 3 * * * cd /path/to/project && php artisan foods:update-prices --method=scraper --no-interaction
```

### Option 2: Laravel Scheduler

Uncomment in `bootstrap/app.php`:

```php
->withSchedule(function ($schedule): void {
    // Update prices daily at 3 AM
    $schedule->command('foods:update-prices --method=scraper --no-interaction')
             ->dailyAt('03:00')
             ->withoutOverlapping();
})
```

Then add to crontab:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### Option 3: Windows Task Scheduler

Create a batch file `update_prices.bat`:
```batch
@echo off
cd C:\Users\simon\Herd\diet-plan
php artisan foods:update-prices --method=scraper --no-interaction
```

Schedule it in Windows Task Scheduler to run daily at 3 AM.

---

## Best Practices

### ✅ DO:
- Test thoroughly before production use
- Monitor logs for issues
- Be respectful with rate limiting
- Cache aggressively (6+ hours)
- Use during off-peak hours (3 AM)
- Have a fallback (manual updates)

### ❌ DON'T:
- Remove rate limiting
- Run continuously/frequently
- Scrape more than necessary
- Ignore errors/blocks
- Deploy without testing
- Use in high-traffic production without legal review

---

## Alternative: Switch to Crowd-Sourcing

If scraping causes issues, you already have crowd-sourcing built:

```bash
# Use user-reported prices instead
php artisan foods:update-from-crowdsource
```

See `docs/PRICING_STRATEGIES.md` for details.

---

## Legal Alternatives

1. **Manual Updates**: 10 minutes weekly, 100% legal
2. **Crowd-Sourcing**: User reports, 100% legal (already built!)
3. **Official Partnership**: Contact Woolworths, 100% legal
4. **Price Comparison APIs**: PriceCheck.co.za, paid but legal

---

## Support

**Questions?**
- Check logs: `storage/logs/laravel.log`
- Test single product: `php artisan woolworths:test eggs`
- Review code: `app/Services/WoolworthsScraperService.php`

**Remember**: You assume all legal responsibility for using this scraper.

**Last Updated**: November 2025
