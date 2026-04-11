# WooCommerce Team Payroll v5.4.10 - Inline CSS & JS Extraction Mapping

## Executive Summary

This document maps all inline CSS and JavaScript code found in 7 PHP files, identifying which code is common (already in common.css/common.js) and which is page-specific. The analysis reveals significant opportunities for consolidation and code reuse.

---

## 1. COMMON STYLES (Already in common.css)

### Design System Variables
- Color palette (primary, secondary, accents)
- Typography system (font sizes, weights, line heights)
- Spacing and layout utilities

### Common Components
- `.wc-tp-search-filter` - Search input with clear button
- `.wc-tp-unified-filter` - Multi-field filter groups
- `.wc-tp-date-filter` / `.wc-tp-date-separator` - Date range inputs
- `.wc-tp-table-section` - Card wrapper for tables
- `.wc-tp-data-table` - Base table styling
- `.wc-tp-sortable-header` - Sortable column headers with icons
- `.wc-tp-badge` - Status/count badges
- `.wc-tp-pagination` - Pagination controls
- `.wc-tp-empty-state` - Empty state messaging
- `.button-primary` - Primary action buttons
- `.wc-tp-page-wrapper` - Page container

### Responsive Breakpoints
- 768px (tablet)
- 480px (mobile)

---

## 2. FILE-BY-FILE ANALYSIS

### FILE 1: includes/backend/class-dashboard.php

#### Inline CSS (1,067 lines total)
**Location:** Lines ~70-600 (within `<style>` tag)

**Common Styles Used:**
- `:root` CSS variables (duplicated - should use common.css)
- `.wc-tp-date-filter` (matches common.css)
- `.wc-tp-stats-grid` (grid layout - UNIQUE)
- `.wc-tp-stat-card` (card styling - UNIQUE)
- `.wc-tp-stat-link` (link styling - UNIQUE)
- `.wc-tp-stat-icon` (icon styling - UNIQUE)
- `.wc-tp-stat-content` (content wrapper - UNIQUE)
- `.wc-tp-stat-value` (value display - UNIQUE)
- `.wc-tp-stat-label` (label styling - UNIQUE)
- `.wc-tp-dashboard-grid` (two-column layout - UNIQUE)
- `.wc-tp-table-section` (matches common.css)
- `.wc-tp-data-table` (matches common.css)
- `.wc-tp-sortable-header` (matches common.css)
- `.wc-tp-badge` (matches common.css)
- `.wc-tp-empty-state` (matches common.css)
- `.button-primary` (matches common.css)
- Responsive media queries (768px, 480px)

**Page-Specific Styles:**
- `.wc-tp-stats-grid` - 4-column grid for stat cards
- `.wc-tp-stat-card` - Stat card with icon + content layout
- `.wc-tp-stat-icon` - Icon sizing and alignment
- `.wc-tp-stat-value` - Large value display with ellipsis
- `.wc-tp-stat-label` - Uppercase label styling
- `.wc-tp-dashboard-grid` - Two-column responsive grid
- `.wc-tp-paid` - Green text for paid amounts
- `.wc-tp-due` - Red text for due amounts
- `.wc-tp-status` - Status badge variants (paid, pending, failed)
- `.wc-tp-quick-edit` - Quick edit button styling
- `.wc-tp-edit-input` - Inline edit input styling
- `.wc-tp-save-edit` / `.wc-tp-cancel-edit` - Edit action buttons

**Inline JavaScript (Lines ~600-1067)**
**Location:** Within `<script>` tag

**Common JS Functions Used:**
- `formatCurrency()` - Currency formatting (matches common.js)
- jQuery AJAX calls
- Table rendering functions
- Sorting logic

**Page-Specific JS Functions:**
- `loadDashboardData()` - Load all dashboard data via AJAX
- `renderStatCards()` - Render stat cards with links
- `renderLatestEmployees()` - Render employees table
- `renderTopEarners()` - Render top earners table
- `renderRecentPayments()` - Render recent payments table
- `renderPayrollTable()` - Render payroll details table
- `attachSortHandlers()` - Attach sorting to table headers
- Currency storage: `wcCurrency`, `wcCurrencySymbol`, `wcCurrencyPos`

