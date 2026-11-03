<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261001144611 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Module 06: Add affectation table for career and movement tracking';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE affectation (id INT AUTO_INCREMENT NOT NULL, employe_id INT NOT NULL, institution_id INT NOT NULL, ancienne_structure_id INT DEFAULT NULL, ancien_service_id INT DEFAULT NULL, ancien_poste_id INT DEFAULT NULL, ancienne_fonction_id INT DEFAULT NULL, structure_id INT DEFAULT NULL, service_id INT DEFAULT NULL, poste_id INT DEFAULT NULL, fonction_id INT DEFAULT NULL, reference VARCHAR(50) NOT NULL, type_mouvement VARCHAR(50) NOT NULL, statut VARCHAR(50) NOT NULL, date_decision DATE NOT NULL, date_effet DATE NOT NULL, date_prise_service DATE DEFAULT NULL, date_fin DATE DEFAULT NULL, reference_acte VARCHAR(100) DEFAULT NULL, motif LONGTEXT DEFAULT NULL, commentaire LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_F4DD61D315B901B2 (ancienne_structure_id), INDEX IDX_F4DD61D362887706 (ancien_service_id), INDEX IDX_F4DD61D3EB66A8BD (ancien_poste_id), INDEX IDX_F4DD61D36F7649CF (ancienne_fonction_id), INDEX IDX_F4DD61D3ED5CA9E6 (service_id), INDEX IDX_F4DD61D357889920 (fonction_id), INDEX idx_affectation_institution (institution_id), INDEX idx_affectation_employe (employe_id), INDEX idx_affectation_poste (poste_id), INDEX idx_affectation_structure (structure_id), INDEX idx_affectation_statut (statut), INDEX idx_affectation_type (type_mouvement), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D31B65292 FOREIGN KEY (employe_id) REFERENCES employe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D310405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D315B901B2 FOREIGN KEY (ancienne_structure_id) REFERENCES structure (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D362887706 FOREIGN KEY (ancien_service_id) REFERENCES service (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D3EB66A8BD FOREIGN KEY (ancien_poste_id) REFERENCES poste (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D36F7649CF FOREIGN KEY (ancienne_fonction_id) REFERENCES fonction (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D32534008B FOREIGN KEY (structure_id) REFERENCES structure (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D3ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D3A0905086 FOREIGN KEY (poste_id) REFERENCES poste (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D357889920 FOREIGN KEY (fonction_id) REFERENCES fonction (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE affectation DROP FOREIGN KEY FK_F4DD61D31B65292');
        $this->addSql('ALTER TABLE affectation DROP FOREIGN KEY FK_F4DD61D310405986');
        $this->addSql('ALTER TABLE affectation DROP FOREIGN KEY FK_F4DD61D315B901B2');
        $this->addSql('ALTER TABLE affectation DROP FOREIGN KEY FK_F4DD61D362887706');
        $this->addSql('ALTER TABLE affectation DROP FOREIGN KEY FK_F4DD61D3EB66A8BD');
        $this->addSql('ALTER TABLE affectation DROP FOREIGN KEY FK_F4DD61D36F7649CF');
        $this->addSql('ALTER TABLE affectation DROP FOREIGN KEY FK_F4DD61D32534008B');
        $this->addSql('ALTER TABLE affectation DROP FOREIGN KEY FK_F4DD61D3ED5CA9E6');
        $this->addSql('ALTER TABLE affectation DROP FOREIGN KEY FK_F4DD61D3A0905086');
        $this->addSql('ALTER TABLE affectation DROP FOREIGN KEY FK_F4DD61D357889920');
        $this->addSql('DROP TABLE affectation');
    }
}
