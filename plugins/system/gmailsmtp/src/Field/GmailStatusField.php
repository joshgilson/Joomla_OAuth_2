<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.GmailSmtp
 *
 * @copyright   (C) 2024 Open Source Matters, Inc.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\GmailSmtp\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Plugin\System\GmailSmtp\OAuth\TokenStorage;

/**
 * Gmail Connection Status Field
 *
 * Displays the current OAuth connection status with visual feedback.
 *
 * @since  1.0.0
 */
class GmailStatusField extends FormField
{
    /**
     * The form field type
     *
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'GmailStatus';

    /**
     * Get the field input markup
     *
     * @return  string
     * @since   1.0.0
     */
    protected function getInput(): string
    {
        $storage = new TokenStorage(Factory::getDbo());
        $tokens  = $storage->getTokens();

        if (empty($tokens) || empty($tokens['refresh_token'])) {
            return $this->renderDisconnected();
        }

        return $this->renderConnected($tokens);
    }

    /**
     * Render connected status
     *
     * @param   array  $tokens  Token data
     *
     * @return  string
     * @since   1.0.0
     */
    private function renderConnected(array $tokens): string
    {
        $email     = htmlspecialchars($tokens['email'] ?? 'Unknown');
        $createdAt = $tokens['created_at'] ?? '';

        $html = '<div class="alert alert-success" style="margin-bottom: 0;">';
        $html .= '<span class="icon-check-circle" aria-hidden="true"></span> ';
        $html .= '<strong>' . Text::_('PLG_SYSTEM_GMAILSMTP_STATUS_CONNECTED') . '</strong><br>';
        $html .= Text::sprintf('PLG_SYSTEM_GMAILSMTP_STATUS_CONNECTED_AS', $email);

        if ($createdAt) {
            $html .= '<br><small class="text-muted">';
            $html .= Text::sprintf('PLG_SYSTEM_GMAILSMTP_STATUS_CONNECTED_SINCE', $createdAt);
            $html .= '</small>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render disconnected status
     *
     * @return  string
     * @since   1.0.0
     */
    private function renderDisconnected(): string
    {
        $html = '<div class="alert alert-warning" style="margin-bottom: 0;">';
        $html .= '<span class="icon-warning" aria-hidden="true"></span> ';
        $html .= '<strong>' . Text::_('PLG_SYSTEM_GMAILSMTP_STATUS_NOT_CONNECTED') . '</strong><br>';
        $html .= Text::_('PLG_SYSTEM_GMAILSMTP_STATUS_NOT_CONNECTED_DESC');
        $html .= '</div>';

        return $html;
    }
}
