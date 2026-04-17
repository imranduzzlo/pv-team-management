# Changelog

## [1.0.65] - 2026-04-17
### Critical Bug Fixes - Reports KPI Cards
- **FIX**: Fixed KPI cards not loading - showing fallback error message
- **FIX**: Fixed AJAX filters serialization issue in reports page
- **TECHNICAL**: Updated all AJAX calls to properly serialize filters as JSON strings
- **TECHNICAL**: Updated all PHP AJAX handlers to parse JSON filters correctly
- **ENHANCEMENT**: Added better error handling and console logging for debugging
- **ENHANCEMENT**: Added error response display instead of generic fallback messages
- **FILES UPDATED**: 
  - `assets/js/reports.js` - 6 AJAX calls updated
  - `includes/class-myaccount.php` - 6 AJAX handlers updated

## [1.0.64] - 2026-04-17
### Critical Bug Fixes
- **FIX**: Fixed JavaScript syntax error in `performance-settings.js` preventing page functionality
- **FIX**: Removed extra closing brace causing role selector and tab switching to fail
- **FIX**: Performance settings page now fully functional with working AJAX calls
- **TECHNICAL**: JavaScript file now has balanced braces (335 opening, 335 closing)
- **TECHNICAL**: All JavaScript files pass syntax validation

## [1.0.63] - 2026-04-17
### Bug Fix Release
- **FIX**: Fixed critical syntax error in `class-performance-settings.php` caused by orphaned duplicate code
- **FIX**: Removed 49 lines of duplicate code that was causing PHP parse error on line 1939
- **TECHNICAL**: All PHP files now pass syntax validation without errors

## [1.0.55] - 2026-04-17
### Reports Page Enhancements & Performance Score Improvements
- **FEATURE**: Performance Score now uses attributed order values based on agent/processor commission split
- **FEATURE**: Added `agent_order_value` and `processor_order_value` to commission data for accurate performance tracking
- **FEATURE**: Performance score respects commission calculation statuses from settings
- **ENHANCEMENT**: Total Earnings KPI now uses actual salary transactions instead of calculated estimates
- **ENHANCEMENT**: Order status filter now only shows commission calculation statuses from settings
- **ENHANCEMENT**: Commission KPI card follows selected filter status dynamically
- **ENHANCEMENT**: Reports KPI grid changed to 2-column layout for better visual organization
- **FIX**: Removed "Orders Processed" and "Avg Order Value" KPI cards as requested
- **FIX**: Fixed KPI cards infinite loading issue caused by undefined salary type variables
- **FIX**: Updated drill-down modals to work with remaining 4 KPI cards
- **FIX**: Performance score now uses attributed order totals (agent 70%, processor 30%) for earnings ranges
- **TECHNICAL**: Updated `calculate_commission()` to store attributed order values in commission_data
- **TECHNICAL**: Enhanced `get_user_earnings()` to return attributed_value for each order
- **TECHNICAL**: Updated `calculate_performance_score()` to use attributed_order_total parameter
- **TECHNICAL**: All AJAX handlers now calculate and use attributed order totals
- **UI**: KPI grid now displays 2 columns on desktop and mobile for cleaner layout
- **UI**: Neutral change indicators now show meaningful data (order count) instead of "No change"

## [1.0.54] - 2026-04-17
### Performance Settings Employee Roles Integration
- **FEATURE**: Performance Settings now respects Employee User Roles configuration
- **FEATURE**: Only roles configured in Settings → WooCommerce → Employee User Roles appear in Performance Settings
- **ENHANCEMENT**: Performance score calculation now validates employee roles before applying configurations
- **ENHANCEMENT**: Added clear messaging explaining employee roles requirement in Performance Settings
- **ENHANCEMENT**: Empty state handling when no employee roles are configured with direct navigation links
- **ENHANCEMENT**: Disabled dropdowns with helpful messages when no employee roles exist
- **FIX**: Performance configurations no longer apply to non-employee roles (admin, editor, etc.)
- **FIX**: Role filtering ensures only actual employees get performance evaluations
- **UI**: Updated all role selectors to clearly indicate "Employee Role" selection
- **UI**: Added warning messages and quick navigation to Employee Roles configuration
- **TECHNICAL**: Enhanced `get_all_roles()` method to filter by employee roles from settings
- **TECHNICAL**: Updated performance score calculation to validate employee role membership
- **TECHNICAL**: Improved role-based configuration security and data integrity

## [1.0.53] - 2026-04-17
### Performance Settings Save Functionality Fix
- **FIX**: Fixed Performance Settings save functionality not properly persisting data
- **FIX**: Resolved unsaved changes warning not clearing after successful AJAX saves
- **FIX**: Enhanced AJAX response handling with proper promise management
- **FIX**: Added fallback mechanism for unsaved changes reset when main function unavailable
- **ENHANCEMENT**: Improved error handling and user feedback during save operations
- **ENHANCEMENT**: Added comprehensive debugging and logging for save process
- **ENHANCEMENT**: Better integration between performance settings and main form change detection
- **TECHNICAL**: Updated all AJAX save functions to return standardized response format
- **TECHNICAL**: Added `wcTpCheckUnsavedChanges()` debug function for troubleshooting
- **TECHNICAL**: Enhanced Promise.all handling with better error reporting and validation

## [1.0.52] - 2026-04-17
### Performance Score & KPI Modal Enhancements
- **FEATURE**: Performance Score now uses role-based configuration from Performance Settings
- **FEATURE**: Performance Score calculation reads from `wc_tp_performance_config` option with role-specific scoring ranges
- **FEATURE**: Dynamic scoring based on user's WordPress role (shop_employee, shop_manager, etc.)
- **FEATURE**: Configurable base score and role-specific factors for earnings, orders, and AOV
- **ENHANCEMENT**: All 6 KPI card modals now display real data instead of random/hardcoded values
- **ENHANCEMENT**: Added "My Salary" KPI modal with salary details and type information
- **ENHANCEMENT**: KPI modals show current filter state (Date Range, Order Status, Role)
- **ENHANCEMENT**: Currency formatting now uses WooCommerce currency settings (symbol, position, decimals)
- **ENHANCEMENT**: Modal data extracted directly from KPI cards preserving WooCommerce formatting
- **FIX**: Performance Score calculation fallback to default ranges when no role config exists
- **FIX**: Removed hardcoded USD currency formatting in favor of WooCommerce settings
- **TECHNICAL**: Updated `calculate_performance_score()` method to accept user_id and retrieve role-based config
- **TECHNICAL**: Updated `loadDrillDownData()` in reports.js to use real KPI values
- **TECHNICAL**: Added helper function `getCurrentFilters()` to display active filters in modals

