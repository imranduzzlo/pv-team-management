# Changelog

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
