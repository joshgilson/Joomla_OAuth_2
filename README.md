# Gmail SMTP OAuth 2.0 Plugin for Joomla

**Secure and reliable email delivery with OAuth 2.0 for Gmail and Google Workspace.**

[![Joomla 5.4+](https://img.shields.io/badge/Joomla-5.4%2B-blue.svg)](https://www.joomla.org)
[![Joomla 6.0+](https://img.shields.io/badge/Joomla-6.0%2B-blue.svg)](https://www.joomla.org)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://www.php.net)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-green.svg)](LICENSE)

## Why This Plugin?

Google requires OAuth 2.0 for SMTP authentication as of January 2025. Traditional username/password SMTP authentication no longer works with Gmail. This plugin provides:

- **OAuth 2.0 Authentication** - Secure, token-based authentication
- **Automatic Token Refresh** - Never worry about expired tokens
- **Simple Setup** - One-click Google authorization
- **Built-in Testing** - Send test emails directly from settings
- **Zero Dependencies** - No Composer required on your server

## Features

- Works with Gmail and Google Workspace accounts
- Encrypted token storage in Joomla database
- Visual connection status indicator
- Copy-to-clipboard for redirect URI
- Debug mode for troubleshooting
- Clean integration with Joomla's mail system

## Requirements

- Joomla 5.4+ or Joomla 6.0+
- PHP 8.1+
- HTTPS enabled on your site (required for OAuth)

## Installation

1. Download the latest release
2. Go to **System → Install → Extensions**
3. Upload and install the plugin
4. Go to **System → Plugins** and search for "Gmail"
5. Open the plugin settings and follow the setup guide

## Setup Guide

### Step 1: Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project (or select existing)
3. Navigate to **APIs & Services → Library**
4. Search for and enable the **Gmail API**

### Step 2: Create OAuth Credentials

1. Go to **APIs & Services → Credentials**
2. Click **Create Credentials → OAuth client ID**
3. Select **Web application**
4. Add a name (e.g., "Joomla SMTP")
5. Under **Authorized redirect URIs**, add the URI shown in the plugin settings
6. Click **Create** and copy your Client ID and Client Secret

### Step 3: Configure the Plugin

1. Open the plugin settings in Joomla
2. Enter your **Client ID** and **Client Secret**
3. Click **Connect to Google**
4. Authorize the application
5. Send a test email to verify

## How It Works

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Joomla Site   │────▶│  Gmail OAuth 2.0 │────▶│   Gmail SMTP    │
│   (Your Site)   │◀────│  (This Plugin)   │◀────│   (Google)      │
└─────────────────┘     └──────────────────┘     └─────────────────┘
         │                       │
         │                       ├── Intercepts mail requests
         │                       ├── Manages OAuth tokens
         │                       └── Configures PHPMailer
         │
         └── Uses standard Joomla mail functions
```

The plugin intercepts Joomla's mail system and configures it to use Gmail's SMTP with OAuth 2.0 authentication (XOAUTH2). Your existing code doesn't need to change - just install, configure, and your emails automatically go through Gmail.

## Troubleshooting

### "Invalid OAuth state" error
- Clear your browser cookies and try again
- Ensure your site uses HTTPS

### "Token refresh failed"
- Your refresh token may have been revoked
- Click "Disconnect" then "Connect to Google" again

### Emails not sending
- Enable Debug Mode in plugin settings
- Check Joomla logs at `administrator/logs/`
- Verify your From email matches the connected Google account

## Security

- Tokens are encrypted using AES-256-GCM with your Joomla secret key
- Client secrets are stored in the database, not in configuration files
- OAuth state parameter prevents CSRF attacks
- Minimal scope requested (only `https://mail.google.com/`)

## Contributing

Contributions are welcome! This is a free, open-source plugin designed to potentially be incorporated into Joomla core.

1. Fork the repository
2. Create your feature branch
3. Submit a pull request

## License

GNU General Public License version 2 or later. See [LICENSE](LICENSE) for details.

## Credits

- Built for the Joomla community
- Uses PHPMailer (bundled with Joomla) for XOAUTH2 support
- Inspired by the need for secure, free Gmail SMTP integration
