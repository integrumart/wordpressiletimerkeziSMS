# İleti Merkezi SMS Plugin - Usage Guide (English)

## Table of Contents
1. [Installation Steps](#installation-steps)
2. [API Configuration](#api-configuration)
3. [User Settings](#user-settings)
4. [2FA Login Process](#2fa-login-process)
5. [Troubleshooting](#troubleshooting)

## Installation Steps

### 1. Upload Plugin Files

**Manual Upload:**
1. Compress all plugin files into a ZIP file
2. Go to WordPress admin panel
3. Click **Plugins > Add New**
4. Click **Upload Plugin** button
5. Select the ZIP file and upload
6. Click **Activate Plugin** button

**FTP Upload:**
1. Upload plugin files via FTP to `/wp-content/plugins/ileti-merkezi-sms/`
2. Go to WordPress admin panel
3. Find "İleti Merkezi SMS 2FA" in the **Plugins** menu
4. Click the **Activate** link

### 2. Plugin Activation

When activated, the plugin automatically:
- Creates the `wp_ileti_merkezi_otp` database table
- Configures default settings:
  - 2FA: Enabled
  - OTP Length: 6 digits
  - OTP Expiry: 5 minutes

## API Configuration

### Step 1: Get İleti Merkezi API Credentials

1. Go to [İleti Merkezi](https://www.iletimerkezi.com/) website
2. Log in to your account
3. Note your API credentials:
   - Username
   - Password
   - Sender Title (approved sender ID)

### Step 2: Configure API in WordPress

1. Log in to WordPress admin panel
2. Go to **Settings > İleti Merkezi SMS**
3. Enter the following information:

```
API Username: [ileti-merkezi-username]
API Password: [ileti-merkezi-password]
Sender Title: [approved-sender-name]
```

4. Click **Save Settings** button

### Step 3: Test API Connection

1. Go to the "Test SMS" section at the bottom of settings page
2. Enter your test phone number (e.g., +905551234567)
3. Click **Send Test SMS** button
4. Check for success message and SMS

**Example Test Output:**
```
✓ SMS sent successfully
```

## User Settings

### Adding Phone Number

Each user needs to add their phone number to use 2FA.

#### For Your Own Profile:
1. Log in to WordPress admin panel
2. Go to **Users > Profile**
3. Find the "SMS 2FA Settings" section
4. Enter your phone number:
   - Format: +90XXXXXXXXXX
   - Example: +905551234567
5. Click **Update Profile** button

#### For Other Users (Administrators):
1. Go to **Users > All Users**
2. Click the **Edit** link for the relevant user
3. Find the "SMS 2FA Settings" section
4. Enter the phone number
5. Click **Update User** button

### 2FA Settings

The plugin enables 2FA by default, but you can customize:

```
Enable 2FA: ✓ (Checked - Enabled)
OTP Length: 6 (between 4-8)
OTP Expiry: 5 (minutes)
```

**Recommended Settings:**
- **High Security:** OTP Length: 8, Expiry: 3 minutes
- **Standard Security:** OTP Length: 6, Expiry: 5 minutes
- **User Friendly:** OTP Length: 4, Expiry: 10 minutes

## 2FA Login Process

### Normal User Login

#### 1. Go to WordPress Login Page
```
https://yoursite.com/wp-login.php
```

#### 2. Enter Username and Password
```
Username: admin
Password: ********
```

#### 3. Receive OTP SMS
- If password is correct, an SMS is sent to your phone
- SMS Content Example:
  ```
  Your verification code for My WordPress Site is: 123456. 
  This code will expire in 5 minutes.
  ```

#### 4. Enter Verification Code
- Login page automatically shows the OTP entry form
- Enter the 6-digit code (e.g., 123456)
- Click **Verify Code** button

#### 5. Login Complete
- If code is correct, you're redirected to WordPress admin panel

### Resending OTP

If SMS doesn't arrive or expires:

1. Click the **"Resend verification code"** link at the bottom of OTP form
2. A new OTP code is sent to your phone
3. Enter the new code

### Temporarily Disabling 2FA

For emergencies, you can temporarily disable 2FA:

1. Connect to server via FTP or file manager
2. Edit `wp-config.php` file
3. Add this line:
```php
define('ILETI_MERKEZI_DISABLE_2FA', true);
```
4. Save the file
5. You can now log in with just password
6. Remove this line to re-enable 2FA

## Troubleshooting

### SMS Not Received

**Solution 1: Check API Credentials**
- Go to Settings > İleti Merkezi SMS
- Ensure API username and password are correct
- Test connection with Test SMS feature

**Solution 2: Check Phone Number Format**
- Phone number format: +90XXXXXXXXXX
- Should not start with zero (wrong: +905501234567, ✗)
- Correct format: +905501234567 ✓

**Solution 3: Check İleti Merkezi Balance**
- Log in to your İleti Merkezi account
- Ensure you have sufficient balance

### "Phone number not found" Error

This error appears when user profile has no phone number.

**Solution:**
1. Go to Users > Profile
2. Add your phone number in "SMS 2FA Settings" section
3. Update profile

### "Invalid or expired verification code" Error

**Reason 1: Code Expired**
- OTP codes are valid for 5 minutes by default
- Click "Resend verification code" to get a new code

**Reason 2: Code Entered Incorrectly**
- Double-check the code
- Enter without spaces or hyphens

**Reason 3: Old Code Used**
- When a new code is sent, old codes become invalid
- Use the code from the most recent SMS

### Plugin Conflicts

Some security or cache plugins may conflict with 2FA.

**Testing:**
1. Temporarily disable all other plugins
2. Keep only İleti Merkezi SMS plugin active
3. Try logging in
4. If it works, enable plugins one by one to find the conflict

### Database Error

Rarely, there may be issues with the OTP table.

**Solution:**
1. Deactivate the plugin
2. Reactivate the plugin (table will be recreated)
3. Or manually check the table from phpMyAdmin:

```sql
SELECT * FROM wp_ileti_merkezi_otp LIMIT 10;
```

## Security Best Practices

1. **Use Strong API Password**
   - Change your İleti Merkezi API password regularly
   - Don't share with others

2. **Keep Phone Numbers Updated**
   - Ensure users keep their phone numbers current
   - Old numbers pose security risk

3. **Keep OTP Expiry Reasonable**
   - Too short (1-2 minutes): Poor user experience
   - Too long (15+ minutes): Security risk
   - Recommended: 5 minutes

4. **Regular Maintenance**
   - Old OTP records are automatically cleaned
   - Follow plugin updates

## Advanced Usage

### Programmatic SMS Sending

To send SMS from your own code:

```php
<?php
// Use İleti Merkezi API class
$api = new Ileti_Merkezi_API();

// Send SMS
$result = $api->send_sms('+905551234567', 'Test message');

if ($result['success']) {
    echo 'SMS sent successfully!';
} else {
    echo 'Error: ' . $result['message'];
}
?>
```

### OTP Generation and Verification

```php
<?php
// Get OTP manager
$otp_manager = Ileti_Merkezi_OTP::get_instance();

// Generate and send OTP for user
$user_id = 1;
$result = $otp_manager->send_otp($user_id);

// Verify OTP
$is_valid = $otp_manager->verify_otp($user_id, '123456');

if ($is_valid) {
    echo 'OTP verified!';
} else {
    echo 'Invalid OTP!';
}
?>
```

### User Phone Number Management

```php
<?php
$otp_manager = Ileti_Merkezi_OTP::get_instance();

// Get phone number
$phone = $otp_manager->get_user_phone($user_id);

// Set phone number
$otp_manager->set_user_phone($user_id, '+905551234567');
?>
```

## Support

For issues, suggestions, or contributions:
- GitHub Issues: https://github.com/integrumart/wordpressiletimerkeziSMS/issues
- Plugin Page: Settings > İleti Merkezi SMS

## Version History

**v1.0.0**
- Initial release
- İleti Merkezi API integration
- 2FA/2-step verification
- OTP management
- Admin settings page
- User profile integration
