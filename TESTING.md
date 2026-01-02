# Plugin Testing Guide

## Manual Testing Checklist

### 1. Plugin Installation & Activation

- [ ] Upload plugin to `/wp-content/plugins/ileti-merkezi-sms/`
- [ ] Navigate to WordPress Admin → Plugins
- [ ] Activate "Ileti Merkezi SMS 2FA" plugin
- [ ] Verify database table `wp_imsms_otp` is created
- [ ] Check for any PHP errors in WordPress debug log

### 2. Admin Settings Configuration

- [ ] Navigate to Settings → SMS 2FA
- [ ] Enter API credentials:
  - API Username
  - API Password  
  - Sender Title
- [ ] Save settings and verify success message
- [ ] Configure OTP Settings:
  - OTP Length: 6
  - OTP Expiry: 5 minutes
  - Max Attempts: 3
- [ ] Save settings

### 3. Test SMS Functionality

- [ ] In Settings → SMS 2FA, scroll to "Test SMS" section
- [ ] Enter a test phone number (e.g., 905xxxxxxxxx)
- [ ] Click "Send Test SMS"
- [ ] Verify SMS is received on the phone
- [ ] Check that success message is shown in admin

### 4. User Profile Configuration

- [ ] Navigate to Users → All Users
- [ ] Edit a test user profile
- [ ] Scroll to "SMS Two-Factor Authentication" section
- [ ] Enter phone number (e.g., 905xxxxxxxxx)
- [ ] Save profile
- [ ] Verify phone number is saved in user meta

### 5. Login Flow with 2FA (No Phone Number)

- [ ] Ensure test user has NO phone number set
- [ ] Log out from WordPress
- [ ] Attempt to login with username and password
- [ ] Verify user is logged in without 2FA prompt

### 6. Login Flow with 2FA (With Phone Number)

- [ ] Ensure test user HAS phone number set
- [ ] Log out from WordPress
- [ ] Enter username and password
- [ ] Click "Log In"
- [ ] Verify SMS is sent with OTP code
- [ ] Verify OTP verification form is displayed
- [ ] Check that phone number is masked (e.g., ********1234)

### 7. OTP Verification - Success

- [ ] Enter correct OTP code from SMS
- [ ] Click "Log In"
- [ ] Verify user is successfully logged in
- [ ] Verify redirect to WordPress admin dashboard

### 8. OTP Verification - Invalid Code

- [ ] Go through login process to receive OTP
- [ ] Enter incorrect OTP code
- [ ] Verify error message is shown
- [ ] Verify remaining attempts counter decreases
- [ ] Attempt up to max attempts (default: 3)
- [ ] Verify appropriate error after max attempts

### 9. OTP Expiration

- [ ] Go through login process to receive OTP
- [ ] Wait for OTP expiry time (default: 5 minutes)
- [ ] Enter the expired OTP
- [ ] Verify error message about expired OTP
- [ ] Request new OTP and verify it works

### 10. Security Checks

- [ ] Verify nonce is present in all forms
- [ ] Check that direct file access is prevented (open plugin files directly in browser)
- [ ] Verify OTP codes are numeric only
- [ ] Check that phone numbers are sanitized
- [ ] Verify SQL injection protection (database queries use prepared statements)
- [ ] Test XSS protection (try entering scripts in form fields)

### 11. Database Verification

Check the `wp_imsms_otp` table:
- [ ] Verify OTP records are created on login
- [ ] Check that old OTPs are deleted before creating new ones
- [ ] Verify `verified` field is updated after successful verification
- [ ] Check that `attempts` counter increments correctly
- [ ] Verify expired OTPs are cleaned up by cron job

### 12. Error Handling

- [ ] Test with incorrect API credentials
- [ ] Test with invalid phone number format
- [ ] Test with unreachable API endpoint
- [ ] Verify all errors are caught and displayed gracefully
- [ ] Check WordPress debug log for any uncaught errors

## Expected Behavior

### Successful 2FA Login Flow:
1. User enters credentials
2. Credentials validated by WordPress
3. Plugin intercepts successful authentication
4. OTP generated and stored in database
5. SMS sent via Ileti Merkezi API
6. Login blocked with verification form shown
7. User enters OTP code
8. OTP validated against database
9. User logged in and redirected

### Security Measures:
- OTP codes expire after configured time
- Maximum attempt limit prevents brute force
- Old OTPs deleted before creating new ones
- Nonce verification on all forms
- Input sanitization on all user inputs
- Prepared statements for database queries
- Session-based user tracking
- Phone numbers stored in user meta (not visible to other users)

## Common Issues and Solutions

### Issue: SMS not received
**Solution:** 
- Check API credentials are correct
- Verify Ileti Merkezi account has credits
- Check sender title is approved
- Verify phone number format

### Issue: Database table not created
**Solution:**
- Deactivate and reactivate plugin
- Check database user has CREATE TABLE permission
- Run activation hook manually via WordPress admin

### Issue: User can't login after entering OTP
**Solution:**
- Check OTP hasn't expired
- Verify attempts haven't exceeded maximum
- Check database for OTP record
- Review WordPress error log

### Issue: Admin settings page not showing
**Solution:**
- Verify plugin is activated
- Check user has 'manage_options' capability
- Clear WordPress cache
- Check for JavaScript errors in browser console

## API Testing (Ileti Merkezi)

To test the API integration:

```php
// Test API connection
$api = new IMSMS_Ileti_Merkezi_API();
$result = $api->send_sms('905xxxxxxxxx', 'Test message');
var_dump($result);
```

Expected response on success:
```php
array(
    'success' => true,
    'message' => 'Message sent successfully',
    'order_id' => '123456'
)
```

Expected response on failure:
```php
array(
    'success' => false,
    'message' => 'Error description',
    'code' => 'error_code'
)
```

## Performance Considerations

- OTP table cleanup runs hourly via WordPress cron
- API requests have 30-second timeout
- Session data is cleaned after successful login
- Database queries are optimized with indexes

## Browser Compatibility

Test the login form on:
- [ ] Chrome/Edge (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Mobile browsers (iOS Safari, Chrome Mobile)

## Accessibility

- [ ] Form labels are properly associated with inputs
- [ ] Error messages are clear and helpful
- [ ] Keyboard navigation works correctly
- [ ] Screen reader friendly

## Plugin Deactivation

- [ ] Deactivate plugin
- [ ] Verify scheduled cron jobs are removed
- [ ] Check that login works normally without 2FA
- [ ] Optionally: Check if database table persists (as expected)
