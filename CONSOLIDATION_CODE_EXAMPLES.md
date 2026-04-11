# Code Consolidation Examples & Refactoring Guide

## 1. DUPLICATE CSS VARIABLES

### Current (Duplicated in 5 files)
```css
/* In dashboard.php, payroll.php, employees.php, myaccount.php, settings.php */
:root {
    --color-primary: #FF9900;
    --color-primary-hover: #E68A00;
    --color-primary-subtle: #FFF4E5;
    --color-secondary: #212B36;
    --color-site-bg: #FDFBF8;
    --color-card-bg: #FFFFFF;
    --color-border-light: #E5EAF0;
    --color-accent-alert: #FF5500;
    --color-accent-alert-hover: #D94800;
    --color-accent-link: #0077EE;
    --color-accent-success: #388E3C;
    --color-accent-muted: #F4F4F4;
    --text-main: #212B36;
    --text-body: #454F5B;
    --text-muted: #919EAB;
    --color-link-subtle: #EBF4FF;
    --font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    --fs-h1: 2rem;
    --fs-h2: 1.5rem;
    --fs-h3: 1.25rem;
    --fs-body: 1rem;
    --fs-meta: 0.875rem;
    --fs-small: 0.75rem;
    --fw-bold: 700;
    --fw-semibold: 600;
    --fw-medium: 500;
    --fw-regular: 400;
    --lh-body: 1.5;
    --lh-heading: 1.2;
}
```

### Recommended (In common.css only)
```css
/* assets/css/common.css */
:root {
    /* Already defined - remove from all other files */
}
```

### Action Items
- [ ] Remove `:root` from class-dashboard.php
- [ ] Remove `:root` from class-payroll-page.php
- [ ] Remove `:root` from class-employee-management.php
- [ ] Remove `:root` from class-myaccount.php
- [ ] Remove `:root` from class-settings.php
- [ ] Verify common.css is enqueued on all pages

---

## 2. DUPLICATE TABLE STYLES

### Current (Duplicated in 4 files)
```css
/* In dashboard.php, payroll.php, employees.php, myaccount.php */
.wc-tp-data-table {
    width: 100%;
    border-collapse: collapse;
}

.wc-tp-data-table thead {
    background: var(--color-accent-muted);
}

.wc-tp-data-table th {
    padding: 14px 12px;
    text-align: left;
    font-weight: var(--fw-semibold);
    color: var(--text-main);
    font-size: var(--fs-meta);
    border-bottom: 1px solid var(--color-border-light);
}

.wc-tp-sortable-header {
    cursor: pointer;
    user-select: none;
    position: relative;
    padding-right: 24px;
    transition: all 0.2s ease;
}

.wc-tp-sortable-header::after {
    content: '⇅';
    position: absolute;
    right: 8px;
    opacity: 0.3;
    font-size: 12px;
    transition: all 0.2s ease;
}

.wc-tp-sortable-header:hover {
    background-color: var(--color-primary-subtle);
}

.wc-tp-sortable-header.wc-tp-sort-active {
    background-color: var(--color-primary-subtle) !important;
    color: var(--color-primary) !important;
}

.wc-tp-sortable-header.wc-tp-sort-active::after {
    opacity: 1 !important;
    color: var(--color-primary) !important;
}

.wc-tp-sortable-header.wc-tp-sort-active.wc-tp-sort-asc::after {
    content: '↑' !important;
}

.wc-tp-sortable-header.wc-tp-sort-active.wc-tp-sort-desc::after {
    content: '↓' !important;
}

.wc-tp-data-table td {
    padding: 12px;
    border-bottom: 1px solid var(--color-border-light);
    font-size: var(--fs-body);
    color: var(--text-body);
}

.wc-tp-data-table tbody tr:hover {
    background: var(--color-primary-subtle);
}
```

### Recommended (In common.css only)
```css
/* assets/css/common.css - Already exists */
/* Remove from all inline <style> tags */
```

