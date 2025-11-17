# Price Scraping Guide

This guide explains how to use the price scraping features in the Diet Plan application.

## Overview

The application supports multiple methods for updating food prices:

1. **Web Scraping** - Automated price extraction from retailer websites
2. **Crowd-Sourced Pricing** - User-reported prices
3. **API Integration** - Official APIs (when available)

## Available Scrapers

### Woolworths South Africa
- **Status**: ✅ Implemented
- **Service**: `WoolworthsScraperService`
- **Website**: https://www.woolworths.co.za
- **Method**: Traditional HTTP scraping
- **Reliability**: Medium (HTML parsing)

### Checkers South Africa (Traditional)
- **Status**: ✅ Implemented
- **Service**: `CheckersScraperService`
- **Website**: https://www.checkers.co.za
- **Method**: Traditional HTTP scraping
- **Reliability**: Low (Anti-bot protection - 403 errors common)

### Checkers South Africa (Browser Automation)
- **Status**: ✅ Implemented
- **Service**: `DuskCheckersScraper`
- **Website**: https://www.checkers.co.za
- **Method**: Laravel Dusk + ChromeDriver
- **Reliability**: High (Bypasses anti-bot protection)
- **See**: `BROWSER_AUTOMATION_GUIDE.md` for details

## Legal & Ethical Warnings

⚠️ **IMPORTANT**: Web scraping may violate Terms of Service

- You are responsible for any legal consequences
- Use at your own risk
- Consider crowd-sourced pricing as the primary method
- Web scraping should be used sparingly and responsibly

## Features

### Rate Limiting
- 2 seconds between requests
- Prevents server overload
- Respectful of retailer resources

### Caching
- Results cached for 6 hours
- Reduces unnecessary requests
- Use `--force` to bypass cache

### Smart Updates
- Only updates prices with significant changes
- Threshold: >5% change OR >R0.50 difference
- Prevents unnecessary database writes

## Command Reference

### Test Woolworths Scraper

```bash
php artisan woolworths:test "product name"
```

**Examples:**
```bash
php artisan woolworths:test eggs
php artisan woolworths:test "brown bread"
php artisan woolworths:test milk
```

### Test Checkers Scraper (Traditional)

```bash
php artisan checkers:test "product name"
```

**Examples:**
```bash
php artisan checkers:test eggs
php artisan checkers:test "brown bread"
php artisan checkers:test milk
```

**Note**: Checkers has anti-bot protection, so expect 403 errors frequently.

### Test Checkers Scraper (Browser Automation) ⭐ Recommended

```bash
php artisan checkers:test-dusk "product name"
```

**Examples:**
```bash
php artisan checkers:test-dusk eggs
php artisan checkers:test-dusk "brown bread"
php artisan checkers:test-dusk milk
```

**Advantage**: Uses headless Chrome to bypass anti-bot protection!

### Update All Food Prices

```bash
php artisan foods:update-prices [options]
```

**Options:**
- `--source=all` - Update from all sources (default)
- `--source=woolworths` - Only update Woolworths prices
- `--source=checkers` - Only update Checkers prices
- `--source=manual` - Manual entries (no auto-update)
- `--method=scraper` - Use traditional web scraping (default)
- `--method=dusk` - Use browser automation (Dusk) - bypasses anti-bot
- `--method=api` - Use API (if available)
- `--force` - Force update, skip cache

**Examples:**

```bash
# Update all prices from all sources
php artisan foods:update-prices

# Update only Woolworths prices
php artisan foods:update-prices --source=woolworths

# Update only Checkers prices
php artisan foods:update-prices --source=checkers

# Force update (bypass cache)
php artisan foods:update-prices --force

# Update Woolworths with forced refresh
php artisan foods:update-prices --source=woolworths --force

# Update Checkers with browser automation (recommended)
php artisan foods:update-prices --source=checkers --method=dusk

# Update Checkers with traditional scraper (will likely fail)
php artisan foods:update-prices --source=checkers --method=scraper
```

