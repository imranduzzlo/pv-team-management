# WooCommerce Team Payroll - Automated Release Script
# This script automates the release process including version updates, git tagging, and GitHub release creation

param(
    [Parameter(Mandatory=$true)]
    [string]$Version,
    
    [Parameter(Mandatory=$true)]
    [string]$ReleaseTitle,
    
    [Parameter(Mandatory=$true)]
    [string]$ReleaseBody,
    
    [string]$GitHubToken,
    
    [string]$Repository = "imranduzzlo/pv-team-management/"
)

# Get GitHub token from environment variable if not provided
if (-not $GitHubToken) {
    $GitHubToken = $env:GITHUB_TOKEN
    if (-not $GitHubToken) {
        Write-Error-Custom "GitHub token not provided. Set GITHUB_TOKEN environment variable or pass -GitHubToken parameter"
        exit 1
    }
}

# Color output functions
function Write-Success {
    param([string]$Message)
    Write-Host "✓ $Message" -ForegroundColor Green
}

function Write-Error-Custom {
    param([string]$Message)
    Write-Host "✗ $Message" -ForegroundColor Red
}

function Write-Info {
    param([string]$Message)
    Write-Host "ℹ $Message" -ForegroundColor Cyan
}

# Step 1: Update version in main plugin file
Write-Info "Step 1: Updating version in woocommerce-team-payroll.php..."
try {
    $pluginFile = "woocommerce-team-payroll.php"
    $content = Get-Content $pluginFile -Raw
    
    # Update version in header
    $content = $content -replace '\* Version: [\d.]+', "* Version: $Version"
    
    # Update version constant
    $content = $content -replace "define\( 'WC_TEAM_PAYROLL_VERSION', '[^']+' \)", "define( 'WC_TEAM_PAYROLL_VERSION', '$Version' )"
    
    Set-Content $pluginFile $content
    Write-Success "Version updated to $Version"
} catch {
    Write-Error-Custom "Failed to update version: $_"
    exit 1
}

# Step 2: Update CHANGELOG.md
Write-Info "Step 2: Updating CHANGELOG.md..."
try {
    $changelogFile = "CHANGELOG.md"
    $today = Get-Date -Format "yyyy-MM-dd"
    
    $changelogEntry = @"
# Changelog

## [$Version] - $today

$ReleaseBody

---

"@
    
    $existingContent = Get-Content $changelogFile -Raw
    $newContent = $changelogEntry + $existingContent
    Set-Content $changelogFile $newContent
    
    Write-Success "CHANGELOG.md updated"
} catch {
    Write-Error-Custom "Failed to update CHANGELOG.md: $_"
    exit 1
}

# Step 3: Git add and commit
Write-Info "Step 3: Committing changes to git..."
try {
    git add -A
    git commit -m "v$Version`: Update version and changelog"
    Write-Success "Changes committed"
} catch {
    Write-Error-Custom "Failed to commit changes: $_"
    exit 1
}

# Step 4: Create git tag
Write-Info "Step 4: Creating git tag..."
try {
    git tag -a "v$Version" -m "Release v$Version`: $ReleaseTitle"
    Write-Success "Git tag created: v$Version"
} catch {
    Write-Error-Custom "Failed to create git tag: $_"
    exit 1
}

# Step 5: Push to GitHub
Write-Info "Step 5: Pushing to GitHub..."
try {
    git push origin main
    Write-Success "Main branch pushed"
    
    git push origin "v$Version"
    Write-Success "Tag pushed: v$Version"
} catch {
    Write-Error-Custom "Failed to push to GitHub: $_"
    exit 1
}

# Step 6: Create GitHub Release
Write-Info "Step 6: Creating GitHub Release..."
try {
    $releaseData = @{
        tag_name = "v$Version"
        name = "v$Version`: $ReleaseTitle"
        body = $ReleaseBody
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
    
    Write-Success "GitHub Release created successfully"
    Write-Info "Release URL: $($response.html_url)"
} catch {
    Write-Error-Custom "Failed to create GitHub Release: $_"
    exit 1
}

Write-Success "Release v$Version completed successfully!"
Write-Info "Release page: https://github.com/$Repository/releases/tag/v$Version"
