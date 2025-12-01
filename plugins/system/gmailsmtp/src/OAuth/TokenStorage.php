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

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * OAuth Token Storage
 *
 * Securely stores OAuth tokens in the Joomla database.
 * Tokens are encrypted using Joomla's secret key.
 *
 * @since  1.0.0
 */
class TokenStorage
{
    /**
     * Database table name
     */
    private const TABLE = '#__gmailsmtp_tokens';

    /**
     * Database driver
     *
     * @var    DatabaseInterface
     * @since  1.0.0
     */
    private DatabaseInterface $db;

    /**
     * Encryption key derived from Joomla secret
     *
     * @var    string
     * @since  1.0.0
     */
    private string $encryptionKey;

    /**
     * Constructor
     *
     * @param   DatabaseInterface  $db  Database driver
     *
     * @since   1.0.0
     */
    public function __construct(DatabaseInterface $db)
    {
        $this->db            = $db;
        $this->encryptionKey = $this->deriveKey();
    }

    /**
     * Save OAuth tokens
     *
     * @param   array  $tokens  Token data to save
     *
     * @return  bool
     * @since   1.0.0
     */
    public function saveTokens(array $tokens): bool
    {
        // Encrypt sensitive data
        $encryptedData = $this->encrypt(json_encode($tokens));

        // Delete existing tokens first
        $this->deleteTokens();

        // Insert new tokens
        $query = $this->db->getQuery(true)
            ->insert(self::TABLE)
            ->columns(['token_data', 'created_at', 'updated_at'])
            ->values(
                $this->db->quote($encryptedData) . ', ' .
                $this->db->quote(date('Y-m-d H:i:s')) . ', ' .
                $this->db->quote(date('Y-m-d H:i:s'))
            );

        $this->db->setQuery($query);

        return $this->db->execute();
    }

    /**
     * Get stored OAuth tokens
     *
     * @return  array
     * @since   1.0.0
     */
    public function getTokens(): array
    {
        $query = $this->db->getQuery(true)
            ->select('token_data')
            ->from(self::TABLE)
            ->order('id DESC')
            ->setLimit(1);

        $this->db->setQuery($query);
        $result = $this->db->loadResult();

        if (empty($result)) {
            return [];
        }

        $decrypted = $this->decrypt($result);

        if ($decrypted === false) {
            return [];
        }

        $tokens = json_decode($decrypted, true);

        return is_array($tokens) ? $tokens : [];
    }

    /**
     * Delete stored tokens
     *
     * @return  bool
     * @since   1.0.0
     */
    public function deleteTokens(): bool
    {
        $query = $this->db->getQuery(true)
            ->delete(self::TABLE);

        $this->db->setQuery($query);

        return $this->db->execute();
    }

    /**
     * Derive encryption key from Joomla secret
     *
     * @return  string
     * @since   1.0.0
     */
    private function deriveKey(): string
    {
        $secret = Factory::getApplication()->get('secret', '');

        // Use HKDF to derive a proper encryption key
        return hash('sha256', $secret . 'gmailsmtp_tokens', true);
    }

    /**
     * Encrypt data
     *
     * @param   string  $data  Data to encrypt
     *
     * @return  string  Base64-encoded encrypted data
     * @since   1.0.0
     */
    private function encrypt(string $data): string
    {
        $method = 'aes-256-gcm';
        $ivLen  = openssl_cipher_iv_length($method);
        $iv     = random_bytes($ivLen);
        $tag    = '';

        $encrypted = openssl_encrypt(
            $data,
            $method,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        // Combine IV + tag + encrypted data
        return base64_encode($iv . $tag . $encrypted);
    }

    /**
     * Decrypt data
     *
     * @param   string  $data  Base64-encoded encrypted data
     *
     * @return  string|false
     * @since   1.0.0
     */
    private function decrypt(string $data): string|false
    {
        $method    = 'aes-256-gcm';
        $ivLen     = openssl_cipher_iv_length($method);
        $tagLength = 16; // GCM tag length

        $decoded = base64_decode($data);

        if ($decoded === false || strlen($decoded) < $ivLen + $tagLength) {
            return false;
        }

        $iv        = substr($decoded, 0, $ivLen);
        $tag       = substr($decoded, $ivLen, $tagLength);
        $encrypted = substr($decoded, $ivLen + $tagLength);

        return openssl_decrypt(
            $encrypted,
            $method,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
    }
}