**AJAX Actions:**
- `wc_tp_get_dashboard_data` - Fetch dashboard data

---

### FILE 2: includes/backend/class-payroll-page.php

#### Inline CSS (Lines ~50-350)
**Common Styles Used:**
- `:root` CSS variables (duplicated)
- `.wc-tp-search-filter` (matches common.css)
- `.wc-tp-date-filter` (matches common.css)
- `.wc-tp-table-section` (matches common.css)
- `.wc-tp-data-table` (matches common.css)
- `.wc-tp-sortable-header` (matches common.css)
- `.wc-tp-badge` (matches common.css)
- `.button-primary` (matches common.css)
- `.wc-tp-pagination` (matches common.css)

**Page-Specific Styles:**
- `.wc-tp-salary-filter` - Salary type filter dropdown
- `.wc-tp-items-per-page` - Items per page selector (inline styling)

**Inline JavaScript (Lines ~350-600)**
**Common JS Functions Used:**
- `formatCurrency()` - Currency formatting
- jQuery AJAX calls
- Pagination rendering

**Page-Specific JS Functions:**
- `loadPayrollData()` - Load payroll data with filters
- `renderPayrollTable()` - Render payroll table with pagination
- `attachPayrollSortHandlers()` - Attach sorting handlers
- `renderPagination()` - Render pagination controls
- Filter state management: `searchQuery`, `salaryTypeFilter`, `itemsPerPage`

**AJAX Actions:**
- `wc_tp_get_payroll_data_range` - Fetch payroll data for date range

**LocalStorage Usage:**
- `wc_tp_payroll_items_per_page` - Save items per page preference

---

### FILE 3: includes/backend/class-employee-management.php

#### Inline CSS (Lines ~50-350)
**Common Styles Used:**
- `:root` CSS variables (duplicated)
- `.wc-tp-search-filter` (matches common.css)
- `.wc-tp-salary-filter` (similar to payroll page)
- `.wc-tp-date-filter` (matches common.css)
- `.wc-tp-table-section` (matches common.css)
- `.wc-tp-data-table` (matches common.css)
- `.wc-tp-sortable-header` (matches common.css)
- `.wc-tp-badge` (matches common.css)
- `.button-primary` (matches common.css)
- `.wc-tp-pagination` (matches common.css)

**Page-Specific Styles:**
- None beyond common patterns

**Inline JavaScript (Lines ~350-650)**
**Common JS Functions Used:**
- jQuery AJAX calls
- Pagination rendering

**Page-Specific JS Functions:**
- `loadEmployeesData()` - Load employees with filters
- `renderEmployeesTable()` - Render employees table
- `attachEmployeesSortHandlers()` - Attach sorting handlers
- `renderPagination()` - Render pagination controls
- Filter state management: `searchQuery`, `salaryTypeFilter`, `startDate`, `endDate`

**AJAX Actions:**
- `wc_tp_get_employees_data` - Fetch employees data
- `wc_tp_update_employee_salary` - Update employee salary
- `wc_tp_add_payment` - Add payment record
- `wc_tp_delete_payment` - Delete payment record
- `wc_tp_get_payment_data` - Get payment data
- `wc_tp_add_order_bonus` - Add order bonus
- `wc_tp_get_salary_history` - Get salary history

**LocalStorage Usage:**
- `wc_tp_employees_items_per_page` - Save items per page preference

---

### FILE 4: includes/backend/class-employee-detail.php

#### Inline CSS
**Location:** Minimal - mostly uses common.css via enqueue

