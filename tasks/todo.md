# Gmail OAuth 2.0 SMTP Plugin for Joomla

## Project Overview

**Goal**: Create a Joomla 5.4+/6+ system plugin that enables OAuth 2.0 authentication for Gmail/Google Workspace SMTP email delivery, replacing the deprecated "Less Secure Apps" method.

**Why This Matters**: Google requires OAuth 2.0 for SMTP authentication as of January 2025. Without this, Joomla sites cannot send emails via Gmail/Google Workspace SMTP.

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
- [ ] Create plugin directory structure
- [ ] Create XML manifest file (gmailsmtp.xml)
- [ ] Create main plugin class (GmailSmtp.php)
- [ ] Create language files (en-GB)
- [ ] Create installation script

### Phase 2: OAuth 2.0 Infrastructure
- [ ] Bundle/include league/oauth2-google library
- [ ] Create TokenStorage class for database persistence
- [ ] Create GoogleOAuthProvider wrapper class
- [ ] Create OAuth callback controller/handler

### Phase 3: Mailer Integration
- [ ] Create custom MailerFactory that supports OAuth
- [ ] Create OAuth2TokenProvider for PHPMailer
- [ ] Hook into Joomla's mailer system (multiple entry points)
- [ ] Handle token refresh automatically

### Phase 4: Admin UI/UX
- [ ] Create configuration form (config.xml)
- [ ] Add "Connect to Google" authorization button
- [ ] Add connection status indicator
- [ ] Add "Send Test Email" functionality
- [ ] Add "Disconnect" functionality
- [ ] Add clear setup instructions in UI

### Phase 5: Polish & Testing
- [ ] Add input validation and error handling
- [ ] Add logging for debugging
- [ ] Test with Gmail personal accounts
- [ ] Test with Google Workspace accounts
- [ ] Create user documentation

---

## File Structure

```
plugins/system/gmailsmtp/
├── gmailsmtp.xml                 # Plugin manifest
├── gmailsmtp.php                 # Main plugin file (bootstrap)
├── services/
│   └── provider.php              # Joomla DI service provider
├── src/
│   ├── Extension/
│   │   └── GmailSmtp.php         # Main plugin class
│   ├── OAuth/
│   │   ├── GoogleProvider.php    # OAuth provider wrapper
│   │   ├── TokenStorage.php      # Database token storage
│   │   └── TokenProvider.php     # PHPMailer token provider
│   ├── Mail/
│   │   └── OAuthMailer.php       # Custom mailer with OAuth
│   └── Controller/
│       └── CallbackController.php # OAuth callback handler
├── forms/
│   └── config.xml                # Plugin configuration form
├── sql/
│   ├── install.mysql.sql         # Create tokens table
│   └── uninstall.mysql.sql       # Drop tokens table
├── language/
│   └── en-GB/
│       ├── plg_system_gmailsmtp.ini
│       └── plg_system_gmailsmtp.sys.ini
└── vendor/                       # Bundled OAuth libraries
    └── (league/oauth2-google + dependencies)
```

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

*To be completed after implementation*

---

## Resources

- [PHPMailer XOAUTH2 Wiki](https://github.com/PHPMailer/PHPMailer/wiki/Using-Gmail-with-XOAUTH2)
- [Google OAuth 2.0 Protocol](https://developers.google.com/gmail/imap/xoauth2-protocol)
- [Joomla Plugin Events](https://docs.joomla.org/Plugin/Events/System)
- [Joomla Mailer Override Forum](https://forum.joomla.org/viewtopic.php?t=1006452)
- [League OAuth2 Google](https://github.com/thephpleague/oauth2-google)
