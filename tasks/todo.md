# Gmail OAuth 2.0 SMTP Plugin for Joomla

## Project Overview

**Goal**: Create a FREE, open-source Joomla 5.4+/6+ system plugin that enables OAuth 2.0 authentication for Gmail/Google Workspace SMTP email delivery. Designed to feel like a native Joomla feature, potentially for core inclusion.

**Why This Matters**: Google requires OAuth 2.0 for SMTP authentication as of January 2025. Without this, Joomla sites cannot send emails via Gmail/Google Workspace SMTP.

---

## Competitive Analysis (vs Web357 Paid Plugin - $39-99/year)

### What They Offer:
- OAuth 2.0 token-based authentication
- Auto token renewal
- Test email functionality
- Authentication status indicators (green checkmark)
- Delete Access Key option
- TLS/SSL encryption configuration

### How We Do Better:

| Feature | Web357 (Paid) | Our Plugin (Free) |
|---------|---------------|-------------------|
| **Price** | $39-99/year | FREE & Open Source |
| **Joomla Integration** | Separate plugin config | Feels like core Joomla |
| **Setup Wizard** | Basic form | Step-by-step guided wizard |
| **Error Diagnostics** | Basic | Detailed with solutions |
| **Core Contribution** | No | Designed for potential Joomla core inclusion |
| **Multi-account** | No | Future-ready architecture |
| **Documentation** | External | Built into plugin UI |
| **Community** | Paid support | Open source community |

### Our Differentiators:
1. **FREE forever** - No licensing, no limits
2. **Native Feel** - Integrates seamlessly with Joomla's mail system
3. **Better UX** - Modern, clean interface with helpful guidance
4. **Open Source** - Community can contribute and improve
5. **Core-Ready** - Architected so Joomla team could adopt it

---

## Technical Architecture

### How It Works

1. **User Setup**:
   - Create Google Cloud Console project
   - Enable Gmail API
   - Create OAuth 2.0 credentials (Client ID + Secret)
   - Enter credentials in Joomla plugin settings

2. **OAuth Flow**:
   - User clicks "Connect to Google" in plugin settings
   - Redirected to Google authorization page
   - User grants permissions (scope: `https://mail.google.com/`)
   - Callback receives authorization code
   - Plugin exchanges code for access + refresh tokens
   - Tokens stored securely in Joomla database

3. **Email Sending**:
   - Plugin intercepts Joomla's mailer factory
   - Configures PHPMailer with XOAUTH2 authentication
   - Uses stored tokens for authentication
   - Automatically refreshes expired access tokens

### Dependencies

- **league/oauth2-google**: Google OAuth 2.0 client for PHP
- **league/oauth2-client**: Base OAuth 2.0 client
- PHPMailer (bundled with Joomla)

---

## Implementation Plan

### Phase 1: Plugin Foundation
- [x] Create plugin directory structure
- [x] Create XML manifest file (gmailsmtp.xml)
- [x] Create main plugin class (GmailSmtp.php)
- [x] Create language files (en-GB)
- [x] Create service provider for Joomla DI

### Phase 2: OAuth 2.0 Infrastructure
- [x] Create self-contained GoogleProvider (no external dependencies!)
- [x] Create TokenStorage class for database persistence
- [x] Create AccessToken and ResourceOwner classes
- [x] Create OAuth callback handler in main plugin

### Phase 3: Mailer Integration
- [x] Create OAuthMailer extending Joomla's Mail class
- [x] Create GmailTokenProvider for PHPMailer XOAUTH2
- [x] Hook into Joomla's mailer system (Factory::$mailer + Mail::$instances)
- [x] Handle token refresh automatically before expiration

### Phase 4: Admin UI/UX
- [x] Create custom form fields (GmailStatus, GmailAuthorize, GmailRedirectUri, GmailTestEmail)
- [x] Add "Connect to Google" authorization button
- [x] Add connection status indicator with visual feedback
- [x] Add "Send Test Email" with AJAX functionality
- [x] Add "Disconnect" functionality with confirmation
- [x] Add clear setup instructions in UI

