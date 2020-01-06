#!/bin/bash

set -e

i=1
total=100
if [ ! -z $1 ]; then
  total="$1"
fi

for theme in $(lando wp --skip-plugins --skip-themes query-popular-themes $total); do
  theme=$(tr -d '\r\n' <<< $theme)
  echo "## $theme ($i of $total)"
  bash check-theme.sh "$theme" || echo "Failed to check theme"
  i=$((i+1))
  echo
done
