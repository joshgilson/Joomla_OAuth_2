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

/**
 * OAuth 2.0 Access Token
 *
 * @since  1.0.0
 */
class AccessToken
{
    /**
     * Access token
     *
     * @var    string
     * @since  1.0.0
     */
    private string $accessToken;

    /**
     * Refresh token
     *
     * @var    string|null
     * @since  1.0.0
     */
    private ?string $refreshToken;

    /**
     * Token expiration timestamp
     *
     * @var    int|null
     * @since  1.0.0
     */
    private ?int $expires;

    /**
     * Token type
     *
     * @var    string
     * @since  1.0.0
     */
    private string $tokenType;

    /**
     * Token scope
     *
     * @var    string
     * @since  1.0.0
     */
    private string $scope;

    /**
     * Constructor
     *
     * @param   array  $data  Token response data from OAuth server
     *
     * @since   1.0.0
     */
    public function __construct(array $data)
    {
        $this->accessToken  = $data['access_token'] ?? '';
        $this->refreshToken = $data['refresh_token'] ?? null;
        $this->tokenType    = $data['token_type'] ?? 'Bearer';
        $this->scope        = $data['scope'] ?? '';

        // Calculate expiration timestamp
        if (isset($data['expires_in'])) {
            $this->expires = time() + (int) $data['expires_in'];
        } elseif (isset($data['expires'])) {
            $this->expires = (int) $data['expires'];
        } else {
            $this->expires = null;
        }
    }

    /**
     * Get access token string
     *
     * @return  string
     * @since   1.0.0
     */
    public function getToken(): string
    {
        return $this->accessToken;
    }

    /**
     * Get refresh token string
     *
     * @return  string|null
     * @since   1.0.0
     */
    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    /**
     * Get expiration timestamp
     *
     * @return  int|null
     * @since   1.0.0
     */
    public function getExpires(): ?int
    {
        return $this->expires;
    }

    /**
     * Check if token has expired
     *
     * @return  bool
     * @since   1.0.0
     */
    public function hasExpired(): bool
    {
        if ($this->expires === null) {
            return false;
        }

        return time() >= $this->expires;
    }

    /**
     * Get token type
     *
     * @return  string
     * @since   1.0.0
     */
    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    /**
     * Get token scope
     *
     * @return  string
     * @since   1.0.0
     */
    public function getScope(): string
    {
        return $this->scope;
    }
}
