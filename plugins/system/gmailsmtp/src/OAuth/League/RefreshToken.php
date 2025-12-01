<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.GmailSmtp
 *
 * @copyright   (C) 2025 Modern Designs
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace League\OAuth2\Client\Grant;

defined('_JEXEC') or die;

/**
 * Minimal stub for League OAuth2 RefreshToken grant
 * Required for PHPMailer OAuth compatibility
 *
 * @since  1.0.0
 */
class RefreshToken
{
    /**
     * Get the grant type string
     *
     * @return  string
     */
    public function __toString(): string
    {
        return 'refresh_token';
    }
}
