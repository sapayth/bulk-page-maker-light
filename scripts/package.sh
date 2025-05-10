#!/bin/bash

set -e

PLUGIN_SLUG="bulk-page-maker-light"
RELEASE_DIR="release"
DIST_DIR="dist"
ZIP_FILE="$DIST_DIR/$PLUGIN_SLUG.zip"

# 1. Run build process
npm run build

# 2. Clean up previous release and dist directories
rm -rf "$RELEASE_DIR" "$DIST_DIR"
mkdir -p "$RELEASE_DIR" "$DIST_DIR"

# 3. Copy required files and directories
cp "$PLUGIN_SLUG.php" "$RELEASE_DIR/"
cp composer.json "$RELEASE_DIR/"
cp readme.txt "$RELEASE_DIR/"
cp -R includes "$RELEASE_DIR/"
cp -R assets "$RELEASE_DIR/"
cp -R vendor "$RELEASE_DIR/"
cp -R build "$RELEASE_DIR/"

# 4. Create zip archive of the release directory contents
cd "$RELEASE_DIR"
zip -r "../$ZIP_FILE" .
cd ..

# 5. Keep the release directory after zipping

echo "Build and packaging complete."
echo "Release directory: $RELEASE_DIR/"
echo "Distribution zip: $ZIP_FILE" 