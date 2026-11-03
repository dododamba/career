<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261001190641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Module 08: Creation des tables type_acte et acte pour la gestion des actes administratifs et seed des types standards';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE acte (id INT AUTO_INCREMENT NOT NULL, type_acte_id INT NOT NULL, institution_id INT NOT NULL, document_id INT DEFAULT NULL, employe_id INT DEFAULT NULL, poste_id INT DEFAULT NULL, structure_id INT DEFAULT NULL, affectation_id INT DEFAULT NULL, acte_parent_id INT DEFAULT NULL, cree_par_id INT DEFAULT NULL, numero VARCHAR(100) NOT NULL, date_signature DATE NOT NULL, date_effet DATE NOT NULL, date_publication DATE DEFAULT NULL, objet VARCHAR(255) NOT NULL, signataire_qualite VARCHAR(255) NOT NULL, signataire_nom VARCHAR(255) DEFAULT NULL, statut VARCHAR(50) NOT NULL, contenu_resume LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_9EC41326C33F7837 (document_id), INDEX IDX_9EC41326A9F322E2 (acte_parent_id), INDEX IDX_9EC41326FC29C013 (cree_par_id), INDEX idx_acte_institution (institution_id), INDEX idx_acte_type (type_acte_id), INDEX idx_acte_employe (employe_id), INDEX idx_acte_poste (poste_id), INDEX idx_acte_structure (structure_id), INDEX idx_acte_affectation (affectation_id), INDEX idx_acte_statut (statut), INDEX idx_acte_numero (numero), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE type_acte (id INT AUTO_INCREMENT NOT NULL, institution_id INT DEFAULT NULL, code VARCHAR(50) NOT NULL, libelle VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, actif TINYINT(1) DEFAULT 1 NOT NULL, INDEX idx_type_acte_code (code), INDEX idx_type_acte_institution (institution_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE acte ADD CONSTRAINT FK_9EC413268F46D732 FOREIGN KEY (type_acte_id) REFERENCES type_acte (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE acte ADD CONSTRAINT FK_9EC4132610405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE acte ADD CONSTRAINT FK_9EC41326C33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE acte ADD CONSTRAINT FK_9EC413261B65292 FOREIGN KEY (employe_id) REFERENCES employe (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE acte ADD CONSTRAINT FK_9EC41326A0905086 FOREIGN KEY (poste_id) REFERENCES poste (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE acte ADD CONSTRAINT FK_9EC413262534008B FOREIGN KEY (structure_id) REFERENCES structure (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE acte ADD CONSTRAINT FK_9EC413266D0ABA22 FOREIGN KEY (affectation_id) REFERENCES affectation (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE acte ADD CONSTRAINT FK_9EC41326A9F322E2 FOREIGN KEY (acte_parent_id) REFERENCES acte (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE acte ADD CONSTRAINT FK_9EC41326FC29C013 FOREIGN KEY (cree_par_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE type_acte ADD CONSTRAINT FK_49CBD62E10405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');

        // Seed default standard administrative act types (institution_id = NULL for global availability)
        $this->addSql("INSERT INTO type_acte (code, libelle, description, actif, institution_id) VALUES
            ('DECRET', 'Décret', 'Décret présidentiel ou ministériel', 1, NULL),
            ('ARRETE', 'Arrêté', 'Arrêté ministériel, préfectoral ou rectoral', 1, NULL),
            ('DECISION', 'Décision', 'Décision administrative ou directoriale', 1, NULL),
            ('NOTE_SERVICE', 'Note de service', 'Note de service ou circulaire interne', 1, NULL),
            ('ORDRE_MISSION', 'Ordre de mission', 'Ordre de mission officiel', 1, NULL),
            ('TITRE_CONGE', 'Titre de congé', 'Titre ou attestation d\'octroi de congé', 1, NULL),
            ('AUTORISATION_ABSENCE', 'Autorisation d\'absence', 'Autorisation formelle d\'absence', 1, NULL),
            ('PV_PRISE_SERVICE', 'Procès-verbal de prise de service', 'PV actant l\'installation ou la prise de fonction', 1, NULL),
            ('PV_CESSATION_SERVICE', 'Procès-verbal de cessation de service', 'PV de départ, radiation ou fin de fonction', 1, NULL),
            ('AUTRE', 'Autre acte administratif', 'Autre document ou acte à portée juridique', 1, NULL)
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE acte DROP FOREIGN KEY FK_9EC413268F46D732');
        $this->addSql('ALTER TABLE acte DROP FOREIGN KEY FK_9EC4132610405986');
        $this->addSql('ALTER TABLE acte DROP FOREIGN KEY FK_9EC41326C33F7837');
        $this->addSql('ALTER TABLE acte DROP FOREIGN KEY FK_9EC413261B65292');
        $this->addSql('ALTER TABLE acte DROP FOREIGN KEY FK_9EC41326A0905086');
        $this->addSql('ALTER TABLE acte DROP FOREIGN KEY FK_9EC413262534008B');
        $this->addSql('ALTER TABLE acte DROP FOREIGN KEY FK_9EC413266D0ABA22');
        $this->addSql('ALTER TABLE acte DROP FOREIGN KEY FK_9EC41326A9F322E2');
        $this->addSql('ALTER TABLE acte DROP FOREIGN KEY FK_9EC41326FC29C013');
        $this->addSql('ALTER TABLE type_acte DROP FOREIGN KEY FK_49CBD62E10405986');
        $this->addSql('DROP TABLE acte');
        $this->addSql('DROP TABLE type_acte');
    }
}
