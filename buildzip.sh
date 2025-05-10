#!/bin/bash
set -e

META_FILE="plugin.json"

if [ ! -f "$META_FILE" ]; then
	echo "Error: $META_FILE not found"
	exit 1
fi

# Extract metadata from plugin.json
echo "Reading metadata from $META_FILE..."
SLUG=$(jq -r '.slug' "$META_FILE")
NAME=$(jq -r '.name' "$META_FILE")
VERSION=$(jq -r '.version' "$META_FILE")
DESCRIPTION=$(jq -r '.description' "$META_FILE")
AUTHOR=$(jq -r '.author' "$META_FILE")
AUTHOR_URI=$(jq -r '.author_uri' "$META_FILE")
PLUGIN_URI=$(jq -r '.plugin_uri' "$META_FILE")
HOMEPAGE=$(jq -r '.homepage' "$META_FILE")
SUPPORT=$(jq -r '.support' "$META_FILE")
LICENSE=$(jq -r '.license' "$META_FILE")
CONTRIBUTORS=$(jq -r '.contributors | join(", ")' "$META_FILE")
TAGS=$(jq -r '.tags | join(", ")' "$META_FILE")

# Auto-detect main plugin file
MAIN_FILE=$(find . -maxdepth 1 -type f -name "*.php" | head -n1 | sed 's|^\./||')
if [ -z "$MAIN_FILE" ]; then
	echo "Error: No PHP plugin file found in current directory"
	exit 1
fi

PLUGIN_DIR="$SLUG"
ZIP_NAME="$SLUG.zip"

# Create plugin header block
HEADER=$(cat <<EOF
<?php
/*
Plugin Name: $NAME
Plugin URI: $PLUGIN_URI
Description: $DESCRIPTION
Version: $VERSION
Author: $AUTHOR
Author URI: $AUTHOR_URI
License: $LICENSE
Text Domain: $SLUG
# Support: $SUPPORT
*/
EOF
)

# Replace the header in the main plugin file
echo "Updating plugin header in $MAIN_FILE..."
awk '/^\s*<\?php/ { print; print ""; system("echo \"$HEADER\""); nextfile } 1' "$MAIN_FILE" > "$MAIN_FILE.tmp" && mv "$MAIN_FILE.tmp" "$MAIN_FILE"

# Generate readme.txt
echo "Generating readme.txt..."
cat > readme.txt <<EOF
=== $NAME ===
Contributors: $CONTRIBUTORS
Tags: $TAGS
Requires at least: 5.5
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: $VERSION
License: $LICENSE
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
$DESCRIPTION

== Screenshots or Resources ==
Plugin homepage: $HOMEPAGE  
Support or feedback: $SUPPORT

== Installation ==
1. Upload the plugin folder to \`/wp-content/plugins/\`
2. Activate via the WordPress Plugins menu
3. Configure from the Linkfolio settings screen

== Changelog ==
= $VERSION =
* Initial public release
EOF

# Build plugin folder and ZIP
rm -rf build
echo "Preparing zip package..."
mkdir -p build/$PLUGIN_DIR
rsync -av --exclude="$0" --exclude="$ZIP_NAME" --exclude=".*" --exclude="plugin.json" --exclude="build" . build/$PLUGIN_DIR

cd build
zip -r "../$ZIP_NAME" "$PLUGIN_DIR"
cd ..

echo "Plugin zip created: $ZIP_NAME"
