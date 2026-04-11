# Changelog

## [5.7.2] - 2026-04-11

### CRITICAL BUG FIX - Employee Detail Page Orders Table & Tab Switching

#### Root Cause Identified
- Date input elements were hidden by default (`display: none`)
- jQuery couldn't properly access values from hidden input elements
- `loadOrdersData()` was receiving empty date values and returning early
- AJAX call was never being made to fetch orders

#### The Fix
- **Store dates in JavaScript variables** instead of relying on hidden inputs
- Added `currentStartDate` and `currentEndDate` variables to track date range
- Updated `updateDateRangeFromPreset()` to store dates in JS variables
- Updated `loadOrdersData()` to use JS variables for date values
- Added fallback for `ajaxurl` in case it's not defined globally
- Added console logging for debugging AJAX issues

#### What Now Works
- ✅ Orders table displays immediately on page load
- ✅ Tab switching between Orders, Payments, and Salary Management works correctly
- ✅ All tab-specific data loads properly when switching tabs
- ✅ Date filtering works correctly
- ✅ Search and status filters work as expected

#### Technical Details
- Removed dependency on hidden input values for date storage
- JavaScript variables now serve as the source of truth for date ranges
- AJAX calls now have proper date values and execute successfully
- Console logging helps identify any remaining issues

### Files Modified
- `includes/class-employee-detail.php` - Fixed date handling and AJAX calls

## [5.7.1] - 2026-04-11

### Bug Fixes

#### Employee Detail Page
- **Fixed Orders Table Not Displaying**: Orders table now loads correctly on page initialization
- **Fixed Tab Switching**: All three tabs (Orders, Payments, Salary Management) now switch properly
- **Removed Duplicate Functions**: Eliminated duplicate JavaScript function definitions that were causing conflicts
- **Consolidated Tab Logic**: Unified tab switching handler to properly load data for each tab
- **Fixed Event Handler Conflicts**: Removed conflicting event delegation handlers that prevented tab switching

### Technical Details

- Reorganized JavaScript initialization order to ensure date range is set before AJAX calls
- Consolidated tab switching logic into single handler with conditional data loading
- Removed duplicate `getDateRangeFromPreset()`, `formatDateForInput()`, and `updateDateRangeFromPreset()` functions
- Fixed event handler attachment for payments and salary tabs

## [5.7.0] - 2026-04-11

### New Features

#### Comprehensive Payments Page
- **New Payments Page**: Added dedicated page for managing all payments across all employees
- **Menu Location**: Added "Payments" menu item after "Team Members" in admin menu
- **Payment Entry**: Add payments directly from the payments page with employee dropdown selection
- **Employee Dropdown**: Select any employee to add payment to their account
- **Payment Details**: Shows Employee Name, Employee ID (vb_user_id), Amount, Payment Date, Added By, Employee Type

#### Advanced Filtering & Search
- **Date Range Filter**: Predefined options (All Time, Today, This Week, This Month, This Year, Last Week, Last Month, Last Year, Last 6 Months, Custom)
- **Employee Type Filter**: Filter by Commission Based, Fixed Salary, or Combined
- **Search Functionality**: Search by employee name, vb_user_id, email, or phone number
- **Real-time Search**: Search updates as you type

#### Table Features
- **Sortable Columns**: Click column headers to sort (Employee, Employee ID, Amount, Payment Date, Added By, Employee Type)
- **Sort Direction**: Toggle between ascending (↑) and descending (↓)
- **Pagination**: Navigate through payment records with page numbers
- **Items Per Page**: Choose 10, 20, 30, 50, or 100 items per page
- **View Action**: Click "View" button to go to employee detail page

#### Employee Detail Page Enhancements
- **Tab Reordering**: Orders → Payments → Salary Management
- **Payments Tab**: 
  - Stat cards showing Total Earnings, Total Paid, Total Due
  - Payment Methods section with add/delete functionality
  - Payment method details (e.g., bKash Personal with account number)
  - Add Payment form with amount and date
  - Payment History table with delete option
- **Salary Management Tab**:
  - Salary type selection (Commission Based, Fixed Salary, Combined)
  - Conditional fields for salary amount and frequency
  - Update salary button with validation
  - Complete salary history table showing all changes
  - Tracks who made changes and when

#### User Profile Improvements
- **Fixed Profile Picture Upload**: Media library now opens correctly
- **Separate JavaScript File**: Profile picture functionality moved to external JS file
- **Proper Enqueuing**: Uses WordPress media library API correctly

### Technical Details

- Created `class-payments-page.php` for payments management
- Added AJAX handler `wc_tp_get_all_payments` for fetching payment data
- Implemented section-wise date filtering for dashboard
- Added payment methods storage in user meta
- Salary history tracking with complete audit trail
- All features use AJAX for smooth user experience
- Responsive design matching current version styling
- Currency formatting with WooCommerce settings

