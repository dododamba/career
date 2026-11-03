<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261001135311 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE organisation_history (id INT AUTO_INCREMENT NOT NULL, institution_id INT NOT NULL, changed_by_id INT DEFAULT NULL, entity_type VARCHAR(50) NOT NULL, entity_id INT NOT NULL, entity_name VARCHAR(255) DEFAULT NULL, action VARCHAR(50) NOT NULL, details JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_443E0748828AD0A0 (changed_by_id), INDEX idx_org_history_institution (institution_id), INDEX idx_org_history_entity (entity_type, entity_id), INDEX idx_org_history_action (action), INDEX idx_org_history_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure (id INT AUTO_INCREMENT NOT NULL, institution_id INT NOT NULL, parent_id INT DEFAULT NULL, responsable_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, code VARCHAR(50) DEFAULT NULL, type VARCHAR(50) DEFAULT \'DIRECTION\' NOT NULL, description LONGTEXT DEFAULT NULL, statut VARCHAR(20) DEFAULT \'ACTIVE\' NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_6F0137EA53C59D72 (responsable_id), INDEX idx_structure_institution (institution_id), INDEX idx_structure_parent (parent_id), INDEX idx_structure_code (code), INDEX idx_structure_statut (statut), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE organisation_history ADD CONSTRAINT FK_443E074810405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE organisation_history ADD CONSTRAINT FK_443E0748828AD0A0 FOREIGN KEY (changed_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE structure ADD CONSTRAINT FK_6F0137EA10405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure ADD CONSTRAINT FK_6F0137EA727ACA70 FOREIGN KEY (parent_id) REFERENCES structure (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE structure ADD CONSTRAINT FK_6F0137EA53C59D72 FOREIGN KEY (responsable_id) REFERENCES employe (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE institution ADD responsable_id INT DEFAULT NULL, ADD adresse VARCHAR(255) DEFAULT NULL, ADD telephone VARCHAR(50) DEFAULT NULL, ADD email VARCHAR(255) DEFAULT NULL, ADD site_web VARCHAR(255) DEFAULT NULL, ADD logo VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE institution ADD CONSTRAINT FK_3A9F98E553C59D72 FOREIGN KEY (responsable_id) REFERENCES employe (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_3A9F98E553C59D72 ON institution (responsable_id)');
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD210405986');
        $this->addSql('ALTER TABLE service ADD structure_id INT DEFAULT NULL, ADD responsable_id INT DEFAULT NULL, ADD code VARCHAR(50) DEFAULT NULL, ADD statut VARCHAR(20) DEFAULT \'ACTIVE\' NOT NULL, ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD22534008B FOREIGN KEY (structure_id) REFERENCES structure (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD253C59D72 FOREIGN KEY (responsable_id) REFERENCES employe (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD210405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_E19D9AD253C59D72 ON service (responsable_id)');
        $this->addSql('CREATE INDEX idx_service_structure ON service (structure_id)');
        $this->addSql('CREATE INDEX idx_service_code ON service (code)');
        $this->addSql('CREATE INDEX idx_service_statut ON service (statut)');
        // Backfill existing services with created_at and updated_at
        $this->addSql('UPDATE service SET created_at = NOW(), updated_at = NOW() WHERE created_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD22534008B');
        $this->addSql('ALTER TABLE organisation_history DROP FOREIGN KEY FK_443E074810405986');
        $this->addSql('ALTER TABLE organisation_history DROP FOREIGN KEY FK_443E0748828AD0A0');
        $this->addSql('ALTER TABLE structure DROP FOREIGN KEY FK_6F0137EA10405986');
        $this->addSql('ALTER TABLE structure DROP FOREIGN KEY FK_6F0137EA727ACA70');
        $this->addSql('ALTER TABLE structure DROP FOREIGN KEY FK_6F0137EA53C59D72');
        $this->addSql('DROP TABLE organisation_history');
        $this->addSql('DROP TABLE structure');
        $this->addSql('ALTER TABLE institution DROP FOREIGN KEY FK_3A9F98E553C59D72');
        $this->addSql('DROP INDEX IDX_3A9F98E553C59D72 ON institution');
        $this->addSql('ALTER TABLE institution DROP responsable_id, DROP adresse, DROP telephone, DROP email, DROP site_web, DROP logo');
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD253C59D72');
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD210405986');
        $this->addSql('DROP INDEX IDX_E19D9AD253C59D72 ON service');
        $this->addSql('DROP INDEX idx_service_structure ON service');
        $this->addSql('DROP INDEX idx_service_code ON service');
        $this->addSql('DROP INDEX idx_service_statut ON service');
        $this->addSql('ALTER TABLE service DROP structure_id, DROP responsable_id, DROP code, DROP statut, DROP created_at, DROP updated_at, CHANGE description description LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD210405986 FOREIGN KEY (institution_id) REFERENCES institution (id)');
    }
}
