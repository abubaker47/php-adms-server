# Deployment Guide

This guide covers various deployment options for the ADMS server in production environments.

## Table of Contents

1. [Docker Deployment](#docker-deployment)
2. [Traditional Linux Server](#traditional-linux-server)
3. [Systemd Service](#systemd-service)
4. [Nginx Configuration](#nginx-configuration)
5. [Apache Configuration](#apache-configuration)
6. [Security Best Practices](#security-best-practices)

---

## Docker Deployment

### Prerequisites
- Docker and Docker Compose installed

### Quick Start with Docker

1. **Build and run with Docker Compose:**
```bash
# Set your API key
export ADMS_API_KEY="your-secure-random-key"

# Start the server
docker-compose up -d
```

2. **View logs:**
```bash
docker-compose logs -f
```

3. **Stop the server:**
```bash
docker-compose down
```

### Manual Docker Build

```bash
# Build the image
docker build -t adms-server .

# Run the container
docker run -d \
  --name adms-server \
  -p 8080:8080 \
  -v $(pwd)/database:/app/database \
  -v $(pwd)/logs:/app/logs \
  -e ADMS_API_KEY="your-secure-key" \
  adms-server
```

---

## Traditional Linux Server

### Prerequisites
- PHP 7.4 or higher
- PHP extensions: pdo, pdo_sqlite, sockets
- Composer
- Web server (Nginx or Apache)

### Installation Steps

1. **Install PHP and dependencies:**
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install -y php7.4 php7.4-cli php7.4-sqlite3 php7.4-fpm composer

# CentOS/RHEL
sudo yum install -y php php-cli php-pdo php-sqlite3 composer
```

2. **Clone and setup:**
```bash
# Clone repository
git clone https://github.com/abubaker47/php-adms-server.git
cd php-adms-server

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php database/migrate.php

# Set permissions
sudo chown -R www-data:www-data .
chmod 755 database logs
chmod 644 database/adms.db
```

3. **Configure environment:**
```bash
# Copy environment file
cp .env.example .env

# Edit with your settings
nano .env
```

---

## Systemd Service

For running the server as a system service on Linux.

### Setup

1. **Copy service file:**
```bash
sudo cp adms-server.service /etc/systemd/system/
```

2. **Edit service configuration:**
```bash
sudo nano /etc/systemd/system/adms-server.service

# Update these fields:
# - WorkingDirectory: Your installation path
# - Environment: Your API key
# - User/Group: Your web server user
```

3. **Enable and start service:**
```bash
# Reload systemd
sudo systemctl daemon-reload

# Enable service to start on boot
sudo systemctl enable adms-server

# Start the service
sudo systemctl start adms-server

# Check status
sudo systemctl status adms-server
```

4. **View logs:**
```bash
sudo journalctl -u adms-server -f
```

---

## Nginx Configuration

### Configuration File

Create `/etc/nginx/sites-available/adms-server`:

```nginx
server {
    listen 80;
    server_name adms.example.com;

    root /var/www/adms-server/public;
    index index.php;

    access_log /var/log/nginx/adms-access.log;
    error_log /var/log/nginx/adms-error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Enable Site

```bash
# Create symbolic link
sudo ln -s /etc/nginx/sites-available/adms-server /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

### SSL/HTTPS with Let's Encrypt

```bash
# Install certbot
sudo apt install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d adms.example.com

# Auto-renewal is setup automatically
```

---

## Apache Configuration

### Configuration File

Create `/etc/apache2/sites-available/adms-server.conf`:

```apache
<VirtualHost *:80>
    ServerName adms.example.com
    DocumentRoot /var/www/adms-server/public

    <Directory /var/www/adms-server/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Enable rewrite engine
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^ index.php [L]
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/adms-error.log
    CustomLog ${APACHE_LOG_DIR}/adms-access.log combined
</VirtualHost>
```

### Enable Site

```bash
# Enable required modules
sudo a2enmod rewrite
sudo a2enmod headers

# Enable site
sudo a2ensite adms-server

# Reload Apache
sudo systemctl reload apache2
```

### SSL/HTTPS with Let's Encrypt

```bash
# Install certbot
sudo apt install certbot python3-certbot-apache

# Obtain certificate
sudo certbot --apache -d adms.example.com
```

---

## Security Best Practices

### 1. API Key Management

```bash
# Generate a strong random key
openssl rand -base64 32

# Set as environment variable
export ADMS_API_KEY="your-generated-key"

# Or add to .env file
echo "ADMS_API_KEY=your-generated-key" >> .env
```

### 2. File Permissions

```bash
# Set proper ownership
sudo chown -R www-data:www-data /var/www/adms-server

# Restrict file permissions
find /var/www/adms-server -type f -exec chmod 644 {} \;
find /var/www/adms-server -type d -exec chmod 755 {} \;

# Make scripts executable
chmod +x /var/www/adms-server/database/migrate.php
chmod +x /var/www/adms-server/test-api.sh
```

### 3. Database Security

```bash
# Secure database file
chmod 600 database/adms.db
chown www-data:www-data database/adms.db

# Regular backups
# Add to crontab:
0 2 * * * cp /var/www/adms-server/database/adms.db /backup/adms-$(date +\%Y\%m\%d).db
```

### 4. Firewall Configuration

```bash
# Allow HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Allow only specific IPs for API access (optional)
sudo ufw allow from 192.168.1.0/24 to any port 8080

# Enable firewall
sudo ufw enable
```

### 5. Log Rotation

Create `/etc/logrotate.d/adms-server`:

```
/var/www/adms-server/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

### 6. PHP Security Settings

Edit `php.ini`:

```ini
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php-errors.log
max_execution_time = 30
memory_limit = 128M
upload_max_filesize = 2M
```

---

## Monitoring

### Health Check Script

Create `/usr/local/bin/adms-healthcheck.sh`:

```bash
#!/bin/bash
HEALTH_URL="http://localhost:8080/api/health"
API_KEY="your-api-key"

RESPONSE=$(curl -s -H "X-API-Key: $API_KEY" $HEALTH_URL)

if echo "$RESPONSE" | grep -q '"status":"healthy"'; then
    echo "OK: ADMS server is healthy"
    exit 0
else
    echo "CRITICAL: ADMS server is not responding"
    exit 2
fi
```

### Systemd Health Check

Add to systemd service file:

```ini
[Service]
ExecStartPre=/usr/local/bin/adms-healthcheck.sh
```

---

## Backup and Recovery

### Database Backup Script

Create `/usr/local/bin/adms-backup.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/backup/adms"
DB_PATH="/var/www/adms-server/database/adms.db"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR
sqlite3 $DB_PATH ".backup $BACKUP_DIR/adms_$DATE.db"
gzip $BACKUP_DIR/adms_$DATE.db

# Keep only last 30 days
find $BACKUP_DIR -name "adms_*.db.gz" -mtime +30 -delete
```

### Automated Backups

Add to crontab:

```bash
# Daily backup at 2 AM
0 2 * * * /usr/local/bin/adms-backup.sh
```

---

## Troubleshooting

### Common Issues

1. **Permission denied on database:**
```bash
sudo chown www-data:www-data database/adms.db
sudo chmod 664 database/adms.db
```

2. **Socket extension not found:**
```bash
# Install sockets extension
sudo apt install php-sockets
sudo systemctl restart php7.4-fpm
```

3. **Service won't start:**
```bash
# Check logs
sudo journalctl -u adms-server -n 50

# Check PHP errors
tail -f /var/log/php-errors.log
```

---

## Performance Tuning

### PHP-FPM Configuration

Edit `/etc/php/7.4/fpm/pool.d/www.conf`:

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
```

### Database Optimization

```sql
-- Run periodically
VACUUM;
ANALYZE;
```

---

## Scaling

For high-load environments:

1. **Load Balancer**: Use Nginx or HAProxy
2. **Database**: Consider migrating to PostgreSQL or MySQL
3. **Caching**: Implement Redis for API responses
4. **Multiple Instances**: Run multiple ADMS instances behind load balancer

---

## Support

For issues and questions:
- GitHub Issues: https://github.com/abubaker47/php-adms-server/issues
- Documentation: See README.md and API.md