### Update from Crowd-Sourced Data

```bash
php artisan foods:update-from-crowdsource
```

This command updates food prices based on verified user-reported prices from the last 7 days.

## How Scraping Works

### Woolworths Scraper

1. Searches for product on woolworths.co.za
2. Parses HTML response
3. Extracts price using multiple methods:
   - JSON-LD structured data
   - HTML price elements
   - JavaScript state objects
4. Validates price (R1 - R1000 range)
5. Updates database if price changed significantly

### Checkers Scraper

1. Searches for product on checkers.co.za
2. Attempts to bypass anti-bot protection with realistic headers
3. Parses HTML response
4. Extracts price using multiple methods:
   - JSON-LD structured data
   - Sixty60 specific patterns
   - HTML price elements
   - JavaScript state objects
5. Validates price (R1 - R1000 range)
6. Updates database if price changed significantly

**Challenge**: Checkers uses Cloudflare or similar anti-bot protection, resulting in frequent 403 Forbidden errors.

## Troubleshooting

### 403 Forbidden Errors (Checkers)

**Problem**: Anti-bot protection blocking requests

**Solutions**:
1. Use crowd-sourced pricing instead
2. Implement browser automation (Selenium/Playwright)
3. Use rotating proxies (check legal implications first)
4. Reduce request frequency

### Price Not Found

**Problem**: Scraper can't extract price from HTML

**Causes**:
- Product doesn't exist
- Website structure changed
- Price in different format

**Solutions**:
1. Check product name spelling
2. Try alternative product names
3. Update scraper patterns in service class
4. Report price manually via crowd-sourcing

### Timeout Errors

**Problem**: Request takes too long

**Solutions**:
1. Check internet connection
2. Increase timeout in service (currently 30s)
3. Try again later

### IP Blocked

**Problem**: Too many requests from your IP

**Solutions**:
1. Wait 24-48 hours
2. Reduce request frequency
3. Use VPN (check legal implications)

## Recommended Strategy

### Primary Method: Crowd-Sourced Pricing
- Most reliable and legal
- Community-driven
- Real-world prices from actual stores
- Location-specific pricing

### Secondary Method: Web Scraping
- Use for initial data population
- Verify crowd-sourced prices
- Update prices when crowd data unavailable
- Use sparingly to avoid ToS violations

## Database Structure

### Foods Table

```sql
foods
  - id
  - name
  - serving_size
  - protein, carbs, fat, fiber
  - energy_kj, calories
  - cost
  - source (woolworths|checkers|manual|crowd-sourced)
  - price_updated_at
  - is_active
  - timestamps
```

### Price Reports Table

```sql
price_reports
  - id
  - food_id
  - user_id
  - reported_price
  - store_location
  - store_chain
  - notes
  - verified
  - reported_at
  - timestamps
```

## Price Source Priority

When multiple prices are available:

1. **Crowd-sourced** (verified, recent reports)
2. **Woolworths scraper** (when product available)
3. **Checkers scraper** (often blocked)
4. **Manual entry** (fallback)

## Scheduling Automated Updates

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Update prices daily at 2 AM
    $schedule->command('foods:update-prices --source=woolworths')
             ->dailyAt('02:00');

    // Update from crowd-sourced data every 6 hours
    $schedule->command('foods:update-from-crowdsource')
             ->everySixHours();
}
```

## API Integration (Future)

Currently, neither Woolworths nor Checkers offers public APIs for price data.

**Potential Future Sources:**
- Official retailer APIs (if they become available)
- Price comparison aggregators (PriceCheck, Pryce)
- Government price monitoring databases

## Contributing

If you improve the scrapers:

1. Update regex patterns in service classes
2. Add new parsing methods
3. Improve error handling
4. Document changes
5. Test thoroughly before committing

## Support

For issues or questions:
1. Check `storage/logs/laravel.log`
2. Enable debug mode in `.env`
3. Run test commands first
4. Report issues with detailed logs
