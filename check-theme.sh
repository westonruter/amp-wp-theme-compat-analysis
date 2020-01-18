#!/bin/bash

set -e

theme=$1
if [ -z "$theme" ]; then
  echo "Missing theme arg."
  exit 1
fi

directory=$2
if [ -z "$directory" ]; then
  echo "Missing directory arg."
  exit 1
fi

set -x

if [ -e "results/theme-directories/$directory/$theme" ]; then
	rm -r "results/theme-directories/$directory/$theme"
fi

mkdir -p /tmp/pending-theme

lando wp --skip-plugins --skip-themes theme activate "$theme"

# TODO: Capture the time it takes to make each request.

lando wp plugin activate populate-widget-areas populate-nav-menu-locations
monster_post_url=$(lando wp --skip-plugins --skip-themes post list --post_type=post --name=monster --field=url | tr -d '\t \r\n')
curl -m 10 -f "$monster_post_url" > "/tmp/pending-theme/monster.html"
lando wp amp validation check-url "$monster_post_url" > "/tmp/pending-theme/monster.json"

lando wp plugin deactivate populate-widget-areas populate-nav-menu-locations
hello_world_post_url=$(lando wp --skip-plugins --skip-themes post list --post_type=post --name=hello-world --field=url | tr -d '\t \r\n')
curl -m 10 -f "$hello_world_post_url" > "/tmp/pending-theme/hello-world.html"
lando wp amp validation check-url "$hello_world_post_url" > "/tmp/pending-theme/hello-world.json"

#if [ -e /Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome ]; then
#  /Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome --headless --disable-gpu --screenshot "$hello_world_post_url"
#  mv screenshot.png "/tmp/pending-theme/hello-world.png"
#fi

# Once results/theme-directories/$directory have been obtained, move into directory.
mkdir -p "results/theme-directories/$directory/$theme"
mv /tmp/pending-theme/* "results/theme-directories/$directory/$theme"
