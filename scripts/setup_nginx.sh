#!/bin/bash

read -rp "Enter the FQDN for the site (e.g., presentation.AlistairHenderson.local): " SITE_NAME

WEBROOT_BASE="/var/www"
WEB_ROOT="$WEBROOT_BASE/presentation"  # Always /var/www/presentation
NGINX_CONF="/etc/nginx/sites-available/$SITE_NAME"
NGINX_LINK="/etc/nginx/sites-enabled/$SITE_NAME"
USER="www-data"

sudo mkdir -p "$WEB_ROOT"

# Auto-detect PHP-FPM socket
PHP_FPM_SOCK=$(find /var/run/php/ -name "php*-fpm.sock" | sort | head -n1)

if [[ -z "$PHP_FPM_SOCK" ]]; then
    echo "No PHP-FPM socket found in /var/run/php/. Is PHP-FPM installed and running?"
    exit 1
fi

echo "Using PHP-FPM socket: $PHP_FPM_SOCK"

sudo tee "$NGINX_CONF" > /dev/null <<EOF
server {
    listen 80;
    server_name $SITE_NAME;
    root $WEB_ROOT;

    index index.php index.html;
    client_max_body_size 100M;

    access_log  /var/log/nginx/$SITE_NAME.access.log;
    error_log   /var/log/nginx/$SITE_NAME.error.log;

    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:$PHP_FPM_SOCK;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

sudo ln -sf "$NGINX_CONF" "$NGINX_LINK"
sudo chown -R $USER:$USER "$WEB_ROOT"
sudo nginx -t && sudo systemctl reload nginx

echo "Nginx config created for $SITE_NAME with logging enabled."
echo "Web root: $WEB_ROOT"
echo "Access log: /var/log/nginx/$SITE_NAME.access.log"
echo "Error log:  /var/log/nginx/$SITE_NAME.error.log"
echo "PHP-FPM socket used: $PHP_FPM_SOCK"
