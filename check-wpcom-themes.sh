#!/bin/bash

set -e
set -x

theme_root='public/wp-content/theme-directories/wpcom-themes'
results_root='results/theme-directories/wpcom-themes'

if [ ! -e "$theme_root" ]; then
	svn co "https://wpcom-themes.svn.automattic.com/" "$theme_root"
fi

theme_count=$(ls "$theme_root" | wc -l | sed 's/ //g')

for theme in $(ls "$theme_root"); do
	if [ ! -d "$theme_root/$theme" ]; then
		continue
	fi
	echo $theme
  i=$((i+1))

  if [ -e "$results_root/$theme" ]; then
    echo "Results already obtained for theme: $theme"
    continue
  fi

  echo "## $theme ($i of $theme_count)"
	cp -r "$theme_root/$theme" "public/wp-content/themes/$theme"

  # Ensure the parent theme is installed (sometimes auto-installed other times not?)
  parent_theme=$(lando wp get-parent-theme "$theme")
  if [ ! -z "$parent_theme" ]; then
  	cp -r "$theme_root/$parent_theme" "public/wp-content/themes/$parent_theme"
  fi

  bash check-theme.sh "$theme" "wpcom-themes" || echo "Failed to check theme"

	# Clean out themes directory.
  rm -R public/wp-content/themes/*
  echo
done
