# WordPress Ileti Merkezi SMS 2FA Plugin

WordPress SMS plugin integrated with Ileti Merkezi API featuring Two-Factor Authentication (2FA) for login security.

## Features

- **SMS Integration**: Send SMS messages using Ileti Merkezi API
- **Two-Factor Authentication**: Add an extra layer of security to WordPress login with SMS-based OTP verification
- **Admin Settings Page**: Easy configuration of API credentials and OTP settings
- **User Profile Integration**: Users can add their phone numbers directly in their profile
- **Security Features**: 
  - Nonce verification
  - Input sanitization
  - Rate limiting with maximum attempt limits
  - OTP expiration
  - Secure session handling

## Installation

1. Download the plugin files
2. Upload to `/wp-content/plugins/ileti-merkezi-sms/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure your Ileti Merkezi API credentials in Settings → SMS 2FA

## Configuration

### Step 1: Configure API Credentials

1. Go to **Settings → SMS 2FA** in WordPress admin
2. Enter your Ileti Merkezi API credentials:
   - API Username
   - API Password
   - Sender Title (the name that appears as SMS sender)
3. Click **Save Settings**

### Step 2: Configure OTP Settings

Configure the One-Time Password settings:
- **OTP Length**: Number of digits in verification code (4-8, default: 6)
- **OTP Expiry**: How long the code is valid in minutes (1-60, default: 5)
- **Maximum Verification Attempts**: How many times a user can try to verify (1-10, default: 3)

### Step 3: Test Your Configuration

1. Enter a test phone number with country code (e.g., 905xxxxxxxxx)
2. Click **Send Test SMS**
3. Verify you receive the SMS

### Step 4: Enable 2FA for Users

For each user who should have 2FA:
1. Go to **Users → All Users**
2. Edit the user profile
3. Scroll to **SMS Two-Factor Authentication** section
4. Enter phone number with country code (e.g., 905xxxxxxxxx)
5. Click **Update Profile**

## Usage

Once configured and a user has a phone number set:

1. User enters username and password on login page
2. If credentials are correct, an SMS with a 6-digit code is sent
3. User enters the verification code on the login page
4. After successful verification, user is logged in

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- Ileti Merkezi API account
- User phone numbers in international format

## Security Features

- **Nonce Protection**: All forms use WordPress nonces
- **Input Sanitization**: All user inputs are sanitized
- **OTP Expiration**: Codes expire after configured time
- **Attempt Limiting**: Maximum verification attempts to prevent brute force
- **Session Security**: Secure session handling for pending verifications
- **Database Cleanup**: Automatic cleanup of expired OTPs

## Plugin Structure

```
ileti-merkezi-sms/
├── ileti-merkezi-sms.php          # Main plugin file
├── admin/
│   └── class-admin-settings.php   # Admin settings page
├── includes/
│   ├── class-ileti-merkezi-api.php # API integration
│   ├── class-otp-manager.php       # OTP generation and verification
│   └── class-login-handler.php     # Login process interception
├── assets/
│   ├── css/
│   │   ├── admin.css               # Admin page styles
│   │   └── login.css               # Login page styles
└── README.md
```

## API Integration

The plugin uses Ileti Merkezi SMS API v1 with XML requests. The API class handles:
- XML request building
- HTTP communication
- Response parsing
- Error handling
- Phone number formatting (Turkish numbers)

## Database Schema

The plugin creates a table `wp_imsms_otp` with the following structure:
- `id`: Primary key
- `user_id`: WordPress user ID
- `otp_code`: Generated verification code
- `phone_number`: User's phone number
- `created_at`: When OTP was created
- `expires_at`: When OTP expires
- `verified`: Verification status
- `attempts`: Number of verification attempts

## Troubleshooting

### SMS Not Sending
- Verify API credentials are correct
- Check Ileti Merkezi account has sufficient credits
- Verify sender title is approved
- Check phone number format (should start with country code)

### User Can't Login
- Verify user has phone number in profile
- Check OTP hasn't expired
- Ensure user hasn't exceeded max attempts
- Check WordPress error log for details

### Test SMS Fails
- Verify all API credentials are entered correctly
- Check phone number includes country code (90 for Turkey)
- Verify Ileti Merkezi account is active

## Support

For issues and questions:
- GitHub: https://github.com/integrumart/wordpressiletimerkeziSMS
- Check WordPress debug log when WP_DEBUG is enabled

## License

GPL v2 or later

## Changelog

### 1.0.0
- Initial release
- Ileti Merkezi API integration
- Two-factor authentication with SMS
- Admin settings page
- User profile phone number field
- OTP generation and verification
- Security features and error handling
