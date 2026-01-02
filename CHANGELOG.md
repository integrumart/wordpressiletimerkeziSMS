# Changelog

All notable changes to the İleti Merkezi SMS 2FA plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-01-02

### Added
- Initial release of İleti Merkezi SMS 2FA plugin
- İleti Merkezi API integration for sending SMS messages
- 2-step verification (2FA) for WordPress login
- Admin settings page for API credentials configuration
  - API Username field
  - API Password field
  - Sender Title field
- 2FA settings configuration
  - Enable/disable 2FA toggle
  - OTP code length setting (4-8 digits)
  - OTP expiry time setting (in minutes)
- User profile integration
  - Phone number field in user profiles
  - Support for both admin and user profile editing
- OTP management system
  - Secure OTP generation
  - Database storage for OTP codes
  - Automatic expiry handling
  - OTP verification
- WordPress login integration
  - Automatic interception of login attempts
  - OTP form display after successful password validation
  - Resend OTP functionality
  - Session management for pending verifications
- Test SMS feature
  - Send test SMS from settings page
  - Verify API configuration
- Database table creation on activation
  - `wp_ileti_merkezi_otp` table for OTP storage
- Uninstall script for clean plugin removal
  - Remove plugin options
  - Drop database table
  - Clean user meta data
- Security features
  - Nonce verification for all forms
  - Input sanitization
  - Output escaping
  - CSRF protection
- Admin CSS styling for settings page
- Comprehensive documentation
  - README with installation and features
  - KULLANIM.md (Turkish usage guide)
  - USAGE.md (English usage guide)
  - Code comments and PHPDoc blocks

### Security
- All user inputs are sanitized and validated
- OTP codes stored securely in database
- Nonce-based CSRF protection
- Session security for pending authentications
- Automatic cleanup of expired OTP codes

## [Unreleased]

### Planned Features
- Support for multiple phone numbers per user
- SMS templates customization
- Admin notification when 2FA is bypassed
- User role-based 2FA requirement
- Backup codes for emergency access
- SMS delivery status tracking
- Detailed logging and audit trail
- Integration with other SMS providers as fallback
- WooCommerce checkout SMS verification
- Custom SMS messages for different events
- Multi-language support (i18n/l10n)
- REST API endpoints for SMS operations

[1.0.0]: https://github.com/integrumart/wordpressiletimerkeziSMS/releases/tag/v1.0.0
