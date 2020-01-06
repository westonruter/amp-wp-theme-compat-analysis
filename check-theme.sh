#!/bin/bash

set -e

theme=$1
if [ -z $theme ]; then
  echo "Missing theme arg."
  exit 1
fi

set -x

if [ -e "results/$theme" ]; then
  echo "Results already obtained for theme: $theme"
  exit
fi

lando wp --skip-plugins --skip-themes theme install --activate "$theme"

mkdir -p "results/$theme"

lando wp plugin activate populate-widget-areas populate-nav-menu-locations
monster_post_url=$(lando wp post list --post_type=post --name=monster --field=url | tr -d '\r\n')
curl "$monster_post_url" > "results/$theme/monster.html"
lando wp amp validation check-url "$monster_post_url" > "results/$theme/monster.json"

lando wp plugin deactivate populate-widget-areas populate-nav-menu-locations
hello_world_post_url=$(lando wp post list --post_type=post --name=hello-world --field=url | tr -d '\r\n')
curl "$hello_world_post_url" > "results/$theme/hello-world.html"
lando wp amp validation check-url "$hello_world_post_url" > "results/$theme/hello-world.json"

if [ -e /Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome ]; then
  /Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome --headless --disable-gpu --screenshot "$hello_world_post_url"
  mv screenshot.png "results/$theme/hello-world.png"
fi
