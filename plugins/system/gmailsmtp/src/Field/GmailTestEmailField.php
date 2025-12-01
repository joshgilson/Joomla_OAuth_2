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
use Joomla\CMS\Uri\Uri;
use Joomla\Plugin\System\GmailSmtp\OAuth\TokenStorage;

/**
 * Gmail Test Email Field
 *
 * Allows sending a test email to verify the OAuth SMTP configuration.
 *
 * @since  1.0.0
 */
class GmailTestEmailField extends FormField
{
    /**
     * The form field type
     *
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'GmailTestEmail';

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

        if (!$isConnected) {
            $html = '<div class="alert alert-info">';
            $html .= Text::_('PLG_SYSTEM_GMAILSMTP_TEST_CONNECT_FIRST');
            $html .= '</div>';
            return $html;
        }

        $testUrl = Uri::root() . 'index.php?option=com_ajax&plugin=gmailsmtp&task=testemail&format=raw';

        $html = '<div class="input-group">';
        $html .= '<input type="email" id="gmailsmtp_test_email" class="form-control" ';
        $html .= 'placeholder="' . Text::_('PLG_SYSTEM_GMAILSMTP_TEST_EMAIL_PLACEHOLDER') . '">';
        $html .= '<button type="button" class="btn btn-success" id="gmailsmtp_test_btn" onclick="sendTestEmail()">';
        $html .= '<span class="icon-envelope" aria-hidden="true"></span> ';
        $html .= Text::_('PLG_SYSTEM_GMAILSMTP_SEND_TEST');
        $html .= '</button>';
        $html .= '</div>';
        $html .= '<div id="gmailsmtp_test_result" class="mt-2"></div>';

        // Add test script
        $html .= '<script>
        async function sendTestEmail() {
            const emailInput = document.getElementById("gmailsmtp_test_email");
            const resultDiv = document.getElementById("gmailsmtp_test_result");
            const btn = document.getElementById("gmailsmtp_test_btn");

            const email = emailInput.value.trim();
            if (!email) {
                resultDiv.innerHTML = \'<div class="alert alert-warning">' . Text::_('PLG_SYSTEM_GMAILSMTP_TEST_ENTER_EMAIL') . '</div>\';
                return;
            }

            // Show loading
            btn.disabled = true;
            btn.innerHTML = \'<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' . Text::_('PLG_SYSTEM_GMAILSMTP_SENDING') . '\';
            resultDiv.innerHTML = "";

            try {
                const response = await fetch("' . $testUrl . '&email=" + encodeURIComponent(email));
                const data = await response.json();

                if (data.success) {
                    resultDiv.innerHTML = \'<div class="alert alert-success"><span class="icon-checkmark"></span> \' + data.message + \'</div>\';
                } else {
                    resultDiv.innerHTML = \'<div class="alert alert-danger"><span class="icon-cancel"></span> \' + data.message + \'</div>\';
                }
            } catch (error) {
                resultDiv.innerHTML = \'<div class="alert alert-danger">' . Text::_('PLG_SYSTEM_GMAILSMTP_TEST_ERROR') . ': \' + error.message + \'</div>\';
            }

            // Reset button
            btn.disabled = false;
            btn.innerHTML = \'<span class="icon-envelope" aria-hidden="true"></span> ' . Text::_('PLG_SYSTEM_GMAILSMTP_SEND_TEST') . '\';
        }
        </script>';

        return $html;
    }
}
