#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Create GitHub Releases for all versions
.DESCRIPTION
    Creates GitHub releases for v5.3.0, v5.3.1, v5.3.2, and v5.3.3
.PARAMETER Token
    GitHub Personal Access Token (required)
.EXAMPLE
    .\create-all-releases.ps1 -Token "ghp_xxxxx"
#>

param(
    [Parameter(Mandatory=$true, HelpMessage="GitHub Personal Access Token")]
    [string]$Token
)

$owner = "imranduzzlo"
$repo = "pv-team-payroll"

$headers = @{
    "Authorization" = "token $Token"
    "Accept" = "application/vnd.github.v3+json"
    "User-Agent" = "PowerShell"
}

# Release configurations
$releases = @(
    @{
        tag = "v5.3.0"
        name = "v5.3.0 - Redesigned Dashboard"
        notes = @"
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
- New helper methods for data retrieval
- Improved CSS with responsive grid layouts
"@
    },
    @{
        tag = "v5.3.1"
        name = "v5.3.1 - Dashboard Fixes"
        notes = @"
## v5.3.1 - Dashboard Fixes

### Bug Fixes
- Fixed dashboard content duplication
- Added empty state icons instead of notices
- Show all employees regardless of commission status

### Improvements
- Cleaner UI with professional empty states
- Better data presentation
- Employees without commission now visible in tables
"@
    },
    @{
        tag = "v5.3.2"
        name = "v5.3.2 - Critical Fixes"
        notes = @"
## v5.3.2 - Critical Fixes

### Critical Fixes
- **FIXED**: Removed duplicate dashboard submenu (root cause of duplication)
- **FIXED**: Employee role query now includes shop_employee role
- **FIXED**: Latest Employees table now shows 10 employees with proper format

### Changes
- Latest Employees table now matches Team Members page format
- Shows Name, Email, Type, Salary/Commission, and Manage button
- Displays all employee roles (shop_employee, shop_manager, administrator)
"@
    },
    @{
        tag = "v5.3.3"
        name = "v5.3.3 - Payment Calculation Fix"
        notes = @"
## v5.3.3 - Payment Calculation Fix

### Critical Fixes
- **FIXED**: Total Paid now shows correct amount from payments array
- **FIXED**: Paid amounts calculated from `_wc_tp_payments` meta, not old keys
- **FIXED**: Date range filtering for payments now works correctly

### Improvements
- Dashboard now shows accurate paid/due amounts
- All currency amounts use WooCommerce store currency
- Payment calculations respect date range filters
"@
    }
)

Write-Host "Creating GitHub Releases..." -ForegroundColor Cyan
Write-Host ""

$created = 0
$failed = 0

foreach ($release in $releases) {
    Write-Host "Processing $($release.tag)..." -ForegroundColor Yellow
    
    $payload = @{
        tag_name = $release.tag
        name = $release.name
        body = $release.notes
        draft = $false
        prerelease = $false
    } | ConvertTo-Json -Depth 10
    
    try {
        $uri = "https://api.github.com/repos/$owner/$repo/releases"
        $response = Invoke-WebRequest -Uri $uri `
            -Method POST `
            -Headers $headers `
            -Body $payload `
            -ContentType "application/json" `
            -ErrorAction Stop
        
        $result = $response.Content | ConvertFrom-Json
        Write-Host "✅ Created: $($release.tag)" -ForegroundColor Green
        Write-Host "   URL: $($result.html_url)" -ForegroundColor Green
        $created++
    }
    catch {
        $statusCode = $_.Exception.Response.StatusCode.Value__
        
        if ($statusCode -eq 422) {
            Write-Host "⚠️  Release already exists: $($release.tag)" -ForegroundColor Yellow
        } else {
            try {
                $errorBody = $_.Exception.Response.Content.ReadAsStream() | ForEach-Object { [System.IO.StreamReader]::new($_).ReadToEnd() }
                $errorJson = $errorBody | ConvertFrom-Json
                Write-Host "❌ Error ($statusCode): $($errorJson.message)" -ForegroundColor Red
            }
            catch {
                Write-Host "❌ Error ($statusCode): $_" -ForegroundColor Red
            }
            $failed++
        }
    }
    
    Write-Host ""
}

Write-Host "=== Summary ===" -ForegroundColor Green
Write-Host "Created: $created releases"
Write-Host "Failed: $failed"
Write-Host ""
Write-Host "View all releases: https://github.com/$owner/$repo/releases" -ForegroundColor Cyan
