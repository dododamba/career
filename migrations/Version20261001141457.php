<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261001141457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Module 04: Add civil status, administrative career fields and structure relation to Employe';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employe ADD structure_id INT DEFAULT NULL, ADD lieu_naissance VARCHAR(255) DEFAULT NULL, ADD sexe VARCHAR(10) DEFAULT NULL, ADD nationalite VARCHAR(100) DEFAULT NULL, ADD date_prise_service DATE DEFAULT NULL, ADD statut_administratif VARCHAR(50) DEFAULT NULL, ADD corps VARCHAR(100) DEFAULT NULL, ADD grade VARCHAR(50) DEFAULT NULL, ADD echelon VARCHAR(20) DEFAULT NULL, ADD categorie VARCHAR(20) DEFAULT NULL, ADD regime VARCHAR(50) DEFAULT NULL, ADD situation_administrative VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE employe ADD CONSTRAINT FK_F804D3B92534008B FOREIGN KEY (structure_id) REFERENCES structure (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_employe_structure ON employe (structure_id)');
        $this->addSql('CREATE INDEX idx_employe_matricule ON employe (matricule)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employe DROP FOREIGN KEY FK_F804D3B92534008B');
        $this->addSql('DROP INDEX idx_employe_structure ON employe');
        $this->addSql('DROP INDEX idx_employe_matricule ON employe');
        $this->addSql('ALTER TABLE employe DROP structure_id, DROP lieu_naissance, DROP sexe, DROP nationalite, DROP date_prise_service, DROP statut_administratif, DROP corps, DROP grade, DROP echelon, DROP categorie, DROP regime, DROP situation_administrative');
    }
}