## [1.0.51] - 2026-04-16
### Enterprise Reports System - Complete Implementation
- **STEP 1**: Master Filter System with date range (preset + custom), order status, role, commission range, time period, sort options, and filter summary
- **STEP 2**: Personal Performance Dashboard with 5 KPI cards (My Earnings, My Commission, Orders Processed, Avg Order Value, Performance Score) with period comparison and color-coded indicators
- **STEP 3**: Personal Analytics Charts with Earnings Trend (Line Chart) and Commission Breakdown (Doughnut Chart) using Chart.js 3.9.1 with dynamic colors
- **STEP 4**: Detailed Performance Metrics with 8 comprehensive metrics (Total Orders, Total Earnings, Avg per Order, Avg Order Value, Commission Rate, Performance Score, Growth Rate, Highest/Lowest Order)
- **STEP 5**: Data Tables with Commission History and Order Processing tables featuring real-time search, sortable headers, pagination, per-page selection, and role/status badges
- **STEP 6**: Goal Tracking with 4 main goals (Monthly Earnings Target, Orders to Process, Average Order Value, Performance Score), progress bars, achievement badges, and performance summary
- **STEP 7**: Interactive Features with filter persistence (localStorage), auto-refresh (30 seconds), drill-down modals for KPI cards and goals with detailed breakdowns
- **STEP 8**: Export & Reporting Tools with CSV, PDF, and Excel export respecting current filters, plus comprehensive print styles for professional output
- **FEATURE**: All sections filtered by unified master filter system - single filter change updates ALL sections simultaneously
- **FEATURE**: Filter state persists across page reloads using localStorage
- **FEATURE**: Auto-refresh every 30 seconds keeps data current without manual refresh
- **FEATURE**: Drill-down modals provide detailed breakdowns for each KPI and goal
- **FEATURE**: Professional export functionality (CSV, PDF, Excel) with employee info and period details
- **FEATURE**: Responsive design optimized for all screen sizes
- **FEATURE**: Dynamic colors from admin settings applied throughout reports
- **TECHNICAL**: 8 AJAX handlers for data loading, 1 export handler, comprehensive JavaScript state management, professional CSS styling with animations

## [1.0.43] - 2026-04-16
### Code Cleanup & Version Update
- **CLEANUP**: Removed commented duplicate orders code that was causing confusion
- **FIX**: Table header layout issue resolved (headers now display horizontally)
- **UPDATE**: Version bumped to 1.0.43 for fresh release
- **MAINTENANCE**: Code cleanup and optimization

## [1.0.39] - 2026-04-14
### Salary System Enhancements & Debug Tools
- **NEW**: Salary Debug & Testing Tools for immediate testing without waiting for cron jobs
- **NEW**: Debug menu in admin (Team Payroll → Salary Debug) with enable/disable toggle
- **NEW**: Test Salary Accumulation - Simulate one day of accumulation
- **NEW**: Get Current Status - View complete salary status and pending accumulation
- **NEW**: Manually Trigger Cron - Force salary processing immediately
- **NEW**: Reset Employee Demo Salary - Clear test data for fresh testing
- **ENHANCED**: Weekly period detection now fully dynamic based on WordPress settings
- **ENHANCED**: Days remaining calculation properly accounts for partial weeks
- **ENHANCED**: Toast notifications integrated for all debug feedback
- **ENHANCED**: Non-technical instructions for debug tool usage
- **FIXED**: My Account CSS now properly applied with specific selectors
- **FIXED**: Employee dropdown shows formatted prices without HTML tags
- **CONSOLIDATED**: My Account class consolidation complete (class-myaccount.php)
- **FEATURE**: Debug tools disabled by default, enable in Settings → Debug tab
- **FEATURE**: Instructions toggle on/off when checkbox is clicked
- **DOCUMENTATION**: Complete debug tool guide with testing examples

## [1.0.38] - 2026-04-14
### Automatic Salary Addition System (Major Feature)
- **NEW**: Automatic base salary addition for fixed and combined salary types
- **NEW**: Daily salary accumulation system with cron jobs (11:50 PM - 11:59 PM)
- **NEW**: Support for daily, weekly, and monthly salary frequencies
- **NEW**: Automatic period-end transfers (week/month end)
- **NEW**: Mid-period salary change handling without gaps or overlaps
- **NEW**: Salary automation class (`class-salary-automation.php`)
- **NEW**: Salary display helper class (`class-salary-display-helper.php`)
- **ENHANCED**: `get_user_total_earnings()` now includes commission + base salary
- **ENHANCED**: Added `get_user_commission_earnings()` for commission-only earnings
- **OPTIMIZED**: Batch processing for 50 employees per minute (500 total per night)
- **OPTIMIZED**: Direct SQL queries instead of looping through `get_users()`
- **OPTIMIZED**: Database indexes for faster meta queries
- **OPTIMIZED**: Removed expensive `recalculate_user_commissions()` on salary change
- **FEATURE**: Earnings breakdown display (commission + salary + pending)
- **FEATURE**: Salary transaction log for audit trail
- **FEATURE**: Pending accumulation display with next transfer date
- **FEATURE**: Support for 5,000+ employees without timeouts
- **FEATURE**: Auto-detect week start day from WordPress settings
- **FEATURE**: Auto-detect days in month (28/29/30/31)
- **FEATURE**: Use user_registered as employee join date
- **DOCUMENTATION**: Complete system documentation and implementation guide

## [1.0.37] - 2026-04-13
### Pagination & Salary History Improvements
- **Pagination Buttons**: Changed from button tags to anchor tags to avoid generic button styling conflicts
- **Pagination Styling**: Removed !important flags, now uses clean anchor tag styling
- **Pagination Scroll**: Added smooth scroll to table header when clicking pagination links
- **Salary History Amounts**: Added frequency abbreviations to amounts (10000$/mn, 10000$/wk, 10000$/dy, 10000$/yr)
- **Commission Display**: Shows %/order for commission-based salary types in history
- **Frequency Abbreviations**: dy (daily), wk (weekly), mn (monthly), yr (yearly)
- **Both Columns**: Applied to both "Previous" and "New" salary columns for clarity