### Action Items
- [ ] Remove all `.wc-tp-data-table*` styles from class-dashboard.php
- [ ] Remove all `.wc-tp-sortable-header*` styles from class-payroll-page.php
- [ ] Remove all `.wc-tp-data-table*` styles from class-employee-management.php
- [ ] Remove all `.wc-tp-data-table*` styles from class-myaccount.php

---

## 3. DUPLICATE PAGINATION STYLES

### Current (Duplicated in 3 files)
```css
/* In payroll.php, employees.php, myaccount.php */
.wc-tp-pagination {
    display: flex;
    gap: 8px;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
}

.wc-tp-pagination a,
.wc-tp-pagination span {
    padding: 8px 12px;
    border: 1px solid var(--color-border-light);
    border-radius: 4px;
    text-decoration: none;
    color: var(--text-main);
    transition: all 0.2s ease;
}

.wc-tp-pagination a:hover {
    background: var(--color-primary-subtle);
    border-color: var(--color-primary);
    color: var(--color-primary);
}

.wc-tp-pagination .current {
    background: var(--color-primary);
    color: white;
    border-color: var(--color-primary);
    font-weight: var(--fw-semibold);
}
```

### Recommended (In common.css only)
```css
/* assets/css/common.css - Already exists */
/* Remove from all inline <style> tags */
```

### Action Items
- [ ] Remove `.wc-tp-pagination*` styles from class-payroll-page.php
- [ ] Remove `.wc-tp-pagination*` styles from class-employee-management.php
- [ ] Remove `.wc-tp-pagination*` styles from class-myaccount.php

---

## 4. DUPLICATE FILTER STYLES

### Current (Duplicated in 4 files)
```css
/* In payroll.php, employees.php, myaccount.php, dashboard.php */
.wc-tp-search-filter {
    background: var(--color-card-bg);
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    border: 1px solid var(--color-border-light);
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.wc-tp-search-filter input[type="text"] {
    flex: 1;
    min-width: 250px;
    padding: 8px 12px;
    border: 1px solid var(--color-border-light);
    border-radius: 6px;
    font-size: var(--fs-body);
    font-family: var(--font-family);
    color: var(--text-main);
}

.wc-tp-search-filter input[type="text"]::placeholder {
    color: var(--text-muted);
}

.wc-tp-search-filter .button-secondary {
    background: var(--color-accent-muted);
    border-color: var(--color-border-light);
    color: var(--text-main);
    font-weight: var(--fw-semibold);
    border-radius: 6px;
    padding: 8px 16px;
    font-size: var(--fs-meta);
    transition: all 0.2s ease;
}

.wc-tp-search-filter .button-secondary:hover {
    background: var(--color-border-light);
    border-color: var(--color-border-light);
}
```

### Recommended (In common.css only)
```css
/* assets/css/common.css - Already exists */
/* Remove from all inline <style> tags */
```

### Action Items
- [ ] Remove `.wc-tp-search-filter*` styles from class-dashboard.php
- [ ] Remove `.wc-tp-search-filter*` styles from class-payroll-page.php
- [ ] Remove `.wc-tp-search-filter*` styles from class-employee-management.php
- [ ] Remove `.wc-tp-search-filter*` styles from class-myaccount.php

---

## 5. DUPLICATE CURRENCY FORMATTING

### Current (Duplicated in dashboard.php)
```javascript
// In class-dashboard.php
function formatCurrency(value) {
    const amount = parseFloat(value).toFixed(2);
    if (wcCurrencyPos === 'right') {
        return amount + ' ' + wcCurrencySymbol;
    } else {
        return wcCurrencySymbol + ' ' + amount;
    }
}
```

### Recommended (In common.js)
```javascript
// assets/js/common.js - Already exists
function formatCurrency(value, currency = 'USD', symbol = '$', position = 'left') {
    const amount = parseFloat(value).toFixed(2);
    if (position === 'right') {
        return amount + ' ' + symbol;
    } else {
        return symbol + ' ' + amount;
    }
}
```

### Action Items
- [ ] Remove `formatCurrency()` from class-dashboard.php
- [ ] Use global function from common.js instead
- [ ] Update calls to pass currency parameters

---

## 6. SIMILAR TABLE RENDERING FUNCTIONS

