#!/bin/bash

lando start

if [ ! -e public ]; then
  mkdir public
fi

if [ ! -e public/index.php ]; then
  lando wp core download
fi

if ! wp core is-installed; then
  lando wp core install --url="https://amp-wp-theme-compat-analysis.lndo.site/" --title="AMP WP Theme Compatibility Analysis" --admin_user=admin --admin_password=password --admin_email=nobody@example.com
fi
