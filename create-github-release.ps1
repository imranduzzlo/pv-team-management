# Create GitHub Release for v5.5.0
# Usage: .\create-github-release.ps1 -GitHubToken "your_token_here"

param(
    [Parameter(Mandatory=$true)]
    [string]$GitHubToken,
    
    [string]$Repository = "imranduzzlo/pv-team-payroll",
    [string]$Version = "5.5.0"
)

$releaseBody = @"
## Major Refactoring: Code Organization & Reusability

### Folder Structure Reorganization
- Created `includes/backend/` for admin/backend pages
- Created `includes/frontend/` for customer-facing pages
- Moved backend classes: Dashboard, Payroll Page, Employee Management, Employee Detail, Settings
- Moved frontend classes: My Account, Shortcodes
- Updated main plugin file to load from new folder structure

### Unified Design System
- Created `assets/css/common.css` (550+ lines) with reusable components
- All pages now use consistent design system
- Reduces CSS duplication across pages

### Shared JavaScript Utilities
- Created `assets/js/common.js` (200+ lines) with shared utility functions
- Created `assets/js/employees.js` for employee management page
- Created `assets/js/dashboard.js` for dashboard-specific logic
- Eliminates duplicate JavaScript across pages

### Page Refactoring
- Dashboard: Removed 1000+ lines of inline CSS/JS
- Employee Management: Removed 663 lines of inline CSS/JS
- Employee Detail: Removed 712 lines of inline CSS/JS
- Settings: Removed 98 lines of inline CSS/JS
- My Account: Removed 60 lines of inline JS
- Shortcodes: Removed 31 lines of inline JS

### Code Quality Improvements
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
"@

Write-Host "Creating GitHub Release for v$Version..." -ForegroundColor Cyan

try {
    $releaseData = @{
        tag_name = "v$Version"
        name = "v$Version - Major Refactoring: Code Organization & Reusability"
        body = $releaseBody
        draft = $false
        prerelease = $false
    } | ConvertTo-Json -Depth 10
    
    $headers = @{
        Authorization = "token $GitHubToken"
        Accept = "application/vnd.github.v3+json"
    }
    
    $response = Invoke-RestMethod -Uri "https://api.github.com/repos/$Repository/releases" `
        -Method Post `
        -Headers $headers `
        -Body $releaseData `
        -ContentType "application/json"
    
    Write-Host "✓ GitHub Release created successfully" -ForegroundColor Green
    Write-Host "Release URL: $($response.html_url)" -ForegroundColor Green
    Write-Host "Release page: https://github.com/$Repository/releases/tag/v$Version" -ForegroundColor Cyan
} catch {
    Write-Host "✗ Failed to create GitHub Release: $_" -ForegroundColor Red
    exit 1
}
