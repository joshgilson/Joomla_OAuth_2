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

use Joomla\CMS\Http\HttpFactory;

/**
 * Google OAuth 2.0 Provider
 *
 * Self-contained OAuth 2.0 implementation for Google services.
 * Avoids external dependencies while providing full OAuth functionality.
 *
 * @since  1.0.0
 */
class GoogleProvider
{
    /**
     * Google OAuth 2.0 endpoints
     */
    private const AUTHORIZE_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL     = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL  = 'https://openidconnect.googleapis.com/v1/userinfo';

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
     * OAuth redirect URI
     *
     * @var    string
     * @since  1.0.0
     */
    private string $redirectUri;

    /**
     * Current OAuth state for CSRF protection
     *
     * @var    string
     * @since  1.0.0
     */
    private string $state = '';

    /**
     * Constructor
     *
     * @param   array  $options  Provider options (clientId, clientSecret, redirectUri)
     *
     * @since   1.0.0
     */
    public function __construct(array $options)
    {
        $this->clientId     = $options['clientId'] ?? '';
        $this->clientSecret = $options['clientSecret'] ?? '';
        $this->redirectUri  = $options['redirectUri'] ?? '';
    }

    /**
     * Get authorization URL
     *
     * @param   array  $options  Additional options (scope, access_type, prompt)
     *
     * @return  string
     * @since   1.0.0
     */
    public function getAuthorizationUrl(array $options = []): string
    {
        $this->state = $this->generateState();

        $scope = $options['scope'] ?? ['email'];
        if (is_array($scope)) {
            $scope = implode(' ', $scope);
        }

        $params = [
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
            'scope'         => $scope,
            'state'         => $this->state,
            'access_type'   => $options['access_type'] ?? 'offline',
        ];

        if (!empty($options['prompt'])) {
            $params['prompt'] = $options['prompt'];
        }

        return self::AUTHORIZE_URL . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Get current state
     *
     * @return  string
     * @since   1.0.0
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Exchange authorization code for access token
     *
     * @param   string  $grant    Grant type ('authorization_code' or 'refresh_token')
     * @param   array   $options  Options (code for auth_code, refresh_token for refresh)
     *
     * @return  AccessToken
     * @throws  \RuntimeException
     * @since   1.0.0
     */
    public function getAccessToken(string $grant, array $options = []): AccessToken
    {
        $params = [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];

        if ($grant === 'authorization_code') {
            $params['grant_type']   = 'authorization_code';
            $params['code']         = $options['code'] ?? '';
            $params['redirect_uri'] = $this->redirectUri;
        } elseif ($grant === 'refresh_token') {
            $params['grant_type']    = 'refresh_token';
            $params['refresh_token'] = $options['refresh_token'] ?? '';
        } else {
            throw new \InvalidArgumentException('Unsupported grant type: ' . $grant);
        }

        $response = $this->makeRequest('POST', self::TOKEN_URL, $params);

        if (isset($response['error'])) {
            $errorMsg = $response['error_description'] ?? $response['error'];
            throw new \RuntimeException('OAuth error: ' . $errorMsg);
        }

        return new AccessToken($response);
    }

    /**
     * Get resource owner (user info) from access token
     *
     * @param   AccessToken  $token  Access token
     *
     * @return  ResourceOwner
     * @throws  \RuntimeException
     * @since   1.0.0
     */
    public function getResourceOwner(AccessToken $token): ResourceOwner
    {
        $response = $this->makeRequest('GET', self::USERINFO_URL, [], [
            'Authorization' => 'Bearer ' . $token->getToken(),
        ]);

        if (isset($response['error'])) {
            throw new \RuntimeException('Failed to get user info: ' . ($response['error_description'] ?? $response['error']));
        }

        return new ResourceOwner($response);
    }

    /**
     * Make HTTP request
     *
     * @param   string  $method   HTTP method
     * @param   string  $url      URL
     * @param   array   $data     POST data
     * @param   array   $headers  Additional headers
     *
     * @return  array
     * @throws  \RuntimeException
     * @since   1.0.0
     */
    private function makeRequest(string $method, string $url, array $data = [], array $headers = []): array
    {
        $http = HttpFactory::getHttp();

        $defaultHeaders = [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $headers = array_merge($defaultHeaders, $headers);

        try {
            if ($method === 'POST') {
                $response = $http->post($url, http_build_query($data), $headers);
            } else {
                $response = $http->get($url, $headers);
            }

            $body = $response->body;

            if (empty($body)) {
                throw new \RuntimeException('Empty response from OAuth server');
            }

            $decoded = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON response: ' . json_last_error_msg());
            }

            return $decoded;

        } catch (\Exception $e) {
            throw new \RuntimeException('HTTP request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Generate random state for CSRF protection
     *
     * @return  string
     * @since   1.0.0
     */
    private function generateState(): string
    {
        return bin2hex(random_bytes(16));
    }
}