**Common Styles Used:**
- All styles from common.css (properly enqueued)
- `.wc-tp-profile-header` (from common.css)
- `.wc-tp-profile-picture` (from common.css)
- `.wc-tp-stats-cards` (from common.css)
- `.wc-tp-tabs-container` (from common.css)
- `.wc-tp-tab-button` (from common.css)
- `.wc-tp-tab-content` (from common.css)
- `.wc-tp-orders-search-filter` (from common.css)
- `.wc-tp-orders-filters` (from common.css)

**Page-Specific Styles:**
- None (all in common.css)

**Inline JavaScript**
**Location:** None - uses enqueued common.js and employee-detail.js

**Note:** This file properly enqueues assets:
```php
wp_enqueue_style( 'wc-tp-common-css', ... );
wp_enqueue_script( 'wc-tp-common-js', ... );
wp_enqueue_script( 'wc-tp-employee-detail-js', ... );
```

---

### FILE 5: includes/backend/class-settings.php

#### Inline CSS (Lines ~150-350)
**Page-Specific Styles:**
- `.wc-tp-roles-container` - Roles repeater container
- `.wc-tp-role-item` - Individual role item styling
- `.wc-tp-role-item.wc-tp-default-role` - Default role highlighting
- `.wc-tp-role-item-header` - Role header layout
- `.wc-tp-role-name` - Role name styling
- `.wc-tp-role-badge` - Default role badge
- `.wc-tp-role-remove` - Remove button styling
- `.wc-tp-role-warning` - Warning message styling
- `.wc-tp-role-capabilities` - Capabilities grid

**Inline JavaScript (Lines ~350-550)**
**Page-Specific JS Functions:**
- `$('#wc-tp-add-role-btn').on('click')` - Add new role handler
- `$(document).on('click', '.wc-tp-role-remove')` - Remove role handler
- Dynamic role item HTML generation

**AJAX Actions:**
- None (form-based settings page)

---

### FILE 6: includes/frontend/class-myaccount.php

#### Inline CSS
**Location:** Inline `<style>` tags within render functions

**Common Styles Used:**
- Table styling (inline `<style>` attributes)
- Basic layout styling

**Page-Specific Styles:**
- `.wc-team-payroll-myaccount-salary` - Salary details section
- `.wc-team-payroll-myaccount-earnings` - Earnings section
- `.wc-team-payroll-myaccount-orders` - Orders section
- `.wc-team-payroll-myaccount-reports` - Reports section
- `.wc-team-payroll-filters` - Filter styling
- `.wc-team-payroll-orders-table` - Orders table styling
- `.report-card` - Report card styling

**Inline JavaScript (Lines ~400-700)**
**Common JS Functions Used:**
- jQuery AJAX calls
- Date formatting

**Page-Specific JS Functions:**
- `loadOrdersData()` - Load orders with filters
- `showOrderDetails()` - Show order details modal
- `closeOrderDetails()` - Close order details modal
- Filter state management: `roleFilter`, `dateFrom`, `dateTo`, `statusFilter`, `sortBy`

**AJAX Actions:**
- `wc_tp_get_orders_data` - Fetch orders data
- `wc_tp_get_order_details` - Fetch order details

**Modal Implementation:**
- Order details modal with inline styling

---

### FILE 7: includes/frontend/class-shortcodes.php

#### Inline CSS
**Location:** Minimal - mostly HTML output

**Page-Specific Styles:**
- `.wc-team-payroll-earnings` - Earnings shortcode wrapper
- `.earnings-summary` - Earnings summary container
- `.earnings-card` - Individual earnings card
- `.wc-team-payroll-orders` - Orders shortcode wrapper
- `.wc-team-payroll-orders-table` - Orders table styling

**Inline JavaScript (Lines ~100-200)**
**Page-Specific JS Functions:**
- `copyToClipboard()` - Copy shortcode to clipboard
- `$('#orders-type').on('change')` - Update shortcode on type change

**AJAX Actions:**
- None (static shortcode builder)

---

## 3. CONSOLIDATION OPPORTUNITIES

### High Priority (Duplicate Code)

