#!/bin/bash

set -x
set -e
lando start

if [ ! -e public ]; then
  mkdir public
fi

if [ ! -e public/index.php ]; then
  lando wp core download
fi

if ! lando wp core is-installed; then
  lando wp core install --url="https://amp-wp-theme-compat-analysis.lndo.site/" --title="AMP WP Theme Compatibility Analysis" --admin_user=admin --admin_password=password --admin_email=nobody@example.com
fi

lando wp plugin install --activate amp
lando wp option update --json amp-options '{"theme_support":"standard"}'

lando wp plugin install --activate wordpress-importer
lando wp plugin install --activate block-unit-test
lando wp plugin install --activate coblocks

if [ ! -e themeunittestdata.wordpress.xml ]; then
  wget https://raw.githubusercontent.com/WPTRT/theme-unit-test/master/themeunittestdata.wordpress.xml
  lando wp import --authors=create themeunittestdata.wordpress.xml
fi

if [ ! -e only_amp.mp4 ]; then
  wget https://blog.amp.dev/wp-content/uploads/2019/04/only_amp.mp4
  lando wp media import --title="Accelerated Mobile Pages is now just AMP" only_amp.mp4
fi

lando wp create-monster-post
lando wp populate-initial-widgets
