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

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * Gmail Redirect URI Field
 *
 * Displays the OAuth redirect URI that users need to configure
 * in their Google Cloud Console project.
 *
 * @since  1.0.0
 */
class GmailRedirectUriField extends FormField
{
    /**
     * The form field type
     *
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'GmailRedirectUri';

    /**
     * Get the field input markup
     *
     * @return  string
     * @since   1.0.0
     */
    protected function getInput(): string
    {
        $redirectUri = Uri::root() . 'index.php?option=com_ajax&plugin=gmailsmtp&task=callback&format=raw';

        $html = '<div class="input-group">';
        $html .= '<input type="text" id="' . $this->id . '" class="form-control" ';
        $html .= 'value="' . htmlspecialchars($redirectUri) . '" readonly>';
        $html .= '<button type="button" class="btn btn-secondary" onclick="copyRedirectUri(this)" ';
        $html .= 'title="' . Text::_('PLG_SYSTEM_GMAILSMTP_COPY_URI') . '">';
        $html .= '<span class="icon-copy" aria-hidden="true"></span>';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '<div class="small text-muted mt-1">';
        $html .= Text::_('PLG_SYSTEM_GMAILSMTP_REDIRECT_URI_HELP');
        $html .= '</div>';

        // Add copy script
        $html .= '<script>
        function copyRedirectUri(btn) {
            const input = document.getElementById("' . $this->id . '");
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value);

            const originalHtml = btn.innerHTML;
            btn.innerHTML = \'<span class="icon-checkmark" aria-hidden="true"></span>\';
            btn.classList.remove("btn-secondary");
            btn.classList.add("btn-success");

            setTimeout(function() {
                btn.innerHTML = originalHtml;
                btn.classList.remove("btn-success");
                btn.classList.add("btn-secondary");
            }, 2000);
        }
        </script>';

        return $html;
    }
}
