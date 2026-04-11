# Release Guide - WooCommerce Team Payroll Plugin

This guide explains how to create automated releases for the plugin using the `create-release.ps1` script.

## Prerequisites

1. PowerShell 5.0 or higher
2. Git installed and configured
3. GitHub Personal Access Token (PAT) with `repo` and `workflow` scopes

## Setting Up GitHub Token

### Option 1: Environment Variable (Recommended)
Set the `GITHUB_TOKEN` environment variable on your system:

**Windows (PowerShell):**
```powershell
$env:GITHUB_TOKEN = "your_github_token_here"
```

**Windows (Permanent - System Environment Variable):**
1. Open System Properties → Environment Variables
2. Click "New" under System variables
3. Variable name: `GITHUB_TOKEN`
4. Variable value: Your GitHub Personal Access Token
5. Click OK and restart PowerShell

**Linux/Mac:**
```bash
export GITHUB_TOKEN="your_github_token_here"
```

### Option 2: Command Line Parameter
Pass the token directly when running the script:
```powershell
.\create-release.ps1 -Version "5.4.11" -ReleaseTitle "Bug Fixes" -ReleaseBody "..." -GitHubToken "your_token"
```

## Creating a Release

### Basic Usage
```powershell
.\create-release.ps1 `
    -Version "5.4.11" `
    -ReleaseTitle "Add New Features" `
    -ReleaseBody "## Features`n- Feature 1`n- Feature 2"
```

### Example with Multi-line Release Body
```powershell
$releaseBody = @"
## New Features
- Employee type filter on payroll page
- Employee creation date filter on team members page

## Bug Fixes
- Fixed custom fields hook issue

## Technical Details
- Updated AJAX handlers
- Improved filtering logic
"@

.\create-release.ps1 `
    -Version "5.4.11" `
    -ReleaseTitle "Add Filters and Fix Custom Fields" `
    -ReleaseBody $releaseBody
```

## What the Script Does

1. **Updates Version Number**
   - Updates `woocommerce-team-payroll.php` header
   - Updates `WC_TEAM_PAYROLL_VERSION` constant

2. **Updates CHANGELOG.md**
   - Adds new version entry with today's date
   - Prepends release body to changelog

3. **Git Operations**
   - Stages all changes
   - Creates commit with message: `v{Version}: Update version and changelog`
   - Creates annotated git tag: `v{Version}`

4. **GitHub Push**
   - Pushes main branch to GitHub
   - Pushes git tag to GitHub

5. **GitHub Release**
   - Creates GitHub Release using the GitHub API
   - Uses provided title and body
   - Sets as non-draft, non-prerelease

## Release Naming Convention

### Version Format
Use semantic versioning: `MAJOR.MINOR.PATCH`
- Example: `5.4.10`, `5.5.0`, `6.0.0`

### Release Title Format
Keep it concise and descriptive:
- `Add Filters and Fix Custom Fields`
- `Fix Refunded Order Commission Calculation`
- `Dashboard Redesign with AJAX`

### Release Body Format
Use Markdown with clear sections:
```markdown
## New Features
- Feature 1
- Feature 2

## Bug Fixes
- Bug fix 1
- Bug fix 2

## Technical Details
- Technical change 1
- Technical change 2
```

## Troubleshooting

### "GitHub token not provided" Error
**Solution:** Set the `GITHUB_TOKEN` environment variable or pass `-GitHubToken` parameter

### "Failed to push to GitHub" Error
**Solution:** 
- Verify you have push access to the repository
- Check your GitHub token has `repo` scope
- Ensure you're on the correct branch (main)

### "Failed to create GitHub Release" Error
**Solution:**
- Verify the GitHub token is valid
- Check the release body for invalid Markdown
- Ensure the version tag doesn't already exist

### "Push cannot contain secrets" Error
**Solution:** 
- Never hardcode the GitHub token in the script
- Always use environment variables or command-line parameters
- If accidentally committed, use `git reset` to remove from history

## Automated Release Workflow

For future updates, follow this workflow:

1. Make code changes and commit them
2. Run the release script with new version number
3. Script automatically:
   - Updates version files
   - Creates git tag
   - Pushes to GitHub
   - Creates GitHub Release

Example:
```powershell
# After making code changes and committing
.\create-release.ps1 `
    -Version "5.4.11" `
    -ReleaseTitle "Your Release Title" `
    -ReleaseBody "Your release notes"
```

## GitHub Token Scopes Required

Your Personal Access Token should have these scopes:
- `repo` - Full control of private repositories
- `workflow` - Update GitHub Actions workflows (optional)

## Security Notes

⚠️ **Important:**
- Never commit the GitHub token to the repository
- Never share your GitHub token
- Use environment variables to store sensitive credentials
- Rotate your token periodically
- Use token expiration dates when available

## Support

For issues or questions about the release process, refer to:
- [GitHub API Documentation](https://docs.github.com/en/rest)
- [GitHub Personal Access Tokens](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token)
