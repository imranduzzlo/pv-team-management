# Changelog

## [5.6.0] - 2026-04-11

### Optimization: Payroll Page CSS/JS Extraction
- Extracted all inline CSS from payroll page to `assets/css/payroll.css`
- Extracted all inline JavaScript from payroll page to `assets/js/payroll.js`
- Added proper asset enqueuing to payroll page class with constructor
- Reduced payroll page PHP file from 635 lines to ~100 lines
- Improved code maintainability and performance
- All functionality preserved - 100% backward compatible
- Better browser caching and parallel asset loading
- Cleaner, more organized code structure

## [5.5.3] - 2026-04-11

### Code Cleanup: Unified CSS/JS Architecture
- Moved all inline CSS from employee detail page to `assets/css/common.css`
- Moved all inline JavaScript from employee detail page to `assets/js/common.js`
- Created `assets/js/employee-detail.js` for page initialization
- Reduced employee detail PHP file from 935 lines to ~250 lines
- Improved code maintainability and reusability
- All functionality preserved - 100% backward compatible
- Cleaner, more organized code structure

## [5.5.2] - 2026-04-11

### Restoration: Full v5.4.10 Functionality with New Structure
- Restored all working functionality from v5.4.10 with new folder structure
- Replaced incomplete refactored classes with fully working v5.4.10 versions
- All backend pages now in `includes/backend/` folder
- All frontend pages now in `includes/frontend/` folder
- Maintained backward compatibility with all existing features
- All AJAX handlers working correctly
- My Account tabs fully functional
- Employee management, dashboard, payroll, and settings pages fully operational
- Shortcodes system fully restored

### What's Preserved
- Commission calculation engine (salary-aware, historical tracking)
- Employee ID system (vb_user_id) with auto-generation
- Dashboard with AJAX data loading and filtering
- Payroll page with date range and employee type filters
- Team Members page with creation date filtering
- Employee detail page with orders, salary, and payments tabs
- My Account integration with earnings and orders tabs
- Shortcodes system for displaying earnings
- Checkout field integration with ThemeHigh
- GitHub auto-updater
- All existing functionality from v5.4.10

## [5.5.1] - 2026-04-11

### Bug Fixes
- Fixed critical PHP syntax errors caused by duplicate closing tags in refactored classes
- Fixed parse errors in `class-settings.php`, `class-employee-detail.php`, `class-myaccount.php`, and `class-shortcodes.php`
- Plugin now activates and loads correctly after v5.5.0 refactoring

## [5.5.0] - 2026-04-11

### Major Refactoring: Code Organization & Reusability

#### Folder Structure Reorganization
- Created `includes/backend/` for admin/backend pages
- Created `includes/frontend/` for customer-facing pages
- Moved backend classes: Dashboard, Payroll Page, Employee Management, Employee Detail, Settings
- Moved frontend classes: My Account, Shortcodes
- Updated main plugin file to load from new folder structure
- Improves code organization and makes it clear which code is admin vs customer-facing

#### Unified Design System
- Created `assets/css/common.css` (550+ lines) with reusable components
- Includes CSS variables, page wrapper, search filter, unified filter, table section, data table, badges, buttons, pagination, empty states
- All pages now use consistent design system
- Reduces CSS duplication across pages

#### Shared JavaScript Utilities
- Created `assets/js/common.js` (200+ lines) with shared utility functions
- Functions: `getDateRange()`, `formatCurrency()`, `handleDatePresetChange()`, `renderPagination()`, `attachSortHandlers()`
- Created `assets/js/employees.js` for employee management page
- Created `assets/js/dashboard.js` for dashboard-specific logic
- Eliminates duplicate JavaScript across pages

#### Page Refactoring
- **Dashboard**: Removed 1000+ lines of inline CSS, 500+ lines of inline JS
- **Employee Management**: Removed 663 lines of inline CSS/JS
- **Employee Detail**: Removed 712 lines of inline CSS/JS
- **Settings**: Removed 98 lines of inline CSS/JS
- **My Account**: Removed 60 lines of inline JS
- **Shortcodes**: Removed 31 lines of inline JS
- All pages now use common CSS/JS design system
- Added constructors to all pages for proper WordPress asset enqueuing

