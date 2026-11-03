<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261001143414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Module 05: Add poste and occupation_poste tables and employe.poste_id relation';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE occupation_poste (id INT AUTO_INCREMENT NOT NULL, poste_id INT NOT NULL, employe_id INT NOT NULL, institution_id INT NOT NULL, date_debut DATE NOT NULL, date_fin DATE DEFAULT NULL, motif VARCHAR(50) DEFAULT NULL, commentaire LONGTEXT DEFAULT NULL, is_actif TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, INDEX idx_occ_poste_institution (institution_id), INDEX idx_occ_poste_poste (poste_id), INDEX idx_occ_poste_employe (employe_id), INDEX idx_occ_poste_actif (is_actif), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE poste (id INT AUTO_INCREMENT NOT NULL, institution_id INT NOT NULL, structure_id INT DEFAULT NULL, service_id INT DEFAULT NULL, fonction_id INT DEFAULT NULL, occupant_actuel_id INT DEFAULT NULL, reference VARCHAR(50) NOT NULL, intitule VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, competences_requises LONGTEXT DEFAULT NULL, niveau VARCHAR(50) DEFAULT NULL, statut VARCHAR(50) NOT NULL, date_creation DATE NOT NULL, date_fermeture DATE DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_7C890FAB57889920 (fonction_id), INDEX IDX_7C890FAB6299F7C0 (occupant_actuel_id), INDEX idx_poste_institution (institution_id), INDEX idx_poste_structure (structure_id), INDEX idx_poste_service (service_id), INDEX idx_poste_statut (statut), INDEX idx_poste_reference (reference), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE occupation_poste ADD CONSTRAINT FK_7077F63DA0905086 FOREIGN KEY (poste_id) REFERENCES poste (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE occupation_poste ADD CONSTRAINT FK_7077F63D1B65292 FOREIGN KEY (employe_id) REFERENCES employe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE occupation_poste ADD CONSTRAINT FK_7077F63D10405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE poste ADD CONSTRAINT FK_7C890FAB10405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE poste ADD CONSTRAINT FK_7C890FAB2534008B FOREIGN KEY (structure_id) REFERENCES structure (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE poste ADD CONSTRAINT FK_7C890FABED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE poste ADD CONSTRAINT FK_7C890FAB57889920 FOREIGN KEY (fonction_id) REFERENCES fonction (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE poste ADD CONSTRAINT FK_7C890FAB6299F7C0 FOREIGN KEY (occupant_actuel_id) REFERENCES employe (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE employe ADD poste_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE employe ADD CONSTRAINT FK_F804D3B9A0905086 FOREIGN KEY (poste_id) REFERENCES poste (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_employe_poste ON employe (poste_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employe DROP FOREIGN KEY FK_F804D3B9A0905086');
        $this->addSql('ALTER TABLE occupation_poste DROP FOREIGN KEY FK_7077F63DA0905086');
        $this->addSql('ALTER TABLE occupation_poste DROP FOREIGN KEY FK_7077F63D1B65292');
        $this->addSql('ALTER TABLE occupation_poste DROP FOREIGN KEY FK_7077F63D10405986');
        $this->addSql('ALTER TABLE poste DROP FOREIGN KEY FK_7C890FAB10405986');
        $this->addSql('ALTER TABLE poste DROP FOREIGN KEY FK_7C890FAB2534008B');
        $this->addSql('ALTER TABLE poste DROP FOREIGN KEY FK_7C890FABED5CA9E6');
        $this->addSql('ALTER TABLE poste DROP FOREIGN KEY FK_7C890FAB57889920');
        $this->addSql('ALTER TABLE poste DROP FOREIGN KEY FK_7C890FAB6299F7C0');
        $this->addSql('DROP TABLE occupation_poste');
        $this->addSql('DROP TABLE poste');
        $this->addSql('DROP INDEX idx_employe_poste ON employe');
        $this->addSql('ALTER TABLE employe DROP poste_id');
    }
}
