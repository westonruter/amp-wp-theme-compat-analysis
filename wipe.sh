#!/bin/bash

set -e

echo "Deleting uploaded files..."
rm -r public/wp-content/uploads/* || echo "Uploaded files empty already"
lando wp db reset --yes
echo "Done"