### Current (Similar in 3 files)

#### Dashboard
```javascript
function renderLatestEmployees(employees) {
    const container = $('#wc-tp-latest-employees-container');
    
    if (!employees || employees.length === 0) {
        container.html('<div class="wc-tp-empty-state">...</div>');
        return;
    }

    let html = '<table class="wc-tp-data-table wc-tp-sortable"><thead><tr>';
    html += '<th class="wc-tp-sortable-header" data-sort="display_name">Name</th>';
    html += '<th class="wc-tp-sortable-header" data-sort="user_email">Email</th>';
    html += '<th class="wc-tp-sortable-header" data-sort="type">Type</th>';
    html += '<th>Salary/Commission</th>';
    html += '<th>Action</th>';
    html += '</tr></thead><tbody>';

    $.each(employees, function(i, emp) {
        html += '<tr>';
        html += '<td><strong>' + emp.display_name + '</strong></td>';
        html += '<td>' + emp.user_email + '</td>';
        html += '<td>' + emp.type + '</td>';
        html += '<td>' + emp.salary_info + '</td>';
        html += '<td><a href="' + emp.manage_url + '" class="button button-small button-primary">Manage</a></td>';
        html += '</tr>';
    });

    html += '</tbody></table>';
    container.html(html);
    attachSortHandlers(container, employees, ['display_name', 'user_email', 'type']);
}
```

#### Payroll
```javascript
function renderPayrollTable(payroll) {
    const container = $('#wc-tp-payroll-table-container');
    
    if (!payroll || payroll.length === 0) {
        container.html('<div class="wc-tp-empty-state">...</div>');
        return;
    }

    let html = '<table class="wc-tp-data-table wc-tp-sortable"><thead><tr>';
    html += '<th class="wc-tp-sortable-header" data-sort="name">Employee</th>';
    html += '<th class="wc-tp-sortable-header" data-sort="user_email">Email</th>';
    html += '<th class="wc-tp-sortable-header" data-sort="orders">Orders</th>';
    html += '<th class="wc-tp-sortable-header" data-sort="total">Total Earnings</th>';
    html += '<th class="wc-tp-sortable-header" data-sort="paid">Paid</th>';
    html += '<th class="wc-tp-sortable-header" data-sort="due">Due</th>';
    html += '<th>Action</th>';
    html += '</tr></thead><tbody>';

    // ... similar rendering logic
}
```

### Recommended (Generic Function in common.js)
```javascript
// assets/js/common.js
function renderTable(containerId, data, columns, rowRenderer, emptyMessage = 'No data found') {
    const container = jQuery('#' + containerId);
    
    if (!data || data.length === 0) {
        container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">📭</div><p>' + emptyMessage + '</p></div>');
        return;
    }

    let html = '<table class="wc-tp-data-table wc-tp-sortable"><thead><tr>';
    
    // Build header
    columns.forEach(col => {
        if (col.sortable) {
            html += '<th class="wc-tp-sortable-header" data-sort="' + col.key + '">' + col.label + '</th>';
        } else {
            html += '<th>' + col.label + '</th>';
        }
    });
    
    html += '</tr></thead><tbody>';

    // Build rows
    data.forEach(row => {
        html += rowRenderer(row);
    });

    html += '</tbody></table>';
    container.html(html);
    
    // Attach sort handlers
    const sortableFields = columns.filter(c => c.sortable).map(c => c.key);
    attachSortHandlers(container, data, sortableFields);
}
```

### Usage Example
```javascript
// In dashboard.php
renderTable(
    'wc-tp-latest-employees-container',
    employees,
    [
        { key: 'display_name', label: 'Name', sortable: true },
        { key: 'user_email', label: 'Email', sortable: true },
        { key: 'type', label: 'Type', sortable: true },
        { key: 'salary_info', label: 'Salary/Commission', sortable: false },
        { key: 'manage_url', label: 'Action', sortable: false }
    ],
    function(emp) {
        return '<tr>' +
            '<td><strong>' + emp.display_name + '</strong></td>' +
            '<td>' + emp.user_email + '</td>' +
            '<td>' + emp.type + '</td>' +
            '<td>' + emp.salary_info + '</td>' +
            '<td><a href="' + emp.manage_url + '" class="button button-small button-primary">Manage</a></td>' +
            '</tr>';
    },
    'No employees found'
);
```