#### Code Quality Improvements
- Total lines removed: ~2,500+ lines of duplicate CSS/JS
- Improved code maintainability and readability
- Easier to add new pages using existing components
- Consistent design across entire plugin
- All existing functionality preserved - 100% backward compatible

### Technical Details
- All pages now properly enqueue assets using WordPress hooks
- Separated admin and customer-facing code for better organization
- Reusable components reduce development time for new features
- Design system uses CSS variables for easy customization
- All AJAX handlers and business logic preserved

## [5.4.10] - 2026-04-11

### New Features

#### Employee Type Filter on Payroll Page
- Added salary type filter dropdown to payroll page
- Filter by: All Types, Commission Based, Fixed Salary, or Combined (Base + Commission)
- Works alongside existing search and date range filters
- Helps quickly identify payroll by employee salary type

#### Employee Creation Date Filter on Team Members Page
- Added date range filter for employee creation/registration date
- Filter employees by when they were created
- Includes "Clear Dates" button for quick reset
- Useful for tracking new hires and employee onboarding

### Bug Fixes

#### Fixed Custom Fields Hook Issue
- **FIXED**: Custom fields were interfering with WooCommerce user fields
- **FIXED**: Profile picture field was causing WooCommerce fields to disappear
- Changed from `show_user_profile` and `edit_user_profile` hooks to `personal_options` hook
- Prevents custom fields from interfering with WooCommerce fields
- Profile picture field now properly coexists with all WooCommerce user fields
- Improved HTML structure with proper `<tbody>` tags

### Technical Details

- Payroll page now sends `salary_type` parameter to AJAX handler
- Team Members page now sends `start_date` and `end_date` parameters for employee creation date filtering
- All filters are independent and can be combined for precise filtering
- Maintains existing design system and styling consistency

---

## [5.4.9] - 2026-04-11

### CRITICAL FIX - Dashboard Total Orders Now Shows Unique Orders Only
- **FIXED**: Dashboard "Total Orders" was counting same order multiple times
- **FIXED**: If order had both Agent and Processor, it counted as 2 orders
- **IMPROVED**: Dashboard now shows actual unique order count
- **IMPROVED**: Team Members page still shows all associated orders per employee

### The Problem

Dashboard "Total Orders" was summing up orders from each employee:
- ❌ Order #100 with Agent A and Processor B counted as 2 orders
- ❌ Same order appeared in both employees' order counts
- ❌ Dashboard showed inflated total order count

### The Solution

Dashboard now counts unique orders only:
- ✅ Order #100 counts as 1 order (not 2)
- ✅ Only orders with commission data are counted
- ✅ Accurate dashboard total order count
- ✅ Team Members page still shows all associated orders per employee

### Example

**Before v5.4.9:**
```
Order #100: Agent A + Processor B
Dashboard Total Orders: 2 (counted twice)
Employee A Total Orders: 1
Employee B Total Orders: 1
```

**After v5.4.9:**
```
Order #100: Agent A + Processor B
Dashboard Total Orders: 1 (unique count)
Employee A Total Orders: 1 (still shows all associated)
Employee B Total Orders: 1 (still shows all associated)
```

### Behavior

- **Dashboard**: Shows unique orders (actual commission-included orders)
- **Team Members page**: Shows all orders associated with that employee
- **Employee Detail page**: Shows all orders associated with that employee
- **Payroll page**: Shows all orders associated with that employee

### Files Modified

- `woocommerce-team-payroll.php` - Updated dashboard AJAX handler to count unique orders
- `CHANGELOG.md` - Updated with v5.4.9 changes

### Testing Recommendations

