# WooCommerce Team Payroll & Commission System

A comprehensive WordPress plugin for managing team-based commission and payroll systems with agents and processors.

## 🚀 Features

- **Employee Management**: Manage agents and processors with detailed profiles
- **Commission Tracking**: Automatic commission calculation based on roles
- **Payroll System**: Track earnings, payments, and salary history
- **Orders Management**: View and filter orders by employee role
- **Performance Tracking**: Monitor employee performance metrics
- **My Account Integration**: Custom endpoints for employees to view their data
- **Admin Dashboard**: Comprehensive admin interface for managing team payroll

## 📋 Requirements

- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.2 or higher

## 📦 Installation

### From GitHub Release (Recommended)

1. Download the latest release ZIP from [Releases](https://github.com/imranduzzlo/pv-team-payroll/releases)
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the ZIP file and click "Install Now"
4. Activate the plugin

### Manual Installation

1. Clone this repository or download as ZIP
2. Upload to `/wp-content/plugins/woocommerce-team-payroll/`
3. Activate the plugin through the 'Plugins' menu in WordPress

## 🔄 Automatic Updates

This plugin supports automatic updates from GitHub:

- Updates are checked automatically every 12 hours
- Update notifications appear in WordPress admin (Plugins page)
- Click "Update Now" to install the latest version
- Works whether the plugin is active or inactive

### Force Update Check

To manually check for updates, add `?force-check=1` to your plugins page URL:
```
https://yoursite.com/wp-admin/plugins.php?force-check=1
```

## ⚙️ Configuration

1. Go to **WooCommerce → Team Payroll** in WordPress admin
2. Configure your settings:
   - Commission rates
   - Salary types
   - Payment methods
   - Performance metrics
3. Add employees and assign roles (Agent/Processor)
4. Start tracking commissions and payroll!

## 📖 Documentation

### Employee Roles

- **Agent**: Sales representatives who bring in orders
- **Processor**: Order processors who fulfill orders
- **Both**: Employees can have both roles simultaneously

### Commission Calculation

- Commissions are calculated based on order totals
- Different rates for agents and processors
- Attributed totals show the portion of order value for each role
- Automatic calculation on order status change

### My Account Endpoints

Employees can access their data through custom My Account pages:
- `/my-account/salary-details/` - View salary information
- `/my-account/my-earnings/` - View earnings history
- `/my-account/orders-commission/` - View orders and commissions
- `/my-account/reports/` - View performance reports

## 🛠️ Development

### File Structure

```
woocommerce-team-payroll/
├── assets/
│   ├── css/          # Stylesheets
│   └── js/           # JavaScript files
├── includes/
│   ├── class-*.php   # Core classes
│   └── ...
├── languages/        # Translation files
├── woocommerce-team-payroll.php  # Main plugin file
├── CHANGELOG.md      # Version history
└── README.md         # This file
```

### Creating a Release

1. Update version in `woocommerce-team-payroll.php`
2. Update `CHANGELOG.md` with changes
3. Commit and push changes
4. Create a new tag: `git tag v1.0.1`
5. Push tag: `git push origin v1.0.1`
6. Create GitHub Release from the tag
7. WordPress sites will automatically detect the update

## 🐛 Bug Reports

Found a bug? Please create an issue on [GitHub Issues](https://github.com/imranduzzlo/pv-team-payroll/issues).

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and changes.

## 👨‍💻 Author

**Imran Hossain**
- Website: [imranhossain.me](https://imranhossain.me/)
- GitHub: [@imranduzzlo](https://github.com/imranduzzlo)

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
Copyright (C) 2026 Imran Hossain

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 🙏 Support

If you find this plugin helpful, please consider:
- ⭐ Starring the repository
- 🐛 Reporting bugs
- 💡 Suggesting new features
- 📖 Improving documentation

---

**Version:** 1.0.0  
**Last Updated:** April 19, 2026
