# Release Notes - Version 1.3.8

**Release Date:** April 19, 2026

## 🎯 Overview

Version 1.3.8 brings the Performance Tracker to the admin employee details page, allowing administrators to view and monitor employee performance metrics directly from the admin panel.

---

## ✨ New Features

### Admin Performance Tab
- **Location**: Admin → Team Payroll → Employees → Employee Details → Performance Tab
- **Position**: New tab after "Salary Management" tab
- **Functionality**: Shows complete Performance Tracker with all features:
  - Overview with summary cards
  - Goals tracking with progress bars
  - Achievements with badge display
  - Baselines with historical data

### Admin Context Support
- Administrators can now view any employee's performance metrics
- Same visual experience as frontend employee view
- Includes all enhancements from v1.3.7:
  - Green backgrounds for achieved goals
  - Congratulations banner when all metrics achieved
  - Proper currency formatting
  - Responsive design

---

## 🔧 Technical Enhancements

### Performance Tracker AJAX Handler
- Modified to accept `user_id` parameter for admin viewing
- Security check: Only admins can view other users' performance
- Backward compatible: Works for both frontend (employee) and admin contexts

### JavaScript Updates
- Automatically detects admin context
- Passes `user_id` parameter when viewing from admin
- Reads user_id from hidden input field
- No changes needed for frontend functionality

### Asset Loading
- Properly enqueues Phosphor Icons in admin context
- Loads shared CSS for consistent styling
- Loads Performance Tracker CSS and JS with dependencies
- Localizes script with necessary data (ajax_url, nonce, currency settings)

---

## 📋 Files Modified

### PHP Files
1. **includes/class-employee-detail.php**
   - Added `render_performance_tab()` function
   - Enqueues necessary CSS and JS assets
   - Passes user_id for AJAX calls

2. **includes/class-performance-tracker-ajax.php**
   - Modified `ajax_get_performance_tracker_data()` to support admin context
   - Added user_id parameter handling with security checks

### JavaScript Files
1. **assets/js/performance-tracker.js**
   - Modified `fetchData()` to include user_id in AJAX requests
   - Detects and uses user_id from hidden input field

---

## 🎨 User Experience

### For Administrators
- Navigate to employee details page
- Click "Performance" tab
- View complete performance metrics:
  - Current period overview
  - Goal progress and achievement status
  - Unlocked achievements with badges
  - Baseline comparisons with historical data
- Same visual design as employee frontend view
- All interactive features work (tab switching, period selection)

### For Employees
- No changes to frontend experience
- Performance Tracker continues to work as before
- All v1.3.7 enhancements remain active

---

## 🔒 Security

- Admin capability check (`manage_options`) required to view other users' performance
- Nonce verification for all AJAX requests
- User ID validation and sanitization
- Maintains separation between admin and employee contexts

---

## 🚀 Upgrade Instructions

1. **Backup**: Always backup your site before updating
2. **Update**: Upload new plugin files or update via GitHub
3. **Test**: Visit employee details page and check Performance tab
4. **Verify**: Ensure performance data loads correctly for employees

---

## 📊 Use Cases

### Performance Review
- Admin can review employee performance during evaluations
- View historical goal achievement
- Check achievement unlocks and milestones
- Compare against baselines

### Team Management
- Monitor team member progress
- Identify top performers
- Track goal completion rates
- Analyze performance trends

### Quick Access
- No need to log in as employee to view their performance
- All metrics in one place
- Same visual design for consistency
- Real-time data from same source as frontend

---

## 🐛 Known Issues

None reported for this release.

---

## 📝 Notes

- Performance Tracker data is shared between frontend and admin views
- Changes made to goals/achievements in settings affect both views
- Email notifications (v1.3.7) continue to work automatically
- All visual enhancements from v1.3.7 are included

---

## 🔗 Related Releases

- **v1.3.7**: Performance Tracker visual enhancements and email notifications
- **v1.3.6**: Currency formatting fixes
- **v1.3.5**: Critical Performance Tracker data loading fix

---

## 💬 Support

For issues or questions:
- GitHub: https://github.com/imranduzzlo/pv-team-payroll
- Check debug.log for error messages
- Verify admin capabilities are set correctly

---

**Thank you for using WooCommerce Team Payroll & Commission System!**
