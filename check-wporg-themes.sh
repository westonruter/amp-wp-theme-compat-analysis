#!/bin/bash

set -e

i=0
total=100
if [ ! -z $1 ]; then
  total="$1"
fi

mkdir -p public/wp-content/theme-directories/wporg-themes

for theme in $(lando wp --skip-plugins --skip-themes query-popular-themes $total); do
  theme=$(tr -d '\r\n' <<< $theme)
  i=$((i+1))

  if [ -e "results/theme-directories/wporg-themes/$theme" ]; then
    echo "Results already obtained for theme: $theme"
    continue
  fi

  echo "## $theme ($i of $total)"

  lando wp --skip-plugins --skip-themes theme install "$theme" || continue

  # Ensure the parent theme is installed (sometimes auto-installed other times not?)
  parent_theme=$(lando wp get-parent-theme "$theme")
  if [ ! -z "$parent_theme" ]; then
    lando wp --skip-plugins --skip-themes theme install "$parent_theme" || continue
  fi

  bash check-theme.sh "$theme" "wporg-themes" || echo "Failed to check theme"

  for theme_dir in public/wp-content/themes/*; do
    theme_basename=$(basename "$theme_dir")
    if [ ! -e "public/wp-content/theme-directories/wporg-themes/$theme_basename" ]; then
      mv "$theme_dir" "public/wp-content/theme-directories/wporg-themes/$theme_basename"
    else
      rm -R "$theme_dir"
    fi
  done
  echo
done