### Phase 5: Polish & Testing
- [x] Add input validation and error handling
- [x] Add logging for debugging (via Joomla's Log system)
- [ ] Test with Gmail personal accounts
- [ ] Test with Google Workspace accounts
- [x] Create user documentation (README.md)

---

## File Structure (Actual Implementation)

```
plugins/system/gmailsmtp/
├── gmailsmtp.xml                 # Plugin manifest with inline config
├── services/
│   └── provider.php              # Joomla DI service provider
├── src/
│   ├── Extension/
│   │   └── GmailSmtp.php         # Main plugin class (handles all events)
│   ├── OAuth/
│   │   ├── GoogleProvider.php    # Self-contained OAuth provider
│   │   ├── AccessToken.php       # Access token wrapper
│   │   ├── ResourceOwner.php     # User info wrapper
│   │   ├── TokenStorage.php      # Encrypted database storage
│   │   └── GmailTokenProvider.php # PHPMailer XOAUTH2 provider
│   ├── Mail/
│   │   └── OAuthMailer.php       # OAuth-enabled mailer
│   └── Field/
│       ├── GmailStatusField.php      # Connection status display
│       ├── GmailAuthorizeField.php   # Connect/Disconnect buttons
│       ├── GmailRedirectUriField.php # Copy-able redirect URI
│       └── GmailTestEmailField.php   # Test email with AJAX
├── sql/
│   ├── install.mysql.sql         # Create tokens table
│   └── uninstall.mysql.sql       # Drop tokens table
└── language/
    └── en-GB/
        ├── plg_system_gmailsmtp.ini
        └── plg_system_gmailsmtp.sys.ini
```

**Key Design Decision**: No external dependencies! The OAuth implementation is self-contained, using only Joomla's built-in HTTP client. This means no Composer required on the server.

---

## Configuration Options

| Option | Description |
|--------|-------------|
| `enabled` | Enable/disable the plugin |
| `client_id` | Google OAuth Client ID |
| `client_secret` | Google OAuth Client Secret |
| `from_email` | From email address (must match authorized Google account) |
| `from_name` | From name for outgoing emails |
| `debug_mode` | Enable detailed logging |

---

## Security Considerations

1. **Token Storage**: Tokens encrypted at rest using Joomla's secret key
2. **Client Secret**: Stored in database, not in configuration files
3. **HTTPS Required**: OAuth callbacks require HTTPS
4. **Scope Limitation**: Only request `https://mail.google.com/` scope
5. **Token Refresh**: Automatic refresh before expiration

---

## User Experience Goals

1. **Simple Setup**: Clear step-by-step instructions in plugin settings
2. **One-Click Connect**: Single button to authorize with Google
3. **Visual Status**: Clear indication of connection status
4. **Test Functionality**: Built-in test email button
5. **Error Messages**: Helpful, actionable error messages
6. **Documentation**: In-plugin help text and links

---

## Compatibility

- **Joomla**: 5.4+ and 6.0+
- **PHP**: 8.1+ (uses 8.4+ practices where beneficial)
- **Database**: MySQL 5.7+, MariaDB 10.2+, PostgreSQL 11+

---

## Review Section

### Implementation Summary

**Completed on**: December 2024

**Total Files Created**: 17 files

**Architecture Highlights**:

1. **Zero External Dependencies**
   - Created self-contained OAuth 2.0 implementation
   - Uses only Joomla's built-in HTTP client (`Joomla\CMS\Http\HttpFactory`)
   - No Composer packages required on the server

2. **Secure Token Storage**
   - AES-256-GCM encryption using Joomla's secret key
   - Tokens stored in dedicated database table
   - Automatic token refresh 5 minutes before expiration

3. **Seamless Joomla Integration**
   - Overrides both `Factory::$mailer` and `Mail::$instances`
   - Works with all Joomla extensions that use standard mail functions
   - Uses Joomla 5+ plugin architecture with DI container

4. **User-Friendly Admin UI**
   - Visual connection status (green/yellow indicators)
   - Copy-to-clipboard for redirect URI
   - One-click Google authorization
   - AJAX-powered test email functionality
   - Built-in setup instructions

### Key Differences from Competitors

| Aspect | Web357 (Paid) | Our Implementation |
|--------|---------------|-------------------|
| Dependencies | Unknown | Zero (self-contained) |
| Encryption | Unknown | AES-256-GCM |
| Token Refresh | Auto | Auto (5-min buffer) |
| Test Email | Yes | Yes (AJAX) |
| Price | $39-99/year | FREE |

### Files Created

| Category | Files |
|----------|-------|
| Core Plugin | 3 (manifest, provider, main class) |
| OAuth Classes | 5 (provider, token, storage, resource owner, PHPMailer provider) |
| Mail Classes | 1 (OAuthMailer) |
| Form Fields | 4 (status, authorize, redirect URI, test email) |
| Language | 2 (main + sys) |
| Database | 2 (install + uninstall SQL) |

### Bugs Fixed During Testing

1. **`getOAuth()` visibility error**
   - **Issue**: `Access level to OAuthMailer::getOAuth() must be public`
   - **Cause**: PHPMailer's parent class requires the method to be public
   - **Fix**: Changed `protected function getOAuth()` to `public function getOAuth()`

2. **League OAuth2 RefreshToken class not found**
   - **Issue**: `Class "League\OAuth2\Client\Grant\RefreshToken" not found`
   - **Cause**: PHPMailer's OAuth class expects League OAuth2 library classes
   - **Fix**: Created stub class at `src/OAuth/League/RefreshToken.php` and loaded it early in main plugin

3. **Test email AJAX returning HTML instead of JSON**
   - **Issue**: `Unexpected token '<', "..."... is not valid JSON` (but email sent successfully)
   - **Cause**: Joomla outputting HTML headers before our JSON response
   - **Fix**: Clear ALL output buffers, set JSON header explicitly, use `exit` for clean termination

### Next Steps for Production

1. **Testing** - Test with real Gmail and Google Workspace accounts
2. **PostgreSQL Support** - Add PostgreSQL install SQL
3. **Multi-language** - Add translations for other languages
4. **JED Submission** - Submit to Joomla Extensions Directory
5. **Core Proposal** - Propose for Joomla core inclusion

---

---

## Issue: ZOOlander Form Error (December 2024)

### Problem
When the Gmail SMTP OAuth plugin is enabled, ZOOlander's Essentials for YOOtheme Pro form elements fail with:
- "Submission failed, please try again or contact us about this issue"
- "Internal Server Error"

The issue occurs on form submission (AJAX) and only when the plugin is enabled.

### Root Cause Analysis

Based on code review, the issue is likely in the mailer override mechanism. Potential causes:

1. **PHP 7.4 Compatibility Issue** (Most Likely)
   - `GmailTokenProvider.php` line 93: uses `mixed` type hint (PHP 8.0+)
   - `TokenStorage.php` line 193: uses `string|false` union type (PHP 8.0+)
   - If server runs PHP 7.4 (supported by Joomla 4.x), these cause parse errors

2. **Reflection Issues with Mail::$instances**
   - `$property->getValue()` returns null on first access before array init
   - Using array access `$instances['Joomla']` on null causes error

3. **Mailer Exception During Send**
   - If OAuth fails during send, exception bubbles up uncaught
   - ZOOlander may not catch mailer exceptions gracefully

### Fix Plan

- [ ] 1. Fix PHP 7.4 compatibility - remove PHP 8.0+ type hints
- [ ] 2. Add defensive null check for Reflection-based mailer override
- [ ] 3. Test and commit changes

---

## Resources

- [PHPMailer XOAUTH2 Wiki](https://github.com/PHPMailer/PHPMailer/wiki/Using-Gmail-with-XOAUTH2)
- [Google OAuth 2.0 Protocol](https://developers.google.com/gmail/imap/xoauth2-protocol)
- [Joomla Plugin Events](https://docs.joomla.org/Plugin/Events/System)
- [Joomla Mailer Override Forum](https://forum.joomla.org/viewtopic.php?t=1006452)
- [League OAuth2 Google](https://github.com/thephpleague/oauth2-google)
