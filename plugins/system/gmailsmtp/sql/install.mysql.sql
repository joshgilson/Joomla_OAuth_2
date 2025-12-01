--
-- Table structure for Gmail SMTP OAuth tokens
--
-- This table stores encrypted OAuth tokens for Gmail SMTP authentication.
-- Only one row is needed as the plugin supports a single Google account.
--

CREATE TABLE IF NOT EXISTS `#__gmailsmtp_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `token_data` text NOT NULL COMMENT 'Encrypted OAuth token data (JSON)',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
