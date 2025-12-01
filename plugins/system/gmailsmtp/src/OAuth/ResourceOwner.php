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
 * OAuth 2.0 Resource Owner (User)
 *
 * @since  1.0.0
 */
class ResourceOwner
{
    /**
     * User data from OAuth server
     *
     * @var    array
     * @since  1.0.0
     */
    private array $data;

    /**
     * Constructor
     *
     * @param   array  $data  User info response data
     *
     * @since   1.0.0
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get user ID
     *
     * @return  string
     * @since   1.0.0
     */
    public function getId(): string
    {
        return $this->data['sub'] ?? '';
    }

    /**
     * Get user email
     *
     * @return  string
     * @since   1.0.0
     */
    public function getEmail(): string
    {
        return $this->data['email'] ?? '';
    }

    /**
     * Get user name
     *
     * @return  string
     * @since   1.0.0
     */
    public function getName(): string
    {
        return $this->data['name'] ?? '';
    }

    /**
     * Check if email is verified
     *
     * @return  bool
     * @since   1.0.0
     */
    public function isEmailVerified(): bool
    {
        return (bool) ($this->data['email_verified'] ?? false);
    }

    /**
     * Get all user data
     *
     * @return  array
     * @since   1.0.0
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
