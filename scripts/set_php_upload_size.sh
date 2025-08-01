#!/bin/bash
PHP_INI=$(php -i | grep "Loaded Configuration" | awk '{print $5}')
if [[ -z "$PHP_INI" ]]; then
  echo "Could not find php.ini"
  exit 1
fi
echo "Using PHP ini: $PHP_INI"
sudo sed -i -r \
    -e 's/^(upload_max_filesize\s*=\s*).*/\1100M/' \
    -e 's/^(post_max_size\s*=\s*).*/\1100M/' \
    "$PHP_INI"
echo "upload_max_filesize and post_max_size set to 100M."
SERVICE=$(systemctl list-units --type=service --state=running | grep php | grep fpm | awk '{print $1}' | head -n1)
if [[ -n "$SERVICE" ]]; then
  sudo systemctl reload "$SERVICE"
  echo "Reloaded $SERVICE"
else
  echo "PHP-FPM service not found or not running. You may need to restart it manually."
fi
