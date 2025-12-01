--
-- Remove Gmail SMTP OAuth tokens table on plugin uninstall
--

DROP TABLE IF EXISTS `#__gmailsmtp_tokens`;
