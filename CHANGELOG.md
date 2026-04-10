# Changelog

## [2.0.6] - 2026-04-10

### Fixed
- Fixed Dashboard page to display real payroll data
- Fixed Payroll page to display real payroll data
- Both pages now show employee details: name, email, orders, earnings, paid, and due amounts
- Added "View" button to access employee detail page from both pages
- Shows "No payroll data" message when no data is available for the selected period

## [2.0.5] - 2026-04-10

### Fixed
- Fixed Employee Detail page access permission error
- Registered Employee Detail page as hidden submenu
- Page is now recognized by WordPress as valid admin page
- Fixes "Sorry, you are not allowed to access this page" error when clicking manage on team members

## [2.0.4] - 2026-04-10

### CRITICAL FIX
- Fixed menu structure completely
- Fixed initialization order: Dashboard now initializes BEFORE Settings
- This ensures parent menu 'wc-team-payroll' exists before Settings tries to attach
- Main menu "Team Payroll" now correctly goes to Dashboard (not Settings)
- Settings submenu now correctly attaches to parent menu
- Fixed "Sorry, you are not allowed to access this page" error on Settings
- Fixed critical errors on Dashboard and Payroll pages
- Simplified page rendering to avoid fatal errors

## [2.0.3] - 2026-04-10

### Fixed
- Fixed GitHub updater not detecting updates in WordPress admin
- Fixed plugin file path detection in update checker
- Now correctly uses relative plugin path for WordPress compatibility

## [2.0.2] - 2026-04-10

### CRITICAL FIX
- Fixed fatal errors on Dashboard and Payroll pages
- Fixed 8 critical static method call errors in Core Engine
- All static methods now correctly use `self::` instead of `$this->`
- Dashboard and Payroll pages now load without critical errors

## [2.0.1] - 2026-04-10

### Fixed
- Fixed menu structure: Main menu now goes to Dashboard instead of Settings
- Removed duplicate Dashboard submenu
- Removed Employee Detail submenu (accessed from Team Members page)
- Fixed critical errors on Dashboard and Payroll pages
- Added error handling for missing payroll data
- Added null checks for user objects to prevent crashes
- Dashboard and Payroll pages now show "No payroll data" message instead of critical error

### Improved
- Better error handling in Dashboard and Payroll rendering
- Safer class existence checks before calling methods

## [2.0.0] - 2026-04-10

### Added
- Initial release of WooCommerce Team Payroll & Commission System
- Commission management with customizable agent/processor split (70/30 default)
- Three salary types: Fixed Salary, Commission-Based, Combined (Base + Commission)
- Salary history tracking with change logs
- Manual payment system with AJAX date/time picker
- Per-order bonus system (employee-specific, not shared)
- Extra earnings rules with conditional logic
- Support for multiple condition types: order total, specific products, categories, agents
- Refunded order commission handling (None/Percentage/Flat)
- Order change logging with timestamps
- Frontend My Account tabs:
  - Salary Details (with history)
  - My Earnings (monthly summary)
  - My Orders (Commission) with filters
  - Reports (quick summary cards)
- Checkout integration with auto-populating agent dropdown
- Role-based access control
- Admin dashboards:
  - Team Dashboard
  - Payroll Management
  - Employee Management
  - Employee Detail page
- AJAX-based operations (no page reloads)
- Shortcode system for displaying earnings
- Support for both ACF and SCF (Smart Custom Fields)
- GitHub automatic update support
- Comprehensive settings page for configuration

### Features
- All operations work via AJAX without page reloads
- Configurable field names for custom checkout and ACF fields
- Automatic commission recalculation on order changes
- Role-based employee filtering
- Date range filtering for earnings reports
- Salary type indicators in admin and frontend
- Complete change history for all operations

### Technical
- Static method architecture for proper WordPress integration
- Proper nonce verification for security
- Sanitized and escaped output
- Support for WordPress 5.0+
- WooCommerce 5.0+ compatible
- PHP 7.2+ required