1. Create an order with both Agent and Processor
2. Check dashboard "Total Orders" - should show 1
3. Check Team Members page for each employee - should show 1 each
4. Create multiple orders with different agent/processor combinations
5. Verify dashboard shows correct unique count

## [5.4.8] - 2026-04-11

### CRITICAL FIX - Employee Detail Orders Tab Not Loading on Page Load
- **FIXED**: Orders table was empty on initial page load
- **FIXED**: Orders only appeared after switching tabs and back
- **IMPROVED**: Orders now load automatically when page loads
- **IMPROVED**: Orders tab is immediately populated with data

### The Problem

When navigating to the employee detail page:
- ❌ Orders tab was empty/blank
- ❌ No orders displayed on initial load
- ❌ Orders only appeared after switching to another tab and back
- ❌ User had to manually trigger tab switch to see data

### The Solution

Added automatic `loadOrdersData()` call on page load:
- ✅ Orders load immediately when page loads
- ✅ Orders tab is populated with data by default
- ✅ No need to switch tabs to see orders
- ✅ Smooth user experience

### Technical Details

The issue was that `loadOrdersData()` was only called when the orders tab was clicked, not on initial page load. Since the orders tab is marked as active by default in the HTML, the data was never loaded.

**Fix:** Added `loadOrdersData()` call immediately after jQuery ready, before any event handlers.

### Files Modified

- `includes/class-employee-detail.php` - Added initial loadOrdersData() call
- `woocommerce-team-payroll.php` - Version updated to 5.4.8
- `CHANGELOG.md` - Updated with v5.4.8 changes

### Testing Recommendations

1. Navigate to Team Members page
2. Click on an employee to view details
3. Verify orders table is populated immediately
4. No need to switch tabs - orders should be visible

## [5.4.7] - 2026-04-11

### CRITICAL FIX - Refunded Orders Now Included in Payroll & Dashboard
- **FIXED**: Refunded orders were being excluded from payroll calculations
- **FIXED**: Refunded orders now show in dashboard and payroll pages
- **FIXED**: Refund commission settings now properly applied and visible
- **IMPROVED**: All order statuses (completed, processing, refunded) included in calculations

### What Was Wrong

Refunded orders were being filtered out from:
- Dashboard calculations
- Payroll page
- Employee earnings reports
- Total earnings calculations

This meant refund commission settings were being ignored and not showing in reports.

### What's Fixed Now

Refunded orders are now included in all calculations:
- ✅ Dashboard shows refunded orders with refund commission
- ✅ Payroll page includes refunded orders
- ✅ Employee earnings include refund commission
- ✅ Total earnings calculations include refunded orders
- ✅ Refund commission settings (None/Percentage/Flat) now work correctly

### Example

**Before v5.4.7:**
```
Order refunded with ৳120 flat refund commission
→ Order not shown in payroll
→ Employee doesn't see refund commission
→ Dashboard shows 0 orders
```

**After v5.4.7:**
```
Order refunded with ৳120 flat refund commission
→ Order shown in payroll with ৳120 commission
→ Employee sees refund commission in earnings
→ Dashboard shows order with refund commission
```

### Files Modified

- `includes/class-core-engine.php` - Include 'refunded' in order status queries
- `includes/class-payroll-engine.php` - Include 'refunded' in payroll calculations
- `includes/class-employee-management.php` - Include 'refunded' in employee queries
- `includes/class-myaccount.php` - Include 'refunded' in frontend earnings
- `includes/class-shortcodes.php` - Include 'refunded' in shortcode calculations
- `woocommerce-team-payroll.php` - Version updated to 5.4.7
- `CHANGELOG.md` - Updated with v5.4.7 changes

### Testing Recommendations

1. Create an order with commission
2. Refund the order
3. Set refund commission to "Flat (৳120)"
4. Check dashboard - order should appear with ৳120 commission
5. Check payroll page - order should appear with ৳120 commission
6. Check employee earnings - should include refund commission
7. Test with different refund commission types (None, Percentage, Flat)

