#!/bin/bash

read -rp "Enter the FQDN for the site (e.g., presentation.AlistairHenderson.local): " SITE_NAME

WEB_ROOT="/var/www/$SITE_NAME"
NGINX_CONF="/etc/nginx/sites-available/$SITE_NAME"
NGINX_LINK="/etc/nginx/sites-enabled/$SITE_NAME"
USER="www-data"

sudo mkdir -p "$WEB_ROOT"

sudo tee "$NGINX_CONF" > /dev/null <<EOF
server {
    listen 80;
    server_name $SITE_NAME;
    root $WEB_ROOT;
    index index.php index.html;
    client_max_body_size 100M;
    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
    }
    location ~ /\.ht {
        deny all;
    }
}
EOF

sudo ln -sf "$NGINX_CONF" "$NGINX_LINK"
sudo chown -R $USER:$USER "$WEB_ROOT"
sudo nginx -t && sudo systemctl reload nginx
