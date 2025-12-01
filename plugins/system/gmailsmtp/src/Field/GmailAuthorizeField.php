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
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Plugin\System\GmailSmtp\OAuth\TokenStorage;

/**
 * Gmail Authorize Button Field
 *
 * Displays Connect/Disconnect buttons for Google OAuth authorization.
 *
 * @since  1.0.0
 */
class GmailAuthorizeField extends FormField
{
    /**
     * The form field type
     *
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'GmailAuthorize';

    /**
     * Get the field input markup
     *
     * @return  string
     * @since   1.0.0
     */
    protected function getInput(): string
    {
        $storage     = new TokenStorage(Factory::getDbo());
        $tokens      = $storage->getTokens();
        $isConnected = !empty($tokens) && !empty($tokens['refresh_token']);

        $token = Session::getFormToken();
        $authorizeUrl  = Uri::root() . 'index.php?option=com_ajax&plugin=gmailsmtp&task=authorize&format=raw';
        $disconnectUrl = Uri::root() . 'index.php?option=com_ajax&plugin=gmailsmtp&task=disconnect&format=raw&' . $token . '=1';

        $html = '<div class="btn-group">';

        if ($isConnected) {
            // Show disconnect button
            $html .= '<a href="' . $disconnectUrl . '" class="btn btn-danger" ';
            $html .= 'onclick="return confirm(\'' . Text::_('PLG_SYSTEM_GMAILSMTP_CONFIRM_DISCONNECT', true) . '\')">';
            $html .= '<span class="icon-cancel" aria-hidden="true"></span> ';
            $html .= Text::_('PLG_SYSTEM_GMAILSMTP_DISCONNECT');
            $html .= '</a>';

            // Show reconnect button
            $html .= '<a href="' . $authorizeUrl . '" class="btn btn-secondary">';
            $html .= '<span class="icon-refresh" aria-hidden="true"></span> ';
            $html .= Text::_('PLG_SYSTEM_GMAILSMTP_RECONNECT');
            $html .= '</a>';
        } else {
            // Show connect button
            $html .= '<a href="' . $authorizeUrl . '" class="btn btn-primary btn-lg">';
            $html .= '<span class="icon-key" aria-hidden="true"></span> ';
            $html .= Text::_('PLG_SYSTEM_GMAILSMTP_CONNECT_TO_GOOGLE');
            $html .= '</a>';
        }

        $html .= '</div>';

        // Add help text
        if (!$isConnected) {
            $html .= '<div class="small text-muted mt-2">';
            $html .= Text::_('PLG_SYSTEM_GMAILSMTP_AUTHORIZE_HELP');
            $html .= '</div>';
        }

        return $html;
    }
}
