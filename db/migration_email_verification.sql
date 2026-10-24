-- Email verification for accounts.
-- Run once on every existing database (local XAMPP via phpMyAdmin, and Aiven) BEFORE deploying the new code.

ALTER TABLE `accounts`
  ADD COLUMN `email_verified_at` datetime DEFAULT NULL,
  ADD COLUMN `verification_token` varchar(64) DEFAULT NULL,
  ADD COLUMN `verification_expires` datetime DEFAULT NULL;

-- Accounts that existed before this feature are treated as already verified, so nobody gets locked out
UPDATE `accounts` SET `email_verified_at` = NOW() WHERE `email_verified_at` IS NULL;