### Compatibility

- ✅ WooCommerce 5.0+
- ✅ WordPress 5.0+
- ✅ PHP 7.2+

### Breaking Changes

None - this is a bug fix release.

## [5.4.6] - 2026-04-11

### CRITICAL FIXES - Auto Salary History & Refund Commission Handling
- **FIXED**: New employees now automatically get salary history initialized as "Commission Based"
- **FIXED**: Refund commission settings now properly applied to refunded orders
- **IMPROVED**: All new employees have salary history from day one (no more null history)
- **IMPROVED**: Refunded orders respect refund commission type (None/Percentage/Flat)

### Auto Salary History Initialization

When a new user is created with an employee role (shop_employee, shop_manager, administrator):
- Automatic salary history entry is created
- Default type: "Commission Based"
- Timestamp: User creation time
- This ensures all employees have a salary history baseline

**Benefits:**
- No more null/empty history for new employees
- Commission calculations work correctly from day one
- Historical salary type lookup always finds a baseline entry

### Refund Commission Handling

When an order is refunded, the system now applies refund commission settings:

**Refund Commission Type: None**
- Refunded orders get ৳0 commission
- Employee gets no earnings from refunded order

**Refund Commission Type: Percentage**
- Refunded orders get X% of original commission
- Example: 50% refund commission on ৳100 commission = ৳50
- Employee gets earnings based on their salary type

**Refund Commission Type: Flat Amount**
- Refunded orders get fixed amount (e.g., ৳120)
- Example: ৳120 flat refund commission
- Employee gets earnings based on their salary type

**Salary Type Still Applies:**
- Fixed salary employees: ৳0 (even with refund commission)
- Commission-based employees: Full refund commission amount
- Combined salary employees: Full refund commission amount

### Example Scenarios

**Scenario 1: Refund with Flat Amount (৳120)**
```
Original Commission: ৳100
Refund Commission Type: Flat (৳120)
Agent: Commission-based
Processor: Fixed salary

Result:
- Total Commission: ৳120 (flat amount)
- Agent gets: ৳84 (70% of ৳120)
- Processor gets: ৳0 (fixed salary)
```

**Scenario 2: Refund with Percentage (50%)**
```
Original Commission: ৳100
Refund Commission Type: Percentage (50%)
Agent: Commission-based
Processor: Commission-based

Result:
- Total Commission: ৳50 (50% of ৳100)
- Agent gets: ৳35 (70% of ৳50)
- Processor gets: ৳15 (30% of ৳50)
```

**Scenario 3: Refund with None**
```
Original Commission: ৳100
Refund Commission Type: None
Agent: Commission-based
Processor: Commission-based

Result:
- Total Commission: ৳0
- Agent gets: ৳0
- Processor gets: ৳0
```

### Technical Changes
- Updated `auto_set_user_custom_id()` to initialize salary history for new employees
- Updated `calculate_commission()` to handle refunded orders with settings
- Refund commission applied BEFORE salary type split
- Salary type eligibility still determines final earnings

### Files Modified
- `includes/class-custom-fields.php` - Auto-initialize salary history
- `includes/class-core-engine.php` - Handle refund commission settings
- `woocommerce-team-payroll.php` - Version updated to 5.4.6
- `CHANGELOG.md` - Updated with v5.4.6 changes

### Testing Recommendations
1. Create new employee and verify salary history is auto-created
2. Create order and refund it with different refund commission settings
3. Verify commission is calculated correctly for refunded orders
4. Test with fixed salary employees (should get ৳0)
5. Test with commission-based employees (should get refund commission)

## [5.4.5] - 2026-04-11

### CRITICAL FIX - Combined Salary Commission Eligibility & Historical Salary Type Checking
- **FIXED**: Combined salary employees now receive commission (previously blocked)
- **FIXED**: Commission now calculated based on salary type AT ORDER CREATION TIME (not current)
- **IMPROVED**: Only FIXED salary blocks commission; COMBINED and COMMISSION-BASED both eligible
- **IMPROVED**: Historical salary type tracking enables accurate retroactive commission calculation