#### CSS Variables
**Current State:** `:root` variables defined in 5 files (dashboard, payroll, employees, settings, myaccount)
**Recommendation:** Remove from all files, use only from common.css
**Savings:** ~50 lines per file × 5 files = 250 lines

#### Common Table Patterns
**Current State:** `.wc-tp-data-table`, `.wc-tp-sortable-header`, `.wc-tp-badge` defined inline
**Recommendation:** Already in common.css - remove inline definitions
**Savings:** ~100 lines per file × 4 files = 400 lines

#### Pagination Styling
**Current State:** `.wc-tp-pagination` defined inline in multiple files
**Recommendation:** Already in common.css - remove inline definitions
**Savings:** ~30 lines per file × 3 files = 90 lines

#### Filter Styling
**Current State:** `.wc-tp-search-filter`, `.wc-tp-date-filter` defined inline
**Recommendation:** Already in common.css - remove inline definitions
**Savings:** ~50 lines per file × 4 files = 200 lines

### Medium Priority (Reusable JS Functions)

#### Table Rendering
**Current State:** `renderPayrollTable()`, `renderEmployeesTable()`, `renderLatestEmployees()` - similar logic
**Recommendation:** Create generic `renderTable()` function in common.js
**Savings:** ~50 lines per function × 3 functions = 150 lines

#### Sort Handlers
**Current State:** `attachSortHandlers()`, `attachPayrollSortHandlers()`, `attachEmployeesSortHandlers()` - nearly identical
**Recommendation:** Create generic `attachTableSortHandlers()` in common.js
**Savings:** ~80 lines per function × 3 functions = 240 lines

#### Pagination Rendering
**Current State:** `renderPagination()` implemented in multiple files
**Recommendation:** Already in common.js - use existing function
**Savings:** ~40 lines per file × 3 files = 120 lines

#### Currency Formatting
**Current State:** `formatCurrency()` defined inline in dashboard
**Recommendation:** Already in common.js - use existing function
**Savings:** ~10 lines

### Low Priority (Page-Specific Code)

#### Dashboard Stats Cards
**Status:** Unique to dashboard - keep as-is
**Lines:** ~100 CSS + ~150 JS

#### Employee Detail Tabs
**Status:** Unique to employee detail - keep as-is
**Lines:** ~50 CSS + ~100 JS

#### Settings Role Management
**Status:** Unique to settings - keep as-is
**Lines:** ~80 CSS + ~100 JS

#### Shortcode Builder
**Status:** Unique to shortcodes - keep as-is
**Lines:** ~30 CSS + ~50 JS

---

## 4. DETAILED MAPPING TABLE

| File | CSS Lines | JS Lines | Common CSS | Common JS | Page-Specific CSS | Page-Specific JS | AJAX Actions |
|------|-----------|----------|-----------|-----------|------------------|------------------|--------------|
| Dashboard | ~530 | ~467 | 60% | 40% | Stats cards, status badges | Data loading, rendering | 1 |
| Payroll | ~300 | ~250 | 80% | 50% | Salary filter | Data loading, pagination | 1 |
| Employees | ~300 | ~300 | 80% | 50% | Salary filter | Data loading, sorting | 7 |
| Employee Detail | 0 | 0 | 100% (enqueued) | 100% (enqueued) | None | None | 0 |
| Settings | ~200 | ~200 | 0% | 0% | Role management UI | Role CRUD | 0 |
| MyAccount | ~100 | ~300 | 20% | 30% | Earnings/orders cards | Filter/sort logic | 2 |
| Shortcodes | ~50 | ~100 | 0% | 0% | Shortcode output | Clipboard copy | 0 |

---

## 5. COMMON UTILITIES ALREADY IN common.js

```javascript
✓ getDateRange(preset)           - Date range calculation
✓ formatCurrency(value, ...)     - Currency formatting
✓ handleDatePresetChange(...)    - Date preset handler
✓ renderPagination(...)          - Pagination rendering
✓ attachSortHandlers(...)        - Table sorting
✓ initializeEmployeeDetailPage() - Employee detail initialization
```

