<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261001100943 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Module 01 IAM: password reset tokens, security audit log, TOTP 2FA fields and security timestamps';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE password_reset_token (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', used_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_6B7BA4B6A76ED395 (user_id), INDEX idx_password_reset_token_hash (token_hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE security_audit_log (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, user_identifier VARCHAR(180) NOT NULL, event VARCHAR(50) NOT NULL, event_label VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_9E5F188AA76ED395 (user_id), INDEX idx_security_audit_log_event (event), INDEX idx_security_audit_log_created_at (created_at), INDEX idx_security_audit_log_user_identifier (user_identifier), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE password_reset_token ADD CONSTRAINT FK_6B7BA4B6A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE security_audit_log ADD CONSTRAINT FK_9E5F188AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user ADD totp_secret VARCHAR(255) DEFAULT NULL, ADD is_totp_enabled TINYINT(1) DEFAULT 0 NOT NULL, ADD backup_codes JSON DEFAULT \'[]\' NOT NULL COMMENT \'(DC2Type:json)\', ADD password_changed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD last_login_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE password_reset_token DROP FOREIGN KEY FK_6B7BA4B6A76ED395');
        $this->addSql('ALTER TABLE security_audit_log DROP FOREIGN KEY FK_9E5F188AA76ED395');
        $this->addSql('DROP TABLE password_reset_token');
        $this->addSql('DROP TABLE security_audit_log');
        $this->addSql('ALTER TABLE `user` DROP totp_secret, DROP is_totp_enabled, DROP backup_codes, DROP password_changed_at, DROP last_login_at');
    }
}