## [1.0.36] - 2026-04-13
### Salary Card Redesign & Pagination Styling
- **Salary Details Grid**: Removed redundant salary-details-grid container
- **Salary Type Badge**: Restored with icon and salary type label
- **Salary Display**: Shows amount and frequency in top right (fixed/combined only)
- **Salary Type Note**: Added for all three salary types (fixed, combined, commission)
- **Fixed Salary Note**: "You receive a fixed salary as shown above."
- **Combined Salary Note**: "You also earn commission from orders in addition to your base salary."
- **Commission Note**: "Your earnings are based entirely on commission from orders you process."
- **Pagination Buttons**: Inactive buttons now have transparent background with button color border
- **Pagination Inactive**: Text and icons use button background color
- **Pagination Hover**: Subtle background (10% opacity) of button color on inactive buttons
- **Pagination Active**: Full button background with button text color
- **Pagination Icons**: Dynamic color matching button styling
- **Dynamic Colors**: All pagination colors applied from theme button settings

## [1.0.35] - 2026-04-13
### Universal Table Styling & Dynamic Card Design
- **Salary History Section**: Applied same heading style as salary information section
- **Section Heading**: Border-bottom with dynamic border color from settings
- **Heading Underline**: ::after pseudo-element with dynamic primary color
- **Table Wrapper**: New card design with border, shadow, and padding
- **Table Wrapper**: Wrapped section-header and table-container together
- **Section Spacing**: 20px gap between children in salary-history-section
- **Wrapper Spacing**: 10px gap between section-header and table-container
- **Table Container**: No border, no border-radius, transparent background
- **Table Header**: Padding 14px 5px, no borders except bottom (1px solid)
- **Table Rows**: Padding 12px 5px, transparent background, bottom border only
- **Row Hover**: Dynamic background color from settings (table_row_hover)
- **Dynamic Styling**: All colors applied from theme settings
- **Border Radius**: Removed from table-wrapper (set to 0)
- **Universal Design**: Consistent card styling across all sections