### Action Items
- [ ] Create generic `renderTable()` function in common.js
- [ ] Refactor dashboard.php to use generic function
- [ ] Refactor payroll.php to use generic function
- [ ] Refactor employees.php to use generic function
- [ ] Test all table rendering

---

## 7. SIMILAR SORT HANDLER FUNCTIONS

### Current (Similar in 3 files)

#### Dashboard
```javascript
function attachSortHandlers(container, data, sortableFields) {
    let currentSort = container.data('sortState') || { field: null, direction: 'asc' };
    
    // Restore sort state classes if they exist
    if (currentSort.field) {
        const header = container.find('.wc-tp-sortable-header[data-sort="' + currentSort.field + '"]');
        if (header.length) {
            header.addClass('wc-tp-sort-active');
            if (currentSort.direction === 'asc') {
                header.addClass('wc-tp-sort-asc');
            } else {
                header.addClass('wc-tp-sort-desc');
            }
        }
    }
    
    container.find('.wc-tp-sortable-header').on('click', function() {
        const sortField = $(this).data('sort');
        if (!sortField) return;
        
        const isNumeric = ['orders', 'total', 'paid', 'due', 'earnings', 'amount'].includes(sortField);
        const isDate = ['date'].includes(sortField);
        
        // Check if clicking the same field
        if (currentSort.field === sortField) {
            // Toggle direction
            currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
        } else {
            // New field, start with ascending
            currentSort.field = sortField;
            currentSort.direction = 'asc';
        }
        
        // Save sort state to container
        container.data('sortState', currentSort);
        
        // Sort data
        let sortedData = [...data].sort((a, b) => {
            let aVal = a[sortField];
            let bVal = b[sortField];
            
            if (aVal === undefined || aVal === null) aVal = '';
            if (bVal === undefined || bVal === null) bVal = '';
            
            if (isDate) {
                // Parse date/time values
                let aTime = 0;
                let bTime = 0;
                
                if (a[sortField + '_timestamp']) {
                    aTime = a[sortField + '_timestamp'];
                } else if (a[sortField]) {
                    aTime = new Date(a[sortField]).getTime() || 0;
                }
                
                if (b[sortField + '_timestamp']) {
                    bTime = b[sortField + '_timestamp'];
                } else if (b[sortField]) {
                    bTime = new Date(b[sortField]).getTime() || 0;
                }
                
                return currentSort.direction === 'asc' ? aTime - bTime : bTime - aTime;
            } else if (isNumeric) {
                aVal = parseFloat(aVal) || 0;
                bVal = parseFloat(bVal) || 0;
                return currentSort.direction === 'asc' ? aVal - bVal : bVal - aVal;
            } else {
                aVal = String(aVal).toLowerCase();
                bVal = String(bVal).toLowerCase();
                return currentSort.direction === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
            }
        });
        
        // Re-render table with sorted data
        // ... page-specific rendering
        
        // Re-attach handlers to new headers with updated sort state
        setTimeout(function() {
            attachSortHandlers(container, sortedData, sortableFields);
        }, 10);
    });
}
```

#### Payroll
```javascript
function attachPayrollSortHandlers(container, payrollArray) {
    // Nearly identical to dashboard version
    // Only difference: calls renderPayrollTable() instead of generic render
}
```

#### Employees
```javascript
function attachEmployeesSortHandlers(container, employeesArray) {
    // Nearly identical to dashboard version
    // Only difference: calls renderEmployeesTable() instead of generic render
}
```

