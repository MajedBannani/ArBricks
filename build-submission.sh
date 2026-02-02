#!/bin/bash

# ArBricks WordPress.org Submission Package Builder
# Version: 1.0
# Date: 2026-02-02

set -e

echo "🚀 ArBricks WordPress.org Submission Package Builder"
echo "=================================================="
echo ""

# Configuration
PLUGIN_SLUG="arbricks"
PLUGIN_VERSION="2.0.0"
BUILD_DIR="./build"
PACKAGE_NAME="${PLUGIN_SLUG}.${PLUGIN_VERSION}"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "📦 Building submission package for ArBricks v${PLUGIN_VERSION}"
echo ""

# Clean previous build
if [ -d "$BUILD_DIR" ]; then
    echo "${YELLOW}Cleaning previous build...${NC}"
    rm -rf "$BUILD_DIR"
fi

# Create build directory
echo "📁 Creating build directory..."
mkdir -p "$BUILD_DIR/$PLUGIN_SLUG"

# Copy plugin files
echo "📋 Copying plugin files..."
rsync -av --progress \
    --exclude='.git' \
    --exclude='.gitignore' \
    --exclude='.gitattributes' \
    --exclude='.DS_Store' \
    --exclude='node_modules' \
    --exclude='tests' \
    --exclude='README.md' \
    --exclude='composer.lock' \
    --exclude='.phpcs.xml' \
    --exclude='phpunit.xml' \
    --exclude='*.backup' \
    --exclude='*.bak' \
    --exclude='*.bak2' \
    --exclude='*.bak3' \
    --exclude='*.broken' \
    --exclude='*.fix' \
    --exclude='*.fix2' \
    --exclude='*.fixbackup' \
    --exclude='*.parsefix' \
    --exclude='build' \
    --exclude='*.zip' \
    ./ "$BUILD_DIR/$PLUGIN_SLUG/"

# Verify required files
echo ""
echo "✅ Verifying required files..."

REQUIRED_FILES=(
    "arbricks.php"
    "readme.txt"
    "uninstall.php"
    "languages/arbricks.pot"
    "assets/vendor/qrcode/qrcode.js"
    "assets/vendor/qrcode/LICENSE"
)

for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "$BUILD_DIR/$PLUGIN_SLUG/$file" ]; then
        echo "${GREEN}✓${NC} $file"
    else
        echo "❌ MISSING: $file"
        exit 1
    fi
done

# Create ZIP
echo ""
echo "🗜️  Creating ZIP archive..."
cd "$BUILD_DIR"
zip -r "${PACKAGE_NAME}.zip" "$PLUGIN_SLUG/" -q

# Get file size
FILESIZE=$(ls -lh "${PACKAGE_NAME}.zip" | awk '{print $5}')

echo ""
echo "${GREEN}✅ Submission package created successfully!${NC}"
echo ""
echo "📦 Package: build/${PACKAGE_NAME}.zip"
echo "📏 Size: ${FILESIZE}"
echo ""
echo "🚀 Next steps:"
echo "   1. Review: build/${PLUGIN_SLUG}/ contents"
echo "   2. Submit: https://wordpress.org/plugins/developers/add/"
echo "   3. Upload: build/${PACKAGE_NAME}.zip"
echo ""
echo "Good luck with your submission! 🎉"
