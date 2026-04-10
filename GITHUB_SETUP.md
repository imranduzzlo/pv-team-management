# GitHub Automatic Updates Setup Guide

This plugin is now configured to receive automatic updates from GitHub. Here's how to use it:

## How It Works

1. **Version Control**: The plugin code is hosted on GitHub at `https://github.com/imranduzzlo/pv-team-payroll`
2. **Automatic Detection**: WordPress checks GitHub for new releases automatically
3. **One-Click Updates**: When a new version is available, you'll see an update notification in your WordPress admin
4. **Automatic Installation**: Click "Update Now" and the new version downloads and installs automatically

## Creating a New Release (For Updates)

When you want to release a new version:

### Step 1: Make Your Changes
- Edit the plugin files locally
- Test thoroughly

### Step 2: Update Version Number
Edit `woocommerce-team-payroll.php` and update the version:
```php
* Version: 2.0.1
```

### Step 3: Update Changelog
Edit `CHANGELOG.md` and add your changes under a new version section:
```markdown
## [2.0.1] - 2026-04-15

### Fixed
- Fixed bug in commission calculation
- Improved performance

### Added
- New feature X
```

### Step 4: Commit and Push
```bash
git add .
git commit -m "Release v2.0.1: Description of changes"
git push origin main
```

### Step 5: Create GitHub Release
Go to https://github.com/imranduzzlo/pv-team-payroll/releases and:

1. Click "Create a new release"
2. Set Tag version: `v2.0.1` (must start with 'v')
3. Set Release title: `Version 2.0.1`
4. Copy the changelog content into the description
5. Click "Publish release"

**Important**: The tag name MUST start with 'v' (e.g., `v2.0.1`, `v2.0.2`)

## How WordPress Detects Updates

WordPress will:
1. Check the GitHub API for the latest release
2. Compare the release tag version with your current plugin version
3. Show an update notification if a newer version exists
4. Download the release ZIP file when you click "Update Now"

## Testing Updates Locally

To test the update system:

1. Install the plugin on a test WordPress site
2. Create a test release on GitHub with a higher version number
3. Go to WordPress Dashboard > Plugins
4. Click "Check for updates" (or wait for automatic check)
5. You should see the update notification
6. Click "Update Now" to test the installation

## Troubleshooting

### Updates Not Showing
- Ensure the release tag starts with 'v' (e.g., `v2.0.1`)
- Check that the version in the plugin file matches the release tag
- Wait up to 12 hours for the cache to clear
- Manually clear the transient: Go to WordPress admin and check for updates

### Update Fails
- Check that the GitHub repository is public
- Verify the release ZIP file is valid
- Check WordPress error logs for details

## File Structure for Releases

When you create a release, GitHub automatically generates a ZIP file. The plugin expects this structure:
```
woocommerce-team-payroll/
├── woocommerce-team-payroll.php
├── includes/
│   ├── class-*.php
├── assets/
│   ├── css/
│   ├── js/
└── languages/
```

## Version Numbering

Use semantic versioning:
- `2.0.0` - Major release (breaking changes)
- `2.0.1` - Patch release (bug fixes)
- `2.1.0` - Minor release (new features)

## Quick Reference

**To release a new version:**
```bash
# 1. Update version in woocommerce-team-payroll.php
# 2. Update CHANGELOG.md
# 3. Commit and push
git add .
git commit -m "Release v2.0.1"
git push origin main

# 4. Create release on GitHub (via web interface)
# - Tag: v2.0.1
# - Title: Version 2.0.1
# - Description: Copy from CHANGELOG.md
```

## Support

For issues with the update system, check:
- GitHub repository: https://github.com/imranduzzlo/pv-team-payroll
- WordPress plugin settings: Team Payroll > Settings
- WordPress error logs: `/wp-content/debug.log`