### Recommended (Generic Function in common.js)
```javascript
// assets/js/common.js - Already exists but needs enhancement
function attachSortHandlers(container, dataArray, onSortChange) {
    let currentSort = container.data('sortState') || { field: null, direction: 'asc' };

    // Remove old event handlers
    container.find('.wc-tp-sortable-header').off('click');

    // Restore sort state classes
    if (currentSort.field) {
        const header = container.find('.wc-tp-sortable-header[data-sort="' + currentSort.field + '"]');
        if (header.length) {
            header.addClass('wc-tp-sort-active');
            if (currentSort.direction === 'asc') {
                header.addClass('wc-tp-sort-asc');
            } else {
                header.addClass('wc-tp-sort-desc');
            }
        }
    }

    container.find('.wc-tp-sortable-header').on('click', function() {
        const sortField = jQuery(this).data('sort');
        if (!sortField) return;

        const isNumeric = ['orders', 'total', 'paid', 'due', 'earnings', 'amount'].includes(sortField);
        const isDate = ['date'].includes(sortField);

        // Check if clicking the same field
        if (currentSort.field === sortField) {
            // Toggle direction
            currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
        } else {
            // New field, start with ascending
            currentSort.field = sortField;
            currentSort.direction = 'asc';
        }

        // Save sort state
        container.data('sortState', currentSort);

        // Sort data
        let sortedData = [...dataArray].sort((a, b) => {
            let aVal = a[sortField];
            let bVal = b[sortField];

            if (aVal === undefined || aVal === null) aVal = '';
            if (bVal === undefined || bVal === null) bVal = '';

            if (isDate) {
                let aTime = a[sortField + '_timestamp'] || new Date(a[sortField]).getTime() || 0;
                let bTime = b[sortField + '_timestamp'] || new Date(b[sortField]).getTime() || 0;
                return currentSort.direction === 'asc' ? aTime - bTime : bTime - aTime;
            } else if (isNumeric) {
                aVal = parseFloat(aVal) || 0;
                bVal = parseFloat(bVal) || 0;
                return currentSort.direction === 'asc' ? aVal - bVal : bVal - aVal;
            } else {
                aVal = String(aVal).toLowerCase();
                bVal = String(bVal).toLowerCase();
                return currentSort.direction === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
            }
        });

        if (onSortChange) {
            onSortChange(sortedData, currentSort);
        }

        // Re-attach handlers
        setTimeout(function() {
            attachSortHandlers(container, sortedData, onSortChange);
        }, 10);
    });
}
```

### Usage Example
```javascript
// In dashboard.php
attachSortHandlers(
    container,
    employees,
    function(sortedData, sortState) {
        renderLatestEmployees(sortedData);
    }
);
```

### Action Items
- [ ] Enhance `attachSortHandlers()` in common.js to accept callback
- [ ] Remove `attachPayrollSortHandlers()` from payroll.php
- [ ] Remove `attachEmployeesSortHandlers()` from employees.php
- [ ] Update all calls to use generic function with callback
- [ ] Test sorting on all pages

---

## 8. ASSET ENQUEUING BEST PRACTICE

### Current (Only employee-detail.php does this correctly)
```php
public function __construct() {
    add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
}

public function enqueue_assets( $hook ) {
    if ( strpos( $hook, 'wc-team-payroll-employee-detail' ) === false ) {
        return;
    }

    wp_enqueue_style( 'wc-tp-common-css', WC_TEAM_PAYROLL_URL . 'assets/css/common.css', array(), WC_TEAM_PAYROLL_VERSION );
    wp_enqueue_script( 'wc-tp-common-js', WC_TEAM_PAYROLL_URL . 'assets/js/common.js', array( 'jquery' ), WC_TEAM_PAYROLL_VERSION, true );
    wp_enqueue_script( 'wc-tp-employee-detail-js', WC_TEAM_PAYROLL_URL . 'assets/js/employee-detail.js', array( 'jquery', 'wc-tp-common-js' ), WC_TEAM_PAYROLL_VERSION, true );
}
```