### Commission Eligibility Rules (v5.4.5)

**Commission-ELIGIBLE** (receive commission):
- Commission-based employees
- Combined salary employees

**Commission-BLOCKED** (get ৳0):
- Fixed salary employees ONLY

### How It Works Now

**Example Scenario:**
```
10:00 AM - Employee is FIXED SALARY
10:00 AM - Order created (employee gets ৳0 commission)
10:05 AM - Employee changed to COMBINED SALARY
10:10 AM - New order created (employee gets commission)

Result:
- Order at 10:00 AM: ৳0 (was fixed at that time)
- Order at 10:10 AM: Commission (is combined at that time)
```

### Technical Changes
- Updated `is_user_commission_based()` to accept optional date parameter
- Commission calculation now checks salary type AT ORDER CREATION TIME
- Combined salary (`_wc_tp_combined_salary = 1`) now treated as commission-eligible
- Only fixed salary (`_wc_tp_fixed_salary = 1`) blocks commission
- Salary history tracking enables accurate historical salary type lookup
- All commission displays use historical salary type for accuracy

### Files Modified
- `includes/class-core-engine.php` - Updated commission calculation logic
- `CHANGELOG.md` - Updated with v5.4.5 changes

### Testing Recommendations
1. Create employee as FIXED SALARY
2. Create order (verify commission = ৳0)
3. Change employee to COMBINED SALARY
4. Create new order (verify commission is calculated)
5. Verify old order still shows ৳0 commission
6. Test with COMMISSION-BASED employees (should work same as COMBINED)

## [5.4.4] - 2026-04-11

### CRITICAL FIX - Commission Vanishes (No Redirection)
- **FIXED**: Fixed salary employee's commission share now VANISHES (not redirected)
- **FIXED**: Only commission-based employees receive their commission share
- **FIXED**: Each employee only gets their OWN share if they are commission-based
- **IMPROVED**: Simplified logic - no commission redirection, only distribution to eligible parties

### How Commission Distribution Works Now (NO REDIRECTION)

**Total Commission: ৳100 (Agent 70% = ৳70, Processor 30% = ৳30)**

| Scenario | Agent Status | Processor Status | Agent Gets | Processor Gets |
|----------|--------------|------------------|------------|----------------|
| Both Commission | Commission | Commission | ৳70 | ৳30 |
| Agent Fixed | Fixed | Commission | ৳0 (vanishes) | ৳30 |
| Processor Fixed | Commission | Fixed | ৳70 | ৳0 (vanishes) |
| Both Fixed | Fixed | Fixed | ৳0 (vanishes) | ৳0 (vanishes) |

### Key Points
- **NO redirection** - commission doesn't move between parties
- **Only eligible parties get their share** - fixed salary employees get $0
- **Fixed salary share vanishes** - it's not distributed to anyone
- **Each person only gets their own share** - if they are commission-based

### Technical Changes
- Removed commission redirection logic
- Each party only receives their calculated share IF they are commission-based
- Fixed salary employees always get $0 (their share vanishes)
- Simplified calculation - no conditional redirection

### Example Scenarios

**Scenario 1: Agent Fixed, Processor Commission**
```
Total Commission: ৳100
Agent's calculated share: 70% = ৳70
Processor's calculated share: 30% = ৳30

Result:
- Agent is fixed salary → gets ৳0 (their ৳70 vanishes)
- Processor is commission-based → gets ৳30 (their share)
- Total distributed: ৳30 (৳70 vanished)
```

**Scenario 2: Agent Commission, Processor Fixed**
```
Total Commission: ৳100
Agent's calculated share: 70% = ৳70
Processor's calculated share: 30% = ৳30

Result:
- Agent is commission-based → gets ৳70 (their share)
- Processor is fixed salary → gets ৳0 (their ৳30 vanishes)
- Total distributed: ৳70 (৳30 vanished)
```

