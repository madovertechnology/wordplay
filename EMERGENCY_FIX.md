# Emergency Fix for Cookie Size Issue

## Immediate Actions (Can be done without deployment)

### 1. Clear All Sessions in Database
Run this SQL command in your production database:

```sql
-- Clear all sessions
DELETE FROM sessions WHERE last_activity < NOW() - INTERVAL '1 hour';

-- Clear old guest data
DELETE FROM guest_data WHERE created_at < NOW() - INTERVAL '7 days';

-- Clear old guests
DELETE FROM guests WHERE created_at < NOW() - INTERVAL '7 days';
```

### 2. Update Environment Variables
Add these to your production environment:

```env
# Reduce session lifetime
SESSION_LIFETIME=30

# Use Redis for sessions
SESSION_DRIVER=redis

# Reduce cookie size
SESSION_COOKIE=daily_games_platform_session
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

### 3. Nginx Configuration (Immediate)
Add this to your nginx configuration:

```nginx
# Increase header buffer sizes
client_header_buffer_size 8k;
large_client_header_buffers 16 16k;

# Increase FastCGI buffer sizes
fastcgi_buffer_size 256k;
fastcgi_buffers 8 256k;
fastcgi_busy_buffers_size 256k;
```

### 4. Clear Application Cache
Run these commands in production:

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Root Cause Analysis

The issue is likely caused by:

1. **Large session data** - Sessions storing too much data
2. **Multiple cookies** - Session, CSRF, and guest tokens
3. **Old session accumulation** - Sessions not being cleaned up
4. **Nginx buffer limits** - Default limits too small

## Quick Test

To test if the issue is resolved:

```bash
# Check current cookie sizes
curl -I https://your-domain.com

# Check session count
php artisan tinker
>>> DB::table('sessions')->count()
```

## Prevention

1. **Regular cleanup** - Set up a cron job to run session cleanup daily
2. **Monitor session size** - Add logging for large sessions
3. **Use Redis** - Better session management with automatic expiry
4. **Limit session data** - Only store essential data in sessions

## Deployment Fixes

The following changes need to be deployed:

1. Session configuration optimization
2. Cleanup command
3. Session size limit middleware
4. Reduced guest token lifetime
5. Cookie files removal from repository

## Monitoring

After applying fixes, monitor:

- 400 errors in logs
- Session table size
- Cookie sizes in browser dev tools
- Nginx error logs 
