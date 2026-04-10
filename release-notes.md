# Release Notes

## v5.3.3 - Payment Calculation Fix

### Critical Fixes
- **FIXED**: Total Paid now shows correct amount from payments array
- **FIXED**: Paid amounts calculated from `_wc_tp_payments` meta, not old keys
- **FIXED**: Date range filtering for payments now works correctly

### Improvements
- Dashboard now shows accurate paid/due amounts
- All currency amounts use WooCommerce store currency
- Payment calculations respect date range filters

### What Was Wrong
The payroll engine was looking for old meta keys (`_payroll_paid_YYYY_MM`) that don't exist. Payments are stored in `_wc_tp_payments` array. Now it correctly reads from the payments array and calculates totals.

---

## v5.3.2 - Critical Fixes

### Critical Fixes
- **FIXED**: Removed duplicate dashboard submenu (root cause of duplication)
- **FIXED**: Employee role query now includes shop_employee role
- **FIXED**: Latest Employees table now shows 10 employees with proper format

### Changes
- Latest Employees table now matches Team Members page format
- Shows Name, Email, Type, Salary/Commission, and Manage button
- Displays all employee roles (shop_employee, shop_manager, administrator)

### What Was Wrong
The dashboard was rendering twice because both the main menu and a submenu were pointing to the same page slug. This has been fixed by removing the redundant submenu.

---

## v5.3.1 - Dashboard Fixes

### Bug Fixes
- Fixed dashboard content duplication
- Added empty state icons instead of notices
- Show all employees regardless of commission status

### Improvements
- Cleaner UI with professional empty states
- Better data presentation
- Employees without commission now visible in tables

---

## v5.3.0 - Redesigned Dashboard

### New Features
- **Date Range Filter** - AJAX-based filtering with date picker
- **Latest Employees Table** - Shows 10 most recent employees
- **Top Earners Table** - Displays top 5 earners for the period
- **Recent Payments Table** - Shows 10 most recent payments
- **Professional Responsive Layout** - Mobile-friendly design

### Improvements
- Removed dashboard duplication
- Enhanced styling with status badges (Paid/Pending/Failed)
- Better visual hierarchy and typography
- Clean empty state icons instead of notices

### Technical Changes
- Added `get_payroll_by_date_range()` method to Payroll Engine
- New helper methods: `get_latest_employees()`, `get_recent_payments()`, `get_top_earners()`
- Improved CSS with responsive grid layouts
- AJAX-ready date filtering
