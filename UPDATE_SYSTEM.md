# WooCommerce Team Payroll - Automatic Update System

## Overview

The plugin now has a complete GitHub-based automatic update system that integrates seamlessly with WordPress. When you release a new version on GitHub, WordPress will automatically detect it and notify users about the update.

## How It Works

### 1. **Version Detection**
- The plugin checks GitHub's API for the latest release
- Compares the GitHub release version with the installed plugin version
- If a newer version is found, WordPress displays an update notification

### 2. **Update Flow**
```
GitHub Release Created (v1.0.0)
         ↓
WordPress Admin Checks for Updates
         ↓
GitHub API Returns Latest Release Info
         ↓
Version Comparison (1.0.0 > current version?)
         ↓
Update Notification Shown to Admin
         ↓
Admin Clicks "Update Now"
         ↓
WordPress Downloads ZIP from GitHub
         ↓
Plugin Updated Automatically
```

### 3. **Key Features**

#### Automatic Update Checks
- Checks for updates every hour (configurable)
- Runs on WordPress admin page load
- Caches results to avoid excessive API calls

#### Update Information Display
- Shows new version number
- Displays changelog from GitHub releases
- Links to GitHub release page
- Shows requirements (WordPress, PHP, WooCommerce versions)

#### Seamless Integration
- Works like any other WordPress plugin update
- Uses WordPress's built-in update mechanism
- No additional configuration needed
- Maintains all plugin functionality during update

## Configuration

### Plugin File Headers
```php
* Plugin Name: WooCommerce Team Payroll & Commission System
* Version: 1.0.0
* GitHub Plugin URI: imranduzzlo/pv-team-payroll
* GitHub Branch: main
```

### GitHub Updater Class
Located in: `includes/class-github-updater.php`

**Configuration:**
- GitHub User: `imranduzzlo`
- GitHub Repo: `pv-team-payroll`
- GitHub Branch: `main`
- API Endpoint: `https://api.github.com/repos/imranduzzlo/pv-team-payroll`

## Creating a New Release

### Step 1: Update Version Numbers
Update these files with the new version:
- `woocommerce-team-payroll.php` - Plugin header and constant
- `CHANGELOG.md` - Add new version entry

### Step 2: Commit Changes
```bash
git add -A
git commit -m "Release v1.1.0 - Description of changes"
```

### Step 3: Push to GitHub
```bash
git push origin main
```

### Step 4: Create GitHub Release
```bash
git tag -a v1.1.0 -m "Version 1.1.0 - Description"
git push origin v1.1.0
```

Or create release via GitHub web interface:
1. Go to https://github.com/imranduzzlo/pv-team-payroll/releases
2. Click "Create a new release"
3. Tag version: `v1.1.0`
4. Release title: `Version 1.1.0 - Description`
5. Add changelog in description
6. Click "Publish release"

## How Users Get Updates

### For End Users
1. WordPress admin checks for updates automatically
2. Update notification appears in Plugins page
3. User clicks "Update Now"
4. Plugin updates automatically
5. All functionality is preserved

### Update Notification Example
```
WooCommerce Team Payroll & Commission System
Version 1.1.0 is available. Update now.
```

### Update Details Modal
When users click on the update notification, they see:
- New version number
- Changelog from GitHub
- Requirements (WordPress, PHP, WooCommerce versions)
- Download link
- Last updated date

## Caching & Performance

### Cache Strategy
- Release info cached for 1 hour
- Update check runs once per hour
- Transient keys:
  - `wc_tp_github_release` - Latest release info
  - `wc_tp_last_update_check` - Last check timestamp

### Cache Clearing
Cache is automatically cleared when:
- Plugin is activated
- Admin page loads (if 1 hour has passed)
- Manual cache clear via settings

## Troubleshooting

### Updates Not Showing
1. Check WordPress version (requires 5.0+)
2. Verify GitHub repository is public
3. Clear WordPress update cache: `delete_transient('update_plugins')`
4. Check PHP error logs for API issues

### API Rate Limiting
- GitHub allows 60 requests per hour for unauthenticated requests
- Plugin caches results to minimize API calls
- If rate limited, updates will be checked again after 1 hour

### Version Comparison Issues
- Versions must follow semantic versioning (e.g., 1.0.0)
- Leading 'v' is automatically stripped (v1.0.0 → 1.0.0)
- Invalid versions default to 0.0.0

## Security Considerations

### GitHub API
- Uses HTTPS for all API calls
- SSL verification enabled
- No authentication required (public repository)

### Plugin Download
- Downloaded from GitHub's official CDN
- Verified by WordPress before installation
- Automatic backup created before update

### Data Integrity
- All user data preserved during update
- Database migrations handled automatically
- Settings and employee data untouched

## Version History

### v1.0.0 (Current)
- Initial production release
- Full GitHub-based update system
- All core features working
- Comprehensive commission and payroll management

## Support

For issues with the update system:
1. Check GitHub repository: https://github.com/imranduzzlo/pv-team-payroll
2. Review error logs in WordPress
3. Verify GitHub API is accessible
4. Check WordPress version compatibility

## Future Enhancements

Potential improvements:
- Beta release channel support
- Automatic rollback on update failure
- Update scheduling
- Changelog display in WordPress admin
- Update notifications via email
