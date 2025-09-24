#!/bin/bash

echo "🧹 Cleaning everything..."
rm -rf build/ vendor/

# Install production dependencies only
composer install --no-dev --optimize-autoloader

# Run PHP-Scoper
/home/mindell-zamora/tools/php-scoper add-prefix --output-dir=build --force

# Create minimal composer.json for autoloading
cat > build/composer.json << 'EOF'
{
    "name": "citizenone/formular-scoped",
    "autoload": {
        "classmap": [
            "../ajax/",
            "../backend/",
            "../cli/",
            "../engine/",
            "../frontend/",
            "../integrations/",
            "../internals/",
            "../internals/models/",
            "../internals/views/",
            "../rest/"
		]
    }
}
EOF

# Generate autoloader
composer dump-autoload --working-dir=build --classmap-authoritative

rm -rf vendor/  # ✅ IMPORTANT: Remove original

# Rename the 'build' directory (which contains the scoped dependencies) to 'vendor'.
mv build vendor

echo "✅ Build completed successfully!"
