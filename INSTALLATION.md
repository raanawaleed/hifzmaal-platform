# HifzMaal Installation Guide

## Prerequisites

Before installing HifzMaal, ensure your system meets these requirements:

- **PHP**: 8.1 or higher
- **Composer**: Latest version
- **Database**: MySQL 5.7+ or PostgreSQL 10+
- **Node.js**: 16.x or higher
- **NPM**: 8.x or higher
- **Web Server**: Apache or Nginx

## Step-by-Step Installation

### 1. Clone Repository
```bash
git clone https://github.com/yourusername/hifzmaal.git
cd hifzmaal
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Environment Configuration
```bash
# Copy environment file
Edit `.env` file with your database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hifzmaal
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 4. Database Setup
```bash
# Create database (if not exists)
mysql -u root -p -e "CREATE DATABASE hifzmaal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations and seeders
php artisan migrate --seed
```

### 5. Storage Setup
```bash
# Create symbolic link
php artisan storage:link

# Set permissions
chmod -R 775 storage bootstrap/cache
```

### 6. Install Frontend Dependencies
```bash
npm install
npm run build
```

### 7. Configure Queue Worker (Optional)

For background jobs:
```bash
# Start queue worker
php artisan queue:work

# Or use supervisor for production
```

### 8. Configure Scheduled Tasks

Add to crontab:
```bash
crontab -e

# Add this line
* * * * * cd /path-to-hifzmaal && php artisan schedule:run >> /dev/null 2>&1
```

### 9. Start Development Server
```bash
php artisan serve
```

Visit: http://localhost:8000

## Production Deployment

### 1. Optimize Application
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

### 2. Environment Variables
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=
```

### 3. Web Server Configuration

#### Apache
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /path-to-hifzmaal/public

    <Directory /path-to-hifzmaal/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path-to-hifzmaal/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 4. SSL Certificate
```bash
# Using Certbot
sudo certbot --nginx -d yourdomain.com
```

### 5. Set Permissions
```bash
chown -R www-data:www-data /path-to-hifzmaal
chmod -R 755 /path-to-hifzmaal
chmod -R 775 /path-to-hifzmaal/storage
chmod -R 775 /path-to-hifzmaal/bootstrap/cache
```

## Troubleshooting

### Issue: Permission Denied
```bash
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R $USER:www-data storage bootstrap/cache
```

### Issue: Database Connection Failed

1. Check database credentials in `.env`
2. Ensure MySQL service is running
3. Test connection: `php artisan tinker` then `DB::connection()->getPdo();`

### Issue: Storage Link Not Working
```bash
# Remove existing link
rm public/storage

# Create new link
php artisan storage:link
```

## Next Steps

1. Create your first family account
2. Set up categories
3. Add family members
4. Start tracking finances

For support, visit: https://hifzmaal.com/support