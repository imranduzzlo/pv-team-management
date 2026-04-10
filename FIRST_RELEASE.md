# Creating Your First GitHub Release

Follow these steps to create the first release so WordPress can detect updates:

## Step 1: Go to GitHub Releases Page

Visit: https://github.com/imranduzzlo/pv-team-payroll/releases

## Step 2: Click "Create a new release"

Or go directly to: https://github.com/imranduzzlo/pv-team-payroll/releases/new

## Step 3: Fill in the Release Details

### Tag version
Enter: `v2.0.0`

(This MUST start with 'v' followed by the version number)

### Release title
Enter: `Version 2.0.0 - Initial Release`

### Description
Copy and paste this:

```
# WooCommerce Team Payroll & Commission System v2.0.0

Initial release of the WooCommerce Team Payroll & Commission System plugin.

## Features

- Commission management with customizable agent/processor split (70/30 default)
- Three salary types: Fixed Salary, Commission-Based, Combined (Base + Commission)
- Salary history tracking with change logs
- Manual payment system with AJAX date/time picker
- Per-order bonus system (employee-specific, not shared)
- Extra earnings rules with conditional logic
- Refunded order commission handling (None/Percentage/Flat)
- Order change logging with timestamps
- Frontend My Account tabs for employees
- Checkout integration with auto-populating agent dropdown
- Role-based access control
- Admin dashboards (Team Dashboard, Payroll, Employee Management)
- AJAX-based operations (no page reloads)
- Shortcode system for displaying earnings
- Support for both ACF and SCF (Smart Custom Fields)
- GitHub automatic update support

## Requirements

- WordPress 5.0+
- WooCommerce 5.0+
- Advanced Custom Fields (ACF) or Smart Custom Fields (SCF)
- PHP 7.2+

## Installation

1. Download from GitHub
2. Upload to `/wp-content/plugins/`
3. Activate in WordPress admin
4. Configure in Team Payroll > Settings

## Support

GitHub: https://github.com/imranduzzlo/pv-team-payroll
```

## Step 4: Publish the Release

Click the green "Publish release" button

## Step 5: Verify

After publishing:
1. Go back to the releases page
2. You should see "v2.0.0" listed
3. The ZIP file should be available for download

## Step 6: Test in WordPress

Now test that WordPress can detect the update:

1. Go to your WordPress admin
2. Go to Plugins page
3. Look for "WooCommerce Team Payroll & Commission System"
4. You should see the version is 2.0.0
5. Click "Check for updates" (or wait for automatic check)
6. The update system should recognize the release

## What Happens Next

Once the release is created:
- WordPress will check GitHub for updates automatically
- When you make changes and create a new release (e.g., v2.0.1), WordPress will show an update notification
- Users can click "Update Now" to install the new version automatically

## Creating Future Releases

For each new version:

1. Update the version number in `woocommerce-team-payroll.php`
2. Update `CHANGELOG.md` with your changes
3. Commit and push to GitHub:
   ```bash
   git add .
   git commit -m "Release v2.0.1"
   git push origin main
   ```
4. Create a new release on GitHub with the new tag (e.g., `v2.0.1`)
5. Add release notes in the description

That's it! WordPress will automatically detect the new version and show an update notification.

## Troubleshooting

**Release not showing in WordPress?**
- Make sure the tag starts with 'v' (e.g., `v2.0.0`)
- Check that the version in the plugin file matches the tag
- Wait up to 12 hours for cache to clear
- Try manually checking for updates in WordPress

**Can't find the releases page?**
- Go to: https://github.com/imranduzzlo/pv-team-payroll
- Click the "Releases" link on the right side
- Or go directly to: https://github.com/imranduzzlo/pv-team-payroll/releases
