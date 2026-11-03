<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261001185239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Module 07 - Creation des tables document et document_version avec cles etrangeres et index pour la gestion documentaire RH securisee';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE document (id INT AUTO_INCREMENT NOT NULL, institution_id INT NOT NULL, employe_id INT DEFAULT NULL, affectation_id INT DEFAULT NULL, ajoute_par_id INT DEFAULT NULL, titre VARCHAR(255) NOT NULL, reference VARCHAR(100) DEFAULT NULL, categorie VARCHAR(50) NOT NULL, niveau_confidentialite VARCHAR(50) NOT NULL, statut VARCHAR(50) NOT NULL, fichier_nom_original VARCHAR(255) NOT NULL, fichier_nom_stockage VARCHAR(255) NOT NULL, type_mime VARCHAR(100) NOT NULL, taille INT NOT NULL, fichier_hash VARCHAR(64) DEFAULT NULL, date_document DATE DEFAULT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_D8698A76DAA76F43 (ajoute_par_id), INDEX idx_document_institution (institution_id), INDEX idx_document_employe (employe_id), INDEX idx_document_affectation (affectation_id), INDEX idx_document_categorie (categorie), INDEX idx_document_statut (statut), INDEX idx_document_confidentialite (niveau_confidentialite), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE document_version (id INT AUTO_INCREMENT NOT NULL, document_id INT NOT NULL, institution_id INT NOT NULL, cree_par_id INT DEFAULT NULL, numero_version INT NOT NULL, fichier_nom_original VARCHAR(255) NOT NULL, fichier_nom_stockage VARCHAR(255) NOT NULL, fichier_hash VARCHAR(64) DEFAULT NULL, taille INT NOT NULL, type_mime VARCHAR(100) NOT NULL, commentaire_version LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_1B73751FFC29C013 (cree_par_id), INDEX idx_doc_version_document (document_id), INDEX idx_doc_version_institution (institution_id), INDEX idx_doc_version_numero (numero_version), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A7610405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A761B65292 FOREIGN KEY (employe_id) REFERENCES employe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A766D0ABA22 FOREIGN KEY (affectation_id) REFERENCES affectation (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76DAA76F43 FOREIGN KEY (ajoute_par_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE document_version ADD CONSTRAINT FK_1B73751FC33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE document_version ADD CONSTRAINT FK_1B73751F10405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE document_version ADD CONSTRAINT FK_1B73751FFC29C013 FOREIGN KEY (cree_par_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A7610405986');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A761B65292');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A766D0ABA22');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76DAA76F43');
        $this->addSql('ALTER TABLE document_version DROP FOREIGN KEY FK_1B73751FC33F7837');
        $this->addSql('ALTER TABLE document_version DROP FOREIGN KEY FK_1B73751F10405986');
        $this->addSql('ALTER TABLE document_version DROP FOREIGN KEY FK_1B73751FFC29C013');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE document_version');
    }
}
