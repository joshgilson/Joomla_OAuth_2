<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.GmailSmtp
 *
 * @copyright   (C) 2024 Open Source Matters, Inc.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\GmailSmtp\Mail;

defined('_JEXEC') or die;

use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\Mail;
use PHPMailer\PHPMailer\OAuth;
use Joomla\Plugin\System\GmailSmtp\OAuth\GmailTokenProvider;

/**
 * OAuth-enabled Mailer
 *
 * Extends Joomla's Mail class to support Gmail OAuth 2.0 authentication.
 * Uses PHPMailer's built-in XOAUTH2 support.
 *
 * @since  1.0.0
 */
class OAuthMailer extends Mail
{
    /**
     * Email address for OAuth
     *
     * @var    string
     * @since  1.0.0
     */
    private string $oauthEmail = '';

    /**
     * OAuth client ID
     *
     * @var    string
     * @since  1.0.0
     */
    private string $oauthClientId = '';

    /**
     * OAuth client secret
     *
     * @var    string
     * @since  1.0.0
     */
    private string $oauthClientSecret = '';

    /**
     * OAuth refresh token
     *
     * @var    string
     * @since  1.0.0
     */
    private string $oauthRefreshToken = '';

    /**
     * OAuth access token
     *
     * @var    string
     * @since  1.0.0
     */
    private string $oauthAccessToken = '';

    /**
     * Set OAuth token configuration
     *
     * @param   string  $email         Email address
     * @param   string  $clientId      OAuth client ID
     * @param   string  $clientSecret  OAuth client secret
     * @param   string  $refreshToken  OAuth refresh token
     * @param   string  $accessToken   OAuth access token
     *
     * @return  void
     * @since   1.0.0
     */
    public function setOAuthToken(
        string $email,
        string $clientId,
        string $clientSecret,
        string $refreshToken,
        string $accessToken
    ): void {
        $this->oauthEmail        = $email;
        $this->oauthClientId     = $clientId;
        $this->oauthClientSecret = $clientSecret;
        $this->oauthRefreshToken = $refreshToken;
        $this->oauthAccessToken  = $accessToken;
    }

    /**
     * Create OAuth instance for PHPMailer
     *
     * This method is called by PHPMailer during SMTP authentication
     * when AuthType is set to 'XOAUTH2'.
     *
     * @return  OAuth
     * @since   1.0.0
     */
    public function getOAuth(): ?OAuth
    {
        if (empty($this->oauthEmail) || empty($this->oauthAccessToken)) {
            return null;
        }

        $provider = new GmailTokenProvider(
            $this->oauthClientId,
            $this->oauthClientSecret,
            $this->oauthRefreshToken,
            $this->oauthAccessToken
        );

        return new OAuth([
            'userName'   => $this->oauthEmail,
            'provider'   => $provider,
        ]);
    }

    /**
     * Pre-send hook to set up OAuth
     *
     * @return  bool
     * @since   1.0.0
     */
    public function preSend(): bool
    {
        try {
            // Set up OAuth if configured
            if ($this->AuthType === 'XOAUTH2' && !empty($this->oauthEmail)) {
                $this->setOAuth($this->getOAuth());
            }

            return parent::preSend();
        } catch (\Throwable $e) {
            $this->ErrorInfo = $e->getMessage();
            Log::add('Gmail SMTP preSend exception: ' . $e->getMessage(), Log::ERROR, 'gmailsmtp');
            return false;
        }
    }

    /**
     * Send mail, catching all exceptions to prevent 500 errors
     *
     * Extensions like ZOOlander throw their own exceptions on mail failure,
     * but don't catch exceptions from the mailer itself. This override ensures
     * any internal exceptions are caught and converted to a false return with
     * ErrorInfo set, allowing the calling code to handle the failure gracefully.
     *
     * @return  bool
     * @since   1.0.0
     */
    public function send(): bool
    {
        try {
            $result = parent::send();

            // Log failures for debugging
            if ($result !== true && !empty($this->ErrorInfo)) {
                Log::add('Gmail SMTP send failed: ' . $this->ErrorInfo, Log::ERROR, 'gmailsmtp');
            }

            return $result;
        } catch (\Throwable $e) {
            // Catch ANY exception and convert to false return
            $this->ErrorInfo = $e->getMessage();
            Log::add('Gmail SMTP send exception: ' . $e->getMessage(), Log::ERROR, 'gmailsmtp');
            return false;
        }
    }
}