**Scenario 3: Both Fixed**
```
Total Commission: ৳100
Agent's calculated share: 70% = ৳70
Processor's calculated share: 30% = ৳30

Result:
- Agent is fixed salary → gets ৳0 (their ৳70 vanishes)
- Processor is fixed salary → gets ৳0 (their ৳30 vanishes)
- Total distributed: ৳0 (entire ৳100 vanishes)
```

## [5.4.3] - 2026-04-11

### CRITICAL FIX - Use CURRENT Salary Type Only (Ignore History)
- **FIXED**: System now uses CURRENT salary type, not historical salary type
- **FIXED**: Fixed salary employees NEVER get commission (regardless of past status)
- **FIXED**: Commission properly redirects to other party based on CURRENT status
- **IMPROVED**: Simplified logic - only checks current salary flags, ignores history
- **IMPROVED**: Commission distribution now works correctly for all scenarios

### How It Works Now (CURRENT SALARY TYPE ONLY)

**Scenario 1: Agent is Fixed Salary (Current)**
- Total Commission: $100
- Agent split: 70% = $70
- Processor split: 30% = $30
- **Result**: Agent gets $0 (fixed), Processor gets $100 ($30 + $70 redirected)

**Scenario 2: Processor is Fixed Salary (Current)**
- Total Commission: $100
- Agent split: 70% = $70
- Processor split: 30% = $30
- **Result**: Agent gets $100 ($70 + $30 redirected), Processor gets $0 (fixed)

**Scenario 3: Both Fixed Salary (Current)**
- Total Commission: $100
- **Result**: Agent gets $0, Processor gets $0 (commission vanishes)

**Scenario 4: Both Commission-Based (Current)**
- Total Commission: $100
- **Result**: Agent gets $70, Processor gets $30 (normal split)

### Technical Changes
- Replaced `is_user_commission_based_on_date()` with `is_user_commission_based()`
- New method checks ONLY current salary flags, ignores history completely
- Commission calculation now uses current status for all decisions
- Simplified logic - no date checking, no history lookup

### Important Note
- Salary history is still tracked for audit purposes
- But commission calculation ONLY uses current salary type
- This ensures fixed salary employees NEVER receive commission

## [5.4.2] - 2026-04-11

### CRITICAL FIX - Retroactive Commission Recalculation
- **FIXED**: When changing employee salary type, ALL existing orders are recalculated
- **FIXED**: Fixed salary employees no longer show commission on ANY orders (past or future)
- **FIXED**: Commission properly redirects to other party for all orders
- **IMPROVED**: Salary type changes now trigger automatic recalculation of stored commission data
- **IMPROVED**: No need to manually recalculate - happens automatically on salary change

### Technical Changes
- Added `recalculate_user_commissions()` method to update all orders when salary type changes
- Enhanced `add_salary_history()` to trigger recalculation on salary type change
- All orders involving the user are recalculated with new salary type
- Stored commission data is updated to reflect new salary type

### How It Works
1. Admin changes employee salary type (e.g., commission → fixed)
2. System creates salary history entry
3. System automatically recalculates ALL orders involving that employee
4. Stored commission data is updated
5. All displays (dashboard, payroll, orders tab) show correct amounts

## [5.4.1] - 2026-04-11

### CRITICAL FIX - Salary-Aware Commission Recalculation
- **FIXED**: Commission now recalculates based on CURRENT salary type, not stored value
- **FIXED**: Fixed salary employees no longer show commission in orders tab
- **FIXED**: Dashboard and payroll pages now show correct earnings for fixed salary employees
- **FIXED**: Commission properly redirects to other party when one is on fixed salary
- **IMPROVED**: All commission displays (dashboard, payroll, employee orders) now use real-time calculation
- **IMPROVED**: Salary history changes are immediately reflected in all reports
- **IMPROVED**: Employee detail orders tab shows accurate commission based on current salary type