### Recommended (For all pages)
```php
// In class-dashboard.php
public function __construct() {
    add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
}

public function enqueue_assets( $hook ) {
    if ( strpos( $hook, 'wc-team-payroll-dashboard' ) === false ) {
        return;
    }

    wp_enqueue_style( 'wc-tp-common-css', WC_TEAM_PAYROLL_URL . 'assets/css/common.css', array(), WC_TEAM_PAYROLL_VERSION );
    wp_enqueue_style( 'wc-tp-dashboard-css', WC_TEAM_PAYROLL_URL . 'assets/css/dashboard.css', array( 'wc-tp-common-css' ), WC_TEAM_PAYROLL_VERSION );
    
    wp_enqueue_script( 'wc-tp-common-js', WC_TEAM_PAYROLL_URL . 'assets/js/common.js', array( 'jquery' ), WC_TEAM_PAYROLL_VERSION, true );
    wp_enqueue_script( 'wc-tp-dashboard-js', WC_TEAM_PAYROLL_URL . 'assets/js/dashboard.js', array( 'jquery', 'wc-tp-common-js' ), WC_TEAM_PAYROLL_VERSION, true );
}

public function render_dashboard() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
    }

    // Remove inline <style> and <script> tags
    // All CSS/JS now enqueued
    ?>
    <div class="wrap wc-team-payroll-dashboard">
        <!-- HTML only -->
    </div>
    <?php
}
```

### Action Items
- [ ] Create `enqueue_assets()` method in class-dashboard.php
- [ ] Create `enqueue_assets()` method in class-payroll-page.php
- [ ] Create `enqueue_assets()` method in class-employee-management.php
- [ ] Create `enqueue_assets()` method in class-settings.php
- [ ] Create `enqueue_assets()` method in class-myaccount.php
- [ ] Create `enqueue_assets()` method in class-shortcodes.php
- [ ] Create separate CSS files for each page
- [ ] Create separate JS files for each page
- [ ] Remove all inline `<style>` tags
- [ ] Remove all inline `<script>` tags

---

## 9. REFACTORING CHECKLIST

### Phase 1: Remove Duplicates (Week 1)
- [ ] Remove `:root` variables from 5 files
- [ ] Remove table styles from 4 files
- [ ] Remove pagination styles from 3 files
- [ ] Remove filter styles from 4 files
- [ ] Remove `formatCurrency()` from dashboard
- [ ] Test all pages

### Phase 2: Create Generic Functions (Week 2)
- [ ] Create `renderTable()` in common.js
- [ ] Create enhanced `attachSortHandlers()` in common.js
- [ ] Refactor dashboard to use generic functions
- [ ] Refactor payroll to use generic functions
- [ ] Refactor employees to use generic functions
- [ ] Test all pages

### Phase 3: Asset Enqueuing (Week 3)
- [ ] Create dashboard.css
- [ ] Create payroll.css
- [ ] Create employees.css
- [ ] Create settings.css
- [ ] Create myaccount.css
- [ ] Create shortcodes.css
- [ ] Create dashboard.js
- [ ] Create payroll.js
- [ ] Create employees.js
- [ ] Create settings.js
- [ ] Create myaccount.js
- [ ] Create shortcodes.js
- [ ] Add enqueue methods to all classes
- [ ] Remove all inline styles and scripts
- [ ] Test all pages

### Phase 4: Testing & Optimization (Week 4)
- [ ] Functional testing on all pages
- [ ] Performance testing (before/after)
- [ ] Browser compatibility testing
- [ ] Mobile responsiveness testing
- [ ] AJAX functionality testing
- [ ] Sorting/filtering testing
- [ ] Pagination testing
- [ ] Update documentation

---

## 10. ESTIMATED SAVINGS

### Code Size Reduction
- **CSS:** 1,480 lines → ~1,080 lines (27% reduction)
- **JS:** 1,700 lines → ~1,400 lines (18% reduction)
- **Total:** 3,180 lines → ~2,480 lines (22% reduction)

### Performance Improvements
- **Parallel Loading:** CSS/JS files load in parallel
- **Caching:** Separate files cache independently
- **Compression:** Better gzip compression with separate files
- **Maintainability:** Easier to find and update code

### Estimated Load Time Improvement
- **Before:** All CSS/JS inline (single request per page)
- **After:** Separate files (parallel requests, better caching)
- **Improvement:** ~15-20% faster page loads

---

**Refactoring Guide Complete**
