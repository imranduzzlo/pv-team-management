# GitHub Release Creator Script
# Usage: .\create-releases.ps1 -Token "your_github_token"

param(
    [Parameter(Mandatory=$true)]
    [string]$Token
)

$owner = "imranduzzlo"
$repo = "pv-team-payroll"
$headers = @{
    "Authorization" = "token $Token"
    "Accept" = "application/vnd.github.v3+json"
}

# Release data
$releases = @(
    @{
        tag_name = "v5.3.0"
        name = "v5.3.0 - Redesigned Dashboard"
        body = @"
## v5.3.0 - Redesigned Dashboard

### New Features
- Date Range Filter with AJAX support
- Latest Employees table
- Top Earners table
- Recent Payments table
- Professional responsive layout

### Improvements
- Removed dashboard duplication
- Enhanced styling with status badges
- Better visual hierarchy
- Mobile-friendly design

### Technical Changes
- Added `get_payroll_by_date_range()` method to Payroll Engine
- New helper methods for data retrieval
- Improved CSS with responsive grid layouts
"@
    },
    @{
        tag_name = "v5.3.1"
        name = "v5.3.1 - Dashboard Fixes"
        body = @"
## v5.3.1 - Dashboard Fixes

### Bug Fixes
- Fixed dashboard content duplication
- Added empty state icons instead of notices
- Show all employees regardless of commission status

### Improvements
- Cleaner UI with professional empty states
- Better data presentation
"@
    },
    @{
        tag_name = "v5.3.2"
        name = "v5.3.2 - Critical Fixes"
        body = @"
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
"@
    }
)

# Create releases
foreach ($release in $releases) {
    Write-Host "Creating release: $($release.tag_name)..." -ForegroundColor Cyan
    
    $body = @{
        tag_name = $release.tag_name
        name = $release.name
        body = $release.body
        draft = $false
        prerelease = $false
    } | ConvertTo-Json
    
    try {
        $response = Invoke-WebRequest -Uri "https://api.github.com/repos/$owner/$repo/releases" `
            -Method POST `
            -Headers $headers `
            -Body $body `
            -ContentType "application/json"
        
        $result = $response.Content | ConvertFrom-Json
        Write-Host "✅ Created: $($result.html_url)" -ForegroundColor Green
    }
    catch {
        $errorResponse = $_.Exception.Response.Content | ConvertFrom-Json
        if ($errorResponse.errors[0].code -eq "already_exists") {
            Write-Host "⚠️  Release already exists: $($release.tag_name)" -ForegroundColor Yellow
        } else {
            Write-Host "❌ Error: $($errorResponse.message)" -ForegroundColor Red
        }
    }
}

Write-Host ""
Write-Host "Done! Check your releases at: https://github.com/$owner/$repo/releases" -ForegroundColor Green