## [1.0.34] - 2026-04-13
### Refined Spacing and Table Styling
- **Salary Info Section**: Added padding-bottom 10px to h3 heading
- **Section Gaps**: Added 20px gap between children using parent container gap
- **Section Margin**: Added margin-bottom 50px to section container
- **Card Styling**: Removed margins from card
- **Salary History Section**: Applied same spacing as salary information section
- **Table Header**: Updated padding to 14px 5px
- **Table Rows**: Updated padding to 16px 5px
- **Table Container**: Removed all borders and border-radius
- **Table Borders**: Bottom border only on rows (1px solid #e9ecef)
- **Search Icon**: Changed from ph-times to ph-x for clear functionality
- **Search Box**: Stretches to fill available space
- **Pagination**: Per-page control (5, 10, 25, 50 items)
- **Row Hover**: Subtle background effect
- **Professional Design**: Clean, minimal appearance throughout

## [1.0.33] - 2026-04-13
### Complete Salary History & Table Styling Overhaul
- **Section Headings**: Removed all margins from h2, h3 headings
- **Heading Underline**: Simplified to single ::after pseudo-element with 34px x 3px primary color underline
- **Salary Information Card**: Fixed to show current/latest salary type dynamically
- **Salary Type Detection**: Checks fixed/combined flags first (same logic as header)
- **Salary Display**: Shows correct amount and frequency based on type
- **Table Container**: Removed border and border-radius for clean appearance
- **Table Header**: Updated padding to 15px top/bottom, 3px left/right
- **Search Functionality**: Box stretches to fill available space
- **Search Icon**: Toggles between magnifying glass and times (×) icon
- **Clear Search**: Click times icon to clear search input
- **Pagination**: Per-page control (5, 10, 25, 50 items)
- **Page Navigation**: Full pagination functionality with page buttons
- **Table Styling**: No borders except bottom border on rows
- **Hover Effect**: Subtle background on row hover
- **Professional Design**: Clean, minimal appearance throughout

## [1.0.32] - 2026-04-13
### Improved Salary History Section Styling
- **Section Heading**: Applied same left border marking style (34px x 3px) to all section headings
- **Heading Styling**: Removed icons from headings, no margins, full-width bottom border
- **Gap**: 20px gap between heading and table section
- **Search Box**: Stretched to fill available space (flex: 1)
- **Search Icon Toggle**: Shows magnifying glass when empty, changes to times (×) icon when text entered
- **Clear Functionality**: Click times icon to clear search input
- **Per-Page Control**: Removed margins from label for better alignment
- **Table Styling**: 
  - Removed all borders except bottom border on rows
  - No container border
  - Subtle background with proper padding
  - Hover effect shows background for clean look
  - Sorting system remains unchanged

## [1.0.31] - 2026-04-13
### Improved Salary Information Section Styling
- Removed padding and border from header bio section
- Fixed salary type detection to show actual current type
- Added left border marking (34px x 3px) to salary info heading
- Full-width bottom border below heading with 20px gap to content
- Removed heading icon and margins
- Added salary display in top right corner of card
- Shows amount/frequency for fixed and combined salaries
- Shows 'Percentage/Order' for commission-based salaries
- Combined salary shows combined icon marking

## [1.0.29] - 2026-04-13
### Fixed Dynamic CSS Class Names
- Updated dynamic CSS selectors to match new header layout class names
- Changed `.role-section` to `.profile-role`
- Changed `.header-container-3` to `.header-row-2`
- Changed `.header-container-4` to `.header-row-3`
- All color variables now properly applied to correct elements
- Verified responsive design on all screen sizes

## [1.0.28] - 2026-04-12
### Professional Header with Organized Containers
- **Container 1**: Profile picture (120px circular with primary color border)
- **Container 2**: Name (left) and Role (right, 100% width with 10% primary background)
- **Container 3**: Two-column layout
  - Left column: ID (clickable to copy), Phone, Email with icons
  - Right column: Salary type, Status, Social icons
- **Container 4**: Bio section with 10px padding-top and header border color
- **Icons**: 18px size, primary color, secondary color on hover
- **Social Icons**: Raw icons without background or border, proper spacing
- **Layout**: Uses parent container gaps, no individual margins/padding
- **Dynamic Styling**: All colors controlled via admin settings
- **Fully Responsive**: Optimized for all screen sizes

## [1.0.27] - 2026-04-12
### Professional Header - Minimal & Fresh Design
- **Complete redesign** of employee header from scratch
- **Modern minimal layout** like big platforms (LinkedIn, GitHub, etc.)
- **Left section**: Profile picture (120px circular with border)
- **Middle section**: Name, role, bio, contact info (ID, email, phone with icons)
- **Right section**: Salary type box and social media icons
- **Clean design**: White background with subtle border and minimal shadow
- **Professional spacing**: Proper typography and visual hierarchy
- **Contact info**: ID, email, phone displayed inline with icons
- **Fully responsive**: Optimized for desktop, tablet, and mobile
- **Dynamic styling**: All colors controlled via admin settings

## [1.0.26] - 2026-04-12
### Header CSS Redesign - Clean Modern Layout
- **Redesigned header CSS from scratch** for clean, modern appearance
- **Icons**: No background, only primary color (dynamic)
- **Labels**: Secondary color (dynamic)
- **Values**: Text color (dynamic)
- **Layout**: Proper horizontal layout with left stats, center profile, right stats
- **Clean Design**: Removed unnecessary backgrounds and shadows
- **Better Spacing**: Improved visual hierarchy and alignment
- **Mobile Responsive**: Optimized for all screen sizes
- **Dynamic Styling**: All colors controlled via admin settings

## [1.0.25] - 2026-04-12
### Modern Profile Card Header - Production Release
- Redesigned employee header with modern profile card layout
- Left column: Employee ID, Email, Phone (vertical with icons)
- Center: Large profile picture with negative margin (30-40% out)
- Below picture: Employee name and role
- Bio section: Clean text with top/bottom borders
- Bottom: Social icons (left) and salary type box (right)
- Clean design without box styling
- Smart placeholders: "---" for missing text, initials for missing images
- Mobile responsive for all screen sizes
- Dynamic styling via admin settings
- Professional appearance matching modern design patterns

## [1.0.24] - 2026-04-12
### Redesigned Employee Header - Modern Profile Card Layout
- **New Header Layout**: Completely redesigned to match modern profile card design
  - Left column: Employee ID, Email, Phone (vertical stack with icons)
  - Center: Large profile picture with negative margin (30-40% out of card)
  - Below picture: Employee name and role
  - Bio section: Clean text display with top/bottom borders
  - Bottom: Social icons (left) and salary type box (right)
- **Clean Design**: Removed box styling, using clean text with icons
- **Smart Placeholders**: 
  - Missing text shows "---" (hyphens)
  - Missing profile picture shows initials (first letters of display name)
- **Improved Spacing**: Better visual hierarchy with proper margins and padding
- **Mobile Responsive**: Optimized for all screen sizes (desktop, tablet, mobile)
- **Dynamic Styling**: All colors controlled via admin settings
- **Professional Look**: Matches modern profile card design patterns

## [1.0.23] - 2026-04-12
### Stable Release - My Account Enhancement Complete
- **Professional Employee Header**: Fully implemented with connecting lines, profile picture, and organized info boxes
- **Complete Data Display**: Employee ID, Name, Phone, Email, Role, Status, and Bio sections
- **Social Media Integration**: Facebook, WhatsApp, Instagram, LinkedIn icons with brand color hovers
- **Salary Type Section**: Dynamic display with conditional linking to Salary Details page
- **Mobile Responsive**: Optimized layouts for desktop (1024px+), tablet (768px), and mobile (480px)
- **Dynamic Styling**: All colors controlled via admin settings (header_border_color, etc.)
- **Advanced Table Features**: Sorting, pagination, search, and per-page options across all My Account pages
- **Consistent Design**: Applied professional design pattern across all 4 My Account pages
- **Admin Settings Integration**: Header border color and styling settings fully functional

## [1.0.22] - 2026-04-12
### Enhanced My Account Implementation
- **Enhanced Employee Header**: Added comprehensive employee header with profile picture, employee ID, role, display name, phone, email, bio, and status across all My Account pages
- **Advanced Table Features**: Implemented sorting, pagination, search, and per-page options for salary history table following admin panel design patterns
- **Dynamic Styling Integration**: Added header background and table styling settings to admin panel with full integration in frontend
- **Improved User Experience**: Consistent header pattern across all 4 My Account pages (Salary Details, My Earnings, Orders Commission, Reports)
- **Professional Design**: Enhanced CSS with proper role labeling (Administrator, Manager, Employee) and responsive layout

## [1.0.21] - 2026-04-12
### Changed
- **Phosphor Icons**: Switched from Font Awesome to Phosphor Icons for better visual appearance
  - Added Phosphor Icons CDN: `https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2`
  - Updated all icons in My Account tabs and content areas
  - Icons: `ph-briefcase`, `ph-wallet`, `ph-shopping-bag`, `ph-chart-bar`, etc.

### Removed
- **Debug Borders**: Completely removed debug borders from all My Account containers
  - No more 2px blue borders around `.wc-team-payroll-salary-details` and similar containers
  - Removed "Remove Debug Border" setting option (now clean by default)
  - Cleaner, more professional appearance

### Improved
- **Icon Consistency**: All icons now use Phosphor icon system for uniform appearance
- **Visual Polish**: Cleaner layout without debug elements
- **Performance**: Reduced CSS complexity by removing conditional border styling

### Technical
- Added Phosphor Icons script dependency
- Updated JavaScript icon injection to use `ph ph-*` classes
- Simplified CSS by removing debug border conditions
- Updated all icon references throughout the codebase

## [1.0.20] - 2026-04-12
### Added
- **Frontend Styling Settings**: New "Frontend Styling" tab in settings with comprehensive customization options
  - Color scheme settings (primary, secondary, heading, text, link colors)
  - Background and border color controls
  - Typography settings (font family, font sizes)
  - Button styling (colors, hover states, border radius)
  - Layout settings (card border radius, shadow depth)
  - Live preview of styling changes
  - Option to remove debug borders

### Changed
- **Outline Icons**: Switched from filled (fas) to outline (far) Font Awesome icons for better visual appearance
- **Dynamic Styling**: My Account pages now use styling settings from admin panel
- **Improved Salary Details Page**: Better layout and styling following admin page design system
- **Removed Debug Border**: Option to remove the blue debug border around My Account sections

### Improved
- **Settings Integration**: Styling settings are saved and applied dynamically to frontend
- **Icon Colors**: Icons now use primary color from settings for consistency
- **Typography**: Font family and sizes are now customizable and applied consistently
- **Card Styling**: Cards, buttons, and layout elements follow the admin design system
- **Color Consistency**: All colors (headings, text, links, buttons) use settings values

### Technical
- Added `wc_team_payroll_styling` option to store frontend styling settings
- Dynamic CSS generation based on user settings
- Improved cache busting for CSS files
- Better CSS specificity for reliable styling application

## [1.0.19] - 2026-04-12
### Fixed
- Fixed HTML escaping issues in My Account tabs (icons now display properly)
- Fixed wc_price() output escaping (currency formatting now displays correctly)
- Improved CSS loading with better cache busting using file modification time
- Enhanced JavaScript icon injection to be more robust and prevent duplicates
- Added debug information to Reports page for troubleshooting
- Fixed all price displays throughout My Account pages to show formatted currency

### Improved
- Better CSS specificity to ensure styles are applied properly
- More reliable icon injection that works after AJAX updates
- Enhanced error handling for CSS and JavaScript loading

## [1.0.18] - 2026-04-12

### 🔧 **Fixes**
- Updated GitHub updater to use pv-team-management repository
- All future updates will now come from the correct repository
- Fixed update detection to use the proper GitHub repository

---

## [1.0.17] - 2026-04-12

### 🔧 **Fixes**
- Fixed version comparison logic in GitHub updater
- Improved version normalization to handle edge cases
- Fixed issue where plugin always showed as needing update
- Added better debugging for version reading
- WordPress now correctly detects when plugin is up to date

---

## [1.0.16] - 2026-04-12

### 🔧 **Fixes**
- Improved GitHub updater reliability
- Removed hourly check limit to force update checks on every admin page load
- Added better debugging for update detection
- WordPress now properly shows plugin updates in the updates page

---

## [1.0.15] - 2026-04-12

### 🔧 **Fixes**
- Moved endpoint registration to main plugin file for proper WooCommerce integration
- All custom My Account pages now render correctly with proper content
- Updated tab icon size to 20px for better visibility

---

## [1.0.14] - 2026-04-12

### 🔧 **Fixes**
- Added template fallback for endpoint rendering
- Endpoints now properly render with WooCommerce template structure
- All custom My Account pages now display correctly without loading issues
- Fixed infinite loading spinner on Salary Details, My Earnings, and Reports pages

---

## [1.0.13] - 2026-04-12

### 🔧 **Fixes**
- Fixed endpoint hook names - WooCommerce converts hyphens to underscores
- Changed hooks from `woocommerce_account_my-salary-details_endpoint` to `woocommerce_account_my_salary_details_endpoint`
- All custom My Account pages now properly render content
- Salary Details, My Earnings, and Reports tabs now display correctly

---

## [1.0.12] - 2026-04-12

### 🔧 **Fixes**
- Added automatic rewrite rules flushing for custom endpoints
- Endpoints now properly recognized by WordPress
- All My Account tabs (Salary Details, My Earnings, My Orders, Reports) now load correctly
- Fixed loading issue on custom pages

---

## [1.0.11] - 2026-04-12

### 🔧 **Fixes**
- Fixed menu icons not displaying properly by using JavaScript injection
- Icons now render correctly without being escaped by WooCommerce
- Phosphor icons now display properly on all My Account menu items

---

## [1.0.10] - 2026-04-12

### 🔧 **Fixes**
- Fixed GitHub updater to use correct repository (pv-team-payroll-demo1)
- WordPress now properly detects and displays available updates
- Update notifications will now appear in WordPress admin

---

## [1.0.9] - 2026-04-12

### ✨ **Features**
- Switched to Phosphor icons library for better icon rendering
- Icons now display with proper styling and alignment

### 🔧 **Fixes**
- Fixed endpoint registration priority to ensure all custom tabs load properly
- Moved endpoint registration to main plugin file with priority 1
- Fixed HTML rendering in menu items with proper escaping
- All custom My Account pages (Salary Details, My Earnings, My Orders, Reports) now load correctly

---

## [1.0.8] - 2026-04-12

### ✨ **Features**
- Switched to Font Awesome 6 icon library for better icon rendering
- Added query variable registration for custom endpoints

### 🔧 **Fixes**
- Fixed endpoint rendering by properly registering query variables
- Improved hook priorities to ensure proper execution order
- Icons now display correctly with Font Awesome

---

## [1.0.7] - 2026-04-12

### 🔧 **Fixes**
- Fixed HTML escaping issue in My Account menu items
- Icons now render properly using CSS pseudo-elements instead of HTML tags
- Fixed endpoint rendering for all custom My Account tabs
- Removed transient-based endpoint registration to ensure consistent behavior
- Added deactivation hook to properly clean up rewrite rules

---

## [1.0.6] - 2026-04-12

### ✨ **Features**
- **My Account Tabs**: Fixed 404 errors on My Account tabs (Salary Details, My Earnings, My Orders, Reports)
- **Simple Line Icons**: Added simple-line-icons library for tab icons
- **Endpoint Registration**: Properly register and flush rewrite rules on plugin activation
- **Tab Icons**: Added icons to My Account menu items for better UX

### 🔧 **Improvements**
- Automatic rewrite rule flushing on plugin activation
- Transient-based endpoint registration to prevent unnecessary flushes
- Responsive CSS styling for My Account pages

---

## [1.0.1] - 2026-04-12

## Improvements

- Enhanced GitHub update checker with better error handling
- Added Debug tab in Settings to troubleshoot update issues
- Users can manually check for updates and clear cache
- Improved error logging for debugging

---
# Changelog

## [1.0.1] - 2026-04-12

### 🔧 **Improvements**
- **GitHub Update Detection**: Enhanced update checker with better error handling
- **Debug Tab**: Added Debug tab in Settings to troubleshoot update issues
- **Manual Update Check**: Users can now manually check for updates and clear cache
- **Better Error Messages**: Improved error logging for debugging

---

## [1.0.0] - 2026-04-12

### 🎉 **MAJOR RELEASE - Production Ready**

#### ✨ **New Features**
- **Salary History Tracking**: Now records changes to salary amount and frequency (not just type)
- **Payment Editing**: Fixed issue where clicking payment method field created multiple forms
- **Removed Payments Menu**: Cleaned up admin menu by removing dedicated Payments page

#### 🐛 **Bug Fixes**
- **Payment Edit Forms**: Fixed multiple edit forms appearing when clicking method dropdown repeatedly
- **Payment Method Edit Forms**: Fixed duplicate forms being created on repeated clicks
- **Salary History**: Now properly tracks all salary changes (type, amount, and frequency)

#### 🔧 **Technical Improvements**
- **Form Cleanup**: Edit forms now properly remove previous instances before creating new ones
- **Event Handling**: Improved event delegation to prevent form duplication
- **Data Integrity**: Salary history now captures all meaningful changes

#### 📦 **Release Highlights**
- Stable production release with all core features working
- Comprehensive commission and payroll management system
- Full GitHub-based update system for automatic WordPress updates
- Professional admin interface with responsive design
- Complete audit trails for all employee changes

---

## [5.8.8] - 2024-12-19

### ✨ **Features**
- **Payroll Details Page**: Added profile pictures and formatted employee names to payroll table
- **Employee Display**: Shows employee name in format `PVVB-EMID1 Md Imran Hossain` with profile picture
- **Enhanced Data**: Added user email, phone, and role information to payroll data AJAX response

---

## [5.8.7] - 2024-12-19

### 🐛 **Bug Fixes**
- **Employee Payroll Details Button**: Fixed "View All" button link from incorrect page slug `wc-team-payroll-payroll` to correct `wc-team-payroll-details`
- Button now correctly navigates to the Payroll page

---

## [5.8.6] - 2024-12-19

### 🐛 **Bug Fixes**
- **Top Earners**: Fixed employee name format from `(PVVB-EMID1) Name` to `PVVB-EMID1 Name`
- **Recent Payments**: Fixed employee name format from `(PVVB-EMID1) Name` to `PVVB-EMID1 Name`
- **Latest Employees**: Fixed duplicate vb_user_id display (was showing `PVVB-EMID1 PVVB-EMID1 Name`)
- **Consistent Formatting**: Applied uniform employee name format across all dashboard tables

---

## [5.8.5] - 2024-12-19

### 🐛 **Bug Fixes**
- **Employee Name Format**: Fixed display format from `(PVVB-EMID1) Name` to `PVVB-EMID1 Name` (removed parentheses)
- **Table Cell Padding**: Added `!important` flag to td padding to prevent media query overrides
- **Consistent Formatting**: Applied fix to all employee name displays across dashboard tables

---

## [5.8.4] - 2024-12-19

### 🐛 **Bug Fixes**
- **Table Header Padding**: Fixed padding being overridden by media query rules - added `!important` flag
- **Employee Name Display**: Simplified format to match payroll table styling with proper `<strong>` tag

---

## [5.8.3] - 2024-12-19

### 🐛 **Bug Fixes**
- **Bulk Delete Issue**: Fixed bulk delete not working on second attempt in employee detail tabs
- **Checkbox State**: Reset "select all" checkbox state when table is re-rendered
- **Employee Name Display**: Fixed duplicate vb_user_id display in latest employees table

---

## [5.8.2] - 2024-12-19

### 🎨 **UI/UX Improvements**
- **Header Padding**: Increased table header padding to match row items (14px 12px)
- **Section Headings**: Removed all margins from section heading tags (h2, h3) for cleaner layout
- **Warning Suppression**: Hidden WooCommerce compatibility warnings on plugin pages

---

## [5.8.1] - 2024-12-19

### 🐛 **Bug Fixes**
- **Table Display Fix**: Fixed broken table headers displaying vertically (column-wise) throughout the plugin
- **CSS Enhancement**: Added explicit display properties to all table elements to prevent conflicts
- **Cross-Browser Compatibility**: Ensured tables render correctly across all browsers and WordPress admin themes

### 🔧 **Technical Improvements**
- Added `!important` flags to table display properties to override conflicting CSS
- Applied fix to all pages: Dashboard, Employee Management, Employee Detail, Payments, and Payroll
- Improved CSS specificity for `.wc-tp-data-table` and child elements

---

## [5.8.0] - 2024-12-19

### 🚀 **Major Features**
- **Employee Status Management System**: Complete employee activation/deactivation functionality
- **Enhanced Global Search**: Comprehensive search across orders, employees, customers, and payments
- **Dashboard UI Polish**: Improved spacing, styling, and user experience

### ✨ **New Features**
- **Employee Status Control**: Active/Inactive dropdown in employee detail pages
- **Checkout Integration**: Inactive employees automatically hidden from agent dropdown
- **Login Security**: Inactive employees blocked from WordPress login with professional warning
- **Contact Information**: Settings for WhatsApp, Email, and Telegram contact details
- **Status Display**: Employee status shown in dashboard tables with color-coded badges
- **Global Search Enhancement**: 
  - Search across all data types (orders, employees, customers, payments)
  - Clear button with X icon
  - Improved search logic with better field coverage
  - Enhanced result display with proper formatting

### 🎨 **UI/UX Improvements**
- **Dashboard Cards**: Text content aligned to bottom for better visual hierarchy
- **Table Headers**: Increased padding (16px 14px) for better readability
- **Section Headers**: Consistent spacing with minimal "View All" buttons
- **Action Icons**: Removed underlines and borders for cleaner appearance
- **Employee Links**: Names link to WordPress user edit page, action icons to detail pages
- **Responsive Design**: Better mobile experience across all components

### 🔧 **Technical Enhancements**
- **AJAX Handler**: `wc_tp_update_employee_status` for real-time status updates
- **Security**: Proper nonce verification and capability checks
- **Data Validation**: Input sanitization and error handling
- **Performance**: Optimized search queries with limits to prevent timeouts
- **Backward Compatibility**: All changes work with existing data

### 🛠 **Settings & Configuration**
- **Contact Settings**: New fields in General settings for employee support contacts
- **Status Management**: Automatic status handling with confirmation dialogs
- **Default Behavior**: New employees default to "active" status

### 📱 **Mobile & Responsive**
- **Profile Actions**: Improved mobile layout for status controls
- **Search Interface**: Better mobile search experience
- **Table Display**: Enhanced responsive table behavior

### 🔒 **Security & Access Control**
- **Role-Based Blocking**: Only applies to team members (employees, managers, admins)
- **Flexible Transitions**: Former employees can become customers without restrictions
- **Professional Messaging**: User-friendly warning messages with contact information

---

## [5.7.8] - 2026-04-12

### MAJOR IMPROVEMENTS - Dashboard UI Enhancements & Global Search

#### Dashboard Stats Cards Layout
- ✅ **5 Columns Per Row**: Changed from 4 to 5 columns for better space utilization
- ✅ **Reduced Height**: Decreased card height from 120px to 100px for compact look
- ✅ **Floating Icons**: Icons moved from left side to top-right corner with absolute positioning
- ✅ **Smaller Icons**: Reduced icon size from 32px to 24px for cleaner appearance
- ✅ **Vertical Layout**: Changed flex direction to column for better text organization
- ✅ **Responsive Breakpoints**: 3 columns at 1024px, 1 column at 768px

#### Global Search Feature
- ✅ **Search Box**: Added before filter section with "Search for anything..." placeholder
- ✅ **Live Results**: Floating results box with real-time search as you type
- ✅ **Multi-Type Search**: Searches across Orders, Employees, Customers, and Payments
- ✅ **Result Badges**: Type indicators (Order, Employee, Customer, Payment) with color coding
- ✅ **Pagination**: Results paginated at 10 items per page
- ✅ **Clickable Results**: Each result links to relevant detail page
- ✅ **Hover Tooltips**: Shows full details on hover
- ✅ **Responsive Design**: Adapts to all screen sizes
- ✅ **AJAX Handler**: `wc_tp_global_search` for efficient searching

#### Dashboard Table Improvements
- ✅ **Profile Pictures**: All employee names now show profile picture (32x32px circular)
- ✅ **Employee Format**: Shows as "[Picture] VBID FirstName" (e.g., "PVVB-EMID1 Imran")
- ✅ **Clickable Names**: Employee names link to their detail page
- ✅ **Hover Tooltips**: Shows Name, Email, Phone, Role on hover
- ✅ **Removed Email Column**: Email now only in tooltip for cleaner tables
- ✅ **Relevant Columns**: Added context-specific columns per table

#### Dashboard Table Column Updates
- **Latest Employees**: Added Status column (shows "Active" badge)
- **Top Earners**: Reordered to show Orders before Total Earnings
- **Recent Payments**: Reordered to show Date before Amount
- **Payroll Table**: Removed email, kept Orders, Total Earnings, Paid, Due

#### Dashboard Table Sorting & Styling
- ✅ **Dashicon Arrows**: Changed from Unicode arrows to dashicons (arrow-up/arrow-down)
- ✅ **Active Sort Indicator**: Icons only show on currently sorted column
- ✅ **Consistent Styling**: Headers use `var(--color-accent-muted)` background
- ✅ **Proper Padding**: Headers 14px 12px, cells 12px for organized look
- ✅ **Hover Effects**: Background changes to `var(--color-primary-subtle)` on hover
- ✅ **Clean Headers**: No persistent highlighting, only arrow indicator

#### Employee Detail Page - Payment Methods
- ✅ **Edit Icon**: Added edit button next to delete for payment methods
- ✅ **Inline Editing**: Edit form similar to payment history editing
- ✅ **Update Handler**: `wc_tp_update_payment_method` AJAX handler for updates
- ✅ **Edit Fields**: Can edit payment method name and account details inline

#### Screen Options Persistence
- ✅ **localStorage Integration**: Items per page preference saved to browser storage
- ✅ **Orders Tab**: Key `wc_tp_orders_per_page`
- ✅ **Payment History**: Key `wc_tp_payment_history_per_page`
- ✅ **Payment Methods**: Key `wc_tp_payment_methods_per_page`
- ✅ **Persistent Across Sessions**: User preferences survive browser restarts

#### Bulk Delete Bug Fix
- ✅ **Fixed Select All Issue**: Bulk delete now works correctly when selecting all via header checkbox
- ✅ **Proper Validation**: Added validation to check if IDs exist before adding to array
- ✅ **Correct Counter**: Uses `totalToDelete` variable to avoid scope issues
- ✅ **All Items Deleted**: Selecting all items now deletes all, not just first item

### Technical Improvements
- ✅ **Global Search Script**: Enqueued only on dashboard page for performance
- ✅ **Profile Picture Meta**: Uses custom field `_wc_tp_profile_picture`
- ✅ **Consistent UX**: All dashboard tables follow same styling and interaction patterns
- ✅ **Performance**: Optimized AJAX calls for search and data fetching
- ✅ **Accessibility**: Proper ARIA labels and keyboard support throughout

## [5.7.7] - 2026-04-12

### MAJOR IMPROVEMENTS - Employee Detail Page & Global Features

#### Global Delete Confirmation Modal
- ✅ **Custom Modal System**: Created reusable delete confirmation modal (similar to toast)
- ✅ **Type "DELETE" Confirmation**: Requires typing "DELETE" to confirm deletion
- ✅ **No Browser Alerts**: Replaced all browser confirm dialogs
- ✅ **Custom Messages**: Shows specific warning messages per action
- ✅ **Responsive Design**: Works on all screen sizes
- ✅ **Global Access**: Available via `wcTPDeleteModal()` function

#### Bulk Select & Delete - Payment History
- ✅ **Select All Checkbox**: Header checkbox to select/deselect all payments
- ✅ **Individual Checkboxes**: Each payment row has a checkbox
- ✅ **Bulk Delete Button**: Shows count of selected items
- ✅ **Batch Processing**: Deletes multiple payments at once
- ✅ **Progress Feedback**: Shows success/failure count after bulk delete

#### Bulk Select & Delete - Payment Methods
- ✅ **Select All Checkbox**: Header checkbox to select/deselect all methods
- ✅ **Individual Checkboxes**: Each method row has a checkbox
- ✅ **Bulk Delete Button**: Shows count of selected items
- ✅ **Batch Processing**: Deletes multiple methods at once
- ✅ **Progress Feedback**: Shows success/failure count after bulk delete

#### Sorting & Pagination - All Tables
- ✅ **Payment History Sorting**: All columns sortable (Amount, Date, Method, Added By)
- ✅ **Payment Methods Sorting**: All columns sortable (Method Name, Details)
- ✅ **Salary History Sorting**: All columns sortable (Date, Types, Amounts, Changed By)
- ✅ **Pagination Controls**: Page numbers with prev/next buttons
- ✅ **Screen Options**: Dropdown to select items per page (5, 10, 25, 50, 100)
- ✅ **Smart Ellipsis**: Shows ... for large page counts
- ✅ **Active Highlighting**: Current page and sort column highlighted

#### Date & User Formatting
- ✅ **Salary History Dates**: Proper format (YYYY-MM-DD HH:MM)
- ✅ **User Display**: Shows actual user display name with link to profile
- ✅ **Tooltips**: Hover shows email and role
- ✅ **Clickable Links**: User names link to WordPress user edit page

#### Salary Management Improvements
- ✅ **Amount Reset**: Clears amount field when changing salary type
- ✅ **Toast Notifications**: Replaced alerts with toast messages
- ✅ **Type Transitions**: Properly handles Commission ↔ Fixed ↔ Combined changes

#### Profile Picture & User ID
- ✅ **Custom Field Integration**: Uses `_wc_tp_profile_picture` meta key
- ✅ **Proper Display**: Shows uploaded profile picture from user profile
- ✅ **Label Update**: Changed "VB User ID" to "User ID"

#### Default Date Filters
- ✅ **Payroll Page**: Changed default from "This Month" to "All Time"
- ✅ **Employee Management**: Changed default from "This Month" to "All Time"

#### Orders Tab Enhancements
- ✅ **Sortable Headers**: All columns clickable for sorting
- ✅ **Sort Icons**: Up/down arrows show sort direction
- ✅ **Default Sort**: Date descending (newest first)
- ✅ **Pagination**: Full pagination with screen options
- ✅ **Active Highlighting**: Sorted column highlighted

#### Back Navigation
- ✅ **Back Button**: Added "Back to Employees" button on employee detail page
- ✅ **Icon & Text**: Left arrow icon with text
- ✅ **Hover Effects**: Primary color on hover

### Technical Improvements
- ✅ **Global Scripts**: Toast and Delete Modal available plugin-wide
- ✅ **Consistent UX**: All tables have same sorting/pagination experience
- ✅ **Performance**: Client-side sorting and pagination for instant response
- ✅ **Accessibility**: Proper ARIA labels and keyboard support

## [5.7.6] - 2026-04-12

### IMPROVED - Employee Detail Page Payments Tab

#### Toast Notification System
- ✅ **Global Toast System**: Added reusable `wcTPToast()` function for all notifications
- ✅ **No Browser Alerts**: Replaced all `alert()` with floating toast notifications
- ✅ **Auto-Hide**: Toasts auto-hide after 4 seconds
- ✅ **Manual Close**: Small close button (×) for manual dismissal
- ✅ **Top-Right Position**: Fixed position in top-right corner
- ✅ **Responsive**: Adapts to mobile screens
- ✅ **Success/Error States**: Color-coded (green for success, red for error)

#### Payment History Enhancements
- ✅ **Added Method Column**: Shows payment method used
- ✅ **Fixed Added By**: Now correctly displays user who added payment (was showing null)
- ✅ **Action Icons**: Changed from buttons to icon buttons (edit + delete)
- ✅ **Inline Editing**: Edit button opens inline form with prefilled values
- ✅ **Edit Payment**: Can edit amount and date inline
- ✅ **Save/Cancel**: Inline form with save and cancel buttons

#### Stats Card Updates
- ✅ **Auto-Update**: Stats cards update automatically after payment add/edit/delete
- ✅ **Real-Time**: Uses new `wc_tp_get_employee_stats` AJAX handler
- ✅ **Accurate**: Total Paid and Total Due reflect latest changes

#### AJAX Handlers Added
- ✅ `wc_tp_get_employee_stats` - Fetches updated employee statistics
- ✅ `wc_tp_update_payment` - Updates existing payment (amount and date)

#### User Experience
- ✅ No page reloads needed
- ✅ Smooth animations and transitions
- ✅ Inline editing without leaving the page
- ✅ Immediate feedback via toast notifications

## [5.7.5] - 2026-04-11

### IMPROVED - Employee Detail Page Tab Architecture

#### Major Architectural Change
- **Switched from JavaScript-based tabs to URL-based tabs** (like Settings page)
- Each tab now uses GET parameter: `?tab=orders`, `?tab=payments`, `?tab=salary`
- Tabs are now completely independent with no shared JavaScript state

#### Benefits
- ✅ **No Tab Interference**: Each tab loads fresh with isolated JavaScript scope
- ✅ **Clean State**: Page reload ensures no leftover variables or data
- ✅ **WordPress Standard**: Uses `nav-tab` and `nav-tab-active` classes
- ✅ **Better Performance**: Only active tab's HTML and JavaScript loads
- ✅ **Reliable**: Same proven pattern as Settings page

#### Technical Implementation
- Changed tab navigation from `<button>` to `<a>` links
- Added conditional rendering: `<?php if ( $current_tab === 'tabname' ) : ?>`
- Each tab initializes only when active (no global initialization)
- Removed shared variables between tabs
- Updated CSS to match WordPress nav-tab styling

#### What Still Works
- ✅ All Orders tab functionality (search, filters, pagination)
- ✅ All Payments tab functionality (methods, history, add/delete)
- ✅ All Salary tab functionality (management, history, update)
- ✅ Profile header and stats cards
- ✅ All AJAX handlers
- ✅ All styling and responsive design

## [5.7.4] - 2026-04-11

### ADDED - Debugging to Employee Detail Page

- Added console logging to AJAX calls for troubleshooting
- Added console logging to tab switching for debugging
- Improved error handling in AJAX calls to show actual errors
- This helps identify why orders table is not showing and tabs are not switching

## [5.7.3] - 2026-04-11

### FIXED - Employee Detail Page Now Working Properly

#### Root Cause Analysis
- v5.7.0+ had complex JavaScript with hidden date inputs causing AJAX failures
- Date inputs were hidden by default (`display: none`), making them inaccessible to jQuery
- v5.6.0 had simpler, working tab switching and table display logic

#### The Solution
- **Reverted to v5.6.0 base structure** - Simple, proven working JavaScript
- **Kept new tab order**: Orders → Payments → Salary Management
- **Added full Payments tab content**:
  - Payment methods section (add/delete)
  - Add payment form
  - Payment history table
- **Added full Salary Management tab content**:
  - Salary type selection
  - Salary amount and frequency fields
  - Update salary button
  - Salary history table

#### What Now Works
- ✅ Orders table displays immediately on page load
- ✅ Tab switching works correctly for all three tabs
- ✅ All tab-specific data loads properly when switching tabs
- ✅ Payment methods can be added/deleted
- ✅ Payments can be added/deleted with proper AJAX
- ✅ Salary can be updated with complete history tracking
- ✅ Date filtering works correctly
- ✅ Search and status filters work as expected
- ✅ All AJAX handlers work properly

#### Technical Details
- Simplified JavaScript initialization
- Removed complex hidden input handling
- Used proven v5.6.0 tab switching logic
- Maintained all AJAX handlers from v5.7.0
- All styling and responsive design preserved

### Files Modified
- `includes/class-employee-detail.php` - Restored working base with new content
- Deleted `includes/class-employee-detail-old.php` - No longer needed

## [5.7.2] - 2026-04-11

### CRITICAL BUG FIX - Employee Detail Page Orders Table & Tab Switching

#### Root Cause Identified
- Date input elements were hidden by default (`display: none`)
- jQuery couldn't properly access values from hidden input elements
- `loadOrdersData()` was receiving empty date values and returning early
- AJAX call was never being made to fetch orders

#### The Fix
- Store dates in JavaScript variables instead of relying on hidden inputs
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


