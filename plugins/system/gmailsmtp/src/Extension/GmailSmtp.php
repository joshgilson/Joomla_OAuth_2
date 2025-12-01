<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.GmailSmtp
 *
 * @copyright   (C) 2024 Open Source Matters, Inc.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\GmailSmtp\Extension;

defined('_JEXEC') or die;

// Load League OAuth2 stub classes required by PHPMailer BEFORE any PHPMailer code runs
// This must be loaded early so PHP can find the class when PHPMailer's OAuth instantiates it
if (!class_exists('League\OAuth2\Client\Grant\RefreshToken')) {
    require_once dirname(__DIR__) . '/OAuth/League/RefreshToken.php';
}

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Http\HttpFactory;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\System\GmailSmtp\OAuth\TokenStorage;
use Joomla\Plugin\System\GmailSmtp\OAuth\GoogleProvider;
use Joomla\Plugin\System\GmailSmtp\Mail\OAuthMailer;

/**
 * Gmail SMTP OAuth 2.0 Plugin
 *
 * Enables OAuth 2.0 authentication for Gmail/Google Workspace SMTP
 * email delivery in Joomla.
 *
 * @since  1.0.0
 */
final class GmailSmtp extends CMSPlugin implements SubscriberInterface
{
    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Token storage instance
     *
     * @var    TokenStorage|null
     * @since  1.0.0
     */
    private ?TokenStorage $tokenStorage = null;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     * @since   1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterInitialise' => 'onAfterInitialise',
            'onAfterRoute'      => 'onAfterRoute',
            'onBeforeRender'    => 'onBeforeRender',
        ];
    }

    /**
     * After initialization event - set up the mailer override
     *
     * @return  void
     * @since   1.0.0
     */
    public function onAfterInitialise(): void
    {
        // Warn if not using HTTPS (security risk for OAuth)
        $app = $this->getApplication();
        if ($this->isConfigured() && !Uri::getInstance()->isSsl() && $app->isClient('administrator')) {
            Log::add('Gmail SMTP: Site is not using HTTPS. OAuth tokens may be at risk.', Log::WARNING, 'gmailsmtp');
        }

        // Only override if we have valid tokens
        if (!$this->isConfigured() || !$this->hasValidTokens()) {
            return;
        }

        $this->overrideMailer();
    }

    /**
     * After route event - handle OAuth callbacks
     *
     * @return  void
     * @since   1.0.0
     */
    public function onAfterRoute(): void
    {
        $app   = $this->getApplication();
        $input = $app->getInput();

        // Check if this is an OAuth callback
        if ($input->get('option') !== 'com_ajax') {
            return;
        }

        if ($input->get('plugin') !== 'gmailsmtp') {
            return;
        }

        $task = $input->get('task', '');

        switch ($task) {
            case 'callback':
                $this->handleOAuthCallback();
                break;
            case 'authorize':
                $this->handleAuthorize();
                break;
            case 'disconnect':
                $this->handleDisconnect();
                break;
            case 'testemail':
                $this->handleTestEmail();
                break;
        }
    }

    /**
     * Before render event - inject admin UI assets if needed
     *
     * @return  void
     * @since   1.0.0
     */
    public function onBeforeRender(): void
    {
        $app = $this->getApplication();

        // Only in admin
        if (!$app->isClient('administrator')) {
            return;
        }

        $input = $app->getInput();

        // Only on plugin edit page for this plugin
        if ($input->get('option') !== 'com_plugins') {
            return;
        }

        if ($input->get('view') !== 'plugin') {
            return;
        }
    }

    /**
     * Check if the plugin is properly configured
     *
     * @return  bool
     * @since   1.0.0
     */
    public function isConfigured(): bool
    {
        $clientId     = $this->params->get('client_id', '');
        $clientSecret = $this->params->get('client_secret', '');

        return !empty($clientId) && !empty($clientSecret);
    }

    /**
     * Check if we have valid OAuth tokens
     *
     * @return  bool
     * @since   1.0.0
     */
    public function hasValidTokens(): bool
    {
        $storage = $this->getTokenStorage();
        $tokens  = $storage->getTokens();

        if (empty($tokens) || empty($tokens['refresh_token'])) {
            return false;
        }

        return true;
    }

    /**
     * Get the current connection status
     *
     * @return  array{connected: bool, email: string, expires: string}
     * @since   1.0.0
     */
    public function getConnectionStatus(): array
    {
        $storage = $this->getTokenStorage();
        $tokens  = $storage->getTokens();

        if (empty($tokens) || empty($tokens['refresh_token'])) {
            return [
                'connected' => false,
                'email'     => '',
                'expires'   => '',
            ];
        }

        return [
            'connected' => true,
            'email'     => $tokens['email'] ?? '',
            'expires'   => $tokens['expires_at'] ?? '',
        ];
    }

    /**
     * Get the OAuth authorization URL
     *
     * @return  string
     * @since   1.0.0
     */
    public function getAuthorizationUrl(): string
    {
        if (!$this->isConfigured()) {
            return '';
        }

        $provider = $this->getGoogleProvider();

        return $provider->getAuthorizationUrl([
            'scope'         => ['https://mail.google.com/'],
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ]);
    }

    /**
     * Get the redirect URI for OAuth callback
     *
     * @return  string
     * @since   1.0.0
     */
    public function getRedirectUri(): string
    {
        return Uri::root() . 'index.php?option=com_ajax&plugin=gmailsmtp&task=callback&format=raw';
    }

    /**
     * Override Joomla's mailer with our OAuth-enabled mailer
     *
     * @return  void
     * @since   1.0.0
     */
    private function overrideMailer(): void
    {
        try {
            $storage = $this->getTokenStorage();
            $tokens  = $storage->getTokens();

            // Check if tokens need refresh
            if ($this->tokensNeedRefresh($tokens)) {
                $tokens = $this->refreshTokens($tokens);
            }

            // Create the OAuth mailer and seed Factory::$mailer
            $mailer = $this->createOAuthMailer($tokens);

            // Override Factory::$mailer for legacy code
            Factory::$mailer = $mailer;

            // Also seed Mail::$instances for code using Mail::getInstance()
            $reflection = new \ReflectionClass(Mail::class);
            $property   = $reflection->getProperty('instances');
            $property->setAccessible(true);
            $instances           = $property->getValue();
            $instances['Joomla'] = $mailer;
            $property->setValue(null, $instances);

            if ($this->params->get('debug_mode', 0)) {
                Log::add('Gmail SMTP OAuth mailer configured successfully', Log::DEBUG, 'gmailsmtp');
            }
        } catch (\Exception $e) {
            Log::add('Gmail SMTP OAuth mailer error: ' . $e->getMessage(), Log::ERROR, 'gmailsmtp');
        }
    }

    /**
     * Create an OAuth-enabled mailer instance
     *
     * @param   array  $tokens  The OAuth tokens
     *
     * @return  OAuthMailer
     * @since   1.0.0
     */
    private function createOAuthMailer(array $tokens): OAuthMailer
    {
        $app    = $this->getApplication();
        $config = $app->getConfig();

        $mailer = new OAuthMailer(true);
        $mailer->isSMTP();
        $mailer->Host       = 'smtp.gmail.com';
        $mailer->Port       = 587;
        $mailer->SMTPSecure = 'tls';
        $mailer->SMTPAuth   = true;
        $mailer->AuthType   = 'XOAUTH2';

        // Configure OAuth2
        $mailer->setOAuthToken(
            $tokens['email'],
            $this->params->get('client_id'),
            $this->params->get('client_secret'),
            $tokens['refresh_token'],
            $tokens['access_token']
        );

        // Set sender
        $fromEmail = $this->params->get('from_email', '') ?: $tokens['email'];
        $fromName  = $this->params->get('from_name', '') ?: $config->get('fromname', 'Joomla');
        $mailer->setFrom($fromEmail, $fromName);

        // Configure additional settings
        $mailer->CharSet  = 'utf-8';
        $mailer->Encoding = 'base64';

        if ($this->params->get('debug_mode', 0)) {
            $mailer->SMTPDebug = 2;
        }

        return $mailer;
    }

    /**
     * Check if tokens need to be refreshed
     *
     * @param   array  $tokens  The OAuth tokens
     *
     * @return  bool
     * @since   1.0.0
     */
    private function tokensNeedRefresh(array $tokens): bool
    {
        if (empty($tokens['expires_at'])) {
            return true;
        }

        // Refresh if token expires within 5 minutes
        $expiresAt = strtotime($tokens['expires_at']);
        $buffer    = 300; // 5 minutes

        return time() >= ($expiresAt - $buffer);
    }

    /**
     * Refresh OAuth tokens
     *
     * @param   array  $tokens  The current tokens
     *
     * @return  array  The refreshed tokens
     * @since   1.0.0
     */
    private function refreshTokens(array $tokens): array
    {
        $provider = $this->getGoogleProvider();

        try {
            $newAccessToken = $provider->getAccessToken('refresh_token', [
                'refresh_token' => $tokens['refresh_token'],
            ]);

            $tokens['access_token'] = $newAccessToken->getToken();
            $tokens['expires_at']   = date('Y-m-d H:i:s', $newAccessToken->getExpires());

            // If we got a new refresh token, save it
            $newRefreshToken = $newAccessToken->getRefreshToken();
            if ($newRefreshToken) {
                $tokens['refresh_token'] = $newRefreshToken;
            }

            // Save updated tokens
            $storage = $this->getTokenStorage();
            $storage->saveTokens($tokens);

            if ($this->params->get('debug_mode', 0)) {
                Log::add('Gmail SMTP OAuth tokens refreshed successfully', Log::DEBUG, 'gmailsmtp');
            }

            return $tokens;
        } catch (\Exception $e) {
            Log::add('Gmail SMTP OAuth token refresh failed: ' . $e->getMessage(), Log::ERROR, 'gmailsmtp');
            throw $e;
        }
    }

    /**
     * Handle OAuth authorization redirect
     *
     * @return  void
     * @since   1.0.0
     */
    private function handleAuthorize(): void
    {
        $app = $this->getApplication();

        // Security: Require admin authentication
        if (!$this->isAdminAuthenticated()) {
            $app->enqueueMessage(Text::_('JGLOBAL_AUTH_ACCESS_DENIED'), 'error');
            $app->redirect(Uri::root() . 'administrator/index.php');
            return;
        }

        if (!$this->isConfigured()) {
            $app->enqueueMessage(Text::_('PLG_SYSTEM_GMAILSMTP_ERROR_NOT_CONFIGURED'), 'error');
            $app->redirect(Uri::root() . 'administrator/index.php?option=com_plugins&view=plugins&filter[search]=gmail');
            return;
        }

        $provider = $this->getGoogleProvider();
        $authUrl  = $provider->getAuthorizationUrl([
            'scope'         => ['https://mail.google.com/', 'email'],
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ]);

        // Store state for CSRF protection
        $app->getSession()->set('gmailsmtp.oauth_state', $provider->getState());

        $app->redirect($authUrl);
    }

    /**
     * Handle OAuth callback from Google
     *
     * @return  void
     * @since   1.0.0
     */
    private function handleOAuthCallback(): void
    {
        $app     = $this->getApplication();
        $input   = $app->getInput();
        $session = $app->getSession();

        // Verify state for CSRF protection
        $state      = $input->get('state', '', 'string');
        $savedState = $session->get('gmailsmtp.oauth_state', '');

        if (empty($state) || $state !== $savedState) {
            $app->enqueueMessage(Text::_('PLG_SYSTEM_GMAILSMTP_ERROR_INVALID_STATE'), 'error');
            $app->redirect(Uri::root() . 'administrator/index.php?option=com_plugins&view=plugins&filter[search]=gmail');
            return;
        }

        // Clear state
        $session->clear('gmailsmtp.oauth_state');

        // Check for errors from Google (sanitize for display)
        $error = $input->get('error', '', 'alnum');
        if (!empty($error)) {
            // Log full error but show generic message to user
            $errorDesc = $input->get('error_description', $error, 'string');
            Log::add('Gmail SMTP OAuth error from Google: ' . $errorDesc, Log::ERROR, 'gmailsmtp');
            $app->enqueueMessage(Text::_('PLG_SYSTEM_GMAILSMTP_ERROR_OAUTH_GENERIC'), 'error');
            $app->redirect(Uri::root() . 'administrator/index.php?option=com_plugins&view=plugins&filter[search]=gmail');
            return;
        }

        // Get authorization code
        $code = $input->get('code', '', 'string');
        if (empty($code)) {
            $app->enqueueMessage(Text::_('PLG_SYSTEM_GMAILSMTP_ERROR_NO_CODE'), 'error');
            $app->redirect(Uri::root() . 'administrator/index.php?option=com_plugins&view=plugins&filter[search]=gmail');
            return;
        }

        try {
            $provider    = $this->getGoogleProvider();
            $accessToken = $provider->getAccessToken('authorization_code', ['code' => $code]);

            // Get user email
            $resourceOwner = $provider->getResourceOwner($accessToken);
            $email         = $resourceOwner->getEmail();

            // Store tokens
            $tokens = [
                'access_token'  => $accessToken->getToken(),
                'refresh_token' => $accessToken->getRefreshToken(),
                'expires_at'    => date('Y-m-d H:i:s', $accessToken->getExpires()),
                'email'         => $email,
                'created_at'    => date('Y-m-d H:i:s'),
            ];

            $storage = $this->getTokenStorage();
            $storage->saveTokens($tokens);

            $app->enqueueMessage(Text::sprintf('PLG_SYSTEM_GMAILSMTP_SUCCESS_CONNECTED', htmlspecialchars($email)), 'success');

        } catch (\Exception $e) {
            // Log full error but show generic message to user
            Log::add('Gmail SMTP OAuth callback error: ' . $e->getMessage(), Log::ERROR, 'gmailsmtp');
            $app->enqueueMessage(Text::_('PLG_SYSTEM_GMAILSMTP_ERROR_OAUTH_GENERIC'), 'error');
        }

        $app->redirect(Uri::root() . 'administrator/index.php?option=com_plugins&view=plugins&filter[search]=gmail');
    }

    /**
     * Handle disconnect request
     *
     * @return  void
     * @since   1.0.0
     */
    private function handleDisconnect(): void
    {
        $app = $this->getApplication();

        // Security: Require admin authentication
        if (!$this->isAdminAuthenticated()) {
            $app->enqueueMessage(Text::_('JGLOBAL_AUTH_ACCESS_DENIED'), 'error');
            $app->redirect(Uri::root() . 'administrator/index.php');
            return;
        }

        // Security: Verify CSRF token
        if (!Session::checkToken('get')) {
            $app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $app->redirect(Uri::root() . 'administrator/index.php?option=com_plugins&view=plugins&filter[search]=gmail');
            return;
        }

        try {
            $storage = $this->getTokenStorage();
            $tokens  = $storage->getTokens();

            // Revoke token at Google before deleting locally
            if (!empty($tokens['access_token'])) {
                $this->revokeGoogleToken($tokens['access_token']);
            }

            $storage->deleteTokens();

            $app->enqueueMessage(Text::_('PLG_SYSTEM_GMAILSMTP_SUCCESS_DISCONNECTED'), 'success');
        } catch (\Exception $e) {
            Log::add('Gmail SMTP disconnect error: ' . $e->getMessage(), Log::ERROR, 'gmailsmtp');
            $app->enqueueMessage(Text::_('PLG_SYSTEM_GMAILSMTP_ERROR_DISCONNECT_GENERIC'), 'error');
        }

        $app->redirect(Uri::root() . 'administrator/index.php?option=com_plugins&view=plugins&filter[search]=gmail');
    }

    /**
     * Revoke OAuth token at Google
     *
     * @param   string  $token  Access token to revoke
     *
     * @return  void
     * @since   1.0.0
     */
    private function revokeGoogleToken(string $token): void
    {
        try {
            $http = HttpFactory::getHttp();
            $http->post('https://oauth2.googleapis.com/revoke', ['token' => $token], [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]);
        } catch (\Exception $e) {
            // Log but don't fail - token will expire eventually
            Log::add('Gmail SMTP token revocation failed: ' . $e->getMessage(), Log::WARNING, 'gmailsmtp');
        }
    }

    /**
     * Handle test email request
     *
     * @return  void
     * @since   1.0.0
     */
    private function handleTestEmail(): void
    {
        // Suppress any PHP errors/warnings from being output
        $originalErrorReporting = error_reporting();
        error_reporting(0);

        // Clear ALL output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Start fresh buffer to capture any rogue output
        ob_start();

        $app   = $this->getApplication();
        $input = $app->getInput();

        $response = ['success' => false, 'message' => ''];

        // Security: Require admin authentication
        if (!$this->isAdminAuthenticated()) {
            $response['message'] = Text::_('JGLOBAL_AUTH_ACCESS_DENIED');
            $this->outputJsonResponse($response, $originalErrorReporting);
            return;
        }

        // Security: Verify CSRF token
        if (!Session::checkToken('get')) {
            $response['message'] = Text::_('JINVALID_TOKEN');
            $this->outputJsonResponse($response, $originalErrorReporting);
            return;
        }

        $testEmail = $input->get('email', '', 'email');

        if (empty($testEmail)) {
            $response['message'] = Text::_('PLG_SYSTEM_GMAILSMTP_ERROR_NO_TEST_EMAIL');
        } else {
            try {
                // Create mailer for test (without debug output)
                $storage = $this->getTokenStorage();
                $tokens  = $storage->getTokens();

                if ($this->tokensNeedRefresh($tokens)) {
                    $tokens = $this->refreshTokens($tokens);
                }

                $mailer = $this->createOAuthMailer($tokens);

                // Disable debug for test email to prevent output
                $mailer->SMTPDebug = 0;

                $mailer->addRecipient($testEmail);
                $mailer->setSubject(Text::_('PLG_SYSTEM_GMAILSMTP_TEST_EMAIL_SUBJECT'));
                $mailer->setBody(Text::_('PLG_SYSTEM_GMAILSMTP_TEST_EMAIL_BODY'));

                $result = $mailer->Send();

                if ($result === true) {
                    $response['success'] = true;
                    $response['message'] = Text::sprintf('PLG_SYSTEM_GMAILSMTP_TEST_EMAIL_SUCCESS', htmlspecialchars($testEmail));
                } else {
                    $response['message'] = Text::_('PLG_SYSTEM_GMAILSMTP_TEST_EMAIL_FAILED');
                }
            } catch (\Throwable $e) {
                Log::add('Gmail SMTP test email error: ' . $e->getMessage(), Log::ERROR, 'gmailsmtp');
                $response['message'] = Text::_('PLG_SYSTEM_GMAILSMTP_TEST_EMAIL_FAILED');
            }
        }

        $this->outputJsonResponse($response, $originalErrorReporting);
    }

    /**
     * Output JSON response and exit
     *
     * @param   array  $response              Response data
     * @param   int    $originalErrorReporting Original error reporting level
     *
     * @return  void
     * @since   1.0.0
     */
    private function outputJsonResponse(array $response, int $originalErrorReporting): void
    {
        // Discard any captured output (debug messages, warnings, etc.)
        ob_end_clean();

        // Restore error reporting
        error_reporting($originalErrorReporting);

        // Now output clean JSON
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        exit;
    }

    /**
     * Check if current user is an authenticated administrator
     *
     * @return  bool
     * @since   1.0.0
     */
    private function isAdminAuthenticated(): bool
    {
        $user = Factory::getUser();

        // Must be logged in and have admin access
        return !$user->guest && $user->authorise('core.manage', 'com_plugins');
    }

    /**
     * Get token storage instance
     *
     * @return  TokenStorage
     * @since   1.0.0
     */
    private function getTokenStorage(): TokenStorage
    {
        if ($this->tokenStorage === null) {
            $this->tokenStorage = new TokenStorage(Factory::getDbo());
        }

        return $this->tokenStorage;
    }

    /**
     * Get Google OAuth provider
     *
     * @return  GoogleProvider
     * @since   1.0.0
     */
    private function getGoogleProvider(): GoogleProvider
    {
        return new GoogleProvider([
            'clientId'     => $this->params->get('client_id'),
            'clientSecret' => $this->params->get('client_secret'),
            'redirectUri'  => $this->getRedirectUri(),
        ]);
    }
}