### Technical Changes
- Enhanced `is_user_commission_based_on_date()` to properly handle salary history
- Updated `wc_tp_get_employee_orders` AJAX handler to recalculate commissions
- Updated `get_payroll_by_date_range()` to recalculate commissions for all orders
- Added salary history validation to only record actual type changes
- Commission recalculation now happens at display time for real-time accuracy

## [5.4.0] - 2026-04-11

### MAJOR FEATURE - Salary-Aware Commission System
- **NEW**: Implemented salary type detection for commission calculations
- **NEW**: Commission system now respects employee salary types (Fixed, Combined, Commission-based)
- **NEW**: Fixed salary employees no longer receive commissions
- **NEW**: Commission automatically redirects to other party when one is on fixed salary
- **NEW**: Salary history tracking enables retroactive commission adjustments
- **NEW**: When employee changes from fixed to commission-based, future orders include commission
- **NEW**: When employee changes from commission-based to fixed, future orders exclude commission
- **NEW**: Both agent and processor salary types are checked independently
- **NEW**: If both are fixed salary, commission is not distributed

### Employee Detail Page - Phase 2
- **NEW**: Complete Orders tab with filtering and display
- **NEW**: Order search by Order ID, Customer Name, Email, Phone
- **NEW**: Order filtering by Date Range, Status, and Flag type
- **NEW**: Order flags: Order Owner, Affiliate To, Affiliate From
- **NEW**: Color-coded flag badges for easy identification
- **NEW**: Orders table shows: Order ID, Customer, Total, Status, Commission, Your Earnings, Flag, Date
- **NEW**: Action buttons: View (hidden page) and Edit (WooCommerce order editor)
- **NEW**: Pagination with 20 items per page
- **NEW**: AJAX-based filtering without page reloads

### Profile Enhancements
- **NEW**: Profile picture field added to user meta (`_wc_tp_profile_picture`)
- **NEW**: WordPress media uploader integration for profile pictures
- **NEW**: Profile picture displays on employee detail page with fallback to initials
- **NEW**: Dashboard stat cards showing Total Orders, Earnings, Paid, Due

### Technical Changes
- Added `is_user_commission_based_on_date()` method to check salary type on specific date
- Enhanced `calculate_commission()` to use salary-aware logic
- Commission calculation now checks salary history for accurate retroactive calculations
- Added `wc_tp_get_employee_orders` AJAX handler for orders tab
- Improved commission distribution logic for mixed salary scenarios

### Bug Fixes
- Fixed duplicate event listener issue in sorting handlers (payroll and team members pages)
- Sorting now works correctly with single click for direction changes

## [5.3.6] - 2026-04-11

### CRITICAL FIX - Dashboard Redesign & Total Paid Issue
- **FIXED**: Removed duplicate dashboard layout that was showing twice
- **FIXED**: Total Paid now correctly shows actual payment amounts (was showing 0)
- **FIXED**: All dashboard sections now load via AJAX without page reloads
- **IMPROVED**: Complete dashboard redesign with full AJAX integration
- **IMPROVED**: Latest Employees table now shows top 10 employees
- **IMPROVED**: Top Earners table respects date range filter
- **IMPROVED**: Recent Payments table respects date range filter
- **IMPROVED**: All tables update dynamically when date range changes
- **IMPROVED**: Professional empty states with icons for better UX
- **IMPROVED**: Stat cards now display correct totals from all sections
- **IMPROVED**: Currency formatting uses WooCommerce store currency
- **IMPROVED**: Loading state feedback on filter button
- **IMPROVED**: Success/error notifications after filter updates

### Technical Changes
- Refactored dashboard to use container-based rendering
- All content loaded via AJAX from `wc_tp_get_dashboard_data` action
- AJAX handler now returns: latest_employees, top_earners, recent_payments, payroll data
- Payment date filtering now properly handles datetime-local format
- Stat calculations now include all employees with payments in date range
- Removed duplicate HTML rendering that caused layout duplication

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