---

## 6. COMMON STYLES ALREADY IN common.css

```css
✓ :root variables               - Design system
✓ .wc-tp-page-wrapper          - Page container
✓ .wc-tp-search-filter         - Search input
✓ .wc-tp-unified-filter        - Multi-field filters
✓ .wc-tp-date-filter           - Date range inputs
✓ .wc-tp-table-section         - Table card wrapper
✓ .wc-tp-data-table            - Table styling
✓ .wc-tp-sortable-header       - Sortable headers
✓ .wc-tp-badge                 - Badges
✓ .wc-tp-pagination            - Pagination
✓ .wc-tp-empty-state           - Empty states
✓ .button-primary              - Primary buttons
✓ .wc-tp-profile-header        - Profile section
✓ .wc-tp-stats-cards           - Stats grid
✓ .wc-tp-tabs-container        - Tab interface
✓ .wc-tp-orders-filters        - Order filters
✓ Responsive media queries     - Mobile/tablet
```

---

## 7. RECOMMENDATIONS

### Immediate Actions
1. **Remove duplicate `:root` variables** from dashboard, payroll, employees, myaccount
2. **Remove duplicate table styles** from all files - use common.css
3. **Remove duplicate pagination styles** from payroll, employees, myaccount
4. **Remove duplicate filter styles** from all files

### Short-term Refactoring
1. Create generic `renderTable()` function in common.js
2. Create generic `attachTableSortHandlers()` in common.js
3. Move all filter logic to common.js utilities
4. Consolidate AJAX data loading patterns

### Long-term Architecture
1. Consider moving page-specific JS to separate files (dashboard.js, payroll.js, etc.)
2. Create CSS modules for page-specific styles
3. Implement component-based architecture for reusable UI patterns
4. Consider using a CSS preprocessor (SCSS) for better organization

---

## 8. CODE QUALITY METRICS

**Total Inline CSS:** ~1,480 lines
**Total Inline JS:** ~1,700 lines
**Duplicated CSS:** ~400 lines (27%)
**Duplicated JS:** ~300 lines (18%)
**Consolidation Potential:** ~700 lines (22% reduction)

**Files with Proper Asset Enqueuing:** 1/7 (14%)
**Files with Inline Styles:** 6/7 (86%)
**Files with Inline Scripts:** 6/7 (86%)

---

## 9. MIGRATION CHECKLIST

- [ ] Extract all page-specific CSS to separate files
- [ ] Extract all page-specific JS to separate files
- [ ] Remove duplicate `:root` variables
- [ ] Remove duplicate common styles
- [ ] Update all files to use `wp_enqueue_style()` and `wp_enqueue_script()`
- [ ] Create generic utility functions in common.js
- [ ] Test all functionality after consolidation
- [ ] Update documentation
- [ ] Performance testing (before/after)

---

## 10. FILE STRUCTURE RECOMMENDATION

```
assets/
├── css/
│   ├── common.css              (existing - design system)
│   ├── dashboard.css           (new - dashboard specific)
│   ├── payroll.css             (new - payroll specific)
│   ├── employees.css           (new - employees specific)
│   ├── employee-detail.css     (new - employee detail specific)
│   ├── settings.css            (new - settings specific)
│   ├── myaccount.css           (new - myaccount specific)
│   └── shortcodes.css          (new - shortcodes specific)
├── js/
│   ├── common.js               (existing - utilities)
│   ├── dashboard.js            (existing - dashboard logic)
│   ├── payroll.js              (new - payroll logic)
│   ├── employees.js            (existing - employees logic)
│   ├── employee-detail.js      (existing - employee detail logic)
│   ├── settings.js             (new - settings logic)
│   ├── myaccount.js            (new - myaccount logic)
│   └── shortcodes.js           (new - shortcodes logic)
```

---

**Document Generated:** 2024
**Version:** 5.4.10
**Status:** Analysis Complete
