#!/bin/bash

# GitHub Release Creator Script
# Usage: ./create-release.sh v2.0.1 "Release description"

VERSION=$1
DESCRIPTION=$2

if [ -z "$VERSION" ] || [ -z "$DESCRIPTION" ]; then
    echo "Usage: ./create-release.sh <version> <description>"
    echo "Example: ./create-release.sh v2.0.1 'Fix menu structure and critical errors'"
    exit 1
fi

# Create the release using GitHub CLI
gh release create "$VERSION" \
    --title "Version ${VERSION#v}" \
    --notes "$DESCRIPTION" \
    --repo imranduzzlo/pv-team-payroll

if [ $? -eq 0 ]; then
    echo "✅ Release $VERSION created successfully!"
    echo "WordPress will detect the update within 12 hours or when you click 'Check for updates'"
else
    echo "❌ Failed to create release. Make sure you have GitHub CLI installed and authenticated."
    echo "Install GitHub CLI: https://cli.github.com/"
fi
