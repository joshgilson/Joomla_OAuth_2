<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.GmailSmtp
 *
 * @copyright   (C) 2024 Open Source Matters, Inc.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\GmailSmtp\OAuth;

defined('_JEXEC') or die;

use PHPMailer\PHPMailer\OAuth;

/**
 * Gmail OAuth Token Provider for PHPMailer
 *
 * Implements the provider interface expected by PHPMailer's OAuth class.
 * This provides the access token for XOAUTH2 SMTP authentication.
 *
 * @since  1.0.0
 */
class GmailTokenProvider
{
    /**
     * OAuth client ID
     *
     * @var    string
     * @since  1.0.0
     */
    private string $clientId;

    /**
     * OAuth client secret
     *
     * @var    string
     * @since  1.0.0
     */
    private string $clientSecret;

    /**
     * OAuth refresh token
     *
     * @var    string
     * @since  1.0.0
     */
    private string $refreshToken;

    /**
     * OAuth access token
     *
     * @var    string
     * @since  1.0.0
     */
    private string $accessToken;

    /**
     * Constructor
     *
     * @param   string  $clientId      OAuth client ID
     * @param   string  $clientSecret  OAuth client secret
     * @param   string  $refreshToken  OAuth refresh token
     * @param   string  $accessToken   OAuth access token
     *
     * @since   1.0.0
     */
    public function __construct(
        string $clientId,
        string $clientSecret,
        string $refreshToken,
        string $accessToken
    ) {
        $this->clientId     = $clientId;
        $this->clientSecret = $clientSecret;
        $this->refreshToken = $refreshToken;
        $this->accessToken  = $accessToken;
    }

    /**
     * Get the access token
     *
     * This method is called by PHPMailer's OAuth class to get the
     * current access token for SMTP authentication.
     *
     * @return  AccessTokenWrapper
     * @since   1.0.0
     */
    public function getAccessToken(string $grant, array $options = []): AccessTokenWrapper
    {
        // If we have a valid access token, return it
        // The token refresh is handled at the plugin level before mail is sent
        return new AccessTokenWrapper($this->accessToken);
    }
}

/**
 * Simple wrapper for access token to satisfy PHPMailer OAuth interface
 *
 * @since  1.0.0
 */
class AccessTokenWrapper
{
    /**
     * Access token string
     *
     * @var    string
     * @since  1.0.0
     */
    private string $token;

    /**
     * Constructor
     *
     * @param   string  $token  Access token
     *
     * @since   1.0.0
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get token string
     *
     * @return  string
     * @since   1.0.0
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * String representation
     *
     * @return  string
     * @since   1.0.0
     */
    public function __toString(): string
    {
        return $this->token;
    }
}
